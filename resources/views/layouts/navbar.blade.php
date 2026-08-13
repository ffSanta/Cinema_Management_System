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
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('bookings*') ? 'active' : '' }}" href="{{ url('/bookings') }}">
                        <i class="bi bi-ticket-perforated"></i> การจอง
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
