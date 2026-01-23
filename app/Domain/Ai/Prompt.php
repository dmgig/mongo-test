<?php

declare(strict_types=1);

namespace App\Domain\Ai;

class Prompt
{
    // Define your hardcoded system prompts here as constants
    public const DEFAULT_SYSTEM_PROMPT = <<<PRMPT
        You are a helpful AI assistant.'
    PRMPT;

    public const ANALYSIS_PROMPT = <<<PRMPT
        Analyze the following data and provide insights.
    PRMPT;
    
    public const CONTENT_SELECTOR_PROMPT = <<<PRMPT
        You are an expert web scraper. Analyze the following HTML skeleton and 
        identify the CSS selectors that target the main article content 
        (e.g., title, body text, main images). Strive for precision. You can return 
        multiple comma-separated selectors. Exclude headers, footers, sidebars, navigation, 
        comments, and script tags. Return ONLY the CSS selector(s), nothing else.
    PRMPT;
    
    public const BREAKDOWN_SUMMARY_PROMPT = <<<PRMPT
        You are an intelligence analyst. Your task is to identify key actors (people and organizations),
        locations and events from the provided text. The text is a small chunk of a larger document.
        
        There is no requirement for this to be human-readable. You can construct it however you like, 
        the only rule is that it be as concise as possible while capturing all relevant entities and events.
        
        For people and locations, we want a very list of keywords for use in disambiguation. Locations can
        be as broad as countries or as specific as street addresses or even just a description of a place.
        You should use context to determine a lower level of specificity for disambiguation.

        I will also provide you with a running summary of the document so far. Your response should 
        be a simple update to the running summary, incorporating any new information from the 
        current chunk.
    PRMPT;
    
    public const BREAKDOWN_PARTIES_PROMPT = <<<PRMPT
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
    PRMPT;

    public const BREAKDOWN_LOCATIONS_PROMPT = <<<PRMPT
        You are an intelligence analyst populating a YAML file. Based on the following summary of a document, 
        please provide a list of all identified locations with infered lower levels of
        specificity for disambiguation. Return as YAML.
    PRMPT; 

    public const BREAKDOWN_TIMELINE_PROMPT = <<<PRMPT
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
    PRMPT;
    
    public const BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT = <<<PRMPT
       You are a historical archivist populating a YAML file. Below is a timeline of events in YAML format. For each event with an imprecise date, please use your knowledge to find a more specific date. If you cannot find a date, leave it as is. Do not guess. Return the updated timeline in the same YAML format.
    PRMPT;

    public function __construct(
        public readonly string $systemPrompt,
        public readonly string $userInput,
        public readonly ?string $model = null
    ) {
    }

    public static function create(string $userInput, string $systemPrompt = self::DEFAULT_SYSTEM_PROMPT, ?string $model = null): self
    {
        return new self($systemPrompt, $userInput, $model);
    }

    public function __toString(): string
    {
        // For simple models, we might just concatenate. 
        // Advanced models might keep them separate (System vs User role).
        // This method is a fallback string representation.
        return "System: {$this->systemPrompt}\n\nUser: {$this->userInput}";
    }
}
