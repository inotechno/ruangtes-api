<?php

namespace App\Http\Controllers\PhotoCapture;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhotoCapture\CapturePhotoRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\TestSession;
use App\Services\PhotoCapture\PhotoCaptureService;
use Illuminate\Http\JsonResponse;

class PhotoCaptureController extends Controller
{
    public function __construct(
        protected PhotoCaptureService $photoService
    ) {}

    /**
     * Capture and store photo.
     */
    public function capture(CapturePhotoRequest $request, string $sessionToken): JsonResponse
    {
        try {
            $session = TestSession::where('session_token', $sessionToken)
                ->where('status', 'in_progress')
                ->firstOrFail();

            $photo = $this->photoService->capturePhoto($session, $request->file('photo'));

            return (new SuccessResource([
                'photo' => [
                    'id' => $photo->id,
                    'url' => $this->photoService->getPhotoUrl($photo),
                    'captured_at' => $photo->captured_at->toIso8601String(),
                ],
            ], 'Photo captured successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get photos for session.
     */
    public function getPhotos(string $sessionToken): JsonResponse
    {
        try {
            $session = TestSession::where('session_token', $sessionToken)->firstOrFail();
            $photos = $this->photoService->getPhotos($session);

            $photosData = $photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $this->photoService->getPhotoUrl($photo),
                    'captured_at' => $photo->captured_at->toIso8601String(),
                ];
            });

            return (new SuccessResource($photosData, 'Photos retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->response();
        }
    }
}
