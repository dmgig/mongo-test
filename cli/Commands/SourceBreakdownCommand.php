<?php

declare(strict_types=1);

namespace Cli\Commands;

use App\Domain\Ai\AiException;
use App\Domain\Ai\AiModelInterface;
use App\Domain\Ai\Prompt;
use App\Domain\Breakdown\Breakdown;
use App\Domain\Breakdown\BreakdownRepositoryInterface;
use App\Domain\Breakdown\BreakdownResult;
use App\Domain\Content\ChunkingService;
use App\Domain\Content\ContentExtractorInterface;
use App\Domain\Source\SourceId;
use App\Domain\Source\SourceService;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SourceBreakdownCommand extends Command
{
    public function __construct(
        private readonly SourceService $sourceService,
        private readonly ContentExtractorInterface $extractor,
        private readonly AiModelInterface $ai,
        private readonly BreakdownRepositoryInterface $breakdownRepo,
        private readonly ChunkingService $chunkingService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('source:breakdown')
            ->setDescription('Breaks down a source into parties and events using AI.')
            ->addArgument('id', InputArgument::REQUIRED, 'The Source ID')
            ->addOption('retry', null, InputOption::VALUE_NONE, 'Enable automatic retries on AI errors');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $retry = (bool)$input->getOption('retry');
        $sourceId = SourceId::fromString($id);
        $verbose = $output->isVerbose();
        $veryVerbose = $output->isVeryVerbose();

        try {
            // 1. Fetch Source and Extract Content
            $output->writeln("Fetching and extracting content for Source ID: $id");
            $source = $this->sourceService->getSource($sourceId);
            $htmlContent = $this->extractor->extractContent($source);

            // 2. Convert to Markdown
            $converter = new HtmlConverter();
            $markdownContent = $converter->convert($htmlContent);

            // 3. Create Breakdown record
            $breakdown = Breakdown::create($sourceId);
            $this->breakdownRepo->save($breakdown);
            $output->writeln("Created Breakdown record: " . $breakdown->id);

            // 4. Chunk Content
            $chunks = $this->chunkingService->chunk($markdownContent);

            // 5. Process Chunks
            $output->writeln("Processing " . count($chunks) . " chunks...");
            foreach ($chunks as $i => $chunk) {
                $output->write("  - Processing chunk " . ($i + 1) . "... ");
                if ($verbose) {
                     $output->writeln("");
                     $output->writeln("    Chunk Length: " . strlen($chunk) . " chars");
                     $output->writeln("    Preview: " . substr($chunk, 0, 100) . "...");
                }
                
                $startTime = microtime(true);

                $prompt = new Prompt(
                    Prompt::BREAKDOWN_SUMMARY_PROMPT,
                    "## Running Summary\n\n{$breakdown->summary}\n\n## Current Chunk\n\n$chunk",
                    null,
                    $retry
                );

                if ($veryVerbose) {
                     $output->writeln("    Prompt: " . (string)$prompt);
                }

                try {
                    $response = $this->ai->generate($prompt);
                    $updatedSummary = $response->text;
                    
                    $breakdown->updateSummary($updatedSummary);
                    $this->breakdownRepo->save($breakdown);
                    $duration = microtime(true) - $startTime;
                    $output->writeln(sprintf("Done (%.2fs)", $duration));
                    
                    if ($verbose) {
                        $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
                    }
                    if ($veryVerbose) {
                        $output->writeln("    Response: " . $updatedSummary);
                    }
                } catch (AiException $e) {
                    $output->writeln("<error>Failed: {$e->getMessage()}</error>");
                    if (!$retry) {
                        $output->writeln("<info>Tip: Use --retry to automatically retry transient errors.</info>");
                    }
                    // For chunks, we might want to stop or continue. Stopping is safer.
                    throw $e;
                }
            }

            // 6. Parties Analysis
            $output->write("Generating parties analysis... ");
            $startTime = microtime(true);

            $partiesPrompt = new Prompt(
                Prompt::BREAKDOWN_PARTIES_PROMPT,
                $breakdown->summary,
                null,
                $retry
            );
            $partiesResponse = $this->ai->generate($partiesPrompt);
            $partiesResult = $partiesResponse->text;
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$partiesResponse->inputTokens} in / {$partiesResponse->outputTokens} out");
            }


            // 7. Locations Analysis
            $output->write("Generating locations analysis... ");
            $startTime = microtime(true);
            $locationsPrompt = new Prompt(
                Prompt::BREAKDOWN_LOCATIONS_PROMPT,
                $breakdown->summary,
                null,
                $retry
            );
            $locationsResponse = $this->ai->generate($locationsPrompt);
            $locationsResult = $locationsResponse->text;
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$locationsResponse->inputTokens} in / {$locationsResponse->outputTokens} out");
            }


            // 8. Timeline Analysis
            $output->write("Generating timeline analysis... ");
            $startTime = microtime(true);
            $timelinePrompt = new Prompt(
                Prompt::BREAKDOWN_TIMELINE_PROMPT,
                $breakdown->summary,
                null,
                $retry
            );
            $timelineResponse = $this->ai->generate($timelinePrompt);
            $timelineResult = $timelineResponse->text;
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$timelineResponse->inputTokens} in / {$timelineResponse->outputTokens} out");
            }


            // 9. Improve Dating of Events
            $output->write("Attempting to date undated events... ");
            $startTime = microtime(true);
            $datingPrompt = new Prompt(
                Prompt::BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT,
                $timelineResult,
                null,
                $retry
            );
            $datedResponse = $this->ai->generate($datingPrompt);
            $datedTimeline = $datedResponse->text;
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$datedResponse->inputTokens} in / {$datedResponse->outputTokens} out");
            }

            
            $result = BreakdownResult::fromYaml($partiesResult, $locationsResult, $datedTimeline);
            $breakdown->setResult($result);
            $this->breakdownRepo->save($breakdown);

            // 10. Output
            $output->writeln("--- Final Result ---");
            // Simple output for now, the real result is structured in the DB
            $output->writeln("Parties: " . count($result->parties));
            $output->writeln("Locations: " . count($result->locations));
            $output->writeln("Events: " . count($result->timeline));
            $output->writeln("--------------------");
            $output->writeln("Breakdown complete. You can review the breakdown at any time using the ID: " . $breakdown->id);


            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
