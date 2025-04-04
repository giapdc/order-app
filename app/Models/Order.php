<?php
//File Order.php
namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderPriority;
use App\Enums\OrderThreshold;

class Order
{
    public $id;
    public $type;
    public $amount;
    public $flag;
    public $status;
    public $priority;

    public function __construct($id, $type, $amount, $flag, $status = OrderStatus::NEW)
    {
        $this->id = $id;
        $this->type = $type;
        $this->amount = $amount;
        $this->flag = $flag;
        $this->status = $status;
        $this->priority = $amount > OrderThreshold::HIGH ? OrderPriority::HIGH : OrderPriority::LOW;
    }
}