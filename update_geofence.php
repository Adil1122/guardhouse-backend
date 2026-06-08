<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Update Usaid Home Office geofence to be near user's location (33.9935728, 72.9103407)
$site = App\Models\Site::where('name', 'Usaid Home Office')->first();

// Set geofence near user's current location with 100m radius
$newGeofence = [
    'place_id' => 'test_location',
    'lat' => '33.9935728',  // User's current latitude
    'lon' => '72.9103407',  // User's current longitude
    'check_in_distance' => 100  // 100 meters radius
];

$site->geofence = $newGeofence;
$site->save();

echo "Updated Usaid Home Office geofence:" . PHP_EOL;
echo "New Geofence: " . json_encode($site->geofence) . PHP_EOL;
echo "This will make the user be INSIDE the geofence when at location (33.9935728, 72.9103407)" . PHP_EOL;
