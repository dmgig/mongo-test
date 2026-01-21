<?php

declare(strict_types=1);

namespace App\Domain\Source;

use DateTimeImmutable;

final class Source
{
    public function __construct(
        public readonly SourceId $id,
        public readonly string $url,
        public readonly string $content,
        public readonly int $httpCode,
        public readonly DateTimeImmutable $accessedAt,
    ) {
    }

    public static function create(string $url, string $content, int $httpCode): self
    {
        return new self(
            SourceId::generate(),
            $url,
            $content,
            $httpCode,
            new DateTimeImmutable()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->id->value,
            'url' => $this->url,
            'content' => $this->content,
            'http_code' => $this->httpCode,
            'accessed_at' => new \MongoDB\BSON\UTCDateTime($this->accessedAt),
        ];
    }
}
