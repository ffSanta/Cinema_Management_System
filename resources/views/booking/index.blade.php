@extends('layouts.app')

@section('title', 'จองตั๋ว')

@section('content')
    <h2 class="mb-4"><i class="bi bi-ticket-perforated"></i> เลือกรอบฉายเพื่อจองตั๋ว</h2>

    @forelse ($showtimes as $movieTitle => $rounds)
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bi bi-film text-primary"></i> {{ $movieTitle }}
                    <span class="text-muted small">({{ $rounds->first()->movie->duration_mins }} นาที)</span>
                </h5>

                <div class="row g-3">
                    @foreach ($rounds as $showtime)
                        @php
                            $available = $showtime->cinema->total_seats - $showtime->bookings_count;
                        @endphp
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                <div class="mb-2">
                                    <div><i class="bi bi-building text-success"></i> {{ $showtime->cinema->name }}</div>
                                    <div><i class="bi bi-clock text-warning"></i> {{ $showtime->show_time->format('d/m/Y H:i') }}</div>
                                    <div><i class="bi bi-cash"></i> {{ number_format((float) $showtime->price) }} บาท</div>
                                </div>
                                <div class="mb-2">
                                    @if ($available > 0)
                                        <span class="badge bg-success">ว่าง {{ $available }} ที่นั่ง</span>
                                    @else
                                        <span class="badge bg-danger">เต็ม</span>
                                    @endif
                                </div>
                                <div class="mt-auto">
                                    @if ($available > 0)
                                        <a href="{{ route('booking.seats', $showtime) }}" class="btn btn-primary btn-sm w-100">
                                            <i class="bi bi-grid-3x3-gap"></i> เลือกที่นั่ง
                                        </a>
                                    @else
                                        <button class="btn btn-secondary btn-sm w-100" disabled>เต็มแล้ว</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1"></i>
                <p class="mt-2 mb-0">ยังไม่มีรอบฉายที่เปิดให้จองในขณะนี้</p>
            </div>
        </div>
    @endforelse
@endsection
