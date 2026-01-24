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
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\EmbeddingService;
use App\Domain\Party\PartyService;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\OutputInterface as ConsoleOutputInterface;

class SourceBreakdownCommand extends Command
{
    private const STRATEGY_QUICK = 'quick';
    private const STRATEGY_GROWING_SUMMARY = 'growing-summary';

    public function __construct(
        private readonly SourceService $sourceService,
        private readonly ContentExtractorInterface $extractor,
        private readonly AiModelInterface $ai,
        private readonly BreakdownRepositoryInterface $breakdownRepo,
        private readonly EventRepositoryInterface $eventRepo,
        private readonly ChunkingService $chunkingService,
        private readonly EmbeddingService $embeddingService,
        private readonly PartyService $partyService
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
            ->addOption("chunk-limit", null, InputOption::VALUE_REQUIRED, "Limit the number of chunks processed", null)
            ->addOption("strategy", null, InputOption::VALUE_REQUIRED, "The breakdown strategy to use (quick or growing-summary)", self::STRATEGY_QUICK);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument("id");
        $retry = (bool)$input->getOption("retry");
        $chunkLimit = $input->getOption("chunk-limit");
        $strategy = $input->getOption("strategy");

        $sourceId = SourceId::fromString($id);

        try {
            $source = $this->sourceService->getSource($sourceId);
            $output->writeln("Fetched source: " . $source->url);

            $markdownContent = $this->getMarkdownContent($source);
            $breakdown = $this->createBreakdownRecord($sourceId, $output);
            $chunks = $this->getChunks($markdownContent, $chunkLimit, $output);

            $masterSummary = $this->generateMasterSummary($chunks, $strategy, $breakdown, $input, $output);
            $breakdown->updateMasterSummary($masterSummary);
            $this->breakdownRepo->save($breakdown);

            $partiesResult = $this->analyzeParties($masterSummary, $retry, $output, $input);
            $breakdown->setPartiesYaml($partiesResult); // Storing JSON string now

            $locationsResult = $this->analyzeLocations($masterSummary, $retry, $output, $input);
            $breakdown->setLocationsYaml($locationsResult); // Storing JSON string now

            $timelineResult = $this->analyzeTimeline($masterSummary, $source->accessedAt, $retry, $output, $input);
            // $breakdown->setTimelineYaml($timelineResult); // Skip storing raw intermediate timeline

            $datedTimeline = $this->improveTimelineDates($timelineResult, $source->accessedAt, $retry, $output, $input);
            $breakdown->setTimelineYaml($datedTimeline); // Storing JSON string now
            
            $result = BreakdownResult::fromArray([
                'parties' => json_decode($partiesResult, true),
                'locations' => json_decode($locationsResult, true),
                'timeline' => json_decode($datedTimeline, true)
            ]);
            $breakdown->setResult($result);
            $this->breakdownRepo->save($breakdown);

            $this->saveEvents($result, $output);
            $this->saveParties($result, $output);
            $this->outputFinalResult($result, $breakdown, $output);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }

    private function getMarkdownContent(\App\Domain\Source\Source $source): string
    {
        $htmlContent = $this->extractor->extractContent($source);
        $converter = new HtmlConverter();
        return $converter->convert($htmlContent);
    }

    private function createBreakdownRecord(SourceId $sourceId, OutputInterface $output): Breakdown
    {
        $breakdown = Breakdown::create($sourceId);
        $this->breakdownRepo->save($breakdown);
        $output->writeln("Created Breakdown record: " . $breakdown->id);
        return $breakdown;
    }

    private function getChunks(string $markdownContent, ?string $chunkLimit, OutputInterface $output): array
    {
        $chunks = $this->chunkingService->chunk($markdownContent);
        if ($chunkLimit !== null) {
            $chunks = array_slice($chunks, 0, (int)$chunkLimit);
            $output->writeln("  (Limited to $chunkLimit chunks by --chunk-limit flag)");
        }
        return $chunks;
    }

    private function generateMasterSummary(array $chunks, string $strategy, Breakdown $breakdown, InputInterface $input, OutputInterface $output): string
    {
        if ($strategy === self::STRATEGY_GROWING_SUMMARY) {
            return $this->generateGrowingSummary($chunks, $breakdown, $input, $output);
        } elseif ($strategy === self::STRATEGY_QUICK) {
            return $this->generateQuickSummary($chunks, $breakdown, $input, $output);
        } else {
            throw new \InvalidArgumentException("Unknown breakdown strategy: " . $strategy);
        }
    }

    private function generateGrowingSummary(array $chunks, Breakdown $breakdown, InputInterface $input, OutputInterface $output): string
    {
        $output->writeln("Processing chunks with growing summary strategy...");
        $runningSummary = "";
        foreach ($chunks as $i => $chunk) {
            $output->write("  - Processing chunk " . ($i + 1) . "... ");
            $startTime = microtime(true);
            
            $prompt = new Prompt(
                Prompt::BREAKDOWN_GROWING_SUMMARY_PROMPT,
                "## Current Chunk\n\n$chunk\n\n## Running Summary\n\n$runningSummary",
                null,
                (bool)$input->getOption("retry")
            );

            $response = $this->ai->generate($prompt);
            $updatedSummary = $response->text;

            $breakdown->addChunkSummary($updatedSummary);
            $runningSummary = $updatedSummary;
            $this->breakdownRepo->save($breakdown);
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            
            if ($output->isVerbose()) {
                $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
            }
        }
        return $runningSummary;
    }

    private function generateQuickSummary(array $chunks, Breakdown $breakdown, InputInterface $input, OutputInterface $output): string
    {
        $output->writeln("Processing " . count($chunks) . " chunks to generate individual summaries (quick strategy)...");
        foreach ($chunks as $i => $chunk) {
            $output->write("  - Processing chunk " . ($i + 1) . "... ");
            $startTime = microtime(true);

            $prompt = new Prompt(
                Prompt::BREAKDOWN_SUMMARY_PROMPT,
                "## Current Chunk\n\n$chunk",
                null,
                (bool)$input->getOption("retry")
            );

            $response = $this->ai->generate($prompt);
            $chunkSummary = $response->text;
            
            $breakdown->addChunkSummary($chunkSummary);
            $this->breakdownRepo->save($breakdown);
            $duration = microtime(true) - $startTime;
            $output->writeln(sprintf("Done (%.2fs)", $duration));
            
            if ($output->isVerbose()) {
                $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
            }
        }

        $output->write("Generating master summary from chunk summaries... ");
        $startTime = microtime(true);
        $masterSummaryPrompt = new Prompt(
            Prompt::BREAKDOWN_MASTER_SUMMARY_PROMPT,
            implode("\n\n", $breakdown->chunkSummaries),
            null,
            (bool)$input->getOption("retry")
        );
        $masterSummaryResponse = $this->ai->generate($masterSummaryPrompt);
        $masterSummary = $masterSummaryResponse->text;
        $duration = microtime(true) - $startTime;
        $output->writeln(sprintf("Done (%.2fs)", $duration));

        if ($output->isVerbose()) {
            $output->writeln("    Tokens: {$masterSummaryResponse->inputTokens} in / {$masterSummaryResponse->outputTokens} out");
        }
        return $masterSummary;
    }
    
    private function analyzeParties(string $summary, bool $retry, OutputInterface $output, InputInterface $input): string
    {
        $output->write("Generating parties analysis... ");
        $startTime = microtime(true);
        $prompt = new Prompt(Prompt::BREAKDOWN_PARTIES_PROMPT, $summary, null, $retry);
        $response = $this->ai->generate($prompt, Prompt::getPartiesSchema());
        $duration = microtime(true) - $startTime;
        $output->writeln(sprintf("Done (%.2fs)", $duration));
        if ($output->isVerbose()) {
            $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
        }
        return $response->text;
    }
    
    private function analyzeLocations(string $summary, bool $retry, OutputInterface $output, InputInterface $input): string
    {
        $output->write("Generating locations analysis... ");
        $startTime = microtime(true);
        $prompt = new Prompt(Prompt::BREAKDOWN_LOCATIONS_PROMPT, $summary, null, $retry);
        $response = $this->ai->generate($prompt, Prompt::getLocationsSchema());
        $duration = microtime(true) - $startTime;
        $output->writeln(sprintf("Done (%.2fs)", $duration));
        if ($output->isVerbose()) {
            $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
        }
        return $response->text;
    }

    private function analyzeTimeline(string $summary, \DateTimeImmutable $sourceDate, bool $retry, OutputInterface $output, InputInterface $input): string
    {
        $output->write("Generating timeline analysis... ");
        $startTime = microtime(true);
        $prompt = new Prompt(
            Prompt::BREAKDOWN_TIMELINE_PROMPT,
            $summary . "\n\n<SOURCE_DATE>" . $sourceDate->format(\DateTimeInterface::ATOM) . "</SOURCE_DATE>",
            null,
            $retry
        );
        $response = $this->ai->generate($prompt, Prompt::getTimelineSchema());
        $duration = microtime(true) - $startTime;
        $output->writeln(sprintf("Done (%.2fs)", $duration));
        if ($output->isVerbose()) {
            $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
        }
        return $response->text;
    }

    private function improveTimelineDates(string $timelineResult, \DateTimeImmutable $sourceDate, bool $retry, OutputInterface $output, InputInterface $input): string
    {
        $output->write("Attempting to date undated events... ");
        $startTime = microtime(true);
        $prompt = new Prompt(
            Prompt::BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT,
            $timelineResult . "\n\n<SOURCE_DATE>" . $sourceDate->format(\DateTimeInterface::ATOM) . "</SOURCE_DATE>",
            null,
            $retry
        );
        $response = $this->ai->generate($prompt, Prompt::getTimelineSchema());
        $duration = microtime(true) - $startTime;
        $output->writeln(sprintf("Done (%.2fs)", $duration));
        if ($output->isVerbose()) {
            $output->writeln("    Tokens: {$response->inputTokens} in / {$response->outputTokens} out");
        }
        return $response->text;
    }
    
    private function saveEvents(BreakdownResult $result, OutputInterface $output): void
    {
        foreach ($result->timeline as $event) {
            $textForEmbedding = $event->name . " " . $event->description;
            $embedding = $this->embeddingService->generateEmbedding($textForEmbedding);
            $event->embedding = $embedding;
            
            $similarEvent = $this->eventRepo->findSimilar($embedding);
            
            if ($similarEvent) {
                // Merging logic can be implemented here. For now, we'll just skip adding duplicates.
                $output->writeln("Skipping duplicate event: " . $event->name);
                continue;
            }
            
            $this->eventRepo->save($event);
        }
        $output->writeln("Saved " . count($result->timeline) . " events to the master timeline.");
    }

    private function saveParties(BreakdownResult $result, OutputInterface $output): void
    {
        foreach ($result->parties as $party) {
            $savedParty = $this->partyService->saveOrUpdateParty($party);
            if ($savedParty->id->value !== $party->id->value) {
                $output->writeln("Updated existing party: " . $savedParty->name . " with ID: " . $savedParty->id);
            } else {
                $output->writeln("Saved new party: " . $savedParty->name . " with ID: " . $savedParty->id);
            }
        }
        $output->writeln("Processed " . count($result->parties) . " parties for the master list.");
    }

    private function outputFinalResult(BreakdownResult $result, Breakdown $breakdown, OutputInterface $output): void
    {
        $output->writeln("--- Final Result ---");
        $output->writeln("Parties: " . count($result->parties));
        $output->writeln("Locations: " . count($result->locations));
        $output->writeln("Events: " . count($result->timeline));
        $output->writeln("---------------------");
        $output->writeln("Breakdown complete. You can review the breakdown at any time using the ID: " . $breakdown->id);
    }
}
