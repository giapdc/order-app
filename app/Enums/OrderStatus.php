<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OrderStatus extends Enum
{
    const NEW = 'new';
    const EXPORTED = 'exported';
    const EXPORT_FAILED = 'export_failed';
    const PROCESSED = 'processed';
    const PENDING = 'pending';
    const ERROR = 'error';
    const API_ERROR = 'api_error';
    const API_FAILURE = 'api_failure';
    const COMPLETED = 'completed';
    const IN_PROGRESS = 'in_progress';
    const UNKNOWN_TYPE = 'unknown_type';
    const DB_ERROR = 'db_error';
}
