@extends('layouts.public')
@section('title','Careers')
@section('content')
  @if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
  @endif
  <form method="get" class="mb-6 flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" class="input input-bordered" placeholder="Search position / location..." />
    <button class="btn btn-primary">Search</button>
  </form>
  @if($jobs->count() === 0)
    <div class="card p-6">No active vacancies.</div>
  @else
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($jobs as $j)
        <a href="{{ route('careers.index', ['q'=>request('q'),'job'=>$j->slug]) }}"
           class="card p-5 hover:shadow-lg transition">
          <h3 class="font-semibold text-lg">{{ $j->title ?? $j->position }}</h3>
          <div class="mt-2 text-sm opacity-75">
            <div>Location: {{ $j->work_location ?? '-' }}</div>
            <div>Type: {{ $j->employment_type ?? '-' }}</div>
          </div>
          <div class="mt-3 text-xs opacity-60">
            Published {{ optional($j->published_at)->diffForHumans() ?? '-' }}
          </div>
        </a>
      @endforeach
    </div>

    <div class="mt-6">{{ $jobs->links() }}</div>
  @endif
  @if($activeJob)
    <div id="jobModal" class="modal-backdrop">
      <div class="modal">
        <button type="button" class="modal-close" onclick="closeJobModal()">✖</button>
        <h2 class="text-xl font-bold">{{ $activeJob->title ?? $activeJob->position }}</h2>
        <div class="mt-2 text-sm opacity-75">
          <span>Location: {{ $activeJob->work_location ?? '-' }}</span> ·
          <span>Type: {{ $activeJob->employment_type ?? '-' }}</span>
        </div>

        @if($req = $activeJob->requirements)
          <div class="mt-4">
            <h3 class="font-semibold mb-2">Requirements</h3>
            <ul class="list-disc ml-5 space-y-1">
              @foreach($req as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @if($activeJob->justification)
          <div class="mt-4">
            <h3 class="font-semibold mb-2">Description</h3>
            <p class="whitespace-pre-line">{{ $activeJob->justification }}</p>
          </div>
        @endif

        <hr class="my-5"/>

        <h3 class="font-semibold text-lg">Apply</h3>
        <form class="mt-3 space-y-3" method="post" enctype="multipart/form-data"
              action="{{ route('careers.apply') }}">
          @csrf
          <input type="hidden" name="slug" value="{{ $activeJob->slug }}"/>

          <div>
            <label class="label">Full Name</label>
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
              <label class="label">Phone</label>
              <input name="phone" value="{{ old('phone') }}" class="input input-bordered w-full">
              @error('phone')<div class="text-error text-sm">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">NIK (optional)</label>
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
            <label class="label">Cover Letter (optional)</label>
            <input type="file" name="cover" class="file-input file-input-bordered w-full">
            @error('cover')<div class="text-error text-sm">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label">Notes (optional)</label>
            <textarea name="notes" class="textarea textarea-bordered w-full" rows="4">{{ old('notes') }}</textarea>
            @error('notes')<div class="text-error text-sm">{{ $message }}</div>@enderror
          </div>

          <div class="flex gap-2">
            <button class="btn btn-primary">Submit Application</button>
            <a class="btn" href="{{ route('careers.index', request()->except('job')) }}">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    <style>
      .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:50;}
      .modal{max-width:800px;width:100%;background:var(--b1,#fff);color:var(--bc,#111);border-radius:1rem;padding:1.25rem;max-height:90vh;overflow:auto;}
      .modal-close{float:right;opacity:.6}
      .modal-close:hover{opacity:1}
    </style>
    <script>
      function closeJobModal(){
        const params = new URLSearchParams(window.location.search);
        params.delete('job');
        const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.replace(url);
      }
      // Close on ESC
      document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeJobModal(); });
    </script>
  @endif
@endsection
