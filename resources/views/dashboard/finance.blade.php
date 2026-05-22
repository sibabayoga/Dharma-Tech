@extends('layouts.app')

@section('title', 'Finance & Laporan')
@section('page-title', '💰 Finance & Laporan')
@section('page-subtitle', 'Otomatisasi pembayaran & laporan keuangan')

@section('content')

{{-- ─── KPI KEUANGAN ──────────────────────────────────────────── --}}
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-label">Total Komitmen Tagihan</div>
        <div class="stat-value" style="font-size:1.3rem;">Rp {{ number_format($total_hutang, 0, ',', '.') }}</div>
        <span class="stat-delta warn">Total Kewajiban</span>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">💸</div>
        <div class="stat-label">Total DP Terbayar</div>
        <div class="stat-value" style="font-size:1.3rem;">Rp {{ number_format($total_dp, 0, ',', '.') }}</div>
        <span class="stat-delta ok">Otomatis via AI</span>
    </div>
    <div class="stat-card {{ $sisa_utang > 0 ? 'danger' : '' }}">
        <div class="stat-icon">⚖️</div>
        <div class="stat-label">Sisa Utang Dagang</div>
        <div class="stat-value" style="font-size:1.3rem;">Rp {{ number_format($sisa_utang, 0, ',', '.') }}</div>
        <span class="stat-delta {{ $sisa_utang > 0 ? 'bad' : 'ok' }}">{{ $sisa_utang > 0 ? 'Belum Lunas' : '✅ Lunas' }}</span>
    </div>
</div>

