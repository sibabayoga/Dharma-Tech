@extends('layouts.app')

@section('title', 'Ringkasan Eksekutif')
@section('page-title', '📊 Ringkasan Eksekutif')
@section('page-subtitle', 'Pantau performa operasional secara real-time')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- ─── KPI STAT CARDS ───────────────────────────────────────── --}}
<div class="stats-grid">
    <div class="stat-card {{ $data_tbs && $data_tbs->stok_aktual <= $data_tbs->reorder_point ? 'danger' : '' }}">
        <div class="stat-icon">🌴</div>
        <div class="stat-label">Bahan Baku (TBS)</div>
        <div class="stat-value">{{ $data_tbs ? number_format($data_tbs->stok_aktual, 1) : '-' }} <small style="font-size:.9rem;font-weight:600;">Ton</small></div>
        @if($data_tbs)
            <span class="stat-delta {{ $data_tbs->stok_aktual > $data_tbs->reorder_point ? 'ok' : 'bad' }}">
                {{ $data_tbs->stok_aktual > $data_tbs->reorder_point ? '✅ Stok Aman' : '🔴 Stok Kritis' }}
            </span>
        @endif
    </div>

    <div class="stat-card gold">
        <div class="stat-icon">🛢️</div>
        <div class="stat-label">Produk Jadi (CPO)</div>
        <div class="stat-value">{{ $data_cpo ? number_format($data_cpo->stok_aktual, 1) : '-' }} <small style="font-size:.9rem;font-weight:600;">Ton</small></div>
        <span class="stat-delta ok">✅ Siap Jual</span>
    </div>

    @if($forecasting !== null)
    <div class="stat-card">
        <div class="stat-icon">🔮</div>
        <div class="stat-label">AI Forecasting TBS</div>
        <div class="stat-value">{{ $forecasting > 0 ? $forecasting : '0' }} <small style="font-size:.9rem;font-weight:600;">Siklus</small></div>
        <span class="stat-delta {{ $forecasting > 3 ? 'ok' : ($forecasting > 0 ? 'warn' : 'bad') }}">
            {{ $forecasting > 0 ? 'Hingga batas kritis' : '🚨 Zona Merah' }}
        </span>
    </div>
    @endif

    <div class="stat-card">
        <div class="stat-icon">🌍</div>
        <div class="stat-label">Rasio RSPO</div>
        <div class="stat-value">{{ $persen_rspo }}<small style="font-size:1rem;font-weight:600;">%</small></div>
        <span class="stat-delta {{ $persen_rspo >= 70 ? 'ok' : ($persen_rspo >= 40 ? 'warn' : 'bad') }}">
            Suplai Bersertifikat
        </span>
    </div>
</div>

{{-- ─── CHARTS ───────────────────────────────────────────────── --}}
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">📉 Pergerakan Stok CPO</div>
            <span class="badge badge-green">Produksi vs Penjualan</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartCPO"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">💳 Tren Nilai Transaksi</div>
            <span class="badge badge-gold">Arus Kas (Rp)</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartKeuangan"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ─── AI FORECASTING & SUSTAINABILITY ─────────────────────── --}}
@if($forecasting !== null || $persen_rspo > 0)
<div class="grid-2">
    @if($forecasting !== null)
    <div class="alert {{ $forecasting > 3 ? 'alert-success' : ($forecasting > 0 ? 'alert-warning' : 'alert-danger') }}">
        <span style="font-size:1.4rem;">🔮</span>
        <div>
            <strong>AI Forecasting:</strong>
            @if($forecasting > 0)
                Bahan baku TBS diprediksi menyentuh batas kritis dalam <strong>{{ $forecasting }} siklus</strong> produksi ke depan.
            @else
                Bahan baku TBS dalam <strong>zona merah</strong>! Segera hubungi Procurement.
            @endif
        </div>
    </div>
    @endif

    @if($persen_rspo > 0)
    <div class="alert alert-success">
        <span style="font-size:1.4rem;">🌍</span>
        <div>
            <strong>Keberlanjutan:</strong> Rasio Suplai TBS Bersertifikat RSPO saat ini: <strong>{{ $persen_rspo }}%</strong>. {{ $persen_rspo >= 70 ? 'Target keberlanjutan tercapai! 🏆' : 'Tingkatkan pembelian dari supplier RSPO.' }}
        </div>
    </div>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script>
// ── Chart CPO ─────────────────────────────────────────────
const riwayatCPO = @json($riwayat_cpo);

const cpoLabels  = [];
const cpoProduksi = [];
const cpoJual    = [];

const grouped = {};
riwayatCPO.forEach(r => {
    const tgl = r.Waktu ? r.Waktu.substring(0, 10) : '?';
    if (!grouped[tgl]) grouped[tgl] = { prod: 0, jual: 0 };
    if (r.Perubahan_Ton > 0) grouped[tgl].prod += r.Perubahan_Ton;
    else grouped[tgl].jual += Math.abs(r.Perubahan_Ton);
});

Object.keys(grouped).sort().forEach(tgl => {
    cpoLabels.push(tgl);
    cpoProduksi.push(grouped[tgl].prod);
    cpoJual.push(grouped[tgl].jual);
});

const ctxCPO = document.getElementById('chartCPO').getContext('2d');
new Chart(ctxCPO, {
    type: 'bar',
    data: {
        labels: cpoLabels,
        datasets: [
            { label: 'Produksi (+)', data: cpoProduksi, backgroundColor: 'rgba(46,125,50,.8)', borderRadius: 6 },
            { label: 'Penjualan (-)', data: cpoJual,    backgroundColor: 'rgba(198,40,40,.75)', borderRadius: 6 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { family: 'Plus Jakarta Sans' } } },
            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
        }
    }
});

// ── Chart Keuangan ────────────────────────────────────────
const keuangan  = @json($keuangan);
const keuLabels = [];
const keuData   = [];

const keuGrouped = {};
keuangan.forEach(k => {
    const tgl = k.Waktu ? k.Waktu.substring(0, 10) : '?';
    keuGrouped[tgl] = (keuGrouped[tgl] || 0) + parseFloat(k.Total_Tagihan);
});

Object.keys(keuGrouped).sort().forEach(tgl => {
    keuLabels.push(tgl);
    keuData.push(keuGrouped[tgl]);
});

const ctxKeu = document.getElementById('chartKeuangan').getContext('2d');
const gradKeu = ctxKeu.createLinearGradient(0, 0, 0, 280);
gradKeu.addColorStop(0, 'rgba(212,160,23,.35)');
gradKeu.addColorStop(1, 'rgba(212,160,23,.0)');

new Chart(ctxKeu, {
    type: 'line',
    data: {
        labels: keuLabels,
        datasets: [{
            label: 'Total Tagihan (Rp)',
            data: keuData,
            borderColor: '#D4A017',
            backgroundColor: gradKeu,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#D4A017',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,.05)' },
                ticks: {
                    font: { family: 'Plus Jakarta Sans' },
                    callback: v => 'Rp ' + (v/1e6).toFixed(0) + 'Jt'
                }
            },
            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
        }
    }
});
</script>
@endpush
