<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUGGING SITE RELATIONSHIP ===\n\n";

$shift = \App\Models\Shift::find(4);
echo "Shift ID: 4\n";
echo "Site ID from shift: " . ($shift->site_id ?? 'NULL') . "\n";

// Test direct site query
$site = \App\Models\Site::find(6);
echo "Direct site query result: " . ($site ? 'Found' : 'Not found') . "\n";

if ($site) {
    echo "Site name: {$site->name}\n";
    echo "Site address: {$site->address}\n";
}

// Test relationship loading
$shiftWithSite = \App\Models\Shift::with('site')->find(4);
echo "Shift with site relationship:\n";
echo "Site object: " . ($shiftWithSite->site ? 'Loaded' : 'NULL') . "\n";

if ($shiftWithSite->site) {
    echo "Site name from relationship: {$shiftWithSite->site->name}\n";
    echo "Site address from relationship: {$shiftWithSite->site->address}\n";
}

// Test the exact API query
$user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
if ($user) {
    $currentShift = \App\Models\Shift::where('assigned_to', $user->id)
        ->where('status', 'clocked-in')
        ->with(['site', 'checkins' => function($query) {
            return $query->orderBy('checked_in_at', 'desc')->take(5);
        }])
        ->first();
    
    echo "API query result:\n";
    echo "Shift found: " . ($currentShift ? 'Yes' : 'No') . "\n";
    echo "Site loaded: " . ($currentShift->site ? 'Yes' : 'No') . "\n";
    
    if ($currentShift && $currentShift->site) {
        echo "Site name from API: {$currentShift->site->name}\n";
        echo "Site address type: " . gettype($currentShift->site->address) . "\n";
        echo "Site address value: " . $currentShift->site->address . "\n";
    }
}
