<?php

namespace App\Http\Controllers\Admin\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrgController extends Controller
{
    // ======= PAGE =======
    public function index(Request $request)
    {
        // Pastikan kategori unit (enabler / operasi / cabang) autopopulate dari code
        $this->ensureUnitCategories();

        $dirs = DB::table('directorates')
            ->select('id', 'code', 'name')
            ->orderByRaw('COALESCE(code,"")')
            ->orderBy('name')
            ->get();

        $units = DB::table('units as u')
            ->leftJoin('directorates as d', 'd.id', '=', 'u.directorate_id')
            ->select(
                'u.id',
                'u.code',
                'u.name',
                'u.category',
                'u.directorate_id',
                'd.code as d_code',
                'd.name as d_name'
            )
            ->orderByRaw('COALESCE(u.code,"")')
            ->orderBy('u.name')
            ->get();

        return view('admin.org.index', compact('dirs', 'units'));
    }

    // ======= JSON HELPERS (optional, kalau mau dipakai AJAX) =======
    public function tree()
    {
        $dirs = DB::table('directorates')
            ->select('id', 'code', 'name')
            ->orderByRaw('COALESCE(code,"")')
            ->orderBy('name')
            ->get();

        $units = DB::table('units')
            ->select('id', 'code', 'name', 'category', 'directorate_id')
            ->orderByRaw('COALESCE(code,"")')
            ->orderBy('name')
            ->get();

        $directorates = $dirs->map(function ($d) use ($units) {
            return [
                'id'    => $d->id,
                'code'  => $d->code,
                'name'  => $d->name,
                'units' => $units->where('directorate_id', $d->id)->values()->all(),
            ];
        })->values();

        $unassigned = $units->whereNull('directorate_id')->values()->all();

        return response()->json([
            'directorates' => $directorates,
            'unassigned'   => $unassigned,
        ]);
    }

