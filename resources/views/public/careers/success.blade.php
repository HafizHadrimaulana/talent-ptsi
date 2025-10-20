@extends('layouts.public')

@section('title','Lamaran Terkirim')

@section('content')
  <div class="card p-8 text-center">
    <div class="text-5xl mb-4">✅</div>
    <h1 class="text-2xl font-bold">Terima kasih!</h1>
    <p class="mt-2">Lamaran kamu untuk posisi <strong>{{ $job->title ?? $job->position }}</strong> sudah kami terima.</p>
    <a href="{{ route('careers.index') }}" class="btn btn-outline mt-6">Kembali ke daftar lowongan</a>
  </div>
@endsection
