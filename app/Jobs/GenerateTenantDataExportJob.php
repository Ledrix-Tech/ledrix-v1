<?php

namespace App\Jobs;

use App\Models\Central\TenantDataExportRequest;
use App\Services\Tenant\TenantDataExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateTenantDataExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $exportId) {}

    public function handle(TenantDataExportService $exports): void
    {
        $export = TenantDataExportRequest::query()->find($this->exportId);
        if (! $export) {
            return;
        }

        $exports->generate($export);
    }

    public function failed(?Throwable $exception): void
    {
        $export = TenantDataExportRequest::query()->find($this->exportId);
        if (! $export) {
            return;
        }

        $export->forceFill([
            'status' => 'failed',
            'meta'   => array_merge($export->meta ?? [], [
                'error' => $exception?->getMessage(),
            ]),
        ])->save();
    }
}
