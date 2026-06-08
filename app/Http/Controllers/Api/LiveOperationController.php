<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LiveOperationService;

class LiveOperationController extends Controller
{   
    protected $service;
    
    public function __construct(LiveOperationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $result = $this->service->list();
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 404);
        }

        return response()->json($result, 200);
    }   
}
