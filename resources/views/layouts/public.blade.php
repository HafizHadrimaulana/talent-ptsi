<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>@yield('title','Careers | PTSI')</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-100 text-base-content">
  <header class="border-b bg-base-100/70 backdrop-blur">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">
      <a href="{{ route('careers.index') }}" class="font-semibold">Careers at PTSI</a>
      <a href="{{ route('login') }}" class="text-sm opacity-70 hover:opacity-100">Login</a>
    </div>
  </header>

  <main class="container mx-auto px-4 py-8">
    @yield('content')
  </main>

  <footer class="border-t">
    <div class="container mx-auto px-4 py-6 text-sm opacity-70">
      © {{ date('Y') }} PTSI
    </div>
  </footer>
</body>
</html>
