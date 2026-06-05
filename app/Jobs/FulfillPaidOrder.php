<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\AcademyFulfillmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FulfillPaidOrder implements ShouldQueue
{
    use Queueable;

    // Temporary Moodle/API issue aaye to job 3 dafa retry karegi.
    public int $tries = 3;

    public function __construct(public int $orderId)
    {
    }

    public function handle(AcademyFulfillmentService $academy): void
    {
        $academy->fulfillQueuedOrder(Order::findOrFail($this->orderId));
    }
}
