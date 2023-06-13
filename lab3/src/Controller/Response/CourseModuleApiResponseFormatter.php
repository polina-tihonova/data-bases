<?php
declare(strict_types=1);

namespace App\Controller\Response;

use App\Model\CourseModuleStatus;

class CourseModuleApiResponseFormatter
{
    public static function formatCourseModuleStatusData(CourseModuleStatus $courseModuleStatusData): array
    {
        $result = [
            'enrollment_id' => $courseModuleStatusData->getEnrollmentId(),
            'module_id' => $courseModuleStatusData->getEnrollmentId(),
            'progress' => $courseModuleStatusData->getProgress(),
            'duration' => $courseModuleStatusData->getDuration()
        ];
        return $result;
    }
}
