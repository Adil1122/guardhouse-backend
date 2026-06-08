<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteDocument;
use App\Http\Resources\SiteDocumentResource;
use Illuminate\Support\Facades\Storage;

class SiteDocumentService
{
    public function createDocument($data, $files = [])
    {
        $site = Site::where('id', $data['site_id'])->first();
        if (!$site) {
            return ['error' => 'Site not found'];
        }

        $filenames = [];
        if ($files) {
            foreach ($files as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('site-documents', $filename, 'public');
                $filenames[] = $filename;
            }
        }

        $record = SiteDocument::create([
            'site_id' => $site->id,
            'name' => $data['name'],
            'offsite_visibility' => $data['offsite_visibility'],
            'files' => $filenames
        ]);

        return new SiteDocumentResource($record);
    }
    
    public function uploadDocumentFiles($siteId, $documentId, $files = [])
    {
        $site = Site::find($siteId);
        if (!$site) {
            return ['error' => 'Site not found'];
        }
        
        $siteDocument = SiteDocument::find($documentId);
        if (!$siteDocument) {
            return ['error' => 'Site document not found'];
        }

        $filenames = [];
        if ($files) {
            foreach ($files as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('site-documents', $filename, 'public');
                $filenames[] = $filename;
            }
        }

        $existingFiles = $siteDocument->files ?? [];
        $siteDocument->files = array_merge($existingFiles, $filenames);
        $siteDocument->save();

        return new SiteDocumentResource($siteDocument);
    }
    
    public function deleteDocumentFile($siteId, $documentId, $fileName)
    {
        $site = Site::find($siteId);
        if (!$site) {
            return ['error' => 'Site not found'];
        }
        
        $siteDocument = SiteDocument::find($documentId);
        if (!$siteDocument) {
            return ['error' => 'Site document not found'];
        }

        if (in_array($fileName, $siteDocument->files ?? [])) {
            Storage::disk('public')->delete('site-documents/' . $fileName);
        } else {
            return ['error' => 'File not found'];
        }

        $siteDocument->files = array_values(array_filter($siteDocument->files ?? [], function ($file) use ($fileName) {
            return $file !== $fileName;
        }));
        
        $siteDocument->save();
        return new SiteDocumentResource($siteDocument);
    }
}
