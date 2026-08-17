@extends('layouts.app')

@section('title', 'โรงภาพยนตร์')

@push('styles')
    <style>
        /* กันชื่อโรงยาวๆ ไม่มีเว้นวรรคดันตารางล้นจอ (ทั้งตารางหลักและแถวย่อย) */
        #cinemasTable td.name-cell {
            white-space: normal;
            max-width: 320px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        #cinemasTable ul.dtr-details {
            width: 100%;
            margin: 0;
        }

        #cinemasTable ul.dtr-details>li {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        #cinemasTable .dtr-data {
            word-break: break-word;
            overflow-wrap: anywhere;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"> จัดการโรงภาพยนตร์</h2>
        <button type="button" class="btn btn-primary" id="btnAdd">
            <i class="bi bi-plus-lg"></i> เพิ่มโรงภาพยนตร์
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="cinemasTable" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ชื่อโรงภาพยนตร์</th>
                        <th>จำนวนที่นั่ง</th>
                        <th>เพิ่มเมื่อ</th>
                        <th class="text-end">จัดการ</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- ===== Modal เพิ่ม/แก้ไขโรงภาพยนตร์ ===== --}}
    <div class="modal fade" id="cinemaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="cinemaForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cinemaModalLabel">เพิ่มโรงภาพยนตร์</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="cinema_id">

                        <div class="mb-3">
                            <label for="name" class="form-label">ชื่อโรงภาพยนตร์ <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name">
                            <div class="invalid-feedback" data-field="name"></div>
                        </div>

                        <div class="mb-3">
                            <label for="total_seats" class="form-label">จำนวนที่นั่ง <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="total_seats" name="total_seats" min="1">
                            <div class="invalid-feedback" data-field="total_seats"></div>
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
                    ต้องการลบ "<strong id="deleteName"></strong>" ใช่หรือไม่?
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
        $(function() {
            // ===== DataTable =====
            const table = $('#cinemasTable').DataTable({
                processing: true,
                responsive: true,
                autoWidth: false,
                ajax: "{{ route('cinemas.data') }}",
                order: [
                    [3, 'desc']
                ],
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'name',
                        className: 'name-cell',
                        responsivePriority: 1,
                        render: function(text) {
                            return $('<div>').text(text || '').html(); // escape HTML
                        }
                    },
                    {
                        data: 'total_seats',
                        className: 'text-center'
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2,
                        className: 'text-end',
                        render: function(id, type, row) {
                            return '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="' +
                                id + '">' +
                                '<i class="bi bi-pencil"></i></button> ' +
                                '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' +
                                id +
                                '" data-name="' + $('<div>').text(row.name).html() + '">' +
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
                    emptyTable: "ยังไม่มีข้อมูลโรงภาพยนตร์",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                }
            });

            const cinemaModal = new bootstrap.Modal('#cinemaModal');
            const deleteModal = new bootstrap.Modal('#deleteModal');
            const toast = new bootstrap.Toast('#appToast', {
                delay: 3000
            });

            function showToast(message, isError = false) {
                $('#appToast').removeClass('bg-success bg-danger')
                    .addClass(isError ? 'bg-danger' : 'bg-success');
                $('#toastBody').text(message);
                toast.show();
            }

            function clearErrors() {
                $('#cinemaForm .form-control').removeClass('is-invalid');
                $('#cinemaForm .invalid-feedback').text('');
            }

            function showErrors(errors) {
                $.each(errors, function(field, messages) {
                    $('#' + field).addClass('is-invalid');
                    $('.invalid-feedback[data-field="' + field + '"]').text(messages[0]);
                });
            }

            // ===== เปิด Modal เพิ่ม =====
            $('#btnAdd').on('click', function() {
                clearErrors();
                $('#cinemaForm')[0].reset();
                $('#cinema_id').val('');
                $('#cinemaModalLabel').text('เพิ่มโรงภาพยนตร์');
                cinemaModal.show();
            });

            // ===== เปิด Modal แก้ไข (ดึงข้อมูลผ่าน AJAX) =====
            $('#cinemasTable').on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                clearErrors();
                $.get('/cinemas/' + id, function(cinema) {
                    $('#cinema_id').val(cinema.id);
                    $('#name').val(cinema.name);
                    $('#total_seats').val(cinema.total_seats);
                    $('#cinemaModalLabel').text('แก้ไขโรงภาพยนตร์');
                    cinemaModal.show();
                }).fail(function() {
                    showToast('ไม่พบข้อมูลโรงภาพยนตร์', true);
                });
            });

            // ===== บันทึก (เพิ่ม/แก้ไข) ผ่าน AJAX =====
            $('#cinemaForm').on('submit', function(e) {
                e.preventDefault();
                clearErrors();

                const id = $('#cinema_id').val();
                const isEdit = !!id;
                const url = isEdit ? '/cinemas/' + id : '/cinemas';
                const method = isEdit ? 'PUT' : 'POST';

                const data = {
                    name: $('#name').val(),
                    total_seats: $('#total_seats').val(),
                };

                $('#btnSave').prop('disabled', true);

                $.ajax({
                    url: url,
                    method: method,
                    data: data,
                }).done(function(res) {
                    cinemaModal.hide();
                    table.ajax.reload(null, false);
                    showToast(res.message);
                }).fail(function(xhr) {
                    if (xhr.status === 422) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', true);
                    }
                }).always(function() {
                    $('#btnSave').prop('disabled', false);
                });
            });

            // ===== เปิด Modal ลบ =====
            $('#cinemasTable').on('click', '.btn-delete', function() {
                $('#delete_id').val($(this).data('id'));
                $('#deleteName').text($(this).data('name'));
                deleteModal.show();
            });

            // ===== ยืนยันลบ (soft delete) ผ่าน AJAX =====
            $('#btnConfirmDelete').on('click', function() {
                const id = $('#delete_id').val();
                $(this).prop('disabled', true);

                $.ajax({
                    url: '/cinemas/' + id,
                    method: 'DELETE',
                }).done(function(res) {
                    deleteModal.hide();
                    table.ajax.reload(null, false);
                    showToast(res.message);
                }).fail(function() {
                    showToast('ลบไม่สำเร็จ กรุณาลองใหม่', true);
                }).always(function() {
                    $('#btnConfirmDelete').prop('disabled', false);
                });
            });
        });
    </script>
@endpush
