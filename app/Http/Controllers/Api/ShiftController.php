<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Services\ShiftService;

class ShiftController extends BaseController
{
    protected $service;
    protected $filterKey = null;

    public function __construct(ShiftService $service)
    {
        parent::__construct(new Shift());
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $query = Shift::with([
            'site',
            'site.customer',
            'site.customer.user',
            'assignedUser',
            'serviceGroup',
            'payGroup',
            'createdBy'
        ])->orderBy('id', 'desc');
        
        return response()->json(\App\Http\Resources\ShiftResource::collection($query->get()), 200);
    }
}
