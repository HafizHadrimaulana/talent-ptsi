<!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
  <title>@yield('title','Login • Talent PTSI')</title>

  {{-- Vite assets khusus halaman auth --}}
  @vite([
    'resources/css/app.css',      
    'resources/css/auth.css',     
    'resources/js/app.js'         
  ])
</head>
<body class="min-h-screen">
  @yield('content')
</body>
</html>
