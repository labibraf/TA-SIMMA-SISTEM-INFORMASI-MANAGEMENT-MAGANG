<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Create test request
$request = Request::create('/test-session', 'GET');
$response = $kernel->handle($request);

// Test session write
echo "=== TEST SESSION ===\n\n";

try {
    // Test write session
    session()->put('test_key', 'test_value_' . time());
    echo "✓ Session write: SUCCESS\n";
    
    // Test read session
    $value = session()->get('test_key');
    echo "✓ Session read: SUCCESS (value: {$value})\n";
    
    // Test session driver
    echo "✓ Session driver: " . config('session.driver') . "\n";
    
    // Test session table (if database driver)
    if (config('session.driver') === 'database') {
        $count = DB::table('sessions')->count();
        echo "✓ Sessions table: {$count} records\n";
    }
    
    // Test session ID
    echo "✓ Session ID: " . session()->getId() . "\n";
    
    echo "\n=== SESSION OK ===\n";
    
} catch (\Exception $e) {
    echo "✗ SESSION ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);
