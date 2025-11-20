<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\TrainingImportServices;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TrainingRequestController extends Controller
{
    protected $importService;

    public function __construct(TrainingImportServices $importService)
    {
        $this->importService = $importService;
    }

    public function getDataLna()
    {
        return view('training.training-request.index');
    }
    
    public function importLna(Request $request)
    {
        Log::info("Mulai import LNA.");
        try {
            $request->validate([
                "chunk" => "required|file",
                "index" => "required|integer",
                "total" => "required|integer",
                "filename" => "required|string",
            ]);

            $chunk = $request->file('chunk');
            $index = $request->index;
            $total = $request->total;
            $filename = $request->filename;

            $tempDir = "chunks/{$filename}";

            if (!Storage::exists($tempDir)) {
                Storage::makeDirectory($tempDir);
            }

            $chunkPath = "{$tempDir}/chunk_{$index}.part";
            Storage::put($chunkPath, file_get_contents($chunk));

            Log::info("Chunk " . $index . " berhasil di-upload.");

            if ($index + 1 < $total) {
                return response()->json([
                    "status"  => "success",
                    "message" => "Chunk {$index} uploaded."
                ]);
            }

            $finalName = time() . "_" . $filename;
            $finalPath = "uploads/{$finalName}";
            
            Log::info("File final selesai digabung: {$finalName}");

            $output = fopen(storage_path("app/{$finalPath}"), "ab");

            for ($i = 0; $i < $total; $i++) {
                $cPath = "{$tempDir}/chunk_{$i}.part";
    
                if (!Storage::exists($cPath)) {
                    return response()->json([
                        "status" => "error",
                        "message" => "Missing chunk {$i}"
                    ], 500);
                }
    
                fwrite($output, Storage::get($cPath));
            }

            fclose($output);

            // Hapus chunk
            Storage::deleteDirectory($tempDir);
    
            Log::info("File final selesai digabung: {$finalName}");

            $importResult = $this->importService->handleImport(
                storage_path("app/{$finalPath}"),
                auth()->id()
            );

            return response()->json([
                "status" => "success",
                "message" => "Chunk {$index} uploaded.",
                "data" => $importResult
            ]);
    
        } catch (\Exception $e) {
            Log::error("Error import chunk: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }
}
