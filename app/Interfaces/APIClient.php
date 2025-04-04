<?php

namespace App\Interfaces;

use App\Responses\APIResponse;

interface APIClient
{
    public function callAPI($orderId): APIResponse;
}