<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\SiteDocument;
use App\Services\SiteDocumentService;
use Illuminate\Support\Facades\Validator;

class SiteDocumentController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(SiteDocumentService $service)
    {
        parent::__construct(new SiteDocument());
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $files = $request->file('files');
        if ($files && !is_array($files)) {
            $files = [$files];
            $data['files'] = $files;
        }
        
        $validator = Validator::make($data, [
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:50',
            'files' => 'required|array',
            'files.*' => 'file|max:5120|mimes:jpg,png,jpeg,docx,pdf',
            'offsite_visibility' => 'boolean'
        ]);

        $validated = $validator->validate();
        
        $result = $this->service->createDocument($validated, $files);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 201);
    }
    
    public function uploadFiles(Request $request)
    {
        $siteId = $request->route('siteId');
        $documentId = $request->route('id');

        $data = ['site_id' => $siteId];
        $files = $request->file('files');
        if ($files && !is_array($files)) {
            $files = [$files];
            $data['files'] = $files;
        }

        $validator = Validator::make($data, [
            'site_id' => 'required|exists:sites,id',
            'files' => 'required|array',
            'files.*' => 'file|max:5120|mimes:jpg,png,jpeg,docx,pdf'
        ]);

        $validated = $validator->validate();
        
        $result = $this->service->uploadDocumentFiles($validated['site_id'], $documentId, $files);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 201);
    }
    
    public function deleteFile(Request $request)
    {
        $documentId = $request->route('id');
        
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'file_name' => 'required|string'
        ]);

        $validated = $validator->validate();
        
        $result = $this->service->deleteDocumentFile($validated['site_id'], $documentId, $validated['file_name']);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 201);
    }
}
