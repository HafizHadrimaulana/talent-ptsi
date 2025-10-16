@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
  <div class="card-glass mb-2" style="display:flex;justify-content:space-between;align-items:center">
    <h2 class="m-0">Izin Prinsip</h2>
    <button class="btn btn-brand" type="button" onclick="openIpModal()">Buat Permintaan</button>
  </div>

  @if(session('ok')) <div class="alert success">{{ session('ok') }}</div> @endif
  @if($errors->any()) <div class="alert danger">{{ $errors->first() }}</div> @endif

  <div class="table-wrap">
    <table class="table-ui">
      <thead><tr><th>Judul</th><th>Posisi</th><th>HC</th><th>Status</th><th class="cell-actions">Aksi</th></tr></thead>
      <tbody>
        @foreach($list as $r)
          <tr>
            <td>{{ $r->title }}</td>
            <td>{{ $r->position }}</td>
            <td>{{ $r->headcount }}</td>
            <td><span class="badge soft">{{ $r->status }}</span></td>
            <td class="cell-actions">
              @if($r->status === 'draft')
                <form method="POST" action="{{ route('rekrutmen.izin-prinsip.submit',$r) }}" style="display:inline">@csrf
                  <button class="btn btn-sm">Submit</button>
                </form>
              @endif
              @if($r->status === 'submitted')
                <form method="POST" action="{{ route('rekrutmen.izin-prinsip.approve',$r) }}" style="display:inline">@csrf
                  <button class="btn btn-sm success">Approve</button>
                </form>
                <form method="POST" action="{{ route('rekrutmen.izin-prinsip.reject',$r) }}" style="display:inline">@csrf
                  <button class="btn btn-sm danger">Reject</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-2">{{ $list->links() }}</div>

  {{-- ===== Modal: Buat Izin Prinsip ===== --}}
  <div id="ipModal" class="modal" hidden>
    <div class="modal-card">
      <div class="modal-header">
        <h3>Buat Izin Prinsip</h3>
        <button class="close-btn" onclick="closeIpModal()">✖</button>
      </div>
      <form id="ipForm" method="POST" action="{{ route('rekrutmen.izin-prinsip.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-2">
            <label>Judul</label>
            <input class="input" name="title" required>
          </div>
          <div class="mb-2">
            <label>Posisi</label>
            <input class="input" name="position" required>
          </div>
          <div class="mb-2">
            <label>Headcount</label>
            <input class="input" type="number" min="1" name="headcount" value="1" required>
          </div>
          <div class="mb-2">
            <label>Justifikasi</label>
            <textarea class="input" name="justification" rows="4"></textarea>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn btn-ghost" type="button" onclick="closeIpModal()">Batal</button>
          <button class="btn btn-brand">Simpan Draft</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openIpModal(){ document.getElementById('ipModal').hidden = false; }
    function closeIpModal(){ document.getElementById('ipModal').hidden = true; }
  </script>
@endsection
