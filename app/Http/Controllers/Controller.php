<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function base64BrandImage($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return base64_encode(Storage::disk('public')->get($path));
        }

        return base64_encode(File::get(public_path('media/logos/mardysa.png')));
    }
}
