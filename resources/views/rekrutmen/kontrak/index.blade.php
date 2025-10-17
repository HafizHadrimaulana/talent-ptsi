@extends('layouts.app')
@section('title','Penerbitan Kontrak')

@section('content')
  <div class="card-glass mb-2" style="display:flex;justify-content:space-between;align-items:center">
    <h2 class="m-0">Kontrak</h2>
    @can('contract.create')
      <button class="btn btn-brand" type="button" onclick="openCtrModal()">Draft Kontrak</button>
    @endcan
  </div>

  @if(session('ok')) <div class="alert success">{{ session('ok') }}</div> @endif
  @if($errors->any()) <div class="alert danger">{{ $errors->first() }}</div> @endif

  <div class="table-wrap">
    <table id="perms-table" class="display table-ui table-compact table-sticky" data-dt>
      <thead><tr><th>No</th><th>Nama</th><th>Posisi</th><th>Jenis</th><th>Status</th><th class="cell-actions">Aksi</th></tr></thead>
      <tbody>
        @foreach($list as $c)
          <tr>
            <td>{{ $c->number ?? '—' }}</td>
            <td>{{ $c->person_name }}</td>
            <td>{{ $c->position }}</td>
            <td>{{ $c->type }}</td>
            <td><span class="badge soft">{{ $c->status }}</span></td>
            <td class="cell-actions">
              @if($c->status === 'draft')
                <form method="POST" action="{{ route('rekrutmen.kontrak.submit',$c) }}" style="display:inline">@csrf
                  <button class="btn btn-sm">Submit</button>
                </form>
              @elseif($c->status === 'review')
                @can('contract.approve')
                <form method="POST" action="{{ route('rekrutmen.kontrak.approve',$c) }}" style="display:inline">@csrf
                  <button class="btn btn-sm success">Approve</button>
                </form>
                @endcan
              @elseif($c->status === 'approved')
                @can('contract.sign')
                <form method="POST" action="{{ route('rekrutmen.kontrak.sign',$c) }}" style="display:inline">@csrf
                  <button class="btn btn-sm">Mark Signed</button>
                </form>
                @endcan
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-2">{{ $list->links() }}</div>

  {{-- ===== Modal: Draft Kontrak ===== --}}
  <div id="ctrModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header">
        <h3>Draft Kontrak</h3>
        <button class="close-btn" type="button" onclick="closeCtrModal()">✖</button>
      </div>
      <form id="ctrForm" method="POST" action="{{ route('rekrutmen.kontrak.store') }}">
        @csrf
        <div class="modal-body">
          <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px">
            <div>
              <label>Jenis Kontrak</label>
              <select name="type" class="input" required>
                <option value="SPK">SPK</option>
                <option value="PKWT">PKWT</option>
              </select>
            </div>
            <div>
              <label>Ambil dari Applicant (opsional)</label>
              <select name="applicant_id" class="input">
                <option value="">—</option>
                @foreach($applicants as $a)
                  <option value="{{ $a->id }}">{{ $a->full_name }} — {{ $a->position_applied }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label>ID Employee (existing, opsional)</label>
              <input name="employee_id" class="input" placeholder="Isi kalau internal">
            </div>
            <div>
              <label>Nama</label>
              <input name="person_name" class="input" required>
            </div>
            <div>
              <label>Posisi</label>
              <input name="position" class="input" required>
            </div>
            <div>
              <label>Mulai</label>
              <input type="date" name="start_date" class="input">
            </div>
            <div>
              <label>Selesai</label>
              <input type="date" name="end_date" class="input">
            </div>
            <div>
              <label>Gaji</label>
              <input type="number" step="0.01" name="salary" class="input">
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn btn-ghost" type="button" onclick="closeCtrModal()">Batal</button>
          <button class="btn btn-brand">Simpan Draft</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openCtrModal(){ document.getElementById('ctrModal').hidden = false; }
    function closeCtrModal(){ document.getElementById('ctrModal').hidden = true; }
  </script>
@endsection
