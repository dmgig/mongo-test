<?php

declare(strict_types=1);

namespace App\Domain\Ai;

class Prompt
{
    public const DEFAULT_SYSTEM_PROMPT = "You are a helpful AI assistant.";

    public const ANALYSIS_PROMPT = "Analyze the following data and provide insights.";
    
    public const CONTENT_SELECTOR_PROMPT = <<<PRMT
        You are an expert web scraper. Analyze the provided HTML skeleton to identify
        CSS selectors for the main article content (title, body text, main images).
        Be precise and return only comma-separated selectors. Exclude navigation, headers,
        footers, sidebars, comments, and script tags.
    PRMT;
    
    public const BREAKDOWN_SUMMARY_PROMPT = <<<PRMT
        You are an intelligence analyst. Summarize the provided text chunk.
        Identify key actors (people, organizations), locations, and events.
        Focus on concise entity and event extraction. Maintain accuracy in dates.
        Return a brief, structured summary as plain text, not YAML. This is for internal use.
    PRMT;

    public const BREAKDOWN_MASTER_SUMMARY_PROMPT = <<<PRMT
        You are an intelligence analyst. Synthesize the provided chunk summaries
        into a single, comprehensive master summary. Identify all key actors (people,
        organizations), locations, and events mentioned across all chunks.
        Ensure accuracy in entities and dates. Return the master summary as plain text, not YAML.
        This is for internal use.
    PRMT;
    
    public const BREAKDOWN_GROWING_SUMMARY_PROMPT = <<<PRMT
        You are an intelligence analyst. Update the provided running summary with new information
        from the current text chunk. Identify and integrate new key actors (people, organizations),
        locations, and events. Prioritize new details and refine existing ones for clarity.
        If the running summary is empty, use the current chunk as the initial summary.
        Maintain accuracy in entities and dates. Return the updated summary as plain text, not YAML.
    PRMT;
    
    public const BREAKDOWN_PARTIES_PROMPT = <<<PRMT
        You are an intelligence analyst. Based on the provided document summary,
        generate a YAML list of all identified parties (people and organizations).
        
        The goal for individuals is a single identifiable consciousness.
        Descriptions should be concise and for disambiguation only (e.g., "(US Senator)", "(Electrician)").
        
        IMPORTANT: Ensure all strings, especially names and descriptions containing special characters
        like double quotes, are properly escaped or enclosed in single quotes as per YAML specifications.

        YAML Structure:
        people:
          - name: "Full Name of Individual"
            aliases: ["Nickname", "Known As"] # Optional
            disambiguation_description: "(e.g., US Senator, Lead Scientist at ACME Corp)" # Optional, for disambiguation only
        organizations:
          - official_name: "Official Name of Organization"
            alternate_names: ["Acronym", "Common Name"] # Optional
            description: "(e.g., Global technology conglomerate, Local community outreach group)" # Optional, for disambiguation only
    PRMT;

    public const BREAKDOWN_LOCATIONS_PROMPT = <<<PRMT
        You are an intelligence analyst. Based on the provided document summary,
        generate a YAML list of all identified locations, inferring lower specificity
        levels for disambiguation where appropriate.

        YAML Structure:
        locations:
          - name: "Location Name"
            # ... other inferred details like city, country, coordinates if available and relevant
    PRMT;

    public const BREAKDOWN_TIMELINE_PROMPT = <<<PRMT
        You are an intelligence analyst. Based on the provided document summary and its source date (<SOURCE_DATE>),
        generate a chronological YAML timeline of events.
        When possible, use the <SOURCE_DATE> as an anchor to infer precise dates for events, avoiding relative terms.

        YAML Structure:
        events:
          - name: "Event Name"
            description: "Event description."
            human_readable_date: "e.g., 'the summer of ''89''', 'a few days after the incident', 'late 1980s', 'early 2000s', 'March 1999', 'May 15, 2001', 'circa 1963', '1963', '1963-11-22', '1963-11-22 13:30:00'"
            start_date: "YYYY-MM-DD HH:MM:SS" # UTC or with timezone if available, otherwise assume local.
            start_precision: "year" # or month, day, hour, minute, second
            end_date: "YYYY-MM-DD HH:MM:SS" # Optional
            end_precision: "year" # Optional, or month, day, hour, minute, second
            is_circa: false # Boolean
    PRMT;
    
    public const BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT = <<<PRMT
        You are a historical archivist. Refine the provided YAML timeline. You have access to the document source date (<SOURCE_DATE>).
        For each event with an imprecise `human_readable_date`, `start_date`, or `start_precision`,
        use your knowledge and the <SOURCE_DATE> to find a more specific `start_date` and `start_precision`.
        Avoid relative dates in `human_readable_date` if a precise date can be determined.
        If an `end_date` is missing but can be inferred, add it.
        If `is_circa` is true but a precise date can be found, set `is_circa` to false.
        Do not guess dates; if a more specific date cannot be confidently determined,
        leave the existing values as they are. Return the updated timeline in the same YAML format.
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
