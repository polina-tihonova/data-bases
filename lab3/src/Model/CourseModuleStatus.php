<?php
declare(strict_types=1);

namespace App\Model;

class CourseModuleStatus
{
    private string $enrollmentId;
    private string $moduleId;
    private int $progress;
    private int $sessionDuration;

    public function __construct(
        string $enrollmentId,
        string $moduleId,
        int $progress,
        int $sessionDuration
    )
    {
        $this->enrollmentId = $enrollmentId;
        $this->moduleId = $moduleId;
        $this->progress = $progress;
        $this->sessionDuration = $sessionDuration;
    }

    public function getEnrollmentId(): string
    {
        return $this->enrollmentId;
    }

    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getDuration(): int
    {
        return $this->sessionDuration;
    }
}