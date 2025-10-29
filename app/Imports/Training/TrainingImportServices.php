<?php

namespace App\Imports\Training;

use App\Models\FileTraining;
use App\Imports\Training\TrainingImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TrainingImportServices
{
    public function handleImport($file, $userId)
    {
        $fileTraining = FileTraining::create([
            'file_name' => $file->getClientOriginalName(),
            'imported_by' => $userId ?? 0,
            'rows' => 0,
        ]);

        Excel::import(new TrainingImport($fileTraining->id), $file);

        $fileTraining->update([
            'rows' => $fileTraining->trainings()->count(),
        ]);

        return $fileTraining;
    }
}
