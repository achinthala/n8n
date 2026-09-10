<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

/**
 * Validation job status constants.
 */
class JobStatus
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    /**
     * @return string[]
     */
    public static function getAll(): array
    {
        return [
            self::PENDING,
            self::RUNNING,
            self::COMPLETED,
            self::FAILED,
        ];
    }
}
