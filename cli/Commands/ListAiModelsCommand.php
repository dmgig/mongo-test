<?php

declare(strict_types=1);

namespace Cli\Commands;

use Gemini\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListAiModelsCommand extends Command
{
    public function __construct(
        private readonly Client $client
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ai:models:available')
            ->setDescription('Lists available AI models from the provider.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln("Fetching available models...");
            $response = $this->client->models()->list();
            
            $output->writeln("Available Models:");
            foreach ($response->models as $model) {
                // Filter for generateContent support if possible, or just list all
                $output->writeln("- <info>" . $model->name . "</info> (" . $model->displayName . ")");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error fetching models: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
