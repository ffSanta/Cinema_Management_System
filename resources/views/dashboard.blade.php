@extends('layouts.app')

@section('title', 'หน้าหลัก')

@push('styles')
    <style>
        .movie-card {
            cursor: pointer;
        }

        .movie-card .poster-wrap {
            position: relative;
            overflow: hidden;
            border-radius: .6rem;
            aspect-ratio: 2 / 3;
            /* กรอบทรงโปสเตอร์ */
            background: #1a1a2e;
            /* พื้นเติมช่องว่างถ้ารูปไม่ใช่ 2:3 */
            box-shadow: 0 .25rem .7rem rgba(0, 0, 0, .14);
        }

        .movie-card .poster-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* เติมเต็มกรอบ (ครอปส่วนเกินให้พอดี 2:3) */
            display: block;
            transition: transform .35s ease;
        }

        .movie-card:hover .poster-wrap img {
            transform: scale(1.07);
        }

        .poster-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, .9) 8%, rgba(0, 0, 0, .2) 55%, transparent);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            text-align: center;
            gap: .6rem;
            padding: 1rem;
            opacity: 0;
            transition: opacity .25s ease;
        }

        .movie-card:hover .poster-overlay,
        .movie-card:focus-within .poster-overlay {
            opacity: 1;
        }

        .poster-overlay .ov-title {
            color: #fff;
            font-weight: 700;
            line-height: 1.25;
            text-shadow: 0 1px 4px rgba(0, 0, 0, .7);
            word-break: break-word;
        }

        /* จอสัมผัส (ไม่มี hover) — โชว์ overlay จางๆ ให้เห็นปุ่มได้ */
        @media (hover: none) {
            .poster-overlay {
                opacity: 1;
                background: linear-gradient(to top, rgba(0, 0, 0, .75), transparent 55%);
            }

            .poster-overlay .ov-title {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="text-center mb-4">
        <h2 class="fw-bold mb-1"> ภาพยนตร์ทั้งหมด</h2>
    </div>

    @if ($movies->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-camera-reels fs-1"></i>
                <p class="mt-2 mb-0">ยังไม่มีภาพยนตร์</p>
            </div>
        </div>
    @else
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 g-md-4">
            @foreach ($movies as $movie)
                <div class="col">
                    <div class="movie-card h-100" tabindex="0" data-title="{{ $movie->title }}"
                        data-synopsis="{{ $movie->synopsis }}" data-poster="{{ $movie->poster_url }}"
                        data-duration="{{ $movie->duration_mins }}" data-date="{{ $movie->created_at->format('d/m/Y') }}">
                        <div class="poster-wrap">
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" loading="lazy">
                            <div class="poster-overlay">
                                <div class="ov-title">{{ $movie->title }}</div>
                                <button type="button" class="btn btn-light btn-sm">
                                    รายละเอียด
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="fw-semibold text-truncate" title="{{ $movie->title }}">{{ $movie->title }} </div>
                            <div class="text-muted small">
                                {{ $movie->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ===== Modal รายละเอียดภาพยนตร์ (show) ===== --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> รายละเอียดภาพยนตร์</h5>
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
                            <p class="text-muted mb-3">
                                <span class="me-3"><i class="bi bi-clock"></i> <span id="dDuration"></span> นาที</span>
                                <span><i class="bi bi-calendar-plus"> </i><span id="dDate"></span></span>
                            </p>
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

            // คลิก/แตะที่การ์ด (รูปหรือปุ่ม) → เปิดรายละเอียด
            $('.movie-card').on('click', function() {
                const $c = $(this);
                $('#dPoster').attr('src', $c.data('poster'));
                $('#dTitle').text($c.data('title'));
                $('#dSynopsis').text($c.data('synopsis') || '-');
                $('#dDuration').text($c.data('duration'));
                $('#dDate').text($c.data('date'));
                detailModal.show();
            });
        });
    </script>
@endpush
