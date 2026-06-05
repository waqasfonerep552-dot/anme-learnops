<?php

namespace App\Services;

use App\Jobs\FulfillPaidOrder;
use App\Models\Order;

class EnrollmentService
{
    public function queueFulfillment(Order $order): void
    {
        // Payment paid hote hi fulfilment queue mein daalte hain, taake user request slow na ho.
        FulfillPaidOrder::dispatch($order->id);
    }
}
