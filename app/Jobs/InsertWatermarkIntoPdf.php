<?php

namespace App\Jobs;

use App\Enums\PdfWatermarkStatus;
use App\Models\PdfWatermark;
use FilippoToso\PdfWatermarker\PdfWatermarker;
use FilippoToso\PdfWatermarker\Support\Pdf;
use FilippoToso\PdfWatermarker\Support\Position;
use FilippoToso\PdfWatermarker\Watermarks\ImageWatermark;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

class InsertWatermarkIntoPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly PdfWatermark $pdfWatermark)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $originalPdfPath = $this->storeFileFromUrl($this->pdfWatermark->pdf_url, '.pdf');
            $pdfPath         = Storage::disk('local')->path('') . $this->getTempPath($this->randomFileName('.pdf'));
            $gsPath          = config('app.gs_path');
            $process         = Process::timeout(10)
                                      ->start("{$gsPath} -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$pdfPath} {$originalPdfPath}");

            while ($process->running()) {
                Sleep::sleep(3);
            }

            $process->wait();
            $errorOutput = trim($process->errorOutput());

            abort_if(!empty($errorOutput), 500, $errorOutput);

            $pdf = new Pdf($pdfPath);

            $watermark = new ImageWatermark($this->storeFileFromUrl($this->pdfWatermark->watermark_url));

            $watermarker = new PDFWatermarker($pdf, $watermark);

            if (!empty($this->pdfWatermark->watermark_position)) {
                $constant = Str::upper($this->pdfWatermark->watermark_position);

                $position = new Position(
                    Position::{$constant},
                    $this->pdfWatermark->watermark_x,
                    $this->pdfWatermark->watermark_y
                );

                $watermarker->setPosition($position);

                if ($this->pdfWatermark->watermark_background) {
                    $watermarker->setAsBackground();
                }
            }

            $pdfPath = 'pdf/' . $this->randomFileName('.pdf');

            $watermarker->save("public/{$pdfPath}");
        } catch (\Throwable $th) {
            $this->pdfWatermark->update([
                                            'status'        => PdfWatermarkStatus::ERROR,
                                            'error_message' => $th->getMessage(),
                                            'ended_at'      => now(),
                                        ]);

            abort(500, $th->getMessage());
        }

        if (File::exists($this->getTempPath())) {
            File::deleteDirectory($this->getTempPath());
        }

        $this->pdfWatermark->update([
                                        'status'    => PdfWatermarkStatus::FINISHED,
                                        'pdf_final' => asset($pdfPath),
                                        'ended_at'  => now(),
                                    ]);

        $webhookUrl = $this->pdfWatermark->user->webhook_url;

        if ($webhookUrl) {
            Http::post($webhookUrl, $this->pdfWatermark->toArray());
        }
    }

    private function storeFileFromUrl(string $fileUrl, ?string $ext = null): string
    {
        $response = Http::timeout(120)->get($fileUrl);

        abort_if($response->failed(), 500, "Cannot get file from url {$fileUrl}");

        $filePath = $this->getTempPath($this->randomFileName($ext));

        Storage::disk('local')->put($filePath, $response->body());

        return Storage::disk('local')->path($filePath);
    }

    private function randomFileName(?string $ext = null): string
    {
        return Str::uuid()->getHex() . $ext;
    }

    private function getTempPath(?string $fileName = null): string
    {
        return "temp/{$this->pdfWatermark->uuid}/{$fileName}";
    }
}
