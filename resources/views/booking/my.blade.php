@extends('layouts.app')

@section('title', 'การจองของฉัน')

@push('styles')
<style>
    #myBookingsTable td.wrap-cell { white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
    #myBookingsTable ul.dtr-details > li { white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
</style>
@endpush

@section('content')
    <h2 class="mb-4"><i class="bi bi-journal-check"></i> การจองของฉัน</h2>

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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td class="wrap-cell">{{ $booking->showtime->movie->title ?? '-' }}</td>
                            <td class="wrap-cell">{{ $booking->showtime->cinema->name ?? '-' }}</td>
                            <td>{{ optional($booking->showtime->show_time)->format('d/m/Y H:i') ?? '-' }}</td>
                            <td><span class="badge bg-primary">{{ $booking->seat_number }}</span></td>
                            <td>
                                @if ($booking->status === 'booked')
                                    <span class="badge bg-success">จองแล้ว</span>
                                @else
                                    <span class="badge bg-secondary">ยกเลิก</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#myBookingsTable').DataTable({
            responsive: true,
            order: [],
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                infoEmpty: "ไม่มีข้อมูล",
                emptyTable: "ยังไม่มีการจอง",
                zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                paginate: { first: "หน้าแรก", last: "หน้าสุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" }
            }
        });
    });
</script>
@endpush
