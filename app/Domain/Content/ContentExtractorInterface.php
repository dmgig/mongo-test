<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Source\Source;
use Symfony\Component\Console\Output\OutputInterface;

interface ContentExtractorInterface
{
    public function extractContent(Source $source, ?OutputInterface $output = null): string;
}
