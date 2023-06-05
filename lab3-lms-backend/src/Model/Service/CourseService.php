<?php
declare(strict_types=1);

namespace App\Model\Service;

use App\Common\Database\Synchronization;
use App\Database\CourseQueryService;

use App\Model\Course;
use App\Model\CourseModuleStatus;
use App\Model\Data\CourseStatusData;
use App\Model\Data\SaveCourseParams;
use App\Model\Data\SaveEnrollmentParams;
use App\Model\Data\SaveMaterialStatusParams;

class CourseService
{
    public function __construct(private Synchronization $synchronization, private CourseQueryService $courseQueryService)
    {
        $this->synchronization = $synchronization;
        $this->courseQueryService = $courseQueryService;
    }

    public function saveCourse(SaveCourseParams $params): string
    {
        return $this->synchronization->doWithTransaction(function () use ($params) {

            $courseId = $params->getCourseId();
            $moduleIds = $params->getModuleIds();
            $requiredModuleIds = $params->getRequiredModuleIds();

            $course = new Course (
                $courseId,
                $moduleIds,
                $requiredModuleIds,
                new \DateTimeImmutable(),
                new \DateTimeImmutable()
            );

            return $this->courseQueryService->saveCourse($course);
        });
    }

    public function deleteCourse(string $courseId): void
    {
        $this->courseQueryService->deleteCourse($courseId);
    }

    public function saveEnrollment(SaveEnrollmentParams $params): string
    {
        return $this->synchronization->doWithTransaction(function () use ($params) {
            $courseId = $params->getCourseId();
            $enrollmentId = $params->getEnrollmentId();

            return $this->courseQueryService->saveEnrollment($enrollmentId, $courseId);
        });
    }

    public function saveMaterialStatus(SaveMaterialStatusParams $params): CourseModuleStatus
    {
        return $this->synchronization->doWithTransaction(function () use ($params) {
            return $this->courseQueryService->saveMaterialStatus(
                $params->getEnrollmentId(),
                $params->getModuleId(),
                $params->getProgress(),
                $params->getSessionDuration()
            );
        });
    }

    public function getCourseStatusData(string $enrollmentId): CourseStatusData
    {
        return $this->courseQueryService->getCourseStatusData($enrollmentId);
    }
}