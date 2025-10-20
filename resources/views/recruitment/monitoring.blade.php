@extends('layouts.app')
@section('title','Rekrutmen · Monitoring')

@section('content')
<div class="card-glass mb-2 p-4 rounded-2xl shadow-md space-y-6">
  {{-- ===== Header Row ===== --}}
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">📊 Monitoring Rekrutmen</h2>
  </div>

  {{-- ===== Alerts (Optional) ===== --}}
  @if(session('ok')) 
    <div class="alert success">{{ session('ok') }}</div>
  @endif
  @if($errors->any()) 
    <div class="alert danger">{{ $errors->first() }}</div>
  @endif

  {{-- ===== Izin Prinsip Section ===== --}}
  <div class="dt-wrapper bg-white/70 dark:bg-slate-900/60 rounded-xl shadow-sm p-4 space-y-3 ios-glass">
    <h3 class="text-lg font-semibold mb-2">Izin Prinsip Terbaru</h3>
    <table id="izin-table" class="display table-ui table-compact table-sticky w-full" data-dt>
      <thead>
        <tr>
          <th>Judul</th>
          <th>Posisi</th>
          <th>Headcount</th>
          <th>Status</th>
          <th>Update</th>
        </tr>
      </thead>
      <tbody>
        @forelse($requests as $req)
          <tr>
            <td>{{ $req->title }}</td>
            <td>{{ $req->position }}</td>
            <td>{{ $req->headcount }}</td>
            <td>
              <span class="badge 
                @if($req->status === 'rejected') danger
                @elseif($req->status === 'draft') warn
                @elseif($req->status === 'approved') success
                @else soft @endif">
                {{ ucfirst($req->status) }}
              </span>
            </td>
            <td>{{ \Carbon\Carbon::parse($req->updated_at)->format('d M Y') }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted">Belum ada data izin prinsip.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ===== Kontrak Section ===== --}}
  <div class="dt-wrapper bg-white/70 dark:bg-slate-900/60 rounded-xl shadow-sm p-4 space-y-3 ios-glass">
    <h3 class="text-lg font-semibold mb-2">Kontrak Terbaru</h3>
    <table id="monitor-table" class="display table-ui table-compact table-sticky w-full" data-dt>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Posisi</th>
          <th>Tipe</th>
          <th>Status</th>
          <th>Periode</th>
        </tr>
      </thead>
      <tbody>
        @forelse($contracts as $c)
          @php
            $start = $c->start_date ? \Carbon\Carbon::parse($c->start_date)->format('d M Y') : '-';
            $end   = $c->end_date ? \Carbon\Carbon::parse($c->end_date)->format('d M Y') : null;
          @endphp
          <tr>
            <td>{{ $c->person_name }}</td>
            <td>{{ $c->position }}</td>
            <td>{{ $c->type }}</td>
            <td>
              <span class="badge 
                @if($c->status === 'rejected') danger
                @elseif($c->status === 'draft') warn
                @elseif($c->status === 'approved') success
                @else soft @endif">
                {{ ucfirst($c->status) }}
              </span>
            </td>
            <td>{{ $start }}@if($end) – {{ $end }}@endif</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted">Belum ada kontrak.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // ===== Initialize DataTables if loaded globally =====
    if (typeof DataTable !== 'undefined') {
      new DataTable('#izin-table', { responsive: true, paging: false, info: false });
      new DataTable('#monitor-table', { responsive: true, paging: false, info: false });
    } else {
      console.warn('⚠️ DataTables not found — make sure it’s imported or globally available.');
    }
  });
</script>
@endsection
