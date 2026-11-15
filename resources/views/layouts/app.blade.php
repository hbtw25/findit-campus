<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · FindIt Campus</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    @yield('styles')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="has-tabbar @yield('body_class')">

    @include('partials.navbar')

    <main class="@yield('main_class', 'container py-4')">
        @hasSection('no_flash')
        @else
            @include('partials.flash')
        @endif
        @yield('content')
    </main>

    @hasSection('hide_tabbar')
    @else
        @include('partials.tabbar')
    @endif

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @yield('scripts')
</body>
</html>
