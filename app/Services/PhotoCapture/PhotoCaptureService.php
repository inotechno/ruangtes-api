<?php

namespace App\Services\PhotoCapture;

use App\Models\TestSession;
use App\Models\TestSessionPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhotoCaptureService
{
    /**
     * Capture and store photo for test session.
     */
    public function capturePhoto(TestSession $session, $photoFile): TestSessionPhoto
    {
        return DB::transaction(function () use ($session, $photoFile) {
            // Store photo
            $path = $photoFile->store('test-sessions/photos', 'public');

            // Create photo record
            $photo = TestSessionPhoto::create([
                'test_session_id' => $session->id,
                'photo_path' => $path,
                'captured_at' => now(),
                'metadata' => [
                    'file_size' => $photoFile->getSize(),
                    'mime_type' => $photoFile->getMimeType(),
                    'captured_at' => now()->toIso8601String(),
                ],
            ]);

            return $photo;
        });
    }

    /**
     * Get photos for session.
     */
    public function getPhotos(TestSession $session): \Illuminate\Database\Eloquent\Collection
    {
        return TestSessionPhoto::where('test_session_id', $session->id)
            ->orderBy('captured_at', 'asc')
            ->get();
    }

    /**
     * Get photo URL.
     */
    public function getPhotoUrl(TestSessionPhoto $photo): string
    {
        return asset('storage/'.$photo->photo_path);
    }
}
