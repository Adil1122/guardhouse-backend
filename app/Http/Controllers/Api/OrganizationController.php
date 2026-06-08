<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\OrganizationSetting;
use Illuminate\Http\Request;

class OrganizationController extends BaseController
{
    public function __construct()
    {
        parent::__construct(new OrganizationSetting());
    }

    public function settings(Request $request)
    {
        $validatedData = $request->validate($this->model->getValidationRules('update'));
        $record = OrganizationSetting::firstOrCreate(['id' => 1]);

        $record->update(array_merge($validatedData, [
            'updated_by' => auth()->id() ?? NULL
        ]));

        return response()->json(new $this->resource($record), 200);
    }
}
