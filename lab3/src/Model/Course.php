<?php
declare(strict_types=1);

namespace App\Model;

class Course
{
    private string $courseId;
    private array $moduleIds;
    private array $requiredModuleIds;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        string $courseId,
        array $moduleIds,
        array $requiredModuleIds,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $updatedAt
    )
    {
        $this->courseId = $courseId;
        $this->moduleIds = $moduleIds;
        $this->requiredModuleIds = $requiredModuleIds;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getCourseId(): string
    {
        return $this->courseId;
    }

    public function getModules(): array
    {
        return $this->moduleIds;
    }

    public function getRequiredModuleIds(): array
    {
        return $this->requiredModuleIds;
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