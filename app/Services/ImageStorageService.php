<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageStorageService
{
    public function storePublic(UploadedFile $file, string $directory = 'properties'): string
    {
        return $this->store($file, $directory, 'public');
    }

    public function storePrivate(UploadedFile $file, string $directory): string
    {
        return $this->store($file, $directory, 'local');
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if (filled($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function store(UploadedFile $file, string $directory, string $disk): string
    {
        $name = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());

        return $file->storeAs($directory, $name, $disk);
    }
}
