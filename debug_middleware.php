<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUGGING MIDDLEWARE ===\n\n";

// Create a mock request to test middleware
$user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
echo "User: ID {$user->id}, Role: {$user->role}\n";

// Simulate different route requests
$routes = [
    'api/worker/current-shift',
    'api/worker/duty-history',
    'worker/current-shift',
    'worker/duty-history'
];

foreach ($routes as $route) {
    echo "\nTesting route: $route\n";
    
    // Create mock request
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    // Mock route
    $mockRoute = new class {
        public function uri() {
            return 'worker/current-shift';
        }
        public function getName() {
            return null; // Routes don't have names
        }
    };
    $request->setRouteResolver(function() use ($mockRoute) {
        return $mockRoute;
    });
    
    // Test middleware logic
    $isAllowed = false;
    if ($user->role === 'admin') {
        $isAllowed = true;
    } else if ($user->role === 'supervisor' || $user->role === 'security-officer') {
        $routePath = $request->route()->uri();
        echo "Route path: '$routePath'\n";
        if ($routePath && str_contains($routePath, 'worker')) {
            $isAllowed = true;
            echo "✅ Worker route allowed\n";
        } else {
            echo "❌ Worker route blocked\n";
        }
    }
    
    echo "Final result: " . ($isAllowed ? 'ALLOWED' : 'BLOCKED') . "\n";
}
