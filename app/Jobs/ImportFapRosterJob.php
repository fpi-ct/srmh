<?php

namespace App\Jobs;

use App\Services\FapImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportFapRosterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $path,
        public ?string $originalName = null
    ) {}

    public function handle(FapImportService $fapImportService): void
    {
        $fapImportService->importFromFile($this->path);
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Import FAP CSV failed', [
            'path' => $this->path,
            'original_name' => $this->originalName,
            'message' => $exception->getMessage(),
        ]);
    }
}
