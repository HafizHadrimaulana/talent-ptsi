@extends('layouts.app')
@section('title','Rekrutmen · Monitoring')

@section('content')
<div class="p-4 space-y-6">
  <h1 class="text-xl font-semibold mb-2">📊 Monitoring Rekrutmen</h1>

  <div class="card-glass p-4 rounded-2xl shadow">
    <h2 class="text-lg font-semibold mb-3">Izin Prinsip Terbaru</h2>
    <table class="table w-full text-sm">
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
            <td><span class="badge">{{ ucfirst($req->status) }}</span></td>
            <td>{{ \Carbon\Carbon::parse($req->updated_at)->format('d M Y') }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted">Belum ada data izin prinsip.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="card-glass p-4 rounded-2xl shadow">
    <h2 class="text-lg font-semibold mb-3">Kontrak Terbaru</h2>
    <table class="table w-full text-sm">
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
            <td><span class="badge">{{ ucfirst($c->status) }}</span></td>
            <td>{{ $start }}@if($end) – {{ $end }}@endif</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted">Belum ada kontrak.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
