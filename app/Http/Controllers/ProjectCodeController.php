<?php

namespace App\Http\Controllers;

use App\Models\ProjectCode;
use Illuminate\Http\Request;

class ProjectCodeController extends Controller
{
    /**
     * Return project codes for dropdown (JSON).
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        // $page = max(1, (int) $request->query('page', 1));
        // $perPage = max(1, (int) $request->query('per_page', 100));

        $query = ProjectCode::query();
        $query->whereNotNull('client_id')->whereNotNull('nama_proyek');
        if ($q) {
            $query->where(function($subQuery) use ($q) {
                $subQuery->where('client_id', 'like', "%{$q}%")
                         ->orWhere('nama_proyek', 'like', "%{$q}%");
            });
        }

        $total = $query->count();

        $items = $query->select('id', 'client_id', 'nama_proyek', 'nama_klien', 'project_status')
            ->orderBy('id', 'desc')
            // ->forPage($page, $perPage)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'client_id' => $item->client_id,
                    'nama_proyek' => $item->nama_proyek,
                    'nama_klien' => $item->nama_klien,
                    'project_status' => $item->project_status,
                ];
            });
        // $more = ($page * $perPage) < $total;
        return response()->json([
            'results' => $items,
            // 'pagination' => ['more' => $more],
            'total' => $total,
        ]);
    }
}
