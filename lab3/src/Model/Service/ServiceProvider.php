<?php
declare(strict_types=1);

namespace App\Model\Service;

use App\Common\Database\ConnectionProvider;
use App\Common\Database\Synchronization;
use App\Database\CourseQueryService;

final class ServiceProvider
{
    private ?CourseService $courseService = null;
    private ?CourseQueryService $courseQueryService = null;

    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null)
        {
            $instance = new self();
        }
        return $instance;
    }

    public function getCourseService(): CourseService
    {
        if ($this->courseService === null)
        {
            $synchronization = new Synchronization(ConnectionProvider::getConnection());
            $this->courseService = new CourseService($synchronization, $this->getCourseQueryService());
        }
        return $this->courseService;
    }

    public function getCourseQueryService(): CourseQueryService
    {
        if ($this->courseQueryService === null)
        {
            $this->courseQueryService = new CourseQueryService(ConnectionProvider::getConnection());
        }
        return $this->courseQueryService;
    }
}
