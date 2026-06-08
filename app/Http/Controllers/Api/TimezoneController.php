<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimezoneController extends Controller
{
    public function index()
    {
        $timezones = \DateTimeZone::listIdentifiers();

        return response()->json([
            'success' => true,
            'data' => $timezones
        ]);
    }
}
