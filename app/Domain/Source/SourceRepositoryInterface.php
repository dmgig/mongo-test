<?php

declare(strict_types=1);

namespace App\Domain\Source;

interface SourceRepositoryInterface
{
    public function save(Source $source): void;
    public function findById(SourceId $id): ?Source;
    public function delete(SourceId $id): void;
    /**
     * @return Source[]
     */
    public function findAll(): array;
}
