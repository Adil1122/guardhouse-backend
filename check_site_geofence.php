<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$site = App\Models\Site::where('name', 'Usaid Home Office')->first();
echo "Site ID: " . $site->id . PHP_EOL;
echo "Site Name: " . $site->name . PHP_EOL;
echo "Geofence: " . json_encode($site->geofence) . PHP_EOL;
echo "Check-in Distance: " . ($site->geofence['check_in_distance'] ?? 'Not set') . PHP_EOL;
