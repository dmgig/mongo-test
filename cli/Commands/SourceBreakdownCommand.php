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
use Symfony\Component\Console\Output\OutputInterface as ConsoleOutputInterface;

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
            ->setName("source:breakdown")
            ->setDescription("Breaks down a source into parties and events using AI.")
            ->addArgument("id", InputArgument::REQUIRED, "The Source ID")
            ->addOption("retry", null, InputOption::VALUE_NONE, "Enable automatic retries on AI errors")
            ->addOption("chunk-limit", null, InputOption::VALUE_REQUIRED, "Limit the number of chunks processed", null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument("id");
        $retry = (bool)$input->getOption("retry");
        $chunkLimit = $input->getOption("chunk-limit");

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

            // Apply chunk limit if set
            if ($chunkLimit !== null) {
                $chunks = array_slice($chunks, 0, (int)$chunkLimit);
                $output->writeln("  (Limited to $chunkLimit chunks by --chunk-limit flag)");
            }

            // 5. Process Chunks (Generate individual chunk summaries)
            $output->writeln("Processing " . count($chunks) . " chunks to generate individual summaries...");
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
                    "## Current Chunk\n\n$chunk", // No running summary here anymore
                    null,
                    $retry
                );

                if ($veryVerbose) {
                     $output->writeln("    Prompt: " . (string)$prompt);
                }

                try {
                    $response = $this->ai->generate($prompt);
                    $chunkSummary = $response->text;
                    
                    $breakdown->addChunkSummary($chunkSummary); // Add to chunkSummaries array
                    $this->breakdownRepo->save($breakdown);
                    $duration = microtime(true) - $startTime;
                    $output->writeln(sprintf("Done (%.2fs)", $duration));
                    
                    if ($verbose) {
                        $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
                    }
                    if ($veryVerbose) {
                        $output->writeln("    Response: " . $chunkSummary);
                    }
                } catch (AiException $e) {
                    $output->writeln("<error>Failed: {$e->getMessage()}</error>");
                    if (!$retry) {
                        $output->writeln("<info>Tip: Use --retry to automatically retry transient errors.</info>");
                    }
                    throw $e;
                }
            }

            // 6. Generate Master Summary
            $output->write("Generating master summary from chunk summaries... ");
            $startTime = microtime(true);
            $masterSummaryPrompt = new Prompt(
                Prompt::BREAKDOWN_MASTER_SUMMARY_PROMPT,
                implode("\n\n", $breakdown->chunkSummaries), // Combine all chunk summaries
                null,
                $retry
            );
            if ($veryVerbose) {
                 $output->writeln("    Prompt: " . (string)$masterSummaryPrompt);
            }
            $masterSummaryResponse = $this->ai->generate($masterSummaryPrompt);
            $masterSummary = $masterSummaryResponse->text;
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$masterSummaryResponse->inputTokens} in / {$masterSummaryResponse->outputTokens} out");
            }
            if ($veryVerbose) {
                 $output->writeln("    Response: " . $masterSummary);
            }
            $breakdown->updateMasterSummary($masterSummary);
            $this->breakdownRepo->save($breakdown);

            // 7. Parties Analysis
            $output->write("Generating parties analysis... ");
            $startTime = microtime(true);

            $partiesPrompt = new Prompt(
                Prompt::BREAKDOWN_PARTIES_PROMPT,
                $breakdown->summary, // Use master summary
                null,
                $retry
            );
            $partiesResponse = $this->ai->generate($partiesPrompt);
            $partiesResult = $partiesResponse->text; // Keep raw for display
            $breakdown->setPartiesYaml($this->stripYamlFences($partiesResult)); // Store stripped version
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$partiesResponse->inputTokens} in / {$partiesResponse->outputTokens} out");
            }


            // 8. Locations Analysis
            $output->write("Generating locations analysis... ");
            $startTime = microtime(true);
            $locationsPrompt = new Prompt(
                Prompt::BREAKDOWN_LOCATIONS_PROMPT,
                $breakdown->summary, // Use master summary
                null,
                $retry
            );
            $locationsResponse = $this->ai->generate($locationsPrompt);
            $locationsResult = $locationsResponse->text; // Keep raw for display
            $breakdown->setLocationsYaml($this->stripYamlFences($locationsResult)); // Store stripped version
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$locationsResponse->inputTokens} in / {$locationsResponse->outputTokens} out");
            }


            // 9. Timeline Analysis
            $output->write("Generating timeline analysis... ");
            $startTime = microtime(true);
            $timelinePrompt = new Prompt(
                Prompt::BREAKDOWN_TIMELINE_PROMPT,
                $breakdown->summary, // Use master summary
                null,
                $retry
            );
            $timelineResponse = $this->ai->generate($timelinePrompt);
            $timelineResult = $timelineResponse->text; // Keep raw for display
            $breakdown->setTimelineYaml($this->stripYamlFences($timelineResult)); // Store stripped version
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$timelineResponse->inputTokens} in / {$timelineResponse->outputTokens} out");
            }


            // 10. Improve Dating of Events
            $output->write("Attempting to date undated events... ");
            $startTime = microtime(true);
            $datingPrompt = new Prompt(
                Prompt::BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT,
                $timelineResult, // Use the raw timeline result for dating
                null,
                $retry
            );
            $datedResponse = $this->ai->generate($datingPrompt);
            $datedTimeline = $datedResponse->text; // Keep raw for display
            $breakdown->setTimelineYaml($this->stripYamlFences($datedTimeline)); // Update with dated timeline, stripped
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            if ($verbose) {
                 $output->writeln("    Tokens: {$datedResponse->inputTokens} in / {$datedResponse->outputTokens} out");
            }

            
            // NOTE: We are no longer using BreakdownResult::fromYaml directly for display, but it still represents the structured data.
            // We will pass the *stripped* YAMLs for parsing into the BreakdownResult object for consistency with other parts of the system
            // that might rely on the structured result.
            $result = BreakdownResult::fromYaml(
                $this->stripYamlFences($partiesResult),
                $this->stripYamlFences($locationsResult),
                $this->stripYamlFences($datedTimeline)
            );
            $breakdown->setResult($result);
            $this->breakdownRepo->save($breakdown);

            // 11. Output
            $output->writeln("--- Final Result ---");
            // Simple output for now, the real result is structured in the DB
            $output->writeln("Parties: " . count($result->parties));
            $output->writeln("Locations: " . count($result->locations));
            $output->writeln("Events: " . count($result->timeline));
            $output->writeln("--------------------");
            $output->writeln("Breakdown complete. You can review the breakdown at any time using the ID: " . $breakdown->id);


            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }

    private function stripYamlFences(string $text): string
    {
        // Remove '```yaml' at the beginning and '```' at the end
        $text = preg_replace('/^```yaml\s*\n?/i', '', $text);
        $text = preg_replace('/\n?```\s*$/', '', $text);
        return trim($text);
    }
}
