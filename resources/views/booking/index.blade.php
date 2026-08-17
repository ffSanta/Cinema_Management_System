@extends('layouts.app')

@section('title', 'จองตั๋ว')

@push('styles')
    <style>
        /* การ์ดหนังธีมโรงหนัง (มืด) */
        .movie-block {
            background: linear-gradient(135deg, #1d1d2e 0%, #121019 55%, #1a1220 100%);
            border-radius: 1rem;
            color: #e9e9f2;
            box-shadow: 0 .55rem 1.5rem rgba(0, 0, 0, .28);
            overflow: hidden;
        }

        .movie-block .poster {
            width: 100%;
            aspect-ratio: 2 / 3;
            object-fit: cover;
            border-radius: .6rem;
            background: #0d0d16;
            box-shadow: 0 .35rem 1rem rgba(0, 0, 0, .55);
        }

        .movie-block .movie-title {
            color: #fff;
            font-weight: 700;
            line-height: 1.25;
        }

        .movie-block .movie-synopsis {
            color: #a9a9bd;
        }

        .movie-block .movie-meta {
            color: #8a8a9e;
        }

        .btn-detail-movie {
            border: 1px solid rgba(255, 255, 255, .35);
            color: #e9e9f2;
            background: transparent;
            border-radius: 2rem;
            padding: .18rem 1rem;
            font-size: .85rem;
        }

        .btn-detail-movie:hover {
            background: rgba(255, 255, 255, .13);
            color: #fff;
        }

        /* แถวโรงภาพยนตร์ */
        .theatre-row {
            border-top: 1px solid rgba(255, 255, 255, .09);
            padding: .9rem 0;
        }

        .theatre-row:first-of-type {
            border-top: none;
        }

        .theatre-name {
            color: #fff;
            font-weight: 600;
        }

        .theatre-meta {
            color: #8a8a9e;
            font-size: .78rem;
        }

        .date-label {
            color: #cfcfe4;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .date-group+.date-group {
            margin-top: .6rem;
        }

        /* ปุ่มเวลา (chip สีทอง) */
        .time-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: linear-gradient(#ecc271, #cf9b3a);
            color: #1a1a1a;
            font-weight: 700;
            border-radius: .45rem;
            padding: .4rem .85rem;
            min-width: 66px;
            font-size: .95rem;
            box-shadow: 0 .15rem .4rem rgba(207, 155, 58, .35);
            transition: transform .1s ease, filter .1s ease;
        }

        .time-chip:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
            color: #000;
        }

        .time-chip.full {
            background: #34343f;
            color: #75758a;
            box-shadow: none;
            pointer-events: none;
            text-decoration: line-through;
        }
    </style>
@endpush

@section('content')
    <h2 class="mb-4"> จองตั๋วภาพยนตร์</h2>

    @forelse ($showtimes as $movieTitle => $rounds)
        @php $movie = $rounds->first()->movie; @endphp
        <div class="movie-block mb-4 p-3 p-md-4" data-title="{{ $movie->title }}" data-synopsis="{{ $movie->synopsis }}"
            data-poster="{{ $movie->poster_url }}" data-duration="{{ $movie->duration_mins }}">

            {{-- หัว: โปสเตอร์ + ข้อมูลหนัง --}}
            <div class="row g-3 g-md-4 mb-2">
                <div class="col-4 col-sm-3 col-md-2 col-xl-1">
                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="poster">
                </div>
                <div class="col-8 col-sm-9 col-md-10 col-xl-11">
                    <h4 class="movie-title mb-1">{{ $movie->title }}</h4>
                    <p class="movie-synopsis small mb-2 text-truncate">{{ $movie->synopsis }}</p>
                    <button type="button" class="btn-detail-movie btn-detail">
                        รายละเอียด
                    </button>
                    <div class="movie-meta small mt-2">
                        <i class="bi bi-clock"></i> {{ $movie->duration_mins }} นาที
                    </div>
                </div>
            </div>

            {{-- แต่ละโรง + แถวเวลา --}}
            @foreach ($rounds->groupBy('cinema.name') as $cinemaName => $cinemaRounds)
                <div class="theatre-row">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="theatre-name"> {{ $cinemaName }}</span>
                        <span class="theatre-meta">
                            {{ $cinemaRounds->first()->cinema->total_seats }} ที่นั่ง/รอบ
                        </span>
                    </div>
                    @foreach ($cinemaRounds->sortBy('show_time')->groupBy(fn($s) => $s->show_time->format('Y-m-d')) as $date => $dateRounds)
                        <div class="date-group">
                            <div class="date-label">
                                {{ $dateRounds->first()->show_time->locale('th')->isoFormat('ddd D MMM YYYY') }}
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($dateRounds as $showtime)
                                    @php $available = $showtime->cinema->total_seats - $showtime->bookings_count; @endphp
                                    @if ($available > 0)
                                        <a href="{{ route('booking.seats', $showtime) }}" class="time-chip"
                                            title="ว่าง {{ $available }} ที่นั่ง">
                                            {{ $showtime->show_time->format('H:i') }}
                                        </a>
                                    @else
                                        <span class="time-chip full"
                                            title="เต็ม">{{ $showtime->show_time->format('H:i') }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1"></i>
                <p class="mt-2 mb-0">ยังไม่มีรอบฉายที่เปิดให้จองในขณะนี้</p>
            </div>
        </div>
    @endforelse

    {{-- ===== Modal รายละเอียดภาพยนตร์ ===== --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-film"></i> รายละเอียดภาพยนตร์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-4 text-center">
                            <img id="dPoster" src="" alt="โปสเตอร์" class="img-fluid rounded shadow-sm"
                                style="max-height:340px; background:#1a1a2e;">
                        </div>
                        <div class="col-12 col-sm-8">
                            <h4 id="dTitle" class="mb-2" style="word-break:break-word;"></h4>
                            <p class="text-muted mb-3"><i class="bi bi-clock"></i> <span id="dDuration"></span> นาที</p>
                            <hr>
                            <h6 class="fw-bold">เรื่องย่อ</h6>
                            <p id="dSynopsis" class="mb-0" style="white-space:pre-line; word-break:break-word;"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const detailModal = new bootstrap.Modal('#detailModal');

            $('.btn-detail').on('click', function(e) {
                e.preventDefault();
                const $b = $(this).closest('.movie-block');
                $('#dPoster').attr('src', $b.data('poster'));
                $('#dTitle').text($b.data('title'));
                $('#dSynopsis').text($b.data('synopsis') || '-');
                $('#dDuration').text($b.data('duration'));
                detailModal.show();
            });
        });
    </script>
@endpush
