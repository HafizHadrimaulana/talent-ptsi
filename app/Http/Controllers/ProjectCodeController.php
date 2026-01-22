<?php

namespace App\Http\Controllers;

use App\Models\ProjectCode;
use App\Models\Project; // Pastikan Model Project di-import
use Illuminate\Http\Request;

class ProjectCodeController extends Controller
{
    /**
     * Return project codes for dropdown (JSON).
     * Gabungan dari CRM (project_code) dan Manual (projects)
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        
        // ---------------------------------------------------------
        // 1. AMBIL DATA DARI CRM (Tabel: project_code)
        // ---------------------------------------------------------
        $crmQuery = ProjectCode::query();
        $crmQuery->whereNotNull('client_id')->whereNotNull('nama_proyek');
        
        if ($q) {
            $crmQuery->where(function($subQuery) use ($q) {
                $subQuery->where('client_id', 'like', "%{$q}%")
                         ->orWhere('nama_proyek', 'like', "%{$q}%");
            });
        }

        // Ambil 20 data teratas agar query tidak berat
        $crmItems = $crmQuery->select('id', 'client_id', 'nama_proyek', 'nama_klien', 'project_status')
            ->orderBy('id', 'desc')
            ->limit(20) 
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'crm-' . $item->id, // Beri prefix ID agar tidak bentrok
                    'client_id' => $item->client_id,
                    'nama_proyek' => $item->nama_proyek,
                    'nama_klien' => $item->nama_klien,
                    'project_status' => $item->project_status,
                    'source' => 'CRM'
                ];
            });

        // ---------------------------------------------------------
        // 2. AMBIL DATA MANUAL (Tabel: projects)
        // ---------------------------------------------------------
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
                    'id' => 'manual-' . $item->id, // Beri prefix ID
                    // MAPPING PENTING: project_code manual dianggap client_id oleh frontend
                    'client_id' => $item->project_code, 
                    'nama_proyek' => $item->project_name,
                    'nama_klien' => 'Internal / Manual', // Default nama klien
                    'project_status' => 'Active',
                    'source' => 'Manual'
                ];
            });

        // ---------------------------------------------------------
        // 3. GABUNGKAN KEDUANYA (Manual ditaruh di atas)
        // ---------------------------------------------------------
        $mergedResults = $manualItems->merge($crmItems);

        return response()->json([
            'results' => $mergedResults,
            'total' => $mergedResults->count(),
        ]);
    }
}