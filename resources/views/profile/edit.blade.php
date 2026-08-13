@extends('layouts.app')

@section('title', 'ข้อมูลส่วนตัว')

@section('content')
    <h2 class="mb-4"><i class="bi bi-person-circle"></i> ข้อมูลส่วนตัว</h2>

    <div class="row g-4">
        {{-- ===== แก้ไขชื่อ/อีเมล ===== --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">ข้อมูลบัญชี</h5>

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $user->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email', $user->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">สิทธิ์การใช้งาน</label>
                            <input type="text" class="form-control" value="{{ $user->role }}" disabled>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> บันทึกข้อมูล
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== เปลี่ยนรหัสผ่าน ===== --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">เปลี่ยนรหัสผ่าน</h5>

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password" name="current_password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="new_password" name="password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