<div class="grid-2">
    {{-- ── AI INVOICE SCANNER ────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">🤖 AI Invoice Scanner</div>
            <span class="badge badge-blue">3-Way Matching</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('finance.upload') }}" enctype="multipart/form-data" id="uploadForm">
                @csrf

                <label class="file-upload-area" for="invoice_pdf" id="uploadArea">
                    <div class="upload-icon">📄</div>
                    <p>Seret & lepas file PDF Invoice Supplier di sini, atau</p>
                    <p><strong>Klik untuk memilih file</strong></p>
                    <p style="font-size:.75rem;margin-top:8px;color:var(--text-muted);">Format: PDF · Maks. 10MB</p>
                    <div class="file-name-display" id="fileNameDisplay" style="display:none;"></div>
                    <input type="file" id="invoice_pdf" name="invoice_pdf" accept=".pdf" onchange="showFileName(this)">
                </label>

                @if($errors->has('invoice_pdf'))
                    <p class="error-text">{{ $errors->first('invoice_pdf') }}</p>
                @endif

                <button type="submit" class="btn btn-gold btn-full" style="margin-top:16px;" id="btnScan">
                    🔍 Scan & Proses Invoice
                </button>
            </form>

            <div class="alert alert-info" style="margin-top:20px;">
                <span>ℹ️</span>
                <div style="font-size:.82rem;">
                    Sistem akan memindai PDF, mengekstrak nomor invoice (INV-XXXX-XXX) dan nilai tagihan, lalu mencocokkan 3-Way Matching secara otomatis. DP 50% akan dicairkan jika dokumen valid.
                </div>
            </div>
        </div>
    </div>

    {{-- ── RINGKASAN STATUS ──────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📈 Status Alur Keuangan</div>
        </div>
        <div class="card-body">
            @php
                $status_counts = [
                    'PO Sent'       => $keuangan->filter(fn($k) => str_contains($k->Status, 'PO Sent'))->count(),
                    'DP 50%'        => $keuangan->filter(fn($k) => str_contains($k->Status, 'DP 50%'))->count(),
                    'Lunas'         => $keuangan->filter(fn($k) => str_contains($k->Status, 'Lunas'))->count(),
                    'Receivable'    => $keuangan->filter(fn($k) => str_contains($k->Status, 'Receivable'))->count(),
                ];
            @endphp
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--cream);border-radius:10px;">
                    <span style="font-size:.875rem;font-weight:600;">⏳ PO Terkirim (Menunggu Invoice)</span>
                    <span class="badge badge-gold">{{ $status_counts['PO Sent'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--cream);border-radius:10px;">
                    <span style="font-size:.875rem;font-weight:600;">🚚 DP 50% Dibayar (Menunggu Barang)</span>
                    <span class="badge badge-blue">{{ $status_counts['DP 50%'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--cream);border-radius:10px;">
                    <span style="font-size:.875rem;font-weight:600;">✅ Lunas (Barang Tiba)</span>
                    <span class="badge badge-green">{{ $status_counts['Lunas'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--cream);border-radius:10px;">
                    <span style="font-size:.875rem;font-weight:600;">📈 Piutang Penjualan (Receivable)</span>
                    <span class="badge badge-gray">{{ $status_counts['Receivable'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── TABEL KEUANGAN ────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">📊 Laporan Arus Kas & Utang Usaha</div>
        <span class="badge badge-gray">{{ $keuangan->count() }} transaksi</span>
    </div>
    <div class="card-body" style="padding:0;">

        {{-- Desktop --}}
        <div class="table-wrapper table-responsive-hide">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Waktu</th>
                        <th>No. Invoice</th>
                        <th>Vendor / Klien</th>
                        <th>Total Tagihan</th>
                        <th>DP Dibayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keuangan as $k)
                    <tr>
                        <td style="color:var(--text-muted);">{{ $k->id_bayar }}</td>
                        <td style="font-size:.78rem;color:var(--text-muted);">{{ $k->Waktu }}</td>
                        <td><code style="font-size:.8rem;background:var(--cream);padding:2px 7px;border-radius:4px;">{{ $k->No_Invoice }}</code></td>
                        <td><strong>{{ $k->Vendor }}</strong></td>
                        <td>Rp {{ number_format($k->Total_Tagihan, 0, ',', '.') }}</td>
                        <td style="color:var(--success);font-weight:700;">Rp {{ number_format($k->DP_Dibayar, 0, ',', '.') }}</td>
                        <td>
                            @php
                                $cls = str_contains($k->Status,'Lunas') ? 'badge-green' :
                                      (str_contains($k->Status,'DP') ? 'badge-blue' :
                                      (str_contains($k->Status,'PO') ? 'badge-gold' : 'badge-gray'));
                            @endphp
                            <span class="badge {{ $cls }}" style="font-size:.7rem;">{{ Str::limit($k->Status, 35) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px;">Belum ada data keuangan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="mobile-card-list" style="padding:12px;">
            @foreach($keuangan as $k)
            <div class="mobile-data-card">
                <div class="mdc-row">
                    <span class="mdc-key">Invoice</span>
                    <span class="mdc-val"><code style="font-size:.8rem;">{{ $k->No_Invoice }}</code></span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Vendor</span>
                    <span class="mdc-val"><strong>{{ $k->Vendor }}</strong></span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Total</span>
                    <span class="mdc-val">Rp {{ number_format($k->Total_Tagihan, 0, ',', '.') }}</span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">DP</span>
                    <span class="mdc-val" style="color:var(--success);font-weight:700;">Rp {{ number_format($k->DP_Dibayar, 0, ',', '.') }}</span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Status</span>
                    <span class="mdc-val">
                        @php $cls = str_contains($k->Status,'Lunas') ? 'badge-green' : (str_contains($k->Status,'DP') ? 'badge-blue' : (str_contains($k->Status,'PO') ? 'badge-gold' : 'badge-gray')); @endphp
                        <span class="badge {{ $cls }}" style="font-size:.68rem;">{{ Str::limit($k->Status, 30) }}</span>
                    </span>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
// Drag & Drop for file upload
const uploadArea = document.getElementById('uploadArea');
['dragover','dragenter'].forEach(e => {
    uploadArea.addEventListener(e, ev => { ev.preventDefault(); uploadArea.classList.add('drag-over'); });
});
['dragleave','drop'].forEach(e => {
    uploadArea.addEventListener(e, ev => { uploadArea.classList.remove('drag-over'); });
});
uploadArea.addEventListener('drop', ev => {
    ev.preventDefault();
    const files = ev.dataTransfer.files;
    if (files.length) {
        document.getElementById('invoice_pdf').files = files;
        showFileName(document.getElementById('invoice_pdf'));
    }
});

function showFileName(input) {
    const el = document.getElementById('fileNameDisplay');
    if (input.files.length) {
        el.textContent = '📎 ' + input.files[0].name;
        el.style.display = 'block';
    }
}

document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnScan');
    btn.textContent = '⏳ Memproses...';
    btn.disabled = true;
});
</script>
@endpush
