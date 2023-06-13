<?php
declare(strict_types=1);

namespace App\Database;

use App\Common\Database\Connection;
use App\Model\Course;
use App\Model\CourseModuleStatus;
use App\Model\Data\ModuleStatusData;
use App\Model\Data\CourseStatusData;
use App\Model\Exception\DuplicateSaveException;
use App\Model\Exception\CourseNotFoundException;

class CourseQueryService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function saveCourse(Course $course): string
    {
        if (!$this->courseExists($course->getCourseId()))
        {
            $courseId = $this->insertCourse($course);
            $this->saveCourseModules($courseId, $course->getModules(), $course->getRequiredModuleIds());
            return $courseId;
        }
        else
        {
            throw new DuplicateSaveException("Курс " . $course->getCourseId() . " уже был сохранен.");
        }
    }

    public function saveEnrollment(string $enrollmentId, string $courseId): string
    {
        if ($this->enrollmentExists($enrollmentId))
        {
            throw new DuplicateSaveException("Назначение на курс " . $enrollmentId . " уже было сохранено.");
        }

        if (!$this->courseExists($courseId))
        {
            throw new CourseNotFoundException("Указанного курса не существует.");
        }

        return $this->insertEnrollment($enrollmentId, $courseId);
    }

    public function saveMaterialStatus(string $enrollmentId, string $moduleId, int $progress, int $duration): CourseModuleStatus
    {
        if (!$this->enrollmentExists($enrollmentId))
        {
            throw new CourseNotFoundException("Указанного назначения не существует.");
        }

        if (!$this->materialExists($moduleId))
        {
            throw new CourseNotFoundException("Указанного материала не существует.");
        }

        $this->updateCourseModuleStatus($enrollmentId, $moduleId, $progress, $duration);
        $this->updateCourseStatus($enrollmentId, $duration);

        return new CourseModuleStatus (
            $enrollmentId,
            $moduleId,
            $progress,
            $this->getModuleStatusDuration($enrollmentId, $moduleId)
        );
    }

    public function getCourseStatusData(string $enrollmentId): CourseStatusData
    {
        $query = <<<SQL
            SELECT enrollment_id, progress, duration
            FROM course_status
            WHERE enrollment_id = :enrollmentId
            SQL;

        $params = [
            ':enrollmentId' => $enrollmentId,
        ];

        $stmt = $this->connection->execute($query, $params);
        $courseData = $stmt->fetch();
        $modules = $this->getModuleStatusData($enrollmentId);

        return $this->hydrateCourseStatusData($courseData, $modules);
    }

    private function saveCourseModules(string $courseId, array $modules, array $requiredModules): void
    {
        if (count($modules) === 0)
        {
            return;
        }

        foreach ($modules as $module)
        {
            if (!$this->materialExists($module))
            {
                $this->insertModule($courseId, $module, $requiredModules);
            }
            else
            {
                throw new DuplicateSaveException("Материал " . $module . " уже был сохранен.");
            }
        }
    }

    private function insertCourse(Course $course): string
    {
        $query = <<<SQL
            INSERT INTO course (course_id)
            VALUES (:courseId)
            SQL;

        $params = [
            ':courseId' => $course->getCourseId()
        ];

        $this->connection->execute($query, $params);

        return $course->getCourseId();
    }
