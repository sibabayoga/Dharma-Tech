@extends('layouts.app')

@section('title', 'Master Inventaris')
@section('page-title', '🗄️ Master Inventaris')
@section('page-subtitle', 'Saldo aktual komoditas di gudang')

@section('content')

<div class="card">
    <div class="card-header">
        <div class="card-title">📦 Tabel Saldo Inventaris</div>
        <span class="badge badge-green">Real-time</span>
    </div>
    <div class="card-body" style="padding:0;">

        {{-- DESKTOP TABLE --}}
        <div class="table-wrapper table-responsive-hide">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Komoditas</th>
                        <th>Stok Aktual (Ton)</th>
                        <th>Reorder Point (Ton)</th>
                        <th>Jml. Pesanan Standar (Ton)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stok as $i => $item)
                    <tr>
                        <td style="color:var(--text-muted);">{{ $i + 1 }}</td>
                        <td><strong>{{ $item->nama_komoditas }}</strong></td>
                        <td>{{ number_format($item->stok_aktual, 2) }}</td>
                        <td>{{ number_format($item->reorder_point, 2) }}</td>
                        <td>{{ number_format($item->jumlah_pesanan, 2) }}</td>
                        <td>
                            @if($item->stok_aktual <= $item->reorder_point)
                                <span class="badge badge-red">🔴 Kritis</span>
                            @elseif($item->stok_aktual <= $item->reorder_point * 1.5)
                                <span class="badge badge-gold">🟡 Perhatian</span>
                            @else
                                <span class="badge badge-green">🟢 Aman</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">Tidak ada data stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="mobile-card-list" style="padding:16px;">
            @foreach($stok as $item)
            <div class="mobile-data-card">
                <div class="mdc-row">
                    <span class="mdc-key">Komoditas</span>
                    <span class="mdc-val"><strong>{{ $item->nama_komoditas }}</strong></span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Stok Aktual</span>
                    <span class="mdc-val" style="color:var(--green-bright);font-size:1.1rem;">{{ number_format($item->stok_aktual, 2) }} Ton</span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Reorder Point</span>
                    <span class="mdc-val">{{ number_format($item->reorder_point, 2) }} Ton</span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Jml. Pesanan</span>
                    <span class="mdc-val">{{ number_format($item->jumlah_pesanan, 2) }} Ton</span>
                </div>
                <div class="mdc-row">
                    <span class="mdc-key">Status</span>
                    <span class="mdc-val">
                        @if($item->stok_aktual <= $item->reorder_point)
                            <span class="badge badge-red">🔴 Kritis</span>
                        @elseif($item->stok_aktual <= $item->reorder_point * 1.5)
                            <span class="badge badge-gold">🟡 Perhatian</span>
                        @else
                            <span class="badge badge-green">🟢 Aman</span>
                        @endif
                    </span>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>
@endsection
