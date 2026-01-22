<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Ai\AiModelInterface;
use App\Domain\Ai\Prompt;
use App\Domain\Source\Source;
use App\Infrastructure\Html\HtmlStructureService;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class AiContentExtractor implements ContentExtractorInterface
{
    public function __construct(
        private readonly HtmlStructureService $htmlStructureService,
        private readonly AiModelInterface $aiModel
    ) {
    }

    public function extractContent(Source $source, ?OutputInterface $output = null): string
    {
        // 1. Get HTML Structure (Skeleton)
        $skeleton = $this->htmlStructureService->extractStructure($source->content);

        if ($output && $output->isVerbose()) {
            $output->writeln("<comment>--- HTML Skeleton ---</comment>");
            $output->writeln($skeleton);
            $output->writeln("<comment>---------------------</comment>");
        }

        // 2. Ask AI for the best CSS Selector
        $prompt = Prompt::create($skeleton, Prompt::CONTENT_SELECTOR_PROMPT);
        $selector = trim($this->aiModel->generate($prompt));

        // Clean up selector (sometimes AI adds quotes or markdown)
        $selector = str_replace(['`', '"', "'"], '', $selector);
        
        if ($output && $output->isVerbose()) {
            $output->writeln("<comment>--- Chosen Selector ---</comment>");
            $output->writeln($selector);
            $output->writeln("<comment>----------------------</comment>");
        }

        // 3. Extract content using the selector from the ORIGINAL HTML
        if (empty($selector)) {
            return "Error: AI could not identify a selector.";
        }

        try {
            $crawler = new Crawler($source->content);
            $contentNodes = $crawler->filter($selector);

            if ($contentNodes->count() > 0) {
                $html = '';
                foreach ($contentNodes as $node) {
                    $html .= $node->ownerDocument->saveHTML($node);
                }

                // Remove script and style tags
                $cleanCrawler = new Crawler($html);
                $cleanCrawler->filter('script, style')->each(function (Crawler $crawler) {
                    foreach ($crawler as $node) {
                        $node->parentNode->removeChild($node);
                    }
                });
                
                return $cleanCrawler->html();
            } else {
                return "Error: Selector '$selector' found no content.";
            }
        } catch (\Exception $e) {
            return "Error applying selector '$selector': " . $e->getMessage();
        }
    }
}
