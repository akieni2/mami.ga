<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'MAMI.GA API',
        'data' => [
            'version' => 'mvp-phase-1',
            'docs' => '/api',
        ],
    ]);
});
