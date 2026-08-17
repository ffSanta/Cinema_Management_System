{{-- ===== Theme กลางของทั้งเว็บ (include ทั้ง layout หลักและ guest) ===== --}}
<style>
    :root {
        --brand: #5b4bd1;
        --brand-dark: #4536a3;
        --brand-050: #efedfb;
        --ink: #211d33;
        --muted: #6b6880;
        --gold: #cf9b3a;
        --gold-2: #ecc271;
        --page-bg: #f4f3f9;
        /* rebrand ลิงก์ Bootstrap ให้เป็นสีแบรนด์ */
        --bs-link-color-rgb: 91, 75, 209;
        --bs-link-hover-color-rgb: 69, 54, 163;
    }

    /* ใช้ body.bg-light เพื่อชนะ .bg-light ของ Bootstrap (specificity สูงกว่า) */
    body,
    body.bg-light {
        background: var(--page-bg) !important;
        color: var(--ink);
    }

    /* ===== Navbar แบรนด์ ===== */
    .navbar.bg-dark {
        background: linear-gradient(90deg, #171526 0%, #241d40 100%) !important;
        box-shadow: 0 .2rem .6rem rgba(0, 0, 0, .18);
    }
    .navbar-brand {
        color: var(--gold-2) !important;
        font-weight: 800;
        letter-spacing: .4px;
    }
    .navbar .nav-link.active { color: #fff !important; font-weight: 600; }

    /* ===== การ์ด / โมดัล สม่ำเสมอ ===== */
    .card {
        border: none;
        border-radius: .9rem;
        box-shadow: 0 .35rem 1rem rgba(33, 29, 51, .07);
    }
    .modal-content { border: none; border-radius: .9rem; overflow: hidden; }
    .modal-header {
        background: var(--brand-050);
        border-bottom: 1px solid #e3def8;
    }

    /* ===== สีหลัก = แบรนด์ ===== */
    .btn-primary {
        --bs-btn-bg: var(--brand);
        --bs-btn-border-color: var(--brand);
        --bs-btn-hover-bg: var(--brand-dark);
        --bs-btn-hover-border-color: var(--brand-dark);
        --bs-btn-active-bg: var(--brand-dark);
        --bs-btn-active-border-color: var(--brand-dark);
        --bs-btn-disabled-bg: var(--brand);
        --bs-btn-disabled-border-color: var(--brand);
    }
    .btn-outline-primary {
        --bs-btn-color: var(--brand);
        --bs-btn-border-color: var(--brand);
        --bs-btn-hover-bg: var(--brand);
        --bs-btn-hover-border-color: var(--brand);
        --bs-btn-active-bg: var(--brand);
        --bs-btn-active-border-color: var(--brand);
    }
    .text-primary { color: var(--brand) !important; }
    .bg-primary { background-color: var(--brand) !important; }
    .page-item.active .page-link { background-color: var(--brand); border-color: var(--brand); }
    .page-link { color: var(--brand); }
    .form-control:focus, .form-select:focus {
        border-color: #a99ff0;
        box-shadow: 0 0 0 .2rem rgba(91, 75, 209, .18);
    }
    .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }

    /* ===== หัวข้อหน้า — มีเส้น accent ทองใต้หัวข้อทุกหน้า ===== */
    main h2 {
        font-weight: 700;
        color: var(--ink);
        position: relative;
        padding-bottom: .55rem;
        margin-bottom: 1.25rem;
    }
    main h2::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 52px;
        height: 3px;
        border-radius: 2px;
        background: linear-gradient(90deg, var(--gold-2), var(--gold));
    }
    /* หัวข้อที่จัดกึ่งกลาง (เช่น dashboard) ให้ accent อยู่กลาง */
    main .text-center h2::after { left: 50%; transform: translateX(-50%); }

    /* ===== ตาราง (DataTables) — หัวตารางแบรนด์ + แถวสลับสีนวล ===== */
    .table > thead > tr > th {
        background: var(--brand-050);
        color: var(--brand-dark);
        border-bottom: 2px solid var(--brand) !important;
        font-weight: 600;
    }
    .table-striped > tbody > tr:nth-of-type(odd) > * {
        --bs-table-bg-type: #faf9fe;
    }
    .table-hover > tbody > tr:hover > * {
        --bs-table-bg-state: #f1eefc;
    }

    /* dropdown / accent */
    .dropdown-item:active { background-color: var(--brand); }

    /* accent ทอง (ผูกกับธีมโรงหนัง) */
    .text-gold { color: var(--gold) !important; }
</style>
