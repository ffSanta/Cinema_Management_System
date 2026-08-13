<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            <i class="bi bi-film"></i> Cinema
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                        <i class="bi bi-house-door"></i> หน้าหลัก
                    </a>
                </li>

                {{-- เมนูฝั่งผู้ใช้ (เฉพาะ user — admin ไม่เห็น/จองไม่ได้) --}}
                @auth
                    @if (auth()->user()->role === 'user')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('booking*') ? 'active' : '' }}" href="{{ route('booking.index') }}">
                                <i class="bi bi-ticket-perforated"></i> จองตั๋ว
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('my-bookings') ? 'active' : '' }}" href="{{ route('booking.my') }}">
                                <i class="bi bi-journal-check"></i> การจองของฉัน
                            </a>
                        </li>
                    @endif

                    {{-- เมนูจัดการ (เฉพาะ admin) --}}
                    @if (auth()->user()->role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('movies*') ? 'active' : '' }}" href="{{ url('/movies') }}">
                                <i class="bi bi-camera-reels"></i> ภาพยนตร์
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('cinemas*') ? 'active' : '' }}" href="{{ url('/cinemas') }}">
                                <i class="bi bi-building"></i> โรงภาพยนตร์
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('showtimes*') ? 'active' : '' }}" href="{{ url('/showtimes') }}">
                                <i class="bi bi-clock"></i> รอบฉาย
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            {{-- ส่วนบัญชีผู้ใช้ (ขวา) --}}
            <ul class="navbar-nav ms-auto">
                @guest
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('login') ? 'active' : '' }}" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('register') ? 'active' : '' }}" href="{{ route('register') }}">
                            <i class="bi bi-person-plus"></i> สมัครสมาชิก
                        </a>
                    </li>
                @endguest

                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person"></i> ข้อมูลส่วนตัว
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
