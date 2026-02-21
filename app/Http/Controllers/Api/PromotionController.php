<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Promotion\CreatePromotionRequest;
use App\Http\Requests\Api\Promotion\PreviewPromotionRequest;
use App\Http\Requests\Api\Promotion\UpdatePromotionRequest;
use App\Http\Resources\PromotionPreviewResource;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PromotionController extends Controller
{
    protected string $modelName = 'Promotion';

    public function __construct(private readonly PromotionService $promotionService) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePromotionRequest $request): JsonResponse
    {
        try {
            $promotion = $this->promotionService->store($request->validated());

            return response()->json([
                'data' => PromotionResource::make($promotion)->resolve(),
                'message' => __('messages.create_success', ['name' => $this->modelName]),
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.create_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): JsonResponse
    {
        try {
            $promotion = $this->promotionService->update($promotion, $request->validated());

            return response()->json([
                'data' => PromotionResource::make($promotion)->resolve(),
                'message' => __('messages.update_success', ['name' => $this->modelName]),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.update_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        try {
            $this->promotionService->destroy($promotion);

            return response()->json([
                'message' => __('messages.delete_success', ['name' => $this->modelName]),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.delete_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Preview whether a promotion payload would win at a given time.
     */
    public function preview(PreviewPromotionRequest $request): JsonResponse
    {
        try {
            $preview = $this->promotionService->preview($request->validated());

            return response()->json([
                'data' => PromotionPreviewResource::make($preview)->resolve(),
                'message' => __('messages.preview_success', ['name' => $this->modelName]),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('messages.preview_error', ['name' => $this->modelName]),
        ], Response::HTTP_BAD_REQUEST);
    }
}
