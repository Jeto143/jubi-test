<?php

declare(strict_types=1);

namespace App\Database;

/**
 * @psalm-immutable
 */
final class ImportableUser implements \JsonSerializable
{
    /**
     * @param string[] $filterIds
     */
    public function __construct(
        private string $id,
        private array $filterIds,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getFilterIds(): array
    {
        return $this->filterIds;
    }

    /**
     * @return array{_id: string, attributs: array<string>}
     */
    public function jsonSerialize(): array
    {
        return ['_id' => $this->id, 'attributs' => $this->filterIds];
    }
}
