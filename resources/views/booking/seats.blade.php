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

    /* กล่องผังที่นั่ง — เลื่อนแนวนอนในกล่องตัวเอง ไม่ดันหน้าให้ล้นจอ */
    .seat-map-wrapper {
        overflow-x: auto;
        max-width: 100%;
        padding-bottom: .5rem;
        text-align: center;
    }
    .seat-map { display: inline-block; min-width: min-content; text-align: left; }
    .seat-row { display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
    .row-label { width: 22px; text-align: center; font-weight: 600; color: #6c757d; flex: 0 0 auto; }
    .seat {
        width: 28px; height: 28px; flex: 0 0 auto;
        border-radius: 5px; font-size: .65rem; line-height: 1;
        border: 1px solid transparent; cursor: pointer;
    }
    /* ช่องทางเดินกลาง (aisle) — เว้นระยะก่อนที่นั่งที่ 11 */
    .seat.aisle { margin-left: 26px; }

    /* ราคาแต่ละแถว (ท้ายแถว) */
    .row-price {
        margin-left: 12px; flex: 0 0 auto;
        font-size: .72rem; font-weight: 600; color: #495057; white-space: nowrap;
    }

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

    /* จอเล็ก (<576px): ย่อที่นั่งให้เห็นได้มากขึ้น ลดการเลื่อน */
    @media (max-width: 575.98px) {
        .seat { width: 22px; height: 22px; font-size: .55rem; }
        .seat-row { gap: 3px; }
        .seat.aisle { margin-left: 18px; }
        .row-label { width: 18px; }
        .row-price { margin-left: 8px; font-size: .65rem; }
    }
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
                        <i class="bi bi-cash"></i> ราคาเริ่มต้น {{ number_format((float) $showtime->price, 2) }} บาท (ต่างกันตามโซน)
                    </p>

                    <div class="screen mb-5">จอภาพยนตร์</div>

                    <div class="seat-map-wrapper">
                        <div class="seat-map">
                            @foreach ($seatRows as $row)
                                <div class="seat-row">
                                    <span class="row-label">{{ $row['label'] }}</span>
                                    @foreach ($row['seats'] as $seat)
                                        @php $isBooked = in_array($seat, $bookedSeats, true); @endphp
                                        {{-- เว้นช่องทางเดินกลางก่อนที่นั่งที่ 11 --}}
                                        <button type="button"
                                            class="seat {{ $isBooked ? 'booked' : 'available' }} {{ $loop->iteration === 11 ? 'aisle' : '' }}"
                                            data-seat="{{ $seat }}"
                                            data-price="{{ $row['price'] }}"
                                            title="{{ $seat }} — {{ $row['zone'] }} {{ number_format($row['price'], 2) }} บาท"
                                            style="--zone: {{ $row['color'] }}"
                                            @disabled($isBooked)>
                                            {{ $loop->iteration }}
                                        </button>
                                    @endforeach
                                    <span class="row-price" style="color: {{ $row['color'] }}">
                                        {{ number_format($row['price'], 0) }}฿
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- คำอธิบายโซน + สถานะ --}}
                    <div class="d-flex flex-wrap gap-3 mt-3 small">
                        @foreach ($zones as $z)
                            <span>
                                <span class="legend-box" style="border:2px solid {{ $z['color'] }}; background:#fff;"></span>
                                {{ $z['zone'] }} — {{ number_format($z['price'], 2) }} บาท
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
                        <span><span id="totalPrice">0.00</span> บาท</span>
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

        // รวมยอดจากราคาจริงของแต่ละที่นั่ง (data-price ตามโซน)
        function refreshSummary() {
            let seats = [];
            let total = 0;
            $('.seat.selected').each(function () {
                seats.push($(this).data('seat'));
                total += parseFloat($(this).data('price'));
            });
            $('#selectedSeats').text(seats.length ? seats.join(', ') : '-');
            $('#seatCount').text(seats.length);
            $('#totalPrice').text(total.toFixed(2));
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
