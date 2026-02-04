<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Source\Source;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use Symfony\Component\Console\Output\OutputInterface;

class ReadabilityContentExtractor implements ContentExtractorInterface
{
    public function extractContent(Source $source, ?OutputInterface $output = null): string
    {
        $config = new Configuration();
        $config->setFixRelativeURLs(true);
        $config->setOriginalURL($source->url);

        $readability = new Readability($config);

        // If the content does not appear to be HTML, return it directly.
        if (!preg_match('/<html|body/i', $source->content)) {
            return $source->content;
        }

        try {
            $readability->parse($source->content);
            return $readability->getContent();
        } catch (ParseException $e) {
            if ($output) {
                $output->writeln("Readability parse error: " . $e->getMessage());
            }
            return $source->content;
        }
    }
}
