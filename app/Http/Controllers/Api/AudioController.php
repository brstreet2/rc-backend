<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Audio\IndexAudioRequest;
use App\Http\Requests\Api\Audio\ScheduleAudioRequest;
use App\Http\Resources\AudioActiveResource;
use App\Http\Resources\AudioResource;
use App\Http\Resources\AudioScheduleResource;
use App\Models\Audio;
use App\Services\AudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AudioController extends Controller
{
    protected string $modelName = 'Audio';

    public function __construct(private readonly AudioService $audioService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexAudioRequest $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            return AudioResource::collection(
                $this->audioService->index($request->pageNumber(), $request->perPage())
            );
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.fetch_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Display a listing of the resource with active promotions.
     */
    public function active(IndexAudioRequest $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            return AudioActiveResource::collection(
                $this->audioService->active($request->pageNumber(), $request->perPage(), $request->at())
            )->additional([
                'message' => __('messages.fetch_success', ['name' => $this->modelName]),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.fetch_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Display winner timeline segments for a single audio.
     */
    public function schedule(ScheduleAudioRequest $request, Audio $audio): JsonResponse|AudioScheduleResource
    {
        try {
            $schedule = $this->audioService->schedule(
                $audio,
                $request->input('from'),
                $request->input('to')
            );

            return AudioScheduleResource::make($schedule)->additional([
                'message' => __('messages.fetch_success', ['name' => $this->modelName]),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.fetch_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }
}
