<?php

namespace App\Http\Controllers\Api;

use FilippoToso\PdfWatermarker\Support\Pdf;
use FilippoToso\PdfWatermarker\Support\Position;
use FilippoToso\PdfWatermarker\Watermarks\ImageWatermark;
use FilippoToso\PdfWatermarker\PdfWatermarker;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

class PdfWatermarkController extends Controller
{
    public function __invoke(Request $request): string
    {
        $request->validate([
                               'pdf_url'              => 'required|string|url',
                               'watermark_url'        => 'required|string|url',
                               'watermark_position'   => 'nullable|string|in:top_left,top_right,top_center,bottom_left,bottom_right,bottom_center,middle_left,middle_right,middle_center',
                               'watermark_x'          => 'nullable|numeric',
                               'watermark_y'          => 'nullable|numeric',
                               'watermark_background' => 'nullable|boolean',
                           ]);

        try {
            $originalPdfPath = $this->storeFileFromUrl($request->input('pdf_url'), 'pdf');
            $pdfPath         = Storage::disk('local')->path('') . 'temp/' . Str::uuid()->getHex() . 'N.pdf';
            $gsPath          = config('app.gs_path');
            $process         = Process::timeout(10)
                                      ->start("{$gsPath} -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$pdfPath} {$originalPdfPath}");

            while ($process->running()) {
                Sleep::sleep(1);
            }

            $process->wait();
            $errorOutput = trim($process->errorOutput());

            abort_if(!empty($errorOutput), 500, $errorOutput);

            $pdf = new Pdf($pdfPath);

            $watermark = new ImageWatermark($this->storeFileFromUrl($request->input('watermark_url', '.png')));

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

    private function storeFileFromUrl(string $fileUrl, ?string $ext = null): string
    {
        $response = Http::timeout(120)->get($fileUrl);

        abort_if($response->failed(), 500, "Cannot get file from url {$fileUrl}");

        $filePath = 'temp/' . Str::uuid()->getHex() . '.' . $ext;

        Storage::disk('local')->put($filePath, $response->body());

        return Storage::disk('local')->path($filePath);
    }
}
