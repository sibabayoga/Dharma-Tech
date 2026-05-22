<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseOrderMail;
use App\Mail\SalesInvoiceMail;
use Smalot\PdfParser\Parser as PdfParser;

class DashboardController extends Controller
{
    // =============================================
    // HALAMAN 1: RINGKASAN EKSEKUTIF
    // =============================================
    public function index()
    {
        $stok = DB::table('Stok_Gudang')->get()->keyBy('nama_komoditas');
        $data_tbs = $stok->get('Kelapa Sawit (TBS)');
        $data_cpo = $stok->get('Crude Palm Oil (CPO)');

        // Data riwayat untuk grafik CPO
        $riwayat_cpo = DB::table('Riwayat_Gudang')
            ->where('Komoditas', 'Crude Palm Oil (CPO)')
            ->orderBy('Waktu')
            ->get();

        // Data keuangan untuk grafik arus kas
        $keuangan = DB::table('Keuangan')->orderBy('Waktu')->get();

        // AI Forecasting
        $keluar_tbs = DB::table('Riwayat_Gudang')
            ->where('Komoditas', 'Kelapa Sawit (TBS)')
            ->where('Perubahan_Ton', '<', 0)
            ->get();

        $forecasting = null;
        if ($keluar_tbs->count() > 0 && $data_tbs) {
            $rata_keluar = abs($keluar_tbs->avg('Perubahan_Ton'));
            $sisa_aman   = $data_tbs->stok_aktual - $data_tbs->reorder_point;
            if ($rata_keluar > 0) {
                $forecasting = $sisa_aman > 0 ? intval($sisa_aman / $rata_keluar) : -1;
            }
        }

        // Sustainability Score
        $semua_masuk = DB::table('Riwayat_Gudang')->where('Perubahan_Ton', '>', 0)->get();
        $persen_rspo = 0;
        if ($semua_masuk->count() > 0) {
            $rspo_count  = $semua_masuk->filter(fn($r) => str_contains($r->Keterangan, 'RSPO'))->count();
            $persen_rspo = round(($rspo_count / $semua_masuk->count()) * 100, 1);
        }

        return view('dashboard.index', compact(
            'data_tbs', 'data_cpo', 'riwayat_cpo', 'keuangan', 'forecasting', 'persen_rspo'
        ));
    }

    // =============================================
    // HALAMAN 2: MASTER INVENTARIS
    // =============================================
    public function inventaris()
    {
        $stok = DB::table('Stok_Gudang')->get();
        return view('dashboard.inventaris', compact('stok'));
    }

    // =============================================
    // HALAMAN 3: OPERASIONAL (GRN)
    // =============================================
    public function grn()
    {
        $vendors  = DB::table('Vendor')->get();
        $riwayat  = DB::table('Riwayat_Gudang')->orderByDesc('id_riwayat')->get();
        $data_tbs = DB::table('Stok_Gudang')->where('nama_komoditas', 'Kelapa Sawit (TBS)')->first();
        return view('dashboard.grn', compact('vendors', 'riwayat', 'data_tbs'));
    }

    public function grnSubmit(Request $request)
    {
        $request->validate([
            'tonase_kotor'  => 'required|numeric|min:0',
            'vendor_id'     => 'required',
            'qc_potongan'   => 'required|numeric|min:0|max:100',
        ]);

        $vendor        = DB::table('Vendor')->where('id_vendor', $request->vendor_id)->first();
        $tonase_bersih = $request->tonase_kotor * (1 - ($request->qc_potongan / 100));
        $keterangan    = "Masuk: {$vendor->Nama_PT} (QC: {$request->qc_potongan}%) - {$vendor->Status_RSPO}";

        DB::table('Stok_Gudang')
            ->where('nama_komoditas', 'Kelapa Sawit (TBS)')
            ->increment('stok_aktual', $tonase_bersih);

        DB::table('Riwayat_Gudang')->insert([
            'Komoditas'    => 'Kelapa Sawit (TBS)',
            'Perubahan_Ton' => $tonase_bersih,
            'Keterangan'   => $keterangan,
        ]);

        // Auto-pelunasan tagihan yang menunggu barang
        $tagihan = DB::table('Keuangan')
            ->where('Vendor', $vendor->Nama_PT)
            ->where('Status', 'like', '%Menunggu Barang%')
            ->orderBy('id_bayar')
            ->first();

        if ($tagihan) {
            DB::table('Keuangan')->where('id_bayar', $tagihan->id_bayar)->update([
                'DP_Dibayar' => $tagihan->Total_Tagihan,
                'Status'     => 'Lunas 100% (Barang Tiba) ✅',
            ]);
        }

        return redirect()->route('grn')->with('success', "Truk berhasil ditimbang! Tagihan ke {$vendor->Nama_PT} otomatis dilunasi (jika ada).");
    }

