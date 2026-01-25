<?php

declare(strict_types=1);

namespace Cli\Commands;

use App\Domain\Source\SourceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSourceCommand extends Command
{
    // The name of the command (the part after "php bin/console")
    protected static $defaultName = 'sources:create';

    public function __construct(
        private readonly SourceService $sourceService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('sources:create')
            ->setDescription('Creates new sources by fetching content from URLs.')
            ->setHelp('This command allows you to create one or more sources...')
            ->addArgument('url', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The URL(s) of the source(s)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $urls = $input->getArgument('url');

        foreach ($urls as $url) {
            $output->writeln("Fetching source from: " . $url);

            try {
                $source = $this->sourceService->createSource($url);
                
                $output->writeln('<info>Source created successfully!</info>');
                $output->writeln("ID: " . $source->id->value);
                $output->writeln("HTTP Code: " . $source->httpCode);
                $output->writeln("Content Length: " . strlen($source->content) . " bytes");
                $output->writeln("-----------------------------------");
                
            } catch (\Exception $e) {
                $output->writeln('<error>Error creating source for URL ' . $url . ': ' . $e->getMessage() . '</error>');
                // Continue to the next URL
            }
        }

        return Command::SUCCESS;
    }
}
