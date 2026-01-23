<?php

declare(strict_types=1);

namespace Cli\Commands;

use App\Domain\Ai\AiModelInterface;
use App\Domain\Ai\Prompt;
use App\Domain\Breakdown\Breakdown;
use App\Domain\Breakdown\BreakdownRepositoryInterface;
use App\Domain\Content\ChunkingService;
use App\Domain\Content\ContentExtractorInterface;
use App\Domain\Source\SourceId;
use App\Domain\Source\SourceService;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('id', InputArgument::REQUIRED, 'The Source ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $sourceId = SourceId::fromString($id);

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
                $output->writeln("  - Processing chunk " . ($i + 1));

                $prompt = new Prompt(
                    'You are an intelligence analyst. Your task is to identify key actors (people and organizations) and events from the provided text. The text is a small chunk of a larger document. I will also provide you with a running summary of the document so far. Your response should be a simple update to the running summary, incorporating any new information from the current chunk. You need especially to keep track of people, organizations, and dates (as accurately as possible).',
                    "## Running Summary\n\n{$breakdown->summary}\n\n## Current Chunk\n\n$chunk"
                );

                $updatedSummary = $this->ai->generate($prompt);
                $breakdown->updateSummary($updatedSummary);
                $this->breakdownRepo->save($breakdown);
            }

            // 6. Final Analysis
            $output->writeln("Generating final analysis...");

            $partiesPrompt = new Prompt(
                'You are an intelligence analyst. Based on the following summary of a document, please provide a list of all identified parties (people and organizations).',
                $breakdown->summary
            );
            $partiesResult = $this->ai->generate($partiesPrompt);

            $timelinePrompt = new Prompt(
                'You are an intelligence analyst. Based on the following summary of a document, please provide a chronological timeline of events with the dates.',
                $breakdown->summary
            );
            $timelineResult = $this->ai->generate($timelinePrompt);

            // 7. Dating Step
            $output->writeln("Attempting to date undated events...");
            $datingPrompt = new Prompt(
                'You are a historical archivist. Below is a timeline of events. For each item in the chronology that is marked as "Undated", please use your knowledge to find a more specific date (even a year or a month is helpful). If you cannot find a date, leave it as "Undated". Do not guess. Return the updated timeline.',
                $timelineResult
            );
            $datedTimeline = $this->ai->generate($datingPrompt);
            
            $finalResult = $partiesResult . "\n\n" . $datedTimeline;
            $breakdown->setResult($finalResult);
            $this->breakdownRepo->save($breakdown);

            // 8. Output
            $output->writeln("--- Final Result ---");
            $output->writeln($finalResult);
            $output->writeln("--------------------");
            $output->writeln("Breakdown complete. You can review the breakdown at any time using the ID: " . $breakdown->id);


            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
