<?php
declare(strict_types=1);

namespace App\Model;

class CourseModule
{
    private string $moduleId;
    private int $isRequired;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        string $moduleId,
        int $isRequired,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $updatedAt
    )
    {
        $this->moduleId = $moduleId;
        $this->isRequired = $isRequired;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getModules(): string
    {
        return $this->moduleId;
    }

    public function getIsRequired(): int
    {
        return $this->isRequired;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}