@extends('layouts.app')

@section('title', 'Pabrikasi - Manufaktur')
@section('page-title', '🏭 Control Room Pabrik')
@section('page-subtitle', 'Manajemen produksi ekstraksi CPO')

@section('content')

<div class="grid-2">
    {{-- ── WORK ORDER FORM ──────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚙️ Work Order Produksi</div>
            <span class="badge badge-green">Rasio Konversi: 20%</span>
        </div>
        <div class="card-body">

            {{-- Sensor Suhu Mesin --}}
            <div class="slider-container">
                <div class="slider-label">
                    <span>🌡️ Suhu Mesin Sterilizer</span>
                    <span class="slider-value-badge" id="suhuBadge">75°C</span>
                </div>
                <input type="range" id="suhuSlider" min="50" max="120" value="75" style="--val:36.36%;"
                    oninput="updateSuhu(this.value)">
                <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted);margin-top:4px;">
                    <span>50°C (Min)</span>
                    <span style="color:var(--warning);">95°C (Batas)</span>
                    <span style="color:var(--danger);">120°C (Max)</span>
                </div>
            </div>

            {{-- Overheat Warning --}}
            <div class="alert alert-danger" id="overheatAlert" style="display:none; margin-bottom:16px;">
                <span>🚨</span>
                <div><strong>OVERHEAT!</strong> Suhu mesin melebihi batas aman 95°C. Produksi dikunci untuk keamanan. Turunkan suhu terlebih dahulu.</div>
            </div>

            {{-- Stok Info --}}
            <div class="alert alert-info" style="margin-bottom:20px;">
                <span>📦</span>
                <div>
                    Stok TBS tersedia: <strong>{{ $data_tbs ? number_format($data_tbs->stok_aktual, 1) : 0 }} Ton</strong>
                    &nbsp;|&nbsp;
                    Stok CPO saat ini: <strong>{{ $data_cpo ? number_format($data_cpo->stok_aktual, 1) : 0 }} Ton</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('pabrik.submit') }}" id="formProduksi">
                @csrf
                <input type="hidden" name="suhu_mesin" id="suhuHidden" value="75">

                <div class="form-group">
                    <label class="form-label" for="batch_tbs">🌴 Jumlah TBS yang akan diproses (Ton)</label>
                    <input type="number" id="batch_tbs" name="batch_tbs" class="form-control"
                        step="0.1" min="0.1" placeholder="Contoh: 35" value="{{ old('batch_tbs') }}" required>
                </div>

                {{-- Preview hasil produksi --}}
                <div class="alert alert-success" id="previewCPO" style="display:none; margin-bottom:16px;">
                    <span>🛢️</span>
                    <div>Estimasi Hasil CPO: <strong id="hasilCPO">-</strong> Ton (20% dari TBS)</div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="btnProduksi">
                    ▶️ MULAI PRODUKSI
                </button>
            </form>
        </div>
    </div>

    {{-- ── TABEL MUTASI PABRIK ──────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📋 Mutasi Pabrik (20 Terakhir)</div>
        </div>
        <div class="card-body" style="padding:0; max-height:480px; overflow-y:auto;">

            {{-- Desktop --}}
            <div class="table-wrapper table-responsive-hide">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Komoditas</th>
                            <th>Perubahan (Ton)</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutasi_pabrik as $m)
                        <tr>
                            <td style="font-size:.78rem;color:var(--text-muted);">{{ $m->Waktu }}</td>
                            <td><span class="badge {{ str_contains($m->Komoditas,'TBS') ? 'badge-green' : 'badge-gold' }}">{{ Str::limit($m->Komoditas, 20) }}</span></td>
                            <td style="{{ $m->Perubahan_Ton > 0 ? 'color:var(--success);font-weight:700;' : 'color:var(--danger);font-weight:700;' }}">
                                {{ $m->Perubahan_Ton > 0 ? '+' : '' }}{{ number_format($m->Perubahan_Ton, 2) }}
                            </td>
                            <td style="font-size:.82rem;">{{ $m->Keterangan }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;">Belum ada mutasi pabrik.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="mobile-card-list" style="padding:12px;">
                @foreach($mutasi_pabrik as $m)
                <div class="mobile-data-card">
                    <div class="mdc-row">
                        <span class="mdc-key">Waktu</span>
                        <span class="mdc-val" style="font-size:.75rem;">{{ $m->Waktu }}</span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Komoditas</span>
                        <span class="mdc-val"><span class="badge {{ str_contains($m->Komoditas,'TBS') ? 'badge-green' : 'badge-gold' }}">{{ Str::limit($m->Komoditas, 18) }}</span></span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Perubahan</span>
                        <span class="mdc-val" style="{{ $m->Perubahan_Ton > 0 ? 'color:var(--success)' : 'color:var(--danger)' }};font-weight:700;">
                            {{ $m->Perubahan_Ton > 0 ? '+' : '' }}{{ number_format($m->Perubahan_Ton, 2) }} Ton
                        </span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Ket.</span>
                        <span class="mdc-val" style="font-size:.78rem;text-align:right;">{{ $m->Keterangan }}</span>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function updateSuhu(val) {
    val = parseInt(val);
    const badge   = document.getElementById('suhuBadge');
    const hidden  = document.getElementById('suhuHidden');
    const alert   = document.getElementById('overheatAlert');
    const btn     = document.getElementById('btnProduksi');
    const slider  = document.getElementById('suhuSlider');
    const pct     = ((val - 50) / (120 - 50) * 100).toFixed(2) + '%';

    slider.style.setProperty('--val', pct);
    badge.textContent = val + '°C';
    hidden.value = val;

    const overheat = val >= 95;
    badge.classList.toggle('hot', overheat);
    alert.style.display = overheat ? 'flex' : 'none';
    btn.disabled = overheat;
    btn.textContent = overheat ? '🚨 MESIN OVERHEAT (TERKUNCI)' : '▶️ MULAI PRODUKSI';
    if (overheat) btn.className = 'btn btn-danger btn-full';
    else btn.className = 'btn btn-primary btn-full';
}

const batchInput = document.getElementById('batch_tbs');
const previewCPO = document.getElementById('previewCPO');
const hasilCPO   = document.getElementById('hasilCPO');

batchInput.addEventListener('input', function() {
    const v = parseFloat(this.value) || 0;
    if (v > 0) {
        hasilCPO.textContent = (v * 0.20).toFixed(2);
        previewCPO.style.display = 'flex';
    } else {
        previewCPO.style.display = 'none';
    }
});
</script>
@endpush
