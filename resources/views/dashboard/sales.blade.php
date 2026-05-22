@extends('layouts.app')

@section('title', 'Sales & Distribusi')
@section('page-title', '🚚 Sales & Distribusi')
@section('page-subtitle', 'Order fulfillment dan distribusi CPO')

@section('content')

<div class="grid-2">
    {{-- ── ORDER FULFILLMENT FORM ───────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 Order Fulfillment</div>
            @if($data_cpo)
            <span class="badge {{ $data_cpo->stok_aktual > 0 ? 'badge-gold' : 'badge-red' }}">
                CPO: {{ number_format($data_cpo->stok_aktual, 1) }} Ton
            </span>
            @endif
        </div>
        <div class="card-body">

            <div class="alert alert-info" style="margin-bottom:20px;">
                <span>💡</span>
                <div style="font-size:.82rem;">
                    Harga jual CPO: <strong>Rp 15.000.000 / Ton</strong>. Invoice akan dikirim otomatis ke email klien via bot.
                </div>
            </div>

            <form method="POST" action="{{ route('sales.submit') }}" id="salesForm">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="nama_klien">🏢 Nama Perusahaan Pembeli</label>
                    <input type="text" id="nama_klien" name="nama_klien" class="form-control"
                        placeholder="Contoh: PT Sawit Makmur" value="{{ old('nama_klien') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email_klien">✉️ Email Klien (B2B)</label>
                    <input type="email" id="email_klien" name="email_klien" class="form-control"
                        placeholder="klien@perusahaan.com (opsional)" value="{{ old('email_klien') }}">
                    <small style="color:var(--text-muted);font-size:.75rem;">Jika kosong, invoice dikirim ke email default admin.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tonase_jual">🛢️ Jumlah Penjualan CPO (Ton)</label>
                    <input type="number" id="tonase_jual" name="tonase_jual" class="form-control"
                        step="0.1" min="0.1"
                        max="{{ $data_cpo ? $data_cpo->stok_aktual : 0 }}"
                        placeholder="Maks: {{ $data_cpo ? number_format($data_cpo->stok_aktual, 1) : 0 }} Ton"
                        value="{{ old('tonase_jual') }}" required>
                    @error('tonase_jual')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Total nilai preview --}}
                <div class="alert alert-success" id="previewTotal" style="display:none; margin-bottom:16px;">
                    <span>💰</span>
                    <div>Total Nilai Penjualan: <strong id="nilaiTotal">-</strong></div>
                </div>

                <button type="submit" class="btn btn-gold btn-full" id="btnSales">
                    ✅ KONFIRMASI PENJUALAN & KIRIM INVOICE
                </button>
            </form>
        </div>
    </div>

    {{-- ── LAPORAN PENJUALAN ───────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📊 Laporan Penjualan (Revenue)</div>
            <span class="badge badge-gold">{{ $penjualan->count() }} transaksi</span>
        </div>
        <div class="card-body" style="padding:0; max-height:480px; overflow-y:auto;">

            @if($penjualan->isEmpty())
                <div style="padding:40px;text-align:center;color:var(--text-muted);">
                    <div style="font-size:2.5rem;margin-bottom:12px;">📭</div>
                    <p>Belum ada riwayat transaksi penjualan.</p>
                </div>
            @else

            {{-- Desktop --}}
            <div class="table-wrapper table-responsive-hide">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Komoditas</th>
                            <th>Jumlah (Ton)</th>
                            <th>Keterangan</th>
                            <th>Nilai Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($penjualan as $p)
                        <tr>
                            <td style="font-size:.78rem;color:var(--text-muted);">{{ $p->Waktu }}</td>
                            <td><span class="badge badge-gold">{{ $p->Komoditas }}</span></td>
                            <td style="color:var(--danger);font-weight:700;">{{ number_format(abs($p->Perubahan_Ton), 2) }}</td>
                            <td style="font-size:.82rem;">{{ $p->Keterangan }}</td>
                            <td style="color:var(--success);font-weight:700;">
                                Rp {{ number_format(abs($p->Perubahan_Ton) * 15000000, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--cream);">
                            <td colspan="4" style="padding:12px 16px;font-weight:700;font-size:.85rem;">TOTAL REVENUE</td>
                            <td style="padding:12px 16px;font-weight:800;font-size:.95rem;color:var(--success);">
                                Rp {{ number_format($penjualan->sum(fn($p) => abs($p->Perubahan_Ton)) * 15000000, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="mobile-card-list" style="padding:12px;">
                @foreach($penjualan as $p)
                <div class="mobile-data-card">
                    <div class="mdc-row">
                        <span class="mdc-key">Waktu</span>
                        <span class="mdc-val" style="font-size:.75rem;">{{ $p->Waktu }}</span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Jumlah</span>
                        <span class="mdc-val" style="color:var(--danger);font-weight:700;">{{ number_format(abs($p->Perubahan_Ton), 2) }} Ton</span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Keterangan</span>
                        <span class="mdc-val" style="font-size:.78rem;text-align:right;">{{ Str::limit($p->Keterangan, 40) }}</span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Nilai</span>
                        <span class="mdc-val" style="color:var(--success);font-weight:700;">
                            Rp {{ number_format(abs($p->Perubahan_Ton) * 15000000, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endforeach

                <div class="mobile-data-card" style="background:var(--success-light);border-color:var(--success);">
                    <div class="mdc-row" style="border:none;">
                        <span class="mdc-key" style="color:var(--success);">TOTAL REVENUE</span>
                        <span class="mdc-val" style="color:var(--success);font-size:1rem;">
                            Rp {{ number_format($penjualan->sum(fn($p) => abs($p->Perubahan_Ton)) * 15000000, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const tonaseInput = document.getElementById('tonase_jual');
const previewEl   = document.getElementById('previewTotal');
const nilaiEl     = document.getElementById('nilaiTotal');

tonaseInput.addEventListener('input', function() {
    const ton = parseFloat(this.value) || 0;
    if (ton > 0) {
        const total = ton * 15000000;
        nilaiEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        previewEl.style.display = 'flex';
    } else {
        previewEl.style.display = 'none';
    }
});

document.getElementById('salesForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSales');
    btn.textContent = '⏳ Memproses & Kirim Invoice...';
    btn.disabled = true;
});
</script>
@endpush
