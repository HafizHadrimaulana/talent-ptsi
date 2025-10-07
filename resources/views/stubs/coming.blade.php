@extends('layouts.app')
@section('title', $title ?? 'Coming Soon')

@section('content')
  <div class="card glass p-6">
    <h2 class="text-xl font-bold mb-2">{{ $title ?? 'Coming Soon' }}</h2>
    <p class="muted">Halaman ini belum tersedia. Fitur sedang dalam pengembangan.</p>
  </div>
@endsection
