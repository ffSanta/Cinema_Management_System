@extends('layouts.app')

@section('title', 'ประวัติการจอง')

@push('styles')
    <style>
        #myBookingsTable td.wrap-cell {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        #myBookingsTable ul.dtr-details>li {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
    </style>
@endpush

@section('content')
    <h2 class="mb-4"> ประวัติการจองของฉัน</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="myBookingsTable" class="table table-striped align-middle w-100">
                <thead>
                    <tr>
                        <th>ภาพยนตร์</th>
                        <th>โรง</th>
                        <th>รอบฉาย</th>
                        <th>ที่นั่ง</th>
                        <th>สถานะ</th>
                        <th class="text-end">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td class="wrap-cell">{{ $booking->showtime?->movie?->title ?? '-' }}</td>
                            <td class="wrap-cell">{{ $booking->showtime?->cinema?->name ?? '-' }}</td>
                            <td>{{ $booking->showtime?->show_time?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td><span class="badge bg-primary">{{ $booking->seat_number }}</span></td>
                            <td>
                                @if ($booking->trashed())
                                    <span class="badge bg-secondary">ยกเลิกแล้ว</span>
                                @else
                                    <span class="badge bg-success">จองแล้ว</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($booking->trashed())
                                    <span class="text-muted">-</span>
                                @else
                                    <button class="btn btn-sm btn-outline-danger btn-cancel" data-id="{{ $booking->id }}"
                                        data-info="{{ ($booking->showtime->movie->title ?? '-') . ' ที่นั่ง ' . $booking->seat_number }}">
                                        <i class="bi bi-x-circle"></i> ยกเลิก
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Modal ยืนยันการยกเลิก ===== --}}
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> ยืนยันการยกเลิก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ต้องการยกเลิกการจอง "<strong id="cancelInfo"></strong>" ใช่หรือไม่?
                    <div class="text-muted small mt-2">ที่นั่งจะกลับมาว่างให้ผู้อื่นจองได้ทันที</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmCancel">
                        <i class="bi bi-x-circle"></i> ยกเลิกการจอง
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
        $(function() {
            const table = $('#myBookingsTable').DataTable({
                responsive: true,
                order: [],
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    },
                    {
                        responsivePriority: 2,
                        targets: 5
                    },
                    {
                        orderable: false,
                        targets: 5
                    },
                ],
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    infoEmpty: "ไม่มีข้อมูล",
                    emptyTable: "ยังไม่มีการจอง",
                    zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                }
            });

            const cancelModal = new bootstrap.Modal('#cancelModal');
            const toast = new bootstrap.Toast('#appToast', {
                delay: 3000
            });
            let currentRow = null;
            let currentId = null;

            function showToast(message, isError = false) {
                $('#appToast').removeClass('bg-success bg-danger')
                    .addClass(isError ? 'bg-danger' : 'bg-success');
                $('#toastBody').text(message);
                toast.show();
            }

            // เปิด modal ยืนยัน
            $('#myBookingsTable').on('click', '.btn-cancel', function() {
                currentId = $(this).data('id');
                currentRow = $(this).closest('tr');
                $('#cancelInfo').text($(this).data('info'));
                cancelModal.show();
            });

            // ยืนยันยกเลิก (soft delete ผ่าน AJAX)
            $('#btnConfirmCancel').on('click', function() {
                $(this).prop('disabled', true);

                $.ajax({
                    url: '/booking/' + currentId + '/cancel',
                    method: 'DELETE',
                }).done(function(res) {
                    cancelModal.hide();
                    // อัปเดตแถว: สถานะ → ยกเลิกแล้ว, เอาปุ่มออก
                    currentRow.find('td').eq(4).html(
                        '<span class="badge bg-secondary">ยกเลิกแล้ว</span>');
                    currentRow.find('td').eq(5).html('<span class="text-muted">-</span>');
                    table.row(currentRow).invalidate('dom').draw(false);
                    showToast(res.message);
                }).fail(function() {
                    showToast('ยกเลิกไม่สำเร็จ กรุณาลองใหม่', true);
                }).always(function() {
                    $('#btnConfirmCancel').prop('disabled', false);
                });
            });
        });
    </script>
@endpush
