<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Welcome! The route is working.';
});

Route::get('/fix-symlink', function () {
    $target = '../storage/app/public';
    $link = public_path('storage');
    
    if (file_exists($link) || is_link($link)) {
        if (is_link($link)) {
            unlink($link);
        } else {
            $backupName = public_path('storage_backup_' . time());
            rename($link, $backupName);
        }
    }
    
    chdir(public_path());
    symlink($target, 'storage');
    
});

Route::get('/fix-permissions', function () {
    $storageDir = public_path('storage');
    if (!is_dir($storageDir)) {
        return "storage folder not found in public directory.";
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($storageDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    chmod($storageDir, 0755);

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            chmod($item->getRealPath(), 0755);
        } else {
            chmod($item->getRealPath(), 0644);
        }
    }

    return "Permissions fixed! Directories set to 755, files set to 644. Check your image link now.";
});