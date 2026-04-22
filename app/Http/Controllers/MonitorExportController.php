<?php

namespace App\Http\Controllers;

use App\Services\MonitorImportService;

class MonitorExportController extends Controller
{
    public function __construct(
        private MonitorImportService $importService
    ) {}

    /**
     * Export monitors to CSV
     */
    public function csv()
    {
        return response()->streamDownload(function () {
            echo $this->importService->exportCsv();
        }, 'monitors-export-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export monitors to JSON
     */
    public function json()
    {
        return response()->streamDownload(function () {
            echo $this->importService->exportJson();
        }, 'monitors-export-'.now()->format('Y-m-d').'.json', [
            'Content-Type' => 'application/json',
        ]);
    }
}
