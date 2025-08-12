<?php

use App\Http\Controllers\Api\BookingController;

Route::get('/available-dates', [BookingController::class, 'availableDates']);
Route::get('/available-times', [BookingController::class, 'availableTimes']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/available-capacities', [BookingController::class, 'availableCapacities']);