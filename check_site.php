<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check if site ID 9 exists
$site = \App\Models\Site::find(9);
if ($site) {
    echo "Site found with ID: " . $site->id . "\n";
    echo "Site name: " . $site->name . "\n";
    echo "Site address: " . $site->address . "\n";
    echo "Site geofence: " . $site->geofence . "\n";
} else {
    echo "Site ID 9 not found\n";
}
