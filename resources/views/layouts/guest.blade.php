<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SRMH')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/srmh.css') }}">
    @stack('head')
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-indigo-50 min-h-screen">
    @yield('content')
    @stack('scripts')
</body>
</html>
