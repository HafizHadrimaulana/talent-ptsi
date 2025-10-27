{{-- resources/views/rekrutmen/monitor.blade.php --}}
@extends('layouts.app')
@section('title','Rekrutmen · Monitoring')

@section('content')
<div class="card-glass mb-2 p-4 rounded-2xl shadow-md space-y-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">📊 Monitoring Rekrutmen</h2>
  </div>

  @if(session('ok'))
    <div class="alert success">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="alert danger">{{ $errors->first() }}</div>
  @endif

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
        @if (count($requests))
          @foreach ($requests as $req)
            <tr>
              <td>{{ $req->title ?? '—' }}</td>
              <td>{{ $req->position ?? '—' }}</td>
              <td>{{ $req->headcount ?? '—' }}</td>
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
          @endforeach
        @endif
      </tbody>
    </table>
  </div>

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
        @if (count($contracts))
          @foreach ($contracts as $c)
            @php
              $nama = $c->person_name ?? $c->candidate_name ?? $c->name ?? '—';
              $pos  = $c->position ?? $c->position_name ?? $c->job_title ?? '—';
              $tipe = $c->type ?? $c->contract_type ?? '—';
              $start = $c->start_date ? \Carbon\Carbon::parse($c->start_date)->format('d M Y') : '—';
              $end   = $c->end_date ? \Carbon\Carbon::parse($c->end_date)->format('d M Y') : null;
            @endphp
            <tr>
              <td>{{ $nama }}</td>
              <td>{{ $pos }}</td>
              <td>{{ $tipe }}</td>
              <td>
                <span class="badge
                  @if($c->status === 'rejected') danger
                  @elseif($c->status === 'draft') warn
                  @elseif($c->status === 'approved' || $c->status === 'signed') success
                  @else soft @endif">
                  {{ ucfirst($c->status) }}
                </span>
              </td>
              <td>{{ $start }}@if($end) – {{ $end }}@endif</td>
            </tr>
          @endforeach
        @endif
      </tbody>
    </table>
  </div>
</div>
@endsection
