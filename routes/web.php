<?php

// Route::statamic('example', 'example-view', [
//    'title' => 'Example'
// ]);
use Illuminate\Support\Facades\Route;

Route::get('/import/blog-entries', [\App\Http\Controllers\ImportController::class, 'importBlogEntries']);
Route::get('/import/json-entries', [\App\Http\Controllers\ImportController::class, 'importJSONEntries']);

