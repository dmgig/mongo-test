<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use MongoDB\Client;
use MongoDB\Database;

final class MongoConnector
{
    public function __construct(
        private readonly string $uri,
        private readonly string $database,
        /** @var array<string, mixed> */
        private readonly array $uriOptions = [],
        /** @var array<string, mixed> */
        private readonly array $driverOptions = [],
    ) {
    }

    public static function fromEnvironment(): self
    {
        // For ddev add-on `ddev add-on get ddev/ddev-mongo` the service name is typically `mongo`.
        // Default ddev credentials are db:db
        $uri = getenv('MONGODB_URI') ?: 'mongodb://db:db@mongo:27017';
        $db = getenv('MONGODB_DATABASE') ?: 'test';

        return new self($uri, $db);
    }

    public function client(): Client
    {
        return new Client($this->uri, $this->uriOptions, $this->driverOptions);
    }

    public function database(): Database
    {
        return $this->client()->selectDatabase($this->database);
    }
}