    // =============================================
    // HALAMAN 4: PABRIKASI (MANUFAKTUR)
    // =============================================
    public function pabrik()
    {
        $data_tbs    = DB::table('Stok_Gudang')->where('nama_komoditas', 'Kelapa Sawit (TBS)')->first();
        $data_cpo    = DB::table('Stok_Gudang')->where('nama_komoditas', 'Crude Palm Oil (CPO)')->first();
        $mutasi_pabrik = DB::table('Riwayat_Gudang')
            ->whereRaw("Keterangan LIKE '%Pabrik%' OR Keterangan LIKE '%Mesin%'")
            ->orderByDesc('id_riwayat')
            ->limit(20)
            ->get();
        return view('dashboard.pabrik', compact('data_tbs', 'data_cpo', 'mutasi_pabrik'));
    }

    public function pabrikSubmit(Request $request)
    {
        $request->validate([
            'batch_tbs'   => 'required|numeric|min:0.1',
            'suhu_mesin'  => 'required|numeric',
        ]);

        if ($request->suhu_mesin >= 95) {
            return back()->withErrors(['batch_tbs' => 'Mesin Overheat! Produksi tidak dapat dilanjutkan.']);
        }

        $batch_tbs  = $request->batch_tbs;
        $hasil_cpo  = $batch_tbs * 0.20;

        DB::table('Stok_Gudang')->where('nama_komoditas', 'Kelapa Sawit (TBS)')->decrement('stok_aktual', $batch_tbs);
        DB::table('Riwayat_Gudang')->insert([
            'Komoditas' => 'Kelapa Sawit (TBS)', 'Perubahan_Ton' => -$batch_tbs, 'Keterangan' => 'Keluar: Konsumsi Mesin Pabrik'
        ]);
        DB::table('Stok_Gudang')->where('nama_komoditas', 'Crude Palm Oil (CPO)')->increment('stok_aktual', $hasil_cpo);
        DB::table('Riwayat_Gudang')->insert([
            'Komoditas' => 'Crude Palm Oil (CPO)', 'Perubahan_Ton' => $hasil_cpo, 'Keterangan' => 'Masuk: Hasil Produksi Mesin'
        ]);

        // Cek stok kritis setelah produksi
        $cek = DB::table('Stok_Gudang')->where('nama_komoditas', 'Kelapa Sawit (TBS)')->first();
        $msg = "Produksi berhasil! {$batch_tbs} Ton TBS → {$hasil_cpo} Ton CPO.";

        if ($cek && $cek->stok_aktual <= $cek->reorder_point) {
            $target    = 250.0;
            $pesanan   = $target - $cek->stok_aktual;
            $harga_tbs = 2500000;
            $estimasi  = $pesanan * $harga_tbs;
            $no_po     = 'PO-AUTO-' . intval($pesanan);

            DB::table('Keuangan')->insert([
                'No_Invoice'    => $no_po,
                'Vendor'        => 'PT Kencana Agrindo',
                'Total_Tagihan' => $estimasi,
                'DP_Dibayar'    => 0,
                'Status'        => 'PO Sent - Menunggu Invoice ⏳',
            ]);

            // Kirim email PO via Laravel Mail (native PHP, no Python)
            try {
                Mail::to('dustintirza@gmail.com')
                    ->send(new PurchaseOrderMail('Kelapa Sawit (TBS)', $pesanan));
            } catch (\Exception $e) {
                // Email gagal tidak menghentikan proses utama
                \Log::warning('Gagal kirim email PO: ' . $e->getMessage());
            }

            $msg .= " Stok TBS kritis! PO otomatis {$pesanan} Ton dikirim ke supplier.";
        }

        return redirect()->route('pabrik')->with('success', $msg);
    }

    // =============================================
    // HALAMAN 5: FINANCE & LAPORAN
    // =============================================
    public function finance()
    {
        $keuangan     = DB::table('Keuangan')->orderByDesc('id_bayar')->get();
        $total_hutang  = $keuangan->sum('Total_Tagihan');
        $total_dp      = $keuangan->sum('DP_Dibayar');
        $sisa_utang    = $total_hutang - $total_dp;
        return view('dashboard.finance', compact('keuangan', 'total_hutang', 'total_dp', 'sisa_utang'));
    }

