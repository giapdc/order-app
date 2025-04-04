<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OrderThreshold extends Enum
{
    const LOW = 50;
    const MEDIUM = 100;
    const HIGH = 200;
}
