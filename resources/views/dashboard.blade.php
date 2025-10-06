@extends('layouts.app')

@section('title','Dashboard')

@section('content')
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="p-4 rounded-xl shadow-sm glass">
      <h3 class="font-bold mb-2">Welcome</h3>
      <p class="muted">Halo, {{ auth()->user()->name ?? '-' }}.</p>
    </div>
    <div class="p-4 rounded-xl shadow-sm glass">
      <h3 class="font-bold mb-2">Role</h3>
      <p class="muted">{{ auth()->user()?->getRoleNames()->implode(', ') ?: '-' }}</p>
    </div>
    <div class="p-4 rounded-xl shadow-sm glass">
      <h3 class="font-bold mb-2">Unit</h3>
      <p class="muted">{{ optional(auth()->user()?->unit)->name ?? '-' }}</p>
    </div>
  </div>
@endsection