//TODO multiinsert
    private function insertModule(string $courseId, string $moduleId, array $requiredModules): void
    {
        $query = <<<SQL
            INSERT INTO course_material (module_id, course_id, is_required)
            VALUES (:moduleId, :courseId, :isRequired)
            SQL;

        $params = [
            ':moduleId' => $moduleId,
            ':courseId' => $courseId,
            ':isRequired' => (int)in_array($moduleId, $requiredModules)
        ];

        $this->connection->execute($query, $params);
    }

    private function insertEnrollment(string $enrollmentId, string $courseId): string
    {
        $query = <<<SQL
            INSERT INTO course_enrollment (enrollment_id, course_id)
            VALUES (:enrollmentId, :courseId)
            SQL;

        $params = [
            ':courseId' => $courseId,
            ':enrollmentId' => $enrollmentId
        ];

        $this->connection->execute($query, $params);

        return $enrollmentId;
    }

    private function updateCourseStatus(string $enrollmentId, int $duration): void
    {
        $query = <<<SQL
            INSERT INTO course_status (enrollment_id)
            VALUES (:enrollmentId)
            ON DUPLICATE KEY UPDATE
                duration = duration + :duration,
                progress = (
                    SELECT IF(COUNT(*) > 0, SUM(IFNULL(cms.progress, 0)) / COUNT(*), 100)
                    FROM course_enrollment ce
                    LEFT JOIN course_material cm ON cm.course_id = ce.course_id
                    LEFT JOIN course_module_status cms ON cms.module_id = cm.module_id
                    WHERE ce.enrollment_id = :enrollmentId AND cm.is_required = 1
                )
            SQL;

        $params = [
            ':enrollmentId' => $enrollmentId,
            ':duration' => $duration
        ];

        $this->connection->execute($query, $params);
    }

    private function updateCourseModuleStatus(string $enrollmentId, string $moduleId, int $progress, int $duration): void
    {
        $query = <<<SQL
            INSERT INTO course_module_status (enrollment_id, module_id)
            VALUES (:enrollmentId, :moduleId)
            ON DUPLICATE KEY UPDATE
                duration = duration + :duration,
                progress = :progress
            SQL;

        $params = [
            ':enrollmentId' => $enrollmentId,
            ':moduleId' => $moduleId,
            ':progress' => $progress,
            ':duration' => $duration
        ];

        $this->connection->execute($query, $params);
    }

    private function getModuleStatusData(string $enrollmentId): array
    {
        $query = <<<SQL
            SELECT
                cm.module_id,
                IFNULL(cms.progress, 0) progress
            FROM
                course_enrollment ce
                JOIN course_material cm ON cm.course_id = ce.course_id
                LEFT JOIN course_module_status cms ON (
            	    cms.module_id = cm.module_id 
            	    AND cms.enrollment_id = ce.enrollment_id
                )
            WHERE
                ce.enrollment_id = :enrollmentId
            	AND cm.is_required = 1
            SQL;

        $params = [
            ':enrollmentId' => $enrollmentId,
        ];

        $stmt = $this->connection->execute($query, $params);

        return array_map(
            fn($row) => $this->hydrateModuleStatusData($row),
            $stmt->fetchAll(\PDO::FETCH_ASSOC)
        );
    }

    private function getModuleStatusDuration(string $enrollmentId, string $moduleId): int
    {
        $query = <<<SQL
            SELECT duration
            FROM course_module_status
            WHERE enrollment_id = :enrollmentId
                AND module_id = :moduleId
            SQL;

        $params = [
            ':enrollmentId' => $enrollmentId,
            ':moduleId' => $moduleId
        ];

        $stmt = $this->connection->execute($query, $params);
        $data = $stmt->fetch();

        return (int)$data['duration'];
    }

    private function hydrateCourseStatusData(array $courseData, array $modules): CourseStatusData
    {
        try
        {
            return new CourseStatusData (
                (string)$courseData['enrollment_id'],
                $modules,
                (int)$courseData['progress'],
                (int)$courseData['duration'],
            );
        }
        catch (\Exception $e)
        {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function hydrateModuleStatusData(array $row): ModuleStatusData
    {
        try
        {
            return new ModuleStatusData (
                (string)$row['module_id'],
                (int)$row['progress']
            );
        }
        catch (\Exception $e)
        {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function courseExists(string $courseId): bool
    {
        $query = <<<SQL
            SELECT IF(course_id IS NOT NULL, TRUE, FALSE) isDuplicated
            FROM course
            WHERE course_id = :courseId
            SQL;

        $params = [
            ':courseId' => $courseId,
        ];

        $stmt = $this->connection->execute($query, $params);
        $data = $stmt->fetch();

        return (bool)$data['isDuplicated'];
    }

    private function materialExists(string $moduleId): bool
    {
        $query = <<<SQL
            SELECT IF(module_id IS NOT NULL, TRUE, FALSE) isDuplicated
            FROM course_material
            WHERE module_id = :moduleId
            SQL;

        $params = [
            ':moduleId' => $moduleId,
        ];

        $stmt = $this->connection->execute($query, $params);
        $data = $stmt->fetch();

        return (bool)$data['isDuplicated'];
    }

    private function enrollmentExists(string $enrollmentId): bool
    {
        $query = <<<SQL
            SELECT IF(enrollment_id IS NOT NULL, TRUE, FALSE) isDuplicated
            FROM course_enrollment
            WHERE enrollment_id = :enrollmentId
            SQL;

        $params = [
            ':enrollmentId' => $enrollmentId,
        ];

        $stmt = $this->connection->execute($query, $params);
        $data = $stmt->fetch();

        return (bool)$data['isDuplicated'];
    }
}