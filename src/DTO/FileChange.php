<?php

namespace Busanstu\DiffDefenderBundle\DTO;


class FileChange
{
    public function __construct(
        public readonly string $relativePath,
        public readonly array $addedLines,
        public readonly array $removedLines,
        public readonly string $status
    ) {
    }

    public function getExtension(): string
    {
        return pathinfo($this->relativePath, PATHINFO_EXTENSION);
    }
}
