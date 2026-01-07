<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\TrainingImportServices;
use App\Models\TrainingReference;
use App\Models\TrainingRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingImportController extends Controller
{
    protected $importService;

    public function __construct(TrainingImportServices $importService)
    {
        $this->importService = $importService;
    }

    // Import LNA
    public function importLna(Request $request)
    {
        $request->validate([
            "chunk" => "required|file",
            "index" => "required|integer",
            "total" => "required|integer",
            "filename" => "required|string",
        ]);

        $fullPath = null;
        $tempDir = null;

        try {
            $chunk = $request->file('chunk');
            $index = (int) $request->index;
            $total = (int) $request->total;
            
            $originalName = pathinfo($request->filename, PATHINFO_FILENAME);
            $extension = pathinfo($request->filename, PATHINFO_EXTENSION);
            $safeName = Str::slug($originalName) . '.' . $extension;

            // Simpan chunk
            $tempDir = $this->saveChunkFile($chunk, $index, $safeName);

            Log::info("Temp dir: " . $tempDir);
            
            if ($index + 1 < $total) {
                return response()->json([
                    "status"  => "success",
                    "message" => "Chunk {$index} uploaded."
                ]);
            }

            // Gabungkan chunks
            [$finalPath, $fullPath] = $this->mergeChunks($tempDir, $safeName, $total);

            if (!file_exists($fullPath)) {
                throw new \Exception("File gabungan tidak ditemukan");
            }

            Log::info("File gabungan: " . $fullPath);
            
            // Import sesuai LNA
            $result = $this->importService->handleImport($fullPath, auth()->id());

            // Hapus file gabungan

            return response()->json($result, 200);
    
        } catch (\Exception $e) {
            Log::error("Error import chunk: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);

        } finally {
            if ($fullPath && file_exists($fullPath)) {
                @unlink($fullPath);
            }
            // Pastikan folder temp bersih
            if ($tempDir && is_dir($tempDir)) {
                $files = glob($tempDir . '/*'); 
                
                if ($files !== false) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                }
                
                @rmdir($tempDir);
            }
        }
    }
    
    // PRIVATE FUNCTION //
    private function saveChunkFile($chunk, int $index, string $safeName): string
    {
        $hashDir = md5(auth()->id() . $safeName); 
        $tempDir = storage_path("app/chunks/{$hashDir}");

        // Gunakan helper Laravel untuk memastikan direktori ada
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0777, true, true);
        }

        // Cek apakah direktori writable
        if (!is_writable($tempDir)) {
            chmod($tempDir, 0777);
        }

        $targetPath = $tempDir . DIRECTORY_SEPARATOR . "chunk_{$index}";

        // Gunakan copy lalu unlink jika move_uploaded_file gagal (Workaround Windows)
        try {
            $chunk->move($tempDir, "chunk_{$index}");
        } catch (\Exception $e) {
            // Jika move gagal, coba salin manual
            if (!copy($chunk->getPathname(), $targetPath)) {
                throw new \Exception("Gagal memindahkan chunk ke: " . $targetPath);
            }
            unlink($chunk->getPathname());
        }

        return $tempDir;
    }

    private function mergeChunks(string $tempDir, string $safeName, int $total): array
    {
        $fileName = time() . '_' . $safeName;
        $relativePath = "uploads/{$fileName}";
        $fullPath = storage_path("app/{$relativePath}");

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }
        
        $output = fopen($fullPath, 'ab');

        for ($i = 0; $i < $total; $i++) {
            $chunkPath = "{$tempDir}/chunk_{$i}";
            if (!file_exists($chunkPath)) {
                fclose($output);
                throw new \Exception("File chunk bagian {$i} hilang.");
            }

            $input = fopen($chunkPath, 'rb');
            stream_copy_to_stream($input, $output);
            fclose($input);
        }

        fclose($output);
        return [$relativePath, $fullPath];
    }
}
