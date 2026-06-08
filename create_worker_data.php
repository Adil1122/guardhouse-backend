<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create worker test data
$site = new \App\Models\Site();
$site->name = 'Main Office Building';
$site->address = '123 Business Ave, Suite 100, New York, NY 10001';
$site->type = 'static';
$site->customer_profile_id = null; // Make nullable for now
$site->geofence = json_encode([
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'radius' => 500,
    'check_in_distance' => 500
]);
$site->save();
echo "Site created with ID: " . $site->id . "\n";

// Get or create worker user
$worker = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
if (!$worker) {
    $worker = new \App\Models\User();
    $worker->first_name = 'Worker';
    $worker->last_name = 'One';
    $worker->email = 'worker_one@gmail.com';
    $worker->password = bcrypt('password123');
    $worker->role = 'security-officer';
    $worker->email_verified_at = now();
    $worker->save();
    echo "Worker created with ID: " . $worker->id . "\n";
} else {
    echo "Found existing worker with ID: " . $worker->id . "\n";
}

// Create current shift
$now = now();
$shiftStart = $now->copy()->subHour(1);
$shiftEnd = $now->copy()->addHours(3);

$shift = new \App\Models\Shift();
$shift->assigned_to = $worker->id;
$shift->start_date = $now->toDateString();
$shift->end_date = $now->toDateString();
$shift->start_time = $shiftStart->toTimeString();
$shift->end_time = $shiftEnd->toTimeString();
$shift->status = 'clocked-in';
$shift->created_by = 1;
$shift->save();
echo "Current shift created with ID: " . $shift->id . "\n";

// Create historical shifts
for ($i = 1; $i <= 5; $i++) {
    $pastDate = $now->copy()->subDays($i);
    $pastStart = $pastDate->copy()->setTime(9, 0);
    $pastEnd = $pastDate->copy()->setTime(17, 0);

    $historicalShift = new \App\Models\Shift();
    $historicalShift->assigned_to = $worker->id;
    $historicalShift->start_date = $pastDate->toDateString();
    $historicalShift->end_date = $pastDate->toDateString();
    $historicalShift->start_time = $pastStart->toTimeString();
    $historicalShift->end_time = $pastEnd->toTimeString();
    $historicalShift->status = 'clocked-out';
    $historicalShift->created_by = 1;
    $historicalShift->created_at = $pastDate;
    $historicalShift->updated_at = $pastDate;
    $historicalShift->save();
    echo "Historical shift $i created with ID: " . $historicalShift->id . "\n";
}

echo "Worker test data creation completed!\n";
