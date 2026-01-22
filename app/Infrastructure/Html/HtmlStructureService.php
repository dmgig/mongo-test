<?php

declare(strict_types=1);

namespace App\Infrastructure\Html;

use DOMDocument;
use DOMNode;
use DOMElement;

class HtmlStructureService
{
    /**
     * Extracts the structural skeleton of an HTML document.
     * Keeps only tags and 'class' attributes.
     */
    public function extractStructure(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Suppress warnings for invalid HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // UTF-8 hack
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Process the root nodes (usually html, or whatever root elements are present)
        $newDoc = new DOMDocument('1.0', 'UTF-8');
        $newDoc->formatOutput = true; // Nice formatting

        foreach ($dom->childNodes as $node) {
            $importedNode = $this->processNode($node, $newDoc);
            if ($importedNode) {
                $newDoc->appendChild($importedNode);
            }
        }

        // Save HTML and remove the XML declaration if present
        return $newDoc->saveHTML();
    }

    private function processNode(DOMNode $node, DOMDocument $doc): ?DOMNode
    {
        // Only process elements
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }

        /** @var DOMElement $node */
        $newNode = $doc->createElement($node->tagName);

        // Copy class, id, and role attributes if they exist
        if ($node->hasAttribute('class')) {
            $newNode->setAttribute('class', $node->getAttribute('class'));
        }
        if ($node->hasAttribute('id')) {
            $newNode->setAttribute('id', $node->getAttribute('id'));
        }
        if ($node->hasAttribute('role')) {
            $newNode->setAttribute('role', $node->getAttribute('role'));
        }

        // Recursively process children
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $processedChild = $this->processNode($child, $doc);
                if ($processedChild) {
                    $newNode->appendChild($processedChild);
                }
            }
        }

        return $newNode;
    }
}
