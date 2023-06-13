<?php
declare(strict_types=1);

namespace App\Controller\Request;

use App\Model\Data\SaveCourseParams;
use App\Model\Data\SaveEnrollmentParams;
use App\Model\Data\SaveMaterialStatusParams;

class CourseApiRequestParser
{
    public static function parseSaveCourseParams(array $parameters): SaveCourseParams
    {
        return new SaveCourseParams(
            self::parseString($parameters, 'courseId'),
            self::parseStringArray($parameters, 'modules'),
            self::parseStringArray($parameters, 'requiredModules')
        );
    }

    public static function parseSaveEnrollmentParams(array $parameters): SaveEnrollmentParams
    {
        return new SaveEnrollmentParams(
            self::parseString($parameters, 'enrollmentId'),
            self::parseString($parameters, 'courseId')
        );
    }

    public static function parseSaveMaterialStatusParams(array $parameters): SaveMaterialStatusParams
    {
        return new SaveMaterialStatusParams(
            self::parseString($parameters, 'enrollmentId'),
            self::parseString($parameters, 'moduleId'),
            self::parseInteger($parameters, 'progress'),
            self::parseInteger($parameters, 'sessionDuration')
        );
    }

    public static function parseInteger(array $parameters, string $name): int
    {
        $value = $parameters[$name] ?? null;
        if (!self::isIntegerValue($value))
        {
            throw new RequestValidationException([$name => 'Invalid integer value']);
        }
        return (int)$value;
    }

    public static function parseFloat(array $parameters, string $name): float
    {
        $value = $parameters[$name] ?? null;
        if (!self::isFloatValue($value))
        {
            throw new RequestValidationException([$name => 'Invalid float value']);
        }
        return (float)$value;
    }


    public static function parseString(array $parameters, string $name, ?int $maxLength = null): string
    {
        $value = $parameters[$name] ?? null;
        if (!is_string($value))
        {
            throw new RequestValidationException([$name => 'Invalid string value']);
        }
        if ($maxLength !== null && mb_strlen($value) > $maxLength)
        {
            throw new RequestValidationException([$name => "String value too long (exceeds $maxLength characters)"]);
        }
        return $value;
    }

    public static function parseStringArray(array $parameters, string $name, ?int $maxLength = null): array
    {
        $values = self::parseArray($parameters, $name);
        foreach ($values as $index => $value)
        {
            if (!is_string($value))
            {
                throw new RequestValidationException([$name => "Invalid string value at index $index"]);
            }
            if ($maxLength !== null && mb_strlen($value) > $maxLength)
            {
                throw new RequestValidationException([$name => "String value too long (exceeds $maxLength characters) at index $index"]);
            }
        }
        return $values;
    }

    public static function parseArray(array $parameters, string $name): array
    {
        $values = $parameters[$name] ?? null;
        if (!is_array($values))
        {
            throw new RequestValidationException([$name => 'Not an array']);
        }
        return $values;
    }

    private static function isIntegerValue(mixed $value): bool
    {
        return is_numeric($value) && (is_int($value) || ctype_digit($value));
    }
}
