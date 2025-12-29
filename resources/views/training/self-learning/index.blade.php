@extends('layouts.app')
@section('title', 'Pelatihan · Self Learning')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Self Learning</h2>
  </div>

  {{-- ===== Alerts (Optional) ===== --}}
  @if(session('success'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='fas fa-check-circle u-success-icon'></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif
  @if($errors->any())
    <div class="alert danger">{{ $errors->first() }}</div>
  @endif

  {{-- ===== DataTable Wrapper ===== --}}

  {{-- ===== SI COURSE ===== --}}
  <div class="dt-wrapper u-mb-xl">
    <div class="flex items-center justify-between u-mb-lg p-10">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-800">SI Course</h2>
      </div>
      <button type="button" class="u-btn u-btn--brand u-hover-lift">Add SI Course</button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      @foreach ([
        ['title' => 'Laravel Fundamentals', 'desc' => 'Pelajari dasar-dasar Laravel dengan studi kasus nyata.', 'img' => 'https://picsum.photos/id/1/200/300'],
        ['title' => 'Tailwind Mastery', 'desc' => 'Kuasi desain modern dengan Tailwind CSS.', 'img' => 'https://picsum.photos/id/1/200/300'],
        ['title' => 'Database Optimization', 'desc' => 'Tingkatkan performa query SQL dengan teknik indexing.', 'img' => 'https://picsum.photos/id/1/200/300'],
        ['title' => 'RESTful API Design', 'desc' => 'Bangun API efisien dan aman dengan Laravel.', 'img' => 'https://picsum.photos/id/1/200/300'],
      ] as $course)
        <div class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition u-p-lg">
          <img src="{{ $course['img'] }}" alt="{{ $course['title'] }}" class="w-full h-40 object-cover rounded-xl mb-3">
          <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $course['title'] }}</h3>
          <p class="text-sm text-gray-600">{{ $course['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 u-mb-lg">
    <div>
      {{-- ===== SI LEARNING EVENT ===== --}}
      <div class="dt-wrapper u-mb-md u-p-lg">
        <div class="flex items-center justify-between u-mb-lg">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-800">SI Learning Event</h2>
          </div>
          <button type="button" class="u-btn u-btn--brand u-hover-lift">Add SI Learning Event</button>
        </div>
    
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          @foreach ([
            ['title' => 'Web Development Bootcamp', 'desc' => 'Workshop intensif 3 hari membangun aplikasi web.', 'img' => 'https://source.unsplash.com/400x250/?workshop,developer'],
            ['title' => 'Database Day', 'desc' => 'Event sharing session tentang database optimization.', 'img' => 'https://source.unsplash.com/400x250/?database,conference'],
            ['title' => 'UI/UX Talk', 'desc' => 'Diskusi seputar tren desain antarmuka modern.', 'img' => 'https://source.unsplash.com/400x250/?ui,ux'],
            ['title' => 'Laravel Meetup', 'desc' => 'Komunitas Laravel Indonesia berbagi pengalaman.', 'img' => 'https://source.unsplash.com/400x250/?laravel,meetup'],
          ] as $event)
            <div class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition">
              <img src="{{ $event['img'] }}" alt="{{ $event['title'] }}" class="w-full h-40 object-cover rounded-xl mb-3">
              <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $event['title'] }}</h3>
              <p class="text-sm text-gray-600">{{ $event['desc'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
      {{-- ===== SI TOPIC ===== --}}
      <div class="dt-wrapper mb-10 u-p-lg">
        <div class="flex items-center justify-between u-mb-lg">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-800">SI Topic</h2>
          </div>
          <button type="button" class="u-btn u-btn--brand u-hover-lift">Add SI Topic</button>
        </div>
    
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          @foreach ([
            ['title' => 'Artificial Intelligence', 'desc' => 'Mengenal konsep AI dan penerapannya di industri.', 'img' => 'https://picsum.photos/id/1/200/300'],
            ['title' => 'Cloud Computing', 'desc' => 'Memahami infrastruktur cloud dan implementasinya.', 'img' => 'https://source.unsplash.com/400x250/?cloud,server'],
            ['title' => 'Cyber Security', 'desc' => 'Keamanan data dan strategi pertahanan digital.', 'img' => 'https://source.unsplash.com/400x250/?cyber,security'],
            ['title' => 'DevOps Culture', 'desc' => 'Integrasi dan kolaborasi tim pengembang modern.', 'img' => 'https://source.unsplash.com/400x250/?devops,team'],
          ] as $topic)
            <div class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition">
              <img src="{{ $topic['img'] }}" alt="{{ $topic['title'] }}" class="w-full h-40 object-cover rounded-xl mb-3">
              <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $topic['title'] }}</h3>
              <p class="text-sm text-gray-600">{{ $topic['desc'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    {{-- ===== SI LIBRARY ===== --}}
    <div class="dt-wrapper mb-10 u-p-lg">
      <div class="flex items-center justify-between u-mb-lg">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-800">SI Library</h2>
        </div>
        <button type="button" class="u-btn u-btn--brand u-hover-lift">Add SI Library</button>
      </div>
  
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ([
          ['title' => 'E-Book Laravel 12', 'desc' => 'Panduan lengkap pengembangan Laravel modern.', 'img' => 'https://source.unsplash.com/400x250/?ebook,programming'],
          ['title' => 'Tailwind Guide', 'desc' => 'Desain UI cepat dengan Tailwind CSS.', 'img' => 'https://source.unsplash.com/400x250/?web,ui'],
          ['title' => 'MySQL Reference', 'desc' => 'Kumpulan tips dan trik manajemen database.', 'img' => 'https://source.unsplash.com/400x250/?mysql,book'],
          ['title' => 'Security Best Practice', 'desc' => 'Panduan keamanan untuk pengembang web.', 'img' => 'https://source.unsplash.com/400x250/?cybersecurity,book'],
        ] as $lib)
          <div class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition">
            <img src="{{ $lib['img'] }}" alt="{{ $lib['title'] }}" class="w-full h-40 object-cover rounded-xl mb-3">
            <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $lib['title'] }}</h3>
            <p class="text-sm text-gray-600">{{ $lib['desc'] }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>

</div>

@endsection

@push('scripts')
  @vite('resources/js/pages/self-learning/index.js')
@endpush
