<?php

namespace App\Http\Controllers\Api;

use App\Enums\PdfWatermarkStatus;
use App\Http\Requests\PdfWatermarkRequest;
use App\Jobs\InsertWatermarkIntoPdf;
use App\Models\PdfWatermark;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PdfWatermarkController extends Controller
{
    public function __invoke(PdfWatermarkRequest $request): JsonResponse
    {
        $pdfWatermark = PdfWatermark::create(
            $request->safe()
                    ->merge([
                                'status'  => PdfWatermarkStatus::PENDING,
                                'user_id' => $request->user()->id,
                            ])
                    ->toArray()
        );

        InsertWatermarkIntoPdf::dispatch($pdfWatermark)->afterCommit();

        return response()->json($pdfWatermark->toArray());
    }
}
