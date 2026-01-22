<?php

namespace App\Http\Controllers;

use App\Models\ProjectCode;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectCodeController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $crmQuery = ProjectCode::query();
        $crmQuery->whereNotNull('client_id')->whereNotNull('nama_proyek');
        
        if ($q) {
            $crmQuery->where(function($subQuery) use ($q) {
                $subQuery->where('client_id', 'like', "%{$q}%")
                         ->orWhere('nama_proyek', 'like', "%{$q}%");
            });
        }
        $crmItems = $crmQuery->select('id', 'client_id', 'nama_proyek', 'nama_klien', 'project_status')
            ->orderBy('id', 'desc')
            ->limit(20) 
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'crm-' . $item->id,
                    'client_id' => $item->client_id,
                    'nama_proyek' => $item->nama_proyek,
                    'nama_klien' => $item->nama_klien,
                    'project_status' => $item->project_status,
                    'source' => 'CRM'
                ];
            });

        $manualQuery = Project::query();
        if ($q) {
            $manualQuery->where(function($subQuery) use ($q) {
                $subQuery->where('project_code', 'like', "%{$q}%")
                         ->orWhere('project_name', 'like', "%{$q}%");
            });
        }
        $manualItems = $manualQuery->select('id', 'project_code', 'project_name')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'manual-' . $item->id,
                    'client_id' => $item->project_code, 
                    'nama_proyek' => $item->project_name,
                    'nama_klien' => 'Internal / Manual',
                    'project_status' => 'Active',
                    'source' => 'Manual'
                ];
            });

        $mergedResults = $manualItems->merge($crmItems);

        return response()->json([
            'results' => $mergedResults,
            'total' => $mergedResults->count(),
        ]);
    }
}