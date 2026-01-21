<?php

require_once dirname(__DIR__) . '/settings.php';

use App\Domain\Ai\Prompt;
use App\Infrastructure\Ai\Gemini\GeminiAdapter;
use Gemini;

// Load API Key from Environment
$apiKey = getenv('GEMINI_API_KEY');

if (!$apiKey) {
    die("Error: GEMINI_API_KEY environment variable not set.\n");
}

try {
    $client = Gemini::client($apiKey);
    $adapter = new GeminiAdapter($client);

    // Test with Analysis Prompt
    $userInput = "The user has logged in 5 times today.";
    $prompt = Prompt::create($userInput, Prompt::ANALYSIS_PROMPT);

    echo "--- Sending Prompt ---\n";
    echo (string) $prompt . "\n\n";
    echo "--- Response ---\n";
    
    $response = $adapter->generate($prompt);
    
    echo $response . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
