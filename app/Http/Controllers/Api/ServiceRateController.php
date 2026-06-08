<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRate;
use Illuminate\Http\Request;

class ServiceRateController extends Controller
{
    /**
     * Get all rates for a service group
     * GET /api/service-groups/{groupId}/rates
     */
    public function index(Request $request, $groupId)
    {
        $rates = ServiceRate::where('service_group_id', $groupId)->get();
        return response()->json($rates, 200);
    }

    /**
     * Create a new rate for a service group
     * POST /api/service-groups/{groupId}/rates
     */
    public function store(Request $request, $groupId)
    {
        $validated = $request->validate([
            'days' => 'required|array|min:1',
            'days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'rate' => 'required|numeric|between:0,99999999.99',
            'from_time' => 'required|date_format:H:i:s',
            'to_time' => 'required|date_format:H:i:s',
        ]);

        $rate = ServiceRate::create([
            'service_group_id' => $groupId,
            'days' => $validated['days'],
            'rate' => $validated['rate'],
            'from_time' => $validated['from_time'],
            'to_time' => $validated['to_time'],
        ]);

        return response()->json($rate, 201);
    }

    /**
     * Get a specific rate
     * GET /api/service-rates/{rateId}
     */
    public function show($rateId)
    {
        $rate = ServiceRate::find($rateId);
        
        if (!$rate) {
            return response()->json(['message' => 'Rate not found'], 404);
        }

        return response()->json($rate, 200);
    }

    /**
     * Update a specific rate
     * PATCH /api/service-rates/{rateId}
     */
    public function update(Request $request, $rateId)
    {
        $rate = ServiceRate::find($rateId);
        
        if (!$rate) {
            return response()->json(['message' => 'Rate not found'], 404);
        }

        $validated = $request->validate([
            'days' => 'nullable|array|min:1',
            'days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'rate' => 'nullable|numeric|between:0,99999999.99',
            'from_time' => 'nullable|date_format:H:i:s',
            'to_time' => 'nullable|date_format:H:i:s',
        ]);

        $rate->update($validated);

        return response()->json($rate, 200);
    }

    /**
     * Delete a specific rate
     * DELETE /api/service-rates/{rateId}
     */
    public function destroy($rateId)
    {
        $rate = ServiceRate::find($rateId);
        
        if (!$rate) {
            return response()->json(['message' => 'Rate not found'], 404);
        }

        $rate->delete();

        return response()->json(['message' => 'Rate deleted successfully'], 200);
    }
}
