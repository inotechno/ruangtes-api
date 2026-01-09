<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        $dbStatus = 'disconnected';
        $redisStatus = 'disconnected';
        
        // Check database
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'disconnected: ' . $e->getMessage();
        }
        
        // Check Redis
        try {
            Redis::ping();
            $redisStatus = 'connected';
        } catch (\Exception $e) {
            $redisStatus = 'disconnected: ' . $e->getMessage();
        }
        
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbStatus,
                'redis' => $redisStatus,
            ],
            'version' => app()->version(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }
});
