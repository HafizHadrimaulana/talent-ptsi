@extends('layouts.public')

@section('title', ($job->title ?? $job->position).' | Karier PTSI')

@section('content')
  <article class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card p-6">
      <h1 class="text-2xl font-bold">{{ $job->title ?? $job->position }}</h1>
      <div class="mt-2 text-sm opacity-75">
        <span>Lokasi: {{ $job->work_location ?? '-' }}</span> ·
        <span>Tipe: {{ $job->employment_type ?? '-' }}</span>
      </div>

      @if($req = $job->requirements)
        <div class="mt-6">
          <h2 class="font-semibold mb-2">Kualifikasi</h2>
          <ul class="list-disc ml-5 space-y-1">
            @foreach($req as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if($job->justification)
        <div class="mt-6">
          <h2 class="font-semibold mb-2">Deskripsi</h2>
          <p class="whitespace-pre-line">{{ $job->justification }}</p>
        </div>
      @endif
    </div>

    <aside class="card p-6">
      <h2 class="font-semibold text-lg">Lamar Posisi Ini</h2>
      <form class="mt-4 space-y-3" method="post" enctype="multipart/form-data"
            action="{{ route('careers.apply', $job->slug) }}">
        @csrf
        <div>
          <label class="label">Nama Lengkap</label>
          <input name="full_name" value="{{ old('full_name') }}" class="input input-bordered w-full" required>
          @error('full_name')<div class="text-error text-sm">{{ $message }}</div>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="input input-bordered w-full" required>
            @error('email')<div class="text-error text-sm">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="label">No. HP</label>
            <input name="phone" value="{{ old('phone') }}" class="input input-bordered w-full">
            @error('phone')<div class="text-error text-sm">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">NIK (opsional)</label>
            <input name="nik_number" value="{{ old('nik_number') }}" class="input input-bordered w-full">
            @error('nik_number')<div class="text-error text-sm">{{ $message }}</div>@enderror
          </div>
        </div>

        <div>
          <label class="label">CV / Resume (PDF/DOC, max 5MB)</label>
          <input type="file" name="cv" class="file-input file-input-bordered w-full" required>
          @error('cv')<div class="text-error text-sm">{{ $message }}</div>@enderror
        </div>
        <div>
          <label class="label">Surat Lamaran (opsional)</label>
          <input type="file" name="cover" class="file-input file-input-bordered w-full">
          @error('cover')<div class="text-error text-sm">{{ $message }}</div>@enderror
        </div>

        <div>
          <label class="label">Catatan (opsional)</label>
          <textarea name="notes" class="textarea textarea-bordered w-full" rows="4">{{ old('notes') }}</textarea>
          @error('notes')<div class="text-error text-sm">{{ $message }}</div>@enderror
        </div>

        <button class="btn btn-primary w-full">Kirim Lamaran</button>
      </form>
    </aside>
  </article>
@endsection
