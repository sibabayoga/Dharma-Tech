@extends('layouts.app')

@section('title', 'Operasional GRN')
@section('page-title', '⚖️ Jembatan Timbang (GRN)')
@section('page-subtitle', 'Penerimaan bahan baku masuk')

@section('content')

<div class="grid-2">
    {{-- ── FORM GRN ──────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚖️ Form Timbang Masuk</div>
            @if($data_tbs)
            <span class="badge {{ $data_tbs->stok_aktual > $data_tbs->reorder_point ? 'badge-green' : 'badge-red' }}">
                TBS: {{ number_format($data_tbs->stok_aktual, 1) }} Ton
            </span>
            @endif
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('grn.submit') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="tonase_kotor">🚛 Tonase Truk TBS Masuk (Ton)</label>
                    <input type="number" id="tonase_kotor" name="tonase_kotor" class="form-control"
                        step="0.1" min="0" placeholder="Contoh: 35.5" value="{{ old('tonase_kotor') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="vendor_id">🏢 Pilih Supplier</label>
                    <select id="vendor_id" name="vendor_id" class="form-control" required>
                        <option value="">— Pilih Supplier —</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id_vendor }}" {{ old('vendor_id') == $v->id_vendor ? 'selected' : '' }}>
                            {{ $v->Nama_PT }} ({{ $v->Status_RSPO }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="qc_potongan">✂️ Potongan Mutu / Sampah (%)</label>
                    <input type="number" id="qc_potongan" name="qc_potongan" class="form-control"
                        step="0.5" min="0" max="100" placeholder="0 — 100" value="{{ old('qc_potongan', 0) }}" required>
                </div>

                {{-- Preview kalkulasi tonase bersih --}}
                <div class="alert alert-info" id="previewBersih" style="display:none; margin-bottom:16px;">
                    <span>🧮</span>
                    <div>Estimasi Tonase Bersih: <strong id="hasilBersih">-</strong> Ton</div>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    ✅ TERIMA BARANG (GRN)
                </button>
            </form>
        </div>
    </div>

    {{-- ── RIWAYAT TIMBANG ──────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📋 Riwayat Aktivitas</div>
            <span class="badge badge-gray">{{ $riwayat->count() }} entri</span>
        </div>
        <div class="card-body" style="padding:0; max-height: 480px; overflow-y:auto;">

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
                        @forelse($riwayat as $r)
                        <tr>
                            <td style="font-size:.78rem;color:var(--text-muted);">{{ $r->Waktu }}</td>
                            <td><span class="badge {{ str_contains($r->Komoditas,'TBS') ? 'badge-green' : 'badge-gold' }}">{{ $r->Komoditas }}</span></td>
                            <td style="{{ $r->Perubahan_Ton > 0 ? 'color:var(--success);font-weight:700;' : 'color:var(--danger);font-weight:700;' }}">
                                {{ $r->Perubahan_Ton > 0 ? '+' : '' }}{{ number_format($r->Perubahan_Ton, 2) }}
                            </td>
                            <td style="font-size:.82rem;">{{ $r->Keterangan }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;">Belum ada riwayat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="mobile-card-list" style="padding:12px;">
                @foreach($riwayat->take(15) as $r)
                <div class="mobile-data-card">
                    <div class="mdc-row">
                        <span class="mdc-key">Waktu</span>
                        <span class="mdc-val" style="font-size:.75rem;">{{ $r->Waktu }}</span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Komoditas</span>
                        <span class="mdc-val"><span class="badge {{ str_contains($r->Komoditas,'TBS') ? 'badge-green' : 'badge-gold' }}">{{ $r->Komoditas }}</span></span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Perubahan</span>
                        <span class="mdc-val" style="{{ $r->Perubahan_Ton > 0 ? 'color:var(--success)' : 'color:var(--danger)' }}; font-weight:700;">
                            {{ $r->Perubahan_Ton > 0 ? '+' : '' }}{{ number_format($r->Perubahan_Ton, 2) }} Ton
                        </span>
                    </div>
                    <div class="mdc-row">
                        <span class="mdc-key">Ket.</span>
                        <span class="mdc-val" style="font-size:.78rem;text-align:right;">{{ Str::limit($r->Keterangan, 50) }}</span>
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
const tonaseInput  = document.getElementById('tonase_kotor');
const qcInput      = document.getElementById('qc_potongan');
const previewBox   = document.getElementById('previewBersih');
const hasilEl      = document.getElementById('hasilBersih');

function updatePreview() {
    const kotor = parseFloat(tonaseInput.value) || 0;
    const qc    = parseFloat(qcInput.value) || 0;
    if (kotor > 0) {
        const bersih = kotor * (1 - qc / 100);
        hasilEl.textContent = bersih.toFixed(2);
        previewBox.style.display = 'flex';
    } else {
        previewBox.style.display = 'none';
    }
}

tonaseInput.addEventListener('input', updatePreview);
qcInput.addEventListener('input', updatePreview);
</script>
@endpush
