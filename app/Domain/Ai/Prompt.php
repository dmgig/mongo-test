<?php

declare(strict_types=1);

namespace App\Domain\Ai;

class Prompt
{
    // Define your hardcoded system prompts here as constants
    public const DEFAULT_SYSTEM_PROMPT = <<<PRMT
        You are a helpful AI assistant.'
    PRMT;

    public const ANALYSIS_PROMPT = <<<PRMT
        Analyze the following data and provide insights.
    PRMT;
    
    public const CONTENT_SELECTOR_PROMPT = <<<PRMT
        You are an expert web scraper. Analyze the following HTML skeleton and 
        identify the CSS selectors that target the main article content 
        (e.g., title, body text, main images). Strive for precision. You can return 
        multiple comma-separated selectors. Exclude headers, footers, sidebars, navigation, 
        comments, and script tags. Return ONLY the CSS selector(s), nothing else.
    PRMT;
    
    public const BREAKDOWN_SUMMARY_PROMPT = <<<PRMT
        You are an intelligence analyst. Your task is to identify key actors (people and organizations),
        locations, and events from the provided text, which is a small chunk of a larger document.
        Your response should be a concise summary of this chunk, focusing on entities and events. 
        You need to keep track of people, organizations, locations, and dates (as accurately as possible).
        Return a brief, structured summary of the current chunk as text, not YAML. This will be used internally.
    PRMT;

    public const BREAKDOWN_MASTER_SUMMARY_PROMPT = <<<PRMT
        You are an intelligence analyst. Below are several summaries of different chunks of a larger document.
        Your task is to synthesize these into a single, comprehensive master summary. This master summary
        should identify all key actors (people and organizations), locations, and events mentioned across all chunks.
        Ensure accuracy in people, organizations, locations, and dates.
        Return a comprehensive, structured master summary of the entire document as text, not YAML. This will be used internally.
    PRMT;
    
    public const BREAKDOWN_PARTIES_PROMPT = <<<PRMT
        You are an intelligence analyst populating a YAML file. Based on the following summary of a document, 
        please provide a list of all identified parties (people and organizations).
        
        The YAML structure should be:
        
        people:
          - name: "full name"
            description: "a brief description"
        organizations:
          - official_name: "official name"
            alternate_names: ["alt name 1", "acronym"]
            description: "a brief description"
    PRMT;

    public const BREAKDOWN_LOCATIONS_PROMPT = <<<PRMT
        You are an intelligence analyst populating a YAML file. Based on the following summary of a document, 
        please provide a list of all identified locations with infered lower levels of
        specificity for disambiguation. Return as YAML.
    PRMT;

    public const BREAKDOWN_TIMELINE_PROMPT = <<<PRMT
        You are an intelligence analyst populating a YAML file. Based on the following summary of a document, 
        please provide a chronological timeline of events.
        
        The YAML structure should be:

        events:
          - name: "Event Name"
            description: "Event description."
            human_readable_date: "e.g., 'the summer of '89', 'a few days after the incident'"
            start_date: "YYYY-MM-DD HH:MM:SS"
            start_precision: "year" # or month, day, hour, minute, second
            end_date: "YYYY-MM-DD HH:MM:SS"
            end_precision: "year"
            is_circa: false
    PRMT;
    
    public const BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT = <<<PRMT
       You are a historical archivist populating a YAML file. Below is a timeline of events in YAML format. For each event with an imprecise date, please use your knowledge to find a more specific date. If you cannot find a date, leave it as is. Do not guess. Return the updated timeline in the same YAML format.
    PRMT;

    public function __construct(
        public readonly string $systemPrompt,
        public readonly string $userInput,
        public readonly ?string $model = null,
        public readonly bool $retry = false
    ) {
    }

    public static function create(string $userInput, string $systemPrompt = self::DEFAULT_SYSTEM_PROMPT, ?string $model = null, bool $retry = false): self
    {
        return new self($systemPrompt, $userInput, $model, $retry);
    }

    public function __toString(): string
    {
        // For simple models, we might just concatenate. 
        // Advanced models might keep them separate (System vs User role).
        // This method is a fallback string representation.
        return "System: {$this->systemPrompt}\n\nUser: {$this->userInput}";
    }
}
