@extends('layouts.guest')

@section('title', 'เข้าสู่ระบบ')

@section('content')
    <h2 class="h5 mb-4 text-center">เข้าสู่ระบบ</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">จดจำการเข้าสู่ระบบ</label>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
            </button>
        </div>
    </form>

    <hr class="my-4">
    <p class="text-center mb-0 small">
        ยังไม่มีบัญชี? <a href="{{ route('register') }}">สมัครสมาชิก</a>
    </p>
@endsection