    public function directoratesList(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('directorates')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->select('id', 'code', 'name')
            ->orderByRaw('COALESCE(code,"")')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function directorateOptions()
    {
        $rows = DB::table('directorates')
            ->select('id', 'code', 'name')
            ->orderByRaw('COALESCE(code,"")')
            ->orderBy('name')
            ->get();

        return response()->json($rows);
    }

    public function unitsList(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('units as u')
            ->leftJoin('directorates as d', 'd.id', '=', 'u.directorate_id')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('u.code', 'like', "%{$q}%")
                        ->orWhere('u.name', 'like', "%{$q}%")
                        ->orWhere('d.code', 'like', "%{$q}%")
                        ->orWhere('d.name', 'like', "%{$q}%");
                });
            })
            ->select(
                'u.id',
                'u.code',
                'u.name',
                'u.category',
                'u.directorate_id',
                'd.code as directorate_code',
                'd.name as directorate_name'
            )
            ->orderByRaw('COALESCE(u.code,"")')
            ->orderBy('u.name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    // ======= DIRECTORATES CRUD =======
    public function directorateStore(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', Rule::unique('directorates', 'code')],
            'name' => ['required', 'string', 'max:200'],
        ]);

        DB::table('directorates')->insert([
            'code'       => $data['code'] ?? null,
            'name'       => $data['name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->redirectBackOk($request, 'Directorate created.');
    }

    public function directorateUpdate(Request $request, $id)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', Rule::unique('directorates', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:200'],
        ]);

        DB::table('directorates')->where('id', $id)->update([
            'code'       => $data['code'] ?? null,
            'name'       => $data['name'],
            'updated_at' => now(),
        ]);

        return $this->redirectBackOk($request, 'Directorate updated.');
    }

    public function directorateDestroy(Request $request, $id)
    {
        $hasUnits = DB::table('units')->where('directorate_id', $id)->exists();
        if ($hasUnits) {
            return $this->redirectBackErr($request, 'Cannot delete: reassign or remove units first.');
        }

        DB::table('directorates')->where('id', $id)->delete();

        return $this->redirectBackOk($request, 'Directorate deleted.');
    }

    // ======= UNITS CRUD =======
    public function unitStore(Request $request)
    {
        $data = $request->validate([
            'code'           => ['nullable', 'string', 'max:60', Rule::unique('units', 'code')],
            'name'           => ['required', 'string', 'max:200'],
            'category'       => ['nullable', 'string', 'max:20', Rule::in(['enabler', 'operasi', 'cabang'])],
            'directorate_id' => ['nullable', 'integer', 'exists:directorates,id'],
        ]);

        DB::table('units')->insert([
            'code'           => $data['code'] ?? null,
            'name'           => $data['name'],
            'category'       => $data['category'] ?? null,
            'directorate_id' => $data['directorate_id'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $this->redirectBackOk($request, 'Unit created.');
    }

    public function unitUpdate(Request $request, $id)
    {
        $data = $request->validate([
            'code'           => ['nullable', 'string', 'max:60', Rule::unique('units', 'code')->ignore($id)],
            'name'           => ['required', 'string', 'max:200'],
            'category'       => ['nullable', 'string', 'max:20', Rule::in(['enabler', 'operasi', 'cabang'])],
            'directorate_id' => ['nullable', 'integer', 'exists:directorates,id'],
        ]);

        DB::table('units')->where('id', $id)->update([
            'code'           => $data['code'] ?? null,
            'name'           => $data['name'],
            'category'       => $data['category'] ?? null,
            'directorate_id' => $data['directorate_id'] ?? null,
            'updated_at'     => now(),
        ]);

        return $this->redirectBackOk($request, 'Unit updated.');
    }

    public function unitDestroy(Request $request, $id)
    {
        DB::table('units')->where('id', $id)->delete();

        return $this->redirectBackOk($request, 'Unit deleted.');
    }

    // ======= helpers for HTML/AJAX dual =======
    private function wantsJson(Request $r): bool
    {
        return $r->expectsJson()
            || $r->wantsJson()
            || $r->header('Accept') === 'application/json';
    }

    private function redirectBackOk(Request $r, string $msg)
    {
        return $this->wantsJson($r)
            ? response()->json(['ok' => true, 'message' => $msg])
            : back()->with('ok', $msg);
    }

    private function redirectBackErr(Request $r, string $msg, int $code = 422)
    {
        return $this->wantsJson($r)
            ? response()->json(['ok' => false, 'message' => $msg], $code)
            : back()->withErrors([$msg]);
    }

    /**
     * Auto-isi kolom units.category berdasarkan kode unit,
     * sesuai grouping Enabler / Operasi / Cabang dari dokumen referensi.
     */
    private function ensureUnitCategories(): void
    {
        // Kalau sudah tidak ada category NULL, skip
        $hasNull = DB::table('units')->whereNull('category')->exists();
        if (! $hasNull) {
            return;
        }

        // Mapping dari kode unit -> kategori
        // Enabler
        $enablerCodes = [
            'SP',
            'SPI',
            'DRP2B',
            'DKA',
            'DPKMR',
            'DMA',
            'DHC',
            'DTI',
            'STO',
            'UTJSL',
            'DOP',
        ];

        // Operasi (DBS)
        $operasiCodes = [
            'DBSOGRE',
            'DBSCNM',
            'DBSGNI',
            'DBSINT',
            'DBSINS',
            'DBSSNE',
        ];

        // Cabang
        $cabangCodes = [
            'SIJAK',
            'SISUB',
            'SIMAK',
            'SIBAT',
            'SIBPP',
            'SIMED',
            'SIPAL',
            'SIPKU',
            'SISMA',
            'SISG',
        ];

        // Update hanya yang category-nya masih NULL
        if (! empty($enablerCodes)) {
            DB::table('units')
                ->whereNull('category')
                ->whereIn('code', $enablerCodes)
                ->update(['category' => 'enabler']);
        }

        if (! empty($operasiCodes)) {
            DB::table('units')
                ->whereNull('category')
                ->whereIn('code', $operasiCodes)
                ->update(['category' => 'operasi']);
        }

        if (! empty($cabangCodes)) {
            DB::table('units')
                ->whereNull('category')
                ->whereIn('code', $cabangCodes)
                ->update(['category' => 'cabang']);
        }
    }
}
