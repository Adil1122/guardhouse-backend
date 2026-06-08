<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = [
    'site_id' => 12,
    'name' => 'Gate 1',
    'geofence' => [
        'place_id' => '',
        'lat' => 0.0,
        'lon' => 0.0,
        'check_in_distance' => 100
    ],
    'qr_code_token' => 'abc'
];

$rules = [
    'site_id' => 'required|exists:sites,id',
    'name' => 'required|string|max:50',
    'geofence' => ['required', new App\Rules\GeofenceRule()],
    'qr_code_token' => 'required|string|max:255',
];

$validator = Illuminate\Support\Facades\Validator::make($data, $rules);

if ($validator->fails()) {
    echo "FAIL: " . json_encode($validator->errors());
} else {
    echo "DONE\n";
}
