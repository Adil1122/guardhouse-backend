<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Services\ShiftNoteService;
use App\Models\ShiftNote;

class ShiftNoteController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(ShiftNoteService $service)
    {
        parent::__construct(new ShiftNote());
        $this->service = $service;
    }

    public function all(Request $request)
    {
        $notes = ShiftNote::with('shift')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($this->resource::collection($notes), 200);
    }
}
