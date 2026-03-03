<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| Rotas para verificação de saúde da aplicação
| Úteis para monitoring e load balancers
|
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => config('app.env'),
    ]);
});

Route::get('/health/detailed', function () {
    $checks = [
        'application' => true,
        'database' => false,
        'cache' => false,
    ];

    // Verificar banco de dados
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        $checks['database'] = false;
        $checks['database_error'] = $e->getMessage();
    }

    // Verificar cache
    try {
        $key = 'health_check_' . time();
        Cache::put($key, 'ok', 10);
        $checks['cache'] = Cache::get($key) === 'ok';
        Cache::forget($key);
    } catch (\Exception $e) {
        $checks['cache'] = false;
        $checks['cache_error'] = $e->getMessage();
    }

    $allOk = array_reduce($checks, function ($carry, $item) {
        return $carry && (is_bool($item) ? $item : true);
    }, true);

    return response()->json([
        'status' => $allOk ? 'ok' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
        'environment' => config('app.env'),
    ], $allOk ? 200 : 503);
});
