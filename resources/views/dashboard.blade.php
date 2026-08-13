@extends('layouts.app')

@section('title', 'หน้าหลัก')

@section('content')
    <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
        <div class="text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-film"></i> Cinema Management</h1>
            <p class="lead text-muted">ระบบจัดการโรงภาพยนตร์</p>
        </div>
    </div>

    @auth
        @if (auth()->user()->role === 'admin')
            {{-- ===== Dashboard ผู้ดูแลระบบ ===== --}}
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="{{ url('/movies') }}" class="text-decoration-none">
                        <div class="card text-center shadow-sm h-100">
                            <div class="card-body">
                                <i class="bi bi-camera-reels fs-1 text-primary"></i>
                                <h5 class="card-title mt-2">ภาพยนตร์</h5>
                                <p class="card-text text-muted small">จัดการข้อมูลภาพยนตร์</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ url('/cinemas') }}" class="text-decoration-none">
                        <div class="card text-center shadow-sm h-100">
                            <div class="card-body">
                                <i class="bi bi-building fs-1 text-success"></i>
                                <h5 class="card-title mt-2">โรงภาพยนตร์</h5>
                                <p class="card-text text-muted small">จัดการข้อมูลโรงภาพยนตร์</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ url('/showtimes') }}" class="text-decoration-none">
                        <div class="card text-center shadow-sm h-100">
                            <div class="card-body">
                                <i class="bi bi-clock fs-1 text-warning"></i>
                                <h5 class="card-title mt-2">รอบฉาย</h5>
                                <p class="card-text text-muted small">จัดการรอบฉายภาพยนตร์</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @else
            {{-- ===== Dashboard สมาชิก (user) ===== --}}
            <div class="alert alert-info">
                <i class="bi bi-person-check"></i>
                สวัสดีคุณ <strong>{{ auth()->user()->name }}</strong> ยินดีต้อนรับเข้าสู่ระบบ
            </div>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-ticket-perforated fs-1 text-danger"></i>
                    <h5 class="mt-3">การจองตั๋วภาพยนตร์</h5>
                    <p class="text-muted">ระบบจองตั๋วกำลังพัฒนา เร็วๆ นี้</p>
                </div>
            </div>
        @endif
    @endauth

    @guest
        {{-- ===== ผู้เยี่ยมชม (ยังไม่ล็อกอิน) ===== --}}
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <p class="lead">เข้าสู่ระบบเพื่อใช้งาน</p>
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus"></i> สมัครสมาชิก
                </a>
            </div>
        </div>
    @endguest
@endsection
