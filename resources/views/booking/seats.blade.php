@extends('layouts.app')

@section('title', 'เลือกที่นั่ง')

@push('styles')
<style>
    /* จอภาพยนตร์ */
    .screen {
        background: linear-gradient(#adb5bd, #dee2e6);
        color: #495057;
        text-align: center;
        border-radius: 6px;
        padding: 6px;
        margin-bottom: 1.5rem;
        font-weight: 600;
        letter-spacing: 2px;
        box-shadow: 0 6px 12px -6px rgba(0, 0, 0, .4);
    }

    /* กล่องผังที่นั่ง — ไม่มีแถบเลื่อน ใช้ scale-to-fit (JS) ย่อผังให้พอดีความกว้างเสมอ */
    .seat-map-wrapper {
        overflow: hidden;
        max-width: 100%;
        text-align: center;
    }
    /* จัดผังเป็นคอลัมน์ + ทุกแถว center รอบแกนกลางเดียวกัน (แถว VIP ที่กว้างกว่าจะไม่เลยไปทางขวา)
       ทุกขนาดอิงตัวแปร --seat-size ซึ่ง JS ปรับให้ผังพอดีความกว้างเสมอ */
    .seat-map { display: inline-flex; flex-direction: column; align-items: center; min-width: min-content; --seat-size: 28px; }
    .seat-row { display: flex; align-items: center; gap: calc(var(--seat-size) * 0.14); margin-bottom: calc(var(--seat-size) * 0.14); }
    .row-label {
        width: calc(var(--seat-size) * 0.8); flex: 0 0 auto;
        text-align: center; font-weight: 600; color: #6c757d;
        font-size: calc(var(--seat-size) * 0.42);
    }
    .seat {
        width: var(--seat-size); height: var(--seat-size); flex: 0 0 auto; padding: 0;
        border-radius: 5px; font-size: calc(var(--seat-size) * 0.42); line-height: 1;
        border: 1px solid transparent; cursor: pointer;
    }
    /* ช่องว่างในแถว (ทางเดินกลาง / จับคู่ VIP) */
    .seat.gap { margin-left: calc(var(--seat-size) * 0.85); }
    /* เว้นระยะระหว่างโซน */
    .seat-row.zone-start { margin-top: calc(var(--seat-size) * 0.85); }

    /* สีที่นั่งตามโซน (ใช้ตัวแปร --zone จาก inline style ของแต่ละที่นั่ง) */
    .seat.available { background: #fff; border-color: var(--zone, #198754); color: var(--zone, #198754); }
    .seat.selected { background: #0d6efd; border-color: #0d6efd; color: #fff; }
    .seat.booked { background: #dee2e6; border-color: #ced4da; color: #adb5bd; cursor: not-allowed; }

    /* hover เฉพาะเครื่องที่มีเมาส์จริง (กัน :hover ค้างบนจอสัมผัส) และไม่ทับที่นั่งที่เลือกอยู่ */
    @media (hover: hover) {
        .seat.available:not(.selected):hover { background: var(--zone, #198754); color: #fff; }
    }

    /* legend */
    .legend-box { width: 16px; height: 16px; border-radius: 4px; display: inline-block; vertical-align: middle; }
</style>
@endpush

@section('content')
    <a href="{{ route('booking.index') }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left"></i> กลับไปเลือกรอบฉาย
    </a>

    <div class="row g-4">
        {{-- ผังที่นั่ง --}}
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ $showtime->movie->title }}</h5>
                    <p class="text-muted mb-3 small">
                        <i class="bi bi-building"></i> {{ $showtime->cinema->name }} &nbsp;|&nbsp;
                        <i class="bi bi-clock"></i> {{ $showtime->show_time->format('d/m/Y H:i') }} &nbsp;|&nbsp;
                        <i class="bi bi-cash"></i> ราคาเริ่มต้น {{ number_format((float) $showtime->price) }} บาท (ต่างกันตามโซน)
                    </p>

                    <div class="screen mb-5">จอภาพยนตร์</div>

                    <div class="seat-map-wrapper" id="seatMapWrapper">
                        <div class="seat-map" id="seatMap">
                            @foreach ($seatRows as $row)
                                <div class="seat-row {{ $row['zone_start'] ? 'zone-start' : '' }}">
                                    <span class="row-label">{{ $row['label'] }}</span>
                                    @foreach ($row['seats'] as $seat)
                                        @php
                                            $isBooked = in_array($seat, $bookedSeats, true);
                                            $pos = $loop->iteration;
                                            // ช่องว่าง: VIP = จับคู่ (เว้นก่อนที่นั่งเลขคี่) / โซนอื่น = ทางเดินกลางที่ $aislePos
                                            $hasGap = $row['pairs'] ? ($pos > 1 && $pos % 2 === 1) : ($pos === $aislePos);
                                        @endphp
                                        <button type="button"
                                            class="seat {{ $isBooked ? 'booked' : 'available' }} {{ $hasGap ? 'gap' : '' }}"
                                            data-seat="{{ $seat }}"
                                            data-price="{{ $row['price'] }}"
                                            title="{{ $seat }} — {{ $row['zone'] }} {{ number_format($row['price']) }} บาท"
                                            style="--zone: {{ $row['color'] }}"
                                            @disabled($isBooked)>
                                            {{ $pos }}
                                        </button>
                                    @endforeach
                                    {{-- spacer ขวา (กว้างเท่าป้ายแถว) ให้ที่นั่งอยู่กึ่งกลางพอดี --}}
                                    <span class="row-label" aria-hidden="true"></span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- คำอธิบายโซน + สถานะ --}}
                    <div class="d-flex flex-wrap gap-3 mt-3 small">
                        @foreach ($zones as $z)
                            <span>
                                <span class="legend-box" style="border:2px solid {{ $z['color'] }}; background:#fff;"></span>
                                {{ $z['zone'] }} — {{ number_format($z['price']) }} บาท
                            </span>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                        <span><span class="legend-box" style="background:#0d6efd;"></span> ที่เลือก</span>
                        <span><span class="legend-box" style="background:#dee2e6;"></span> จองแล้ว</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- สรุปการจอง --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">สรุปการจอง</h5>
                    <p class="mb-1">ที่นั่งที่เลือก:</p>
                    <p id="selectedSeats" class="fw-bold text-primary">-</p>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>จำนวน</span>
                        <span><span id="seatCount">0</span> ที่นั่ง</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold mt-2">
                        <span>รวม</span>
                        <span><span id="totalPrice">0</span> บาท</span>
                    </div>
                    <button id="btnConfirm" class="btn btn-success w-100 mt-3" disabled>
                        <i class="bi bi-check-circle"></i> ยืนยันการจอง
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="appToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastBody"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        const storeUrl = "{{ route('booking.store', $showtime) }}";
        const toast = new bootstrap.Toast('#appToast', { delay: 3500 });

        function showToast(message, isError = false) {
            $('#appToast').removeClass('bg-success bg-danger')
                          .addClass(isError ? 'bg-danger' : 'bg-success');
            $('#toastBody').text(message);
            toast.show();
        }

        // ปรับขนาดที่นั่งให้ผังพอดีความกว้างเสมอ — ไม่มีแถบเลื่อน (ทุกมิติอิง --seat-size จึงย่อได้เป็นเส้นตรง)
        const seatWrapperEl = document.getElementById('seatMapWrapper');
        const seatMapEl = document.getElementById('seatMap');
        function fitSeatMap() {
            if (!seatWrapperEl || !seatMapEl) return;
            const REF = 28; // ขนาดอ้างอิง
            const avail = seatWrapperEl.clientWidth;
            if (avail < 80) return; // layout ยังไม่นิ่ง — รอ ResizeObserver เรียกอีกครั้ง
            seatMapEl.style.setProperty('--seat-size', REF + 'px');
            const natural = seatMapEl.getBoundingClientRect().width;
            const size = natural > avail ? Math.max(9, Math.floor(REF * avail / natural)) : REF;
            seatMapEl.style.setProperty('--seat-size', size + 'px');
        }
        // ResizeObserver จับตอนกล่องได้ขนาดจริง/เปลี่ยนขนาด (ทนต่อ timing กว่า event load)
        if (window.ResizeObserver && seatWrapperEl) {
            new ResizeObserver(fitSeatMap).observe(seatWrapperEl);
        }
        $(window).on('resize load', fitSeatMap);
        fitSeatMap();

        // รวมยอดจากราคาจริงของแต่ละที่นั่ง (data-price ตามโซน) — ราคาเป็นจำนวนเต็ม
        function refreshSummary() {
            let seats = [];
            let total = 0;
            $('.seat.selected').each(function () {
                seats.push($(this).data('seat'));
                total += parseInt($(this).data('price'), 10);
            });
            $('#selectedSeats').text(seats.length ? seats.join(', ') : '-');
            $('#seatCount').text(seats.length);
            $('#totalPrice').text(total.toLocaleString());
            $('#btnConfirm').prop('disabled', seats.length === 0);
        }

        // เลือก/ยกเลิกที่นั่ง (เฉพาะที่ว่าง)
        $('.seat.available').on('click', function () {
            $(this).toggleClass('selected');
            refreshSummary();
        });

        // ยืนยันการจอง
        $('#btnConfirm').on('click', function () {
            const seats = $('.seat.selected').map(function () { return $(this).data('seat'); }).get();
            if (seats.length === 0) return;

            $(this).prop('disabled', true);

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: { seats: seats },
            }).done(function (res) {
                res.seats.forEach(function (seat) {
                    $('.seat[data-seat="' + seat + '"]')
                        .removeClass('available selected').addClass('booked')
                        .prop('disabled', true).off('click');
                });
                refreshSummary();
                showToast(res.message);
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    showToast(xhr.responseJSON.message, true);
                    (xhr.responseJSON.taken || []).forEach(function (seat) {
                        $('.seat[data-seat="' + seat + '"]')
                            .removeClass('available selected').addClass('booked')
                            .prop('disabled', true).off('click');
                    });
                    refreshSummary();
                } else {
                    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', true);
                }
            }).always(function () {
                $('#btnConfirm').prop('disabled', $('.seat.selected').length === 0);
            });
        });
    });
</script>
@endpush
