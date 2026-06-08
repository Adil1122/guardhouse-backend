<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftTimeclockLog;
use App\Services\ShiftTimeclockLogService;
use App\Http\Resources\ShiftTimeclockLogResource;

class ShiftTimeClockLogController extends Controller
{
    protected $model;
    protected $service;
    protected $resource;

    public function __construct(ShiftTimeclockLogService $service)
    {
        $this->model = new ShiftTimeclockLog();
        $this->resource = ShiftTimeclockLogResource::class;
        $this->service = $service;
    }

    public function store(Request $request, $shiftId)
    {
        $validatedData = $request->validate($this->model->getValidationRules('store'));
        
        $result = $this->service->create($validatedData);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json($result, 200);
    }
}
