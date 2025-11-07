{{-- resources/views/rekrutmen/monitor.blade.php --}}
@extends('layouts.app')
@section('title','Rekrutmen · Monitoring')

@section('content')
<div class="u-card u-card--glass u-hover-lift u-mb-lg">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">📊 Monitoring Rekrutmen</h2>
  </div>

  @if(session('ok'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class="fas fa-check-circle u-success-icon"></i>
        <span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm">
        <i class="fas fa-exclamation-circle u-error-icon"></i>
        <span>{{ $errors->first() }}</span>
      </div>
    </div>
  @endif

  {{-- Izin Prinsip --}}
  <div class="dt-wrapper u-mb-lg">
    <h3 class="u-font-semibold u-mb-sm">Izin Prinsip Terbaru</h3>
    <div class="u-scroll-x">
      <table id="izin-table" class="u-table u-table-mobile" data-dt>
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
          @if (count($requests))
            @foreach ($requests as $req)
              <tr>
                <td>{{ $req->title ?? '—' }}</td>
                <td>{{ $req->position ?? '—' }}</td>
                <td><span class="u-badge u-badge--glass">{{ $req->headcount ?? '—' }}</span></td>
                <td>
                  @php
                    $st = $req->status;
                    $badge = $st==='draft' ? 'u-badge--warn' : ($st==='approved' ? 'u-badge--success' : 'u-badge--glass'); 
                  @endphp
                  <span class="u-badge {{ $badge }}">{{ ucfirst($st) }}</span>
                </td>
                <td>{{ \Carbon\Carbon::parse($req->updated_at)->format('d M Y') }}</td>
              </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>

  {{-- Kontrak --}}
  <div class="dt-wrapper">
    <h3 class="u-font-semibold u-mb-sm">Kontrak Terbaru</h3>
    <div class="u-scroll-x">
      <table id="monitor-table" class="u-table u-table-mobile" data-dt>
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
          @if (count($contracts))
            @foreach ($contracts as $c)
              @php
                $nama  = $c->person_name ?? $c->candidate_name ?? $c->name ?? '—';
                $pos   = $c->position ?? $c->position_name ?? $c->job_title ?? '—';
                $tipe  = $c->type ?? $c->contract_type ?? '—';
                $start = $c->start_date ? \Carbon\Carbon::parse($c->start_date)->format('d M Y') : '—';
                $end   = $c->end_date ? \Carbon\Carbon::parse($c->end_date)->format('d M Y') : null;
                $st    = $c->status;
                $badge = in_array($st,['approved','signed']) ? 'u-badge--success' : ($st==='draft' ? 'u-badge--warn' : 'u-badge--glass');
              @endphp
              <tr>
                <td>{{ $nama }}</td>
                <td>{{ $pos }}</td>
                <td><span class="u-badge u-badge--glass">{{ $tipe }}</span></td>
                <td><span class="u-badge {{ $badge }}">{{ ucfirst($st) }}</span></td>
                <td>{{ $start }}@if($end) – {{ $end }}@endif</td>
              </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof DataTable !== 'undefined') {
    new DataTable('#izin-table', {
      responsive: true, paging: false, info: false,
      language: {
        search: "Cari:",
        zeroRecords: "Tidak ada data izin prinsip yang ditemukan",
        infoEmpty: "Menampilkan 0 data",
        infoFiltered: "(disaring dari _MAX_ total data)"
      }
    });
    new DataTable('#monitor-table', {
      responsive: true, paging: false, info: false,
      language: {
        search: "Cari:",
        zeroRecords: "Tidak ada data kontrak yang ditemukan",
        infoEmpty: "Menampilkan 0 data",
        infoFiltered: "(disaring dari _MAX_ total data)"
      }
    });
  }
});
</script>
@endsection
