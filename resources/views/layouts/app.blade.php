<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- CSRF token สำหรับ AJAX request --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Cinema') — Cinema Management</title>

    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- DataTables (Bootstrap 5 theme) --}}
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    {{-- DataTables Responsive (ยุบคอลัมน์บนจอเล็ก) --}}
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

    @include('layouts.theme')

    @stack('styles')
</head>
<body class="bg-light">

    @include('layouts.navbar')

    <main class="container py-4">
        {{-- แจ้งเตือน (flash message) --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- jQuery (ต้องมาก่อน Bootstrap JS และ DataTables) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- Bootstrap 5 JS bundle (รวม Popper) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    {{-- DataTables Responsive --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    {{-- ตั้งค่า CSRF token ให้ทุก AJAX request อัตโนมัติ --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
