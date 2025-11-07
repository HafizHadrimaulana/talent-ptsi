@extends('layouts.app')

@section('title','Dashboard')

@section('content')
    <div class="p-6 md:p-8 rounded-2xl shadow-lg glass border border-white/10 backdrop-blur-md">
      <div class="space-y-5" style = "margin-left : 12px;" >
        <p class="text-2xl font-bold mb-4 text-primary">
          Hello, <span class="font-semibold">{{ auth()->user()->name ?? '-' }}</span>.
        </p>
        <div class="flex flex-col gap-3 mt-2">
          <div class="flex flex-col">
            <span class="text-base font-medium text-gray-900">
              {{ auth()->user()?->getRoleNames()->implode(', ') ?: '-' }}
            </span>
            <span class = "text-base font-medium text-gray-900">
              {{ optional(auth()->user()?->unit)->name ?? '-' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
