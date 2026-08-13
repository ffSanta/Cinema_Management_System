@extends('layouts.app')

@section('title', 'ภาพยนตร์')

@push('styles')
<style>
    /* ให้เรื่องย่อขึ้นบรรทัดใหม่ได้ (ข้อความไทยไม่มีเว้นวรรค ต้องใช้ break-word) */
    #moviesTable td.synopsis-cell {
        white-space: normal;
        max-width: 260px;
        min-width: 160px;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    /* ไม่ให้ทั้งตารางดันกว้างล้นจอ */
    #moviesTable {
        table-layout: auto;
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-camera-reels"></i> จัดการภาพยนตร์</h2>
        <button type="button" class="btn btn-primary" id="btnAdd">
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
                        <th class="text-end">จัดการ</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- ===== Modal เพิ่ม/แก้ไขภาพยนตร์ ===== --}}
    <div class="modal fade" id="movieModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="movieForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="movieModalLabel">เพิ่มภาพยนตร์</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        {{-- เก็บ id ตอนแก้ไข --}}
                        <input type="hidden" id="movie_id">

                        <div class="mb-3">
                            <label for="title" class="form-label">ชื่อเรื่อง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title">
                            <div class="invalid-feedback" data-field="title"></div>
                        </div>

                        <div class="mb-3">
                            <label for="duration_mins" class="form-label">ความยาว (นาที) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="duration_mins" name="duration_mins" min="1">
                            <div class="invalid-feedback" data-field="duration_mins"></div>
                        </div>

                        <div class="mb-3">
                            <label for="synopsis" class="form-label">เรื่องย่อ <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="synopsis" name="synopsis" rows="4"></textarea>
                            <div class="invalid-feedback" data-field="synopsis"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" id="btnSave">
                            <i class="bi bi-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== Modal ยืนยันการลบ ===== --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ต้องการลบ "<strong id="deleteTitle"></strong>" ใช่หรือไม่?
                    <input type="hidden" id="delete_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                        <i class="bi bi-trash"></i> ลบ
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Toast แจ้งเตือน ===== --}}
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
        // ===== DataTable =====
        const table = $('#moviesTable').DataTable({
            processing: true,
            responsive: true, // ยุบคอลัมน์ที่ไม่สำคัญเป็นแถวย่อยบนจอเล็ก
            autoWidth: false,
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
                { data: 'title', responsivePriority: 1 }, // สำคัญสุด แสดงเสมอ
                { data: 'duration_mins', className: 'text-center' },
                {
                    data: 'synopsis',
                    className: 'synopsis-cell',
                    responsivePriority: 10001, // ยุบก่อนใคร (ยาวสุด)
                    render: function (text) {
                        if (!text) return '';
                        // escape HTML แล้วปล่อยให้ CSS ตัดบรรทัดเอง
                        return $('<div>').text(text).html();
                    }
                },
                { data: 'created_at' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 2, // ปุ่มจัดการ แสดงเสมอ
                    className: 'text-end',
                    render: function (id, type, row) {
                        return '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="' + id + '">' +
                               '<i class="bi bi-pencil"></i></button> ' +
                               '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + id +
                               '" data-title="' + $('<div>').text(row.title).html() + '">' +
                               '<i class="bi bi-trash"></i></button>';
                    }
                },
            ],
            language: {
                processing: "กำลังโหลด...",
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                infoEmpty: "ไม่มีข้อมูล",
                infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                emptyTable: "ยังไม่มีข้อมูลภาพยนตร์",
                paginate: { first: "หน้าแรก", last: "หน้าสุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" }
            }
        });

        const movieModal = new bootstrap.Modal('#movieModal');
        const deleteModal = new bootstrap.Modal('#deleteModal');
        const toast = new bootstrap.Toast('#appToast', { delay: 3000 });

        // ===== ฟังก์ชันช่วยเหลือ =====
        function showToast(message, isError = false) {
            $('#appToast').removeClass('bg-success bg-danger')
                          .addClass(isError ? 'bg-danger' : 'bg-success');
            $('#toastBody').text(message);
            toast.show();
        }

        function clearErrors() {
            $('#movieForm .form-control').removeClass('is-invalid');
            $('#movieForm .invalid-feedback').text('');
        }

        function showErrors(errors) {
            $.each(errors, function (field, messages) {
                $('#' + field).addClass('is-invalid');
                $('.invalid-feedback[data-field="' + field + '"]').text(messages[0]);
            });
        }

        // ===== เปิด Modal เพิ่ม =====
        $('#btnAdd').on('click', function () {
            clearErrors();
            $('#movieForm')[0].reset();
            $('#movie_id').val('');
            $('#movieModalLabel').text('เพิ่มภาพยนตร์');
            movieModal.show();
        });

        // ===== เปิด Modal แก้ไข (ดึงข้อมูลผ่าน AJAX) =====
        $('#moviesTable').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            clearErrors();
            $.get('/movies/' + id, function (movie) {
                $('#movie_id').val(movie.id);
                $('#title').val(movie.title);
                $('#duration_mins').val(movie.duration_mins);
                $('#synopsis').val(movie.synopsis);
                $('#movieModalLabel').text('แก้ไขภาพยนตร์');
                movieModal.show();
            }).fail(function () {
                showToast('ไม่พบข้อมูลภาพยนตร์', true);
            });
        });

        // ===== บันทึก (เพิ่ม/แก้ไข) ผ่าน AJAX =====
        $('#movieForm').on('submit', function (e) {
            e.preventDefault();
            clearErrors();

            const id = $('#movie_id').val();
            const isEdit = !!id;
            const url = isEdit ? '/movies/' + id : '/movies';
            const method = isEdit ? 'PUT' : 'POST';

            const data = {
                title: $('#title').val(),
                duration_mins: $('#duration_mins').val(),
                synopsis: $('#synopsis').val(),
            };

            $('#btnSave').prop('disabled', true);

            $.ajax({
                url: url,
                method: method,
                data: data,
            }).done(function (res) {
                movieModal.hide();
                table.ajax.reload(null, false); // reload ตารางโดยไม่รีเซ็ตหน้า
                showToast(res.message);
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', true);
                }
            }).always(function () {
                $('#btnSave').prop('disabled', false);
            });
        });

        // ===== เปิด Modal ลบ =====
        $('#moviesTable').on('click', '.btn-delete', function () {
            $('#delete_id').val($(this).data('id'));
            $('#deleteTitle').text($(this).data('title'));
            deleteModal.show();
        });

        // ===== ยืนยันลบ (soft delete) ผ่าน AJAX =====
        $('#btnConfirmDelete').on('click', function () {
            const id = $('#delete_id').val();
            $(this).prop('disabled', true);

            $.ajax({
                url: '/movies/' + id,
                method: 'DELETE',
            }).done(function (res) {
                deleteModal.hide();
                table.ajax.reload(null, false);
                showToast(res.message);
            }).fail(function () {
                showToast('ลบไม่สำเร็จ กรุณาลองใหม่', true);
            }).always(function () {
                $('#btnConfirmDelete').prop('disabled', false);
            });
        });
    });
</script>
@endpush
