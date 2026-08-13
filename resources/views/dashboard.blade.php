@extends('layouts.app')

@section('title', 'หน้าหลัก')

@section('content')
    <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
        <div class="text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-film"></i> Cinema Management</h1>
            <p class="lead text-muted">ระบบจัดการโรงภาพยนตร์</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
            <a href="{{ url('/bookings') }}" class="text-decoration-none">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-ticket-perforated fs-1 text-danger"></i>
                        <h5 class="card-title mt-2">การจอง</h5>
                        <p class="card-text text-muted small">จัดการการจองตั๋ว</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
