<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/settings.php';

use App\Infrastructure\Mongo\MongoConnector;

header('Content-Type: text/plain; charset=utf-8');

try {
    $connector = MongoConnector::fromEnvironment();
    $db = $connector->database();

    // Force a round-trip to the server.
    $result = $db->command(['ping' => 1])->toArray();

    echo "Mongo ping ok\n";
    echo "Database: " . $db->getDatabaseName() . "\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Error connecting to MongoDB:\n";
    echo $e->getMessage();
}
