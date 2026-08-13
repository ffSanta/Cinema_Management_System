@extends('layouts.app')

@section('title', 'ภาพยนตร์')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-camera-reels"></i> จัดการภาพยนตร์</h2>
        {{-- ปุ่มเพิ่มหนัง (จะต่อ Modal ในฟีเจอร์ถัดไป) --}}
        <button type="button" class="btn btn-primary" disabled>
            <i class="bi bi-plus-lg"></i> เพิ่มภาพยนตร์
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="moviesTable" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>โปสเตอร์</th>
                        <th>ชื่อเรื่อง</th>
                        <th>ความยาว (นาที)</th>
                        <th>เรื่องย่อ</th>
                        <th>เพิ่มเมื่อ</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#moviesTable').DataTable({
            processing: true,
            ajax: "{{ route('movies.data') }}",
            order: [[5, 'desc']],
            columns: [
                { data: 'id' },
                {
                    data: 'poster_url',
                    orderable: false,
                    searchable: false,
                    render: function (url) {
                        return '<img src="' + url + '" alt="poster" ' +
                               'style="width:50px;height:75px;object-fit:cover;border-radius:4px;">';
                    }
                },
                { data: 'title' },
                { data: 'duration_mins', className: 'text-center' },
                {
                    data: 'synopsis',
                    render: function (text) {
                        if (!text) return '';
                        // ตัดเรื่องย่อให้สั้นลงในตาราง
                        return text.length > 60 ? text.substring(0, 60) + '…' : text;
                    }
                },
                { data: 'created_at' },
            ],
            language: {
                // ข้อความภาษาไทยสำหรับ DataTables
                processing: "กำลังโหลด...",
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                infoEmpty: "ไม่มีข้อมูล",
                infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                emptyTable: "ยังไม่มีข้อมูลภาพยนตร์",
                paginate: {
                    first: "หน้าแรก",
                    last: "หน้าสุดท้าย",
                    next: "ถัดไป",
                    previous: "ก่อนหน้า"
                }
            }
        });
    });
</script>
@endpush
