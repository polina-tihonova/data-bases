<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use App\Controller\CourseApiController;

require __DIR__ . '/../vendor/autoload.php';

$isProduction = getenv('APP_ENV') === 'prod';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(!$isProduction, true, true);

$app->post('/course/save', CourseApiController::class . ':saveCourse');
$app->delete('/course/delete', CourseApiController::class . ':deleteCourse');
$app->post('/course/enrollment/save', CourseApiController::class . ':saveEnrollment');
$app->post('/course/material_status/save', CourseApiController::class . ':saveMaterialStatus');
$app->post('/course/enrollment/status', CourseApiController::class . ':getCourseStatus');

$app->run();
