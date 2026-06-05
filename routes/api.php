<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/easypaisa/webhook', [PaymentController::class, 'webhook'])
    ->name('api.payments.easypaisa.webhook');
