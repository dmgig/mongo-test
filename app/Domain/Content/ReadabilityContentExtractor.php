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

        try {
            $readability->parse($source->content);
            return $readability->getContent();
        } catch (ParseException $e) {
            return "Error: Readability could not extract content: " . $e->getMessage();
        }
    }
}
