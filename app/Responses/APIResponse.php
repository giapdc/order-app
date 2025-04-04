<?php

namespace App\Responses;

use App\Models\Order;

class APIResponse
{
    public $status;
    public $data;

    public function __construct($status, Order $data)
    {
        $this->status = $status;
        $this->data = $data;
    }
}