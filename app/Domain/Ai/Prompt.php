<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use Gemini\Data\Schema;
use Gemini\Enums\DataType;

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
        generate a list of all identified parties (people and organizations).
        
        The goal for individuals is a single identifiable consciousness.
        Descriptions should be concise and for disambiguation only (e.g., "(US Senator)", "(Electrician)").
    PRMT;

    public const BREAKDOWN_LOCATIONS_PROMPT = <<<PRMT
        You are an intelligence analyst. Based on the provided document summary,
        generate a list of all identified locations, inferring lower specificity
        levels for disambiguation where appropriate.
    PRMT;

    public const BREAKDOWN_TIMELINE_PROMPT = <<<PRMT
        You are an intelligence analyst. Based on the provided document summary and its source date (<SOURCE_DATE>),
        generate a chronological timeline of events.
        When possible, use the <SOURCE_DATE> as an anchor to infer precise dates for events, avoiding relative terms.

        Date Rules:
        1. If there is only one datetime, it must be the start_date.
        2. The start_date must always precede the end_date.
        3. If the start_date and end_date match, only a start_date is needed. Leave end_date empty.
        4. If "present" or "now" is implied for the end_date, use the <SOURCE_DATE> as the end_date.
    PRMT;
    
    public const BREAKDOWN_IMPROVE_TIMELINE_DATES_PROMPT = <<<PRMT
        You are a historical archivist. Refine the provided timeline. You have access to the document source date (<SOURCE_DATE>).
        For each event with an imprecise `human_readable_date`, `start_date`, or `start_precision`,
        use your knowledge and the <SOURCE_DATE> to find a more specific `start_date` and `start_precision`.
        Avoid relative dates in `human_readable_date` if a precise date can be determined.
        If an `end_date` is missing but can be inferred, add it.
        If `is_circa` is true but a precise date can be found, set `is_circa` to false.
        
        Date Rules:
        1. If there is only one datetime, it must be the start_date.
        2. The start_date must always precede the end_date.
        3. If the start_date and end_date match, only a start_date is needed. Leave end_date empty.
        4. If "present" or "now" is implied for the end_date, use the <SOURCE_DATE> as the end_date.

        Do not guess dates; if a more specific date cannot be confidently determined,
        leave the existing values as they are. Return the updated timeline using the same structure.
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

    public static function getPartiesSchema(): Schema
    {
        return new Schema(
            type: DataType::OBJECT,
            properties: [
                'people' => new Schema(
                    type: DataType::ARRAY,
                    items: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'name' => new Schema(type: DataType::STRING),
                            'aliases' => new Schema(type: DataType::ARRAY, items: new Schema(type: DataType::STRING)),
                            'disambiguation_description' => new Schema(type: DataType::STRING)
                        ],
                        required: ['name']
                    )
                ),
                'organizations' => new Schema(
                    type: DataType::ARRAY,
                    items: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'official_name' => new Schema(type: DataType::STRING),
                            'alternate_names' => new Schema(type: DataType::ARRAY, items: new Schema(type: DataType::STRING)),
                            'description' => new Schema(type: DataType::STRING)
                        ],
                        required: ['official_name']
                    )
                )
            ]
        );
    }

    public static function getLocationsSchema(): Schema
    {
        return new Schema(
            type: DataType::OBJECT,
            properties: [
                'locations' => new Schema(
                    type: DataType::ARRAY,
                    items: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'name' => new Schema(type: DataType::STRING)
                            // Add inferred fields later if needed
                        ],
                        required: ['name']
                    )
                )
            ]
        );
    }

    public static function getTimelineSchema(): Schema
    {
        return new Schema(
            type: DataType::OBJECT,
            properties: [
                'events' => new Schema(
                    type: DataType::ARRAY,
                    items: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'name' => new Schema(type: DataType::STRING),
                            'description' => new Schema(type: DataType::STRING),
                            'human_readable_date' => new Schema(type: DataType::STRING),
                            'start_date' => new Schema(type: DataType::STRING),
                            'start_precision' => new Schema(type: DataType::STRING, enum: ['year', 'month', 'day', 'hour', 'minute', 'second', 'decade', 'season', 'quarter']),
                            'end_date' => new Schema(type: DataType::STRING),
                            'end_precision' => new Schema(type: DataType::STRING, enum: ['year', 'month', 'day', 'hour', 'minute', 'second', 'decade', 'season', 'quarter']),
                            'is_circa' => new Schema(type: DataType::BOOLEAN)
                        ],
                        required: ['name', 'description']
                    )
                )
            ]
        );
    }
}
