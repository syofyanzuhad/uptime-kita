<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportMonitorFileRequest;
use App\Http\Requests\ProcessMonitorImportRequest;
use App\Services\MonitorImportService;
use Inertia\Inertia;

class MonitorImportController extends Controller
{
    public function __construct(
        private MonitorImportService $importService
    ) {}

    /**
     * Show the import page
     */
    public function index()
    {
        return Inertia::render('uptime/Import');
    }

    /**
     * Parse uploaded file and return preview data
     */
    public function preview(ImportMonitorFileRequest $request)
    {
        $file = $request->file('import_file');
        $format = $request->input('format') ?? $this->importService->detectFormat($file);

        $result = $this->importService->parseFile($file, $format);

        return response()->json($result);
    }

    /**
     * Process the import with user's duplicate resolution choices
     */
    public function process(ProcessMonitorImportRequest $request)
    {
        try {
            $result = $this->importService->import(
                $request->input('rows'),
                $request->input('duplicate_action'),
                $request->input('resolutions', [])
            );

            $message = "Berhasil mengimport {$result['imported']} monitor.";
            if ($result['updated'] > 0) {
                $message .= " {$result['updated']} monitor diupdate.";
            }
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} monitor dilewati.";
            }

            return redirect()->route('monitor.index')
                ->with('flash', [
                    'message' => $message,
                    'type' => 'success',
                ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Gagal mengimport monitor: '.$e->getMessage(),
                    'type' => 'error',
                ]);
        }
    }

    /**
     * Download sample CSV template
     */
    public function sampleCsv()
    {
        return response()->streamDownload(function () {
            echo $this->importService->generateSampleCsv();
        }, 'monitors-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download sample JSON template
     */
    public function sampleJson()
    {
        return response()->streamDownload(function () {
            echo $this->importService->generateSampleJson();
        }, 'monitors-template.json', [
            'Content-Type' => 'application/json',
        ]);
    }
}
