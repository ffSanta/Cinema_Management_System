@extends('layouts.guest')

@section('title', 'สมัครสมาชิก')

@section('content')
    <h2 class="h5 mb-4 text-center">สมัครสมาชิก</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">ชื่อ</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   id="name" name="name" value="{{ old('name') }}" autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">ยืนยันรหัสผ่าน</label>
            <input type="password" class="form-control"
                   id="password_confirmation" name="password_confirmation">
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> สมัครสมาชิก
            </button>
        </div>
    </form>

    <hr class="my-4">
    <p class="text-center mb-0 small">
        มีบัญชีอยู่แล้ว? <a href="{{ route('login') }}">เข้าสู่ระบบ</a>
    </p>
@endsection
