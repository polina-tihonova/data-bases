<?php
declare(strict_types=1);

namespace App\Model\Data;

class SaveCourseParams
{
    private string $courseId;
    private array $moduleIds;
    private array $requiredModuleIds;

    public function __construct(
        string $courseId,
        array $moduleIds,
        array $requiredModuleIds
    )
    {
        $this->courseId = $courseId;
        $this->moduleIds = $moduleIds;
        $this->requiredModuleIds = $requiredModuleIds;
    }

    public function getCourseId(): string
    {
        return $this->courseId;
    }

    public function getModuleIds(): array
    {
        return $this->moduleIds;
    }

    public function getRequiredModuleIds(): array
    {
        return $this->requiredModuleIds;
    }
}