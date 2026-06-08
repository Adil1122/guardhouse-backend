<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CREATING TEST CHECK-IN ===\n\n";

// Get worker user and current shift
$user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
$currentShift = \App\Models\Shift::where('assigned_to', $user->id)
    ->where('status', 'clocked-in')
    ->with(['site'])
    ->first();

if (!$currentShift) {
    echo "ERROR: No current shift found\n";
    exit(1);
}

echo "Creating check-in for:\n";
echo "User ID: {$user->id}\n";
echo "Shift ID: {$currentShift->id}\n";
echo "Site ID: {$currentShift->site_id}\n";
echo "Site Name: {$currentShift->site->name}\n";

// Create a test check-in record
$checkin = new \App\Models\Checkin();
$checkin->shift_id = $currentShift->id;
$checkin->user_id = $user->id;
$checkin->latitude = 40.7128; // Same as site coordinates
$checkin->longitude = -74.0060;
$checkin->location_description = 'Test check-in at main entrance';
$checkin->notes = 'Automated test check-in for verification';
$checkin->type = 'regular';
$checkin->inside_geofence = true; // Within 500m radius
$checkin->distance_from_site = 50; // 50 meters from center
$checkin->checked_in_at = now();

try {
    $checkin->save();
    echo "SUCCESS: Check-in created with ID: {$checkin->id}\n";
    echo "Location: {$checkin->latitude}, {$checkin->longitude}\n";
    echo "Inside Geofence: " . ($checkin->inside_geofence ? 'Yes' : 'No') . "\n";
    echo "Distance: {$checkin->distance_from_site}m\n";
    echo "Time: {$checkin->checked_in_at}\n";
} catch (Exception $e) {
    echo "ERROR: Failed to create check-in: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION ===\n";
$allCheckins = \App\Models\Checkin::where('shift_id', $currentShift->id)->get();
echo "Total checkins for shift {$currentShift->id}: " . $allCheckins->count() . "\n";

foreach ($allCheckins as $ci) {
    echo "Check-in ID: {$ci->id}, Time: {$ci->checked_in_at}, Inside: " . ($ci->inside_geofence ? 'Yes' : 'No') . "\n";
}