    public function financeUpload(Request $request)
    {
        $request->validate(['invoice_pdf' => 'required|file|mimes:pdf|max:10240']);

        $file     = $request->file('invoice_pdf');
        $tempPath = $file->storeAs('temp', $file->getClientOriginalName());
        $fullPath = storage_path('app/private/' . $tempPath);

        // OCR via smalot/pdfparser (100% native PHP, no Python needed)
        try {
            $parser   = new PdfParser();
            $pdf      = $parser->parseFile($fullPath);
            $teks_asli = $pdf->getText();
        } catch (\Exception $e) {
            @unlink($fullPath);
            return redirect()->route('finance')->with('error', '❌ Gagal membaca PDF. Pastikan file tidak terproteksi.');
        }

        @unlink($fullPath);

        // Bersihkan spasi untuk pencocokan (sama seperti logika Python sebelumnya)
        $teks_bersih = preg_replace('/\s+/', '', $teks_asli);

        // Ekstrak nomor Invoice (format: INV-XXXX-XXX)
        preg_match('/INV-\d{4}-\d{3}/', $teks_bersih, $inv_match);
        $inv_no = $inv_match[0] ?? 'NONE';

        // Ekstrak nominal tagihan (format: RpXXX.XXX)
        preg_match('/Rp([\d.]+)/', $teks_bersih, $tag_match);
        $tagihan = floatval(str_replace('.', '', $tag_match[1] ?? '0'));

        // 3-Way Matching: cek elemen wajib
        $matched = (
            str_contains($teks_bersih, 'INV-') &&
            str_contains($teks_bersih, 'KelapaSawit') &&
            str_contains($teks_asli, 'Ton')
        );

        if ($matched && $inv_no !== 'NONE') {
            $existing = DB::table('Keuangan')->where('No_Invoice', $inv_no)->first();
            if (!$existing) {
                $dp = $tagihan * 0.50;
                $po = DB::table('Keuangan')->where('Status', 'like', '%Menunggu Invoice%')->orderByDesc('id_bayar')->first();
                if ($po) {
                    DB::table('Keuangan')->where('id_bayar', $po->id_bayar)->update([
                        'No_Invoice'    => $inv_no,
                        'Total_Tagihan' => $tagihan,
                        'DP_Dibayar'    => $dp,
                        'Status'        => 'DP 50% Paid - Menunggu Barang 🚚',
                    ]);
                } else {
                    DB::table('Keuangan')->insert([
                        'No_Invoice'    => $inv_no,
                        'Vendor'        => 'PT Kencana Agrindo',
                        'Total_Tagihan' => $tagihan,
                        'DP_Dibayar'    => $dp,
                        'Status'        => 'DP 50% Paid - Menunggu Barang 🚚',
                    ]);
                }
                return redirect()->route('finance')->with('success', "✅ 3-Way Matching OK! Invoice {$inv_no} — DP 50% Rp " . number_format($dp, 0, ',', '.') . " telah dicairkan otomatis.");
            }
            return redirect()->route('finance')->with('warning', "Invoice {$inv_no} sudah pernah diproses sebelumnya.");
        }

        return redirect()->route('finance')->with('error', '❌ Dokumen tidak memenuhi 3-Way Matching. Pastikan PDF mengandung nomor INV-, Kelapa Sawit, dan Ton.');
    }

    // =============================================
    // HALAMAN 6: SALES & DISTRIBUSI
    // =============================================
    public function sales()
    {
        $data_cpo  = DB::table('Stok_Gudang')->where('nama_komoditas', 'Crude Palm Oil (CPO)')->first();
        $penjualan = DB::table('Riwayat_Gudang')
            ->where('Keterangan', 'like', '%Penjualan%')
            ->orderByDesc('id_riwayat')
            ->get();
        return view('dashboard.sales', compact('data_cpo', 'penjualan'));
    }

    public function salesSubmit(Request $request)
    {
        $request->validate([
            'nama_klien'   => 'required|string',
            'email_klien'  => 'nullable|email',
            'tonase_jual'  => 'required|numeric|min:0.1',
        ]);

        $data_cpo = DB::table('Stok_Gudang')->where('nama_komoditas', 'Crude Palm Oil (CPO)')->first();

        if ($request->tonase_jual > $data_cpo->stok_aktual) {
            return back()->withErrors(['tonase_jual' => 'Stok CPO tidak mencukupi untuk memenuhi pesanan ini!'])->withInput();
        }

        $harga_per_ton = 15000000;
        $total_tagihan = $request->tonase_jual * $harga_per_ton;
        $email_tujuan  = $request->email_klien ?: 'anandanaurahmutiara@gmail.com';
        $nama_klien    = $request->nama_klien;

        DB::table('Stok_Gudang')->where('nama_komoditas', 'Crude Palm Oil (CPO)')->decrement('stok_aktual', $request->tonase_jual);
        DB::table('Riwayat_Gudang')->insert([
            'Komoditas'    => 'Crude Palm Oil (CPO)',
            'Perubahan_Ton' => -$request->tonase_jual,
            'Keterangan'   => "Penjualan ke {$nama_klien}",
        ]);
        DB::table('Keuangan')->insert([
            'No_Invoice'    => 'SALE-' . strtoupper(substr($nama_klien, 0, 3)),
            'Vendor'        => $nama_klien,
            'Total_Tagihan' => $total_tagihan,
            'DP_Dibayar'    => 0,
            'Status'        => 'PENDING PAYMENT (Receivable) 📈',
        ]);

        // Kirim email Invoice via Laravel Mail (native PHP, no Python)
        try {
            Mail::to($email_tujuan)
                ->send(new SalesInvoiceMail($nama_klien, 'Crude Palm Oil (CPO)', $request->tonase_jual, $total_tagihan));
        } catch (\Exception $e) {
            // Email gagal tidak menghentikan proses utama
            \Log::warning('Gagal kirim email Invoice: ' . $e->getMessage());
        }

        return redirect()->route('sales')->with('success', "✅ Penjualan {$request->tonase_jual} Ton CPO ke {$nama_klien} berhasil! Invoice dikirim ke {$email_tujuan}.");
    }
}
