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
        $navQuery = ProjectCode::query();
        if ($q) {
            $navQuery->where(function($subQuery) use ($q) {
                $subQuery->where('kode_project', 'like', "%{$q}%")
                         ->orWhere('nama_project', 'like', "%{$q}%")
                         ->orWhere('nama_klien', 'like', "%{$q}%");
            });
        }
        $navItems = $navQuery->select('id', 'kode_project', 'nama_project', 'nama_klien', 'tgl_mulai', 'tgl_akhir')
            ->orderBy('tgl_mulai', 'desc')
            ->limit(20) 
            ->get()
            ->map(function ($item) {
                return [
                    'id'             => 'nav-' . $item->id,
                    'client_id'      => $item->kode_project, 
                    'nama_proyek'    => $item->nama_project, 
                    'nama_klien'     => $item->nama_klien,
                    'project_status' => 'Active', 
                    'source'         => 'NAV'
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
                    'id'             => 'manual-' . $item->id,
                    'client_id'      => $item->project_code, 
                    'nama_proyek'    => $item->project_name,
                    'nama_klien'     => 'Internal / Manual',
                    'project_status' => 'Active',
                    'source'         => 'Manual'
                ];
            });
        $mergedResults = $manualItems->merge($navItems);

        return response()->json([
            'results' => $mergedResults,
            'total'   => $mergedResults->count(),
        ]);
    }
}