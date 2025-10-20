@extends('app') {{-- kalau app.blade utama kamu bernama app --}}
@section('title','Publish Lowongan')

@section('content')
  <div class="card p-6 max-w-2xl">
    <h1 class="text-xl font-bold mb-4">Publish Lowongan</h1>
    <form method="post" action="{{ route('recruitment.publish.update',$req) }}">
      @csrf @method('PUT')
      <div class="space-y-3">
        <div>
          <label class="label">Judul</label>
          <input name="title" value="{{ old('title',$req->title) }}" class="input input-bordered w-full" required>
          @error('title')<div class="text-error text-sm">{{ $message }}</div>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Lokasi Kerja</label>
            <input name="work_location" value="{{ old('work_location',$req->work_location) }}" class="input input-bordered w-full">
          </div>
          <div>
            <label class="label">Tipe</label>
            <input name="employment_type" value="{{ old('employment_type',$req->employment_type) }}" class="input input-bordered w-full" placeholder="Full-time/Contract/Intern">
          </div>
        </div>
        <div>
          <label class="label">Kualifikasi (satu per baris)</label>
          <textarea name="requirements[]" class="textarea textarea-bordered w-full" rows="6"
            placeholder="Contoh:
S1 Teknik
Pengalaman 2 tahun
Bersedia dinas">
{{ implode("\n", old('requirements', $req->requirements ?? [])) }}</textarea>
          <small class="opacity-60">Server akan split per baris.</small>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_published" value="1" {{ $req->is_published ? 'checked' : '' }}>
          <span>Publish sekarang</span>
        </div>
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ url()->previous() }}" class="btn">Batal</a>
      </div>
    </form>
  </div>
@endsection
