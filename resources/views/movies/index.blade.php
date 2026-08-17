@extends('layouts.app')

@section('title', 'ภาพยนตร์')

@push('styles')
    <style>
        /* ให้ชื่อเรื่อง/เรื่องย่อขึ้นบรรทัดใหม่ได้ (ข้อความไทยไม่มีเว้นวรรค ต้องใช้ break-word) */
        #moviesTable td.title-cell {
            white-space: normal;
            max-width: 200px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        /* เรื่องย่อ: บรรทัดเดียว ตัดที่เหลือด้วย … (ดูเต็มที่ปุ่มรายละเอียด) */
        #moviesTable .synopsis-ellipsis {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ไม่ให้ทั้งตารางดันกว้างล้นจอ */
        #moviesTable {
            table-layout: auto;
        }

        /* แถวย่อย (child row) ตอนจอ < 768px ก็ต้องตัดบรรทัดข้อความยาวไม่มีเว้นวรรคด้วย
                                   ไม่งั้น เช่น "dddd...60ตัว" จะดันแถวย่อยกว้างล้นจอ */
        #moviesTable ul.dtr-details {
            width: 100%;
            margin: 0;
        }

        #moviesTable ul.dtr-details>li {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        #moviesTable .dtr-data {
            word-break: break-word;
            overflow-wrap: anywhere;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ auth()->user()->role === 'admin' ? 'จัดการภาพยนตร์' : 'ภาพยนตร์' }}</h2>
        @if (auth()->user()->role === 'admin')
            <button type="button" class="btn btn-primary" id="btnAdd">
                <i class="bi bi-plus-lg"></i> เพิ่มภาพยนตร์
            </button>
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="moviesTable" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>โปสเตอร์</th>
                        <th>ชื่อเรื่อง</th>
                        <th>ความยาว</th>
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
                            <label for="duration_mins" class="form-label">ความยาว (นาที) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="duration_mins" name="duration_mins"
                                min="1">
                            <div class="invalid-feedback" data-field="duration_mins"></div>
                        </div>

                        <div class="mb-3">
                            <label for="synopsis" class="form-label">เรื่องย่อ <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="synopsis" name="synopsis" rows="4"></textarea>
                            <div class="invalid-feedback" data-field="synopsis"></div>
                        </div>

                        <div class="mb-3">
                            <label for="poster_image" class="form-label">
                                โปสเตอร์ <small class="text-muted">(jpg/png/webp, ไม่เกิน 2MB)</small>
                            </label>
                            <input type="file" class="form-control" id="poster_image" name="poster_image"
                                accept="image/jpeg,image/png,image/webp">
                            <div class="invalid-feedback" data-field="poster_image"></div>
                            {{-- พรีวิวรูป (รูปเดิมตอนแก้ไข หรือรูปที่เพิ่งเลือก) --}}
                            <div class="mt-2">
                                <img id="posterPreview" src="" alt="พรีวิวโปสเตอร์" class="img-thumbnail d-none"
                                    style="max-height:180px; max-width:100%;">
                            </div>
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

    {{-- ===== Modal รายละเอียดภาพยนตร์ (show) ===== --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รายละเอียดภาพยนตร์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="detailPoster" src="" alt="โปสเตอร์" class="img-fluid rounded shadow-sm"
                            style="max-height:280px;">
                    </div>
                    <h4 id="detailTitle" class="text-center mb-1" style="word-break:break-word;"></h4>
                    <p class="text-muted text-center mb-3">
                        <span id="detailDuration"></span> นาที
                    </p>
                    <hr>
                    <h6 class="fw-bold">เรื่องย่อ</h6>
                    <p id="detailSynopsis" class="mb-0" style="white-space:pre-line; word-break:break-word;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
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
            const isAdmin = @json(auth()->user()->role === 'admin'); // แสดงปุ่มแก้ไข/ลบเฉพาะ admin

            // ===== DataTable =====
            const table = $('#moviesTable').DataTable({
                processing: true,
                responsive: true, // ยุบคอลัมน์ที่ไม่สำคัญเป็นแถวย่อยบนจอเล็ก
                autoWidth: false,
                ajax: "{{ route('movies.data') }}",
                order: [
                    [5, 'desc']
                ],
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'poster_url',
                        orderable: false,
                        searchable: false,
                        render: function(url) {
                            return '<img src="' + url + '" alt="poster" ' +
                                'style="width:50px;height:75px;object-fit:cover;border-radius:4px;">';
                        }
                    },
                    {
                        data: 'title',
                        className: 'title-cell',
                        responsivePriority: 1, // สำคัญสุด แสดงเสมอ
                        render: function(text) {
                            return $('<div>').text(text || '').html(); // escape + ให้ CSS ตัดบรรทัด
                        }
                    },
                    {
                        data: 'duration_mins',
                        className: 'text-center'
                    },
                    {
                        data: 'synopsis',
                        responsivePriority: 10001, // ยุบก่อนใคร (ยาวสุด)
                        render: function(text) {
                            if (!text) return '';
                            // บรรทัดเดียว ตัดด้วย … (escape กัน HTML)
                            return '<div class="synopsis-ellipsis">' + $('<div>').text(text)
                                .html() + '</div>';
                        }
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2, // ปุ่มจัดการ แสดงเสมอ
                        className: 'text-end',
                        render: function(id, type, row) {
                            let html =
                                '<button class="btn btn-sm btn-outline-info btn-detail" data-id="' +
                                id + '" title="รายละเอียด">รายละเอียด</button>';
                            if (isAdmin) {
                                html +=
                                    ' <button class="btn btn-sm btn-outline-primary btn-edit" data-id="' +
                                    id + '"><i class="bi bi-pencil"></i></button> ' +
                                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' +
                                    id +
                                    '" data-title="' + $('<div>').text(row.title).html() + '">' +
                                    '<i class="bi bi-trash"></i></button>';
                            }
                            return html;
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
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                }
            });

            const movieModal = new bootstrap.Modal('#movieModal');
            const deleteModal = new bootstrap.Modal('#deleteModal');
            const detailModal = new bootstrap.Modal('#detailModal');
            const toast = new bootstrap.Toast('#appToast', {
                delay: 3000
            });

            // ===== ปุ่มรายละเอียด — แสดงรูป/ชื่อ/เรื่องย่อ (show) =====
            $('#moviesTable').on('click', '.btn-detail', function() {
                const id = $(this).data('id');
                $.get('/movies/' + id, function(m) {
                    $('#detailPoster').attr('src', m.poster_url);
                    $('#detailTitle').text(m.title);
                    $('#detailDuration').text(m.duration_mins);
                    $('#detailSynopsis').text(m.synopsis);
                    detailModal.show();
                }).fail(function() {
                    showToast('ไม่พบข้อมูลภาพยนตร์', true);
                });
            });

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
                $.each(errors, function(field, messages) {
                    $('#' + field).addClass('is-invalid');
                    $('.invalid-feedback[data-field="' + field + '"]').text(messages[0]);
                });
            }

            // ===== เปิด Modal เพิ่ม =====
            $('#btnAdd').on('click', function() {
                clearErrors();
                $('#movieForm')[0].reset();
                $('#movie_id').val('');
                $('#posterPreview').addClass('d-none').attr('src', ''); // ล้างพรีวิว
                $('#movieModalLabel').text('เพิ่มภาพยนตร์');
                movieModal.show();
            });

            // ===== เปิด Modal แก้ไข (ดึงข้อมูลผ่าน AJAX) =====
            $('#moviesTable').on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                clearErrors();
                $.get('/movies/' + id, function(movie) {
                    $('#movie_id').val(movie.id);
                    $('#title').val(movie.title);
                    $('#duration_mins').val(movie.duration_mins);
                    $('#synopsis').val(movie.synopsis);
                    $('#poster_image').val(''); // ล้างไฟล์ที่อาจค้างไว้
                    // โชว์โปสเตอร์เดิม (ถ้ามี)
                    if (movie.has_poster) {
                        $('#posterPreview').attr('src', movie.poster_url).removeClass('d-none');
                    } else {
                        $('#posterPreview').addClass('d-none').attr('src', '');
                    }
                    $('#movieModalLabel').text('แก้ไขภาพยนตร์');
                    movieModal.show();
                }).fail(function() {
                    showToast('ไม่พบข้อมูลภาพยนตร์', true);
                });
            });

            // ===== พรีวิวรูปตอนเลือกไฟล์ =====
            $('#poster_image').on('change', function() {
                const file = this.files[0];
                if (file) {
                    $('#posterPreview').attr('src', URL.createObjectURL(file)).removeClass('d-none');
                }
            });

            // ===== บันทึก (เพิ่ม/แก้ไข) ผ่าน AJAX =====
            $('#movieForm').on('submit', function(e) {
                e.preventDefault();
                clearErrors();

                const id = $('#movie_id').val();
                const isEdit = !!id;

                // ใช้ FormData เพื่อส่งไฟล์รูปไปด้วย
                const formData = new FormData();
                formData.append('title', $('#title').val());
                formData.append('duration_mins', $('#duration_mins').val());
                formData.append('synopsis', $('#synopsis').val());

                const fileInput = $('#poster_image')[0];
                if (fileInput.files.length > 0) {
                    formData.append('poster_image', fileInput.files[0]);
                }

                let url = '/movies';
                if (isEdit) {
                    url = '/movies/' + id;
                    formData.append('_method', 'PUT'); // spoof PUT (multipart ส่ง PUT ตรงๆ ไม่ได้)
                }

                $('#btnSave').prop('disabled', true);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false, // อย่าแปลง FormData เป็น query string
                    contentType: false, // ให้ browser ตั้ง multipart boundary เอง
                }).done(function(res) {
                    movieModal.hide();
                    table.ajax.reload(null, false); // reload ตารางโดยไม่รีเซ็ตหน้า
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
            $('#moviesTable').on('click', '.btn-delete', function() {
                $('#delete_id').val($(this).data('id'));
                $('#deleteTitle').text($(this).data('title'));
                deleteModal.show();
            });

            // ===== ยืนยันลบ (soft delete) ผ่าน AJAX =====
            $('#btnConfirmDelete').on('click', function() {
                const id = $('#delete_id').val();
                $(this).prop('disabled', true);

                $.ajax({
                    url: '/movies/' + id,
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
