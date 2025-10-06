<!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>@yield('title','Login • Talent PTSI')</title>

  {{-- Vite assets khusus halaman auth --}}
  @vite([
    'resources/css/app.css',       {{-- berisi import tailwind + token + glass utilities --}}
    'resources/css/auth.css',      {{-- style khusus halaman login --}}
    'resources/js/app.js'          {{-- opsional, kalau ada --}}
  ])
</head>
<body class="min-h-screen">
  @yield('content')
</body>
</html>
