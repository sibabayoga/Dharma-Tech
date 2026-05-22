<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Dharma Tech OS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌿</text></svg>">
</head>
<body>

<div class="login-page">

    <!-- ── LEFT VISUAL PANEL ──────────────────────────── -->
    <div class="login-visual">
        <div class="login-visual-content">
            <div class="brand-icon-lg">🌿</div>
            <h2>Dharma Tech OS</h2>
            <p>Platform ERP terintegrasi untuk manajemen rantai pasok kelapa sawit yang berkelanjutan dan cerdas.</p>

            <div class="login-features">
                <div class="login-feature-item">
                    <div class="feat-icon">📊</div>
                    <span>Dashboard eksekutif real-time dengan forecasting AI</span>
                </div>
                <div class="login-feature-item">
                    <div class="feat-icon">🤖</div>
                    <span>Otomatisasi PO, pembayaran & invoice berbasis RPA</span>
                </div>
                <div class="login-feature-item">
                    <div class="feat-icon">🌍</div>
                    <span>Pemantauan sertifikasi RSPO untuk keberlanjutan</span>
                </div>
                <div class="login-feature-item">
                    <div class="feat-icon">📄</div>
                    <span>Pemindaian invoice PDF otomatis dengan 3-Way Matching</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT FORM PANEL ───────────────────────────── -->
    <div class="login-form-side">
        <div class="form-logo">🌿</div>
        <h3>Selamat Datang</h3>
        <p class="login-subtitle">Masuk untuk mengakses sistem ERP Dharma Tech</p>

        @if($errors->has('username'))
            <div class="alert alert-danger" style="width:100%; margin-bottom:20px;">
                <span>❌</span>
                <div>{{ $errors->first('username') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="width:100%;">
            @csrf

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">👤</span>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username..."
                        value="{{ old('username') }}"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">🔒</span>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password..."
                        autocomplete="current-password"
                        required
                    >
                </div>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="remember" id="remember" style="width:16px;height:16px;accent-color:#3A7D44;cursor:pointer;">
                <label for="remember" style="font-size:.84rem; color:var(--text-mid); cursor:pointer; margin-bottom:0;">Ingat saya</label>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                🔑 Masuk ke Sistem
            </button>
        </form>

        <p style="margin-top:32px; font-size:.75rem; color:var(--text-muted); text-align:center; line-height:1.6;">
            Dharma Tech OS &copy; {{ date('Y') }} &middot; ERP Ecosystem Edition<br>
            Hanya pengguna terdaftar yang dapat mengakses sistem ini.
        </p>
    </div>

</div>

</body>
</html>
