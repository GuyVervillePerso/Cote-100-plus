<?php

// Route::statamic('example', 'example-view', [
//    'title' => 'Example'
// ]);
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/import/blog-entries', [ImportController::class, 'importMonthlyEntries']);
Route::get('/import/json-entries', [ImportController::class, 'importJSONEntries']);

