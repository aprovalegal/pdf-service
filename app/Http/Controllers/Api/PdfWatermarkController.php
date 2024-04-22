<?php

namespace App\Http\Controllers\Api;

use FilippoToso\PdfWatermarker\Support\Pdf;
use FilippoToso\PdfWatermarker\Support\Position;
use FilippoToso\PdfWatermarker\Watermarks\ImageWatermark;
use FilippoToso\PdfWatermarker\PdfWatermarker;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PdfWatermarkController extends Controller
{
    public function __invoke(Request $request): string
    {
        $request->validate([
                               'pdf_url'              => 'required|string|max:5120',
                               'watermark_url'        => 'required|string|max:5120',
                               'watermark_position'   => 'nullable|string|in:top_left,top_right,top_center,bottom_left,bottom_right,bottom_center,middle_left,middle_right,middle_center',
                               'watermark_x'          => 'nullable|numeric',
                               'watermark_y'          => 'nullable|numeric',
                               'watermark_background' => 'nullable|boolean',
                           ]);

        try {
            $pdf = new Pdf($this->storeFileFromUrl($request->input('pdf_url')));

            // The image must have a 96 DPI resolution.
            $watermark = new ImageWatermark($this->storeFileFromUrl($request->input('watermark_url')));

            $watermarker = new PDFWatermarker($pdf, $watermark);

            if (!empty($request->input('watermark_position'))) {
                $constant = Str::upper($request->input('watermark_position'));

                $position = new Position(
                    Position::{$constant},
                    $request->float('watermark_x'),
                    $request->float('watermark_y')
                );

                $watermarker->setPosition($position);

                if ($request->boolean('watermark_background')) {
                    $watermarker->setAsBackground();
                }
            }

            $pdfPath = 'pdf/' . Str::uuid()->getHex() . '.pdf';

            $watermarker->save($pdfPath);
        } catch (\Throwable $th) {
            abort(500, $th->getMessage());
        }

        return asset($pdfPath);
    }

    /**
     * @throws RequestException
     */
    private function storeFileFromUrl(string $fileUrl): string
    {
        $response = Http::get($fileUrl)
                        ->throw();

        abort_if($response->failed(), 500, "Cannot get file from url {$fileUrl}");

        $filePath = 'temp/' . Str::uuid()->getHex() . '.pdf';

        Storage::disk('local')->put($filePath, $response->body());

        return Storage::disk('local')->path($filePath);
    }
}
