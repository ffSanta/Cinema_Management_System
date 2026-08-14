@extends('layouts.app')

@section('title', 'รอบฉาย')

@push('styles')
<style>
    #showtimesTable td.wrap-cell {
        white-space: normal;
        max-width: 220px;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    #showtimesTable ul.dtr-details { width: 100%; margin: 0; }
    #showtimesTable ul.dtr-details > li {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    #showtimesTable .dtr-data { word-break: break-word; overflow-wrap: anywhere; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-clock"></i> จัดการรอบฉาย</h2>
        <button type="button" class="btn btn-primary" id="btnAdd">
            <i class="bi bi-plus-lg"></i> เพิ่มรอบฉาย
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="showtimesTable" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ภาพยนตร์</th>
                        <th>โรงภาพยนตร์</th>
                        <th>เวลาฉาย</th>
                        <th>ราคา (บาท)</th>
                        <th class="text-end">จัดการ</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- ===== Modal เพิ่ม/แก้ไขรอบฉาย ===== --}}
    <div class="modal fade" id="showtimeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="showtimeForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="showtimeModalLabel">เพิ่มรอบฉาย</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="showtime_id">

                        <div class="mb-3">
                            <label for="movie_id" class="form-label">ภาพยนตร์ <span class="text-danger">*</span></label>
                            <select class="form-select" id="movie_id" name="movie_id">
                                <option value="">-- เลือกภาพยนตร์ --</option>
                                @foreach ($movies as $movie)
                                    <option value="{{ $movie->id }}">{{ $movie->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="movie_id"></div>
                        </div>

                        <div class="mb-3">
                            <label for="cinema_id" class="form-label">โรงภาพยนตร์ <span class="text-danger">*</span></label>
                            <select class="form-select" id="cinema_id" name="cinema_id">
                                <option value="">-- เลือกโรงภาพยนตร์ --</option>
                                @foreach ($cinemas as $cinema)
                                    <option value="{{ $cinema->id }}">{{ $cinema->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="cinema_id"></div>
                        </div>

                        <div class="mb-3">
                            <label for="show_time" class="form-label">เวลาฉาย <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="show_time" name="show_time"
                                   min="{{ $minShowTime }}">
                            <div class="invalid-feedback" data-field="show_time"></div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">ราคา (บาท) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01">
                            <div class="invalid-feedback" data-field="price"></div>
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
                    ต้องการลบรอบฉาย "<strong id="deleteLabel"></strong>" ใช่หรือไม่?
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
        const table = $('#showtimesTable').DataTable({
            processing: true,
            responsive: true,
            autoWidth: false,
            ajax: "{{ route('showtimes.data') }}",
            order: [[3, 'asc']], // เรียงตามเวลาฉาย จากใกล้ที่สุดขึ้นก่อน
            columns: [
                { data: 'id' },
                { data: 'movie', className: 'wrap-cell', responsivePriority: 1,
                  render: function (t) { return $('<div>').text(t || '').html(); } },
                { data: 'cinema', className: 'wrap-cell',
                  render: function (t) { return $('<div>').text(t || '').html(); } },
                {
                    data: 'show_time',
                    // แสดงข้อความ display แต่เรียงลำดับด้วย timestamp (กันเรียงตาม string เพี้ยน)
                    render: function (data, type) {
                        return (type === 'sort' || type === 'type') ? data.timestamp : data.display;
                    }
                },
                { data: 'price', className: 'text-end' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 2,
                    className: 'text-end',
                    render: function (id, type, row) {
                        const label = row.movie + ' @ ' + row.cinema;
                        return '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="' + id + '">' +
                               '<i class="bi bi-pencil"></i></button> ' +
                               '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + id +
                               '" data-label="' + $('<div>').text(label).html() + '">' +
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
                emptyTable: "ยังไม่มีข้อมูลรอบฉาย",
                paginate: { first: "หน้าแรก", last: "หน้าสุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" }
            }
        });

        const showtimeModal = new bootstrap.Modal('#showtimeModal');
        const deleteModal = new bootstrap.Modal('#deleteModal');
        const toast = new bootstrap.Toast('#appToast', { delay: 4000 });

        function showToast(message, isError = false) {
            $('#appToast').removeClass('bg-success bg-danger')
                          .addClass(isError ? 'bg-danger' : 'bg-success');
            $('#toastBody').text(message);
            toast.show();
        }

        function clearErrors() {
            $('#showtimeForm .form-control, #showtimeForm .form-select').removeClass('is-invalid');
            $('#showtimeForm .invalid-feedback').text('');
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
            $('#showtimeForm')[0].reset();
            $('#showtime_id').val('');
            $('#showtimeModalLabel').text('เพิ่มรอบฉาย');
            showtimeModal.show();
        });

        // ===== เปิด Modal แก้ไข (ดึงข้อมูลผ่าน AJAX) =====
        $('#showtimesTable').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            clearErrors();
            $.get('/showtimes/' + id, function (s) {
                $('#showtime_id').val(s.id);
                $('#movie_id').val(s.movie_id);
                $('#cinema_id').val(s.cinema_id);
                $('#show_time').val(s.show_time);
                $('#price').val(s.price);
                $('#showtimeModalLabel').text('แก้ไขรอบฉาย');
                showtimeModal.show();
            }).fail(function () {
                showToast('ไม่พบข้อมูลรอบฉาย', true);
            });
        });

        // ===== บันทึก (เพิ่ม/แก้ไข) ผ่าน AJAX =====
        $('#showtimeForm').on('submit', function (e) {
            e.preventDefault();
            clearErrors();

            const id = $('#showtime_id').val();
            const isEdit = !!id;
            const url = isEdit ? '/showtimes/' + id : '/showtimes';
            const method = isEdit ? 'PUT' : 'POST';

            const data = {
                movie_id: $('#movie_id').val(),
                cinema_id: $('#cinema_id').val(),
                show_time: $('#show_time').val(),
                price: $('#price').val(),
            };

            $('#btnSave').prop('disabled', true);

            $.ajax({ url: url, method: method, data: data })
                .done(function (res) {
                    showtimeModal.hide();
                    table.ajax.reload(null, false);
                    showToast(res.message);
                })
                .fail(function (xhr) {
                    if (xhr.status === 422) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', true);
                    }
                })
                .always(function () {
                    $('#btnSave').prop('disabled', false);
                });
        });

        // ===== เปิด Modal ลบ =====
        $('#showtimesTable').on('click', '.btn-delete', function () {
            $('#delete_id').val($(this).data('id'));
            $('#deleteLabel').text($(this).data('label'));
            deleteModal.show();
        });

        // ===== ยืนยันลบ (soft delete) ผ่าน AJAX =====
        $('#btnConfirmDelete').on('click', function () {
            const id = $('#delete_id').val();
            $(this).prop('disabled', true);

            $.ajax({ url: '/showtimes/' + id, method: 'DELETE' })
                .done(function (res) {
                    deleteModal.hide();
                    table.ajax.reload(null, false);
                    showToast(res.message);
                })
                .fail(function () {
                    showToast('ลบไม่สำเร็จ กรุณาลองใหม่', true);
                })
                .always(function () {
                    $('#btnConfirmDelete').prop('disabled', false);
                });
        });
    });
</script>
@endpush
