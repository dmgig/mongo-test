<?php

declare(strict_types=1);

namespace Cli\Commands;

use App\Domain\Content\ContentExtractorInterface;
use App\Domain\Source\SourceService;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Console\Command\Command;
use App\Domain\Source\SourceId;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtractContentCommand extends Command
{
    /**
     * @param ContentExtractorInterface[] $extractors
     */
    public function __construct(
        private readonly SourceService $sourceService,
        private readonly iterable $extractors
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('sources:content')
            ->setDescription('Extracts core content from an existing Source by ID.')
            ->addArgument('id', InputArgument::REQUIRED, 'The Source ID')
            ->addOption('extractor', null, InputOption::VALUE_REQUIRED, 'The extractor to use (ai or readability)', 'ai')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (html or markdown)', 'html');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $extractorName = $input->getOption('extractor');
        $format = $input->getOption('format');

        $output->writeln("Processing Source ID: $id with extractor: $extractorName");

        try {
            $extractor = null;
            foreach ($this->extractors as $key => $e) {
                if ($key === $extractorName) {
                    $extractor = $e;
                    break;
                }
            }
            
            if (!$extractor) {
                throw new \Exception("Extractor '$extractorName' not found.");
            }

            // 1. Fetch Source
            $source = $this->sourceService->getSource(SourceId::fromString($id));
            $output->writeln("Found source: " . $source->url);

            // 2. Extract Content
            $output->writeln("Extracting core content (this may take a moment)...");
            $content = $extractor->extractContent($source, $output);

            // 3. Convert to Markdown if requested
            if ($format === 'markdown') {
                $converter = new HtmlConverter();
                $content = $converter->convert($content);
            }

            // 4. Output
            $output->writeln("--- Extracted Content ---");
            $output->writeln($content);
            $output->writeln("-------------------------");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
