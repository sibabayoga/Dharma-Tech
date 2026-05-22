<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
  .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.1); }
  .header { background: linear-gradient(135deg, #1a4731, #2d7a50); padding: 30px; text-align: center; color: #fff; }
  .header h1 { margin: 0; font-size: 22px; }
  .header p { margin: 5px 0 0; opacity: .8; font-size: 13px; }
  .badge { display: inline-block; background: #f59e0b; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-top: 10px; }
  .body { padding: 30px; }
  .body p { color: #444; line-height: 1.6; }
  .detail-box { background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 8px; padding: 20px; margin: 20px 0; }
  .detail-box table { width: 100%; border-collapse: collapse; }
  .detail-box td { padding: 8px 0; color: #333; font-size: 14px; }
  .detail-box td:first-child { font-weight: bold; color: #1a4731; width: 45%; }
  .footer { background: #f9fafb; padding: 20px 30px; text-align: center; color: #888; font-size: 12px; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>🌴 DHARMA TECH OS</h1>
    <p>Sistem ERP Rantai Pasok Kelapa Sawit</p>
    <span class="badge">⚠️ URGENT: Purchase Order</span>
  </div>
  <div class="body">
    <p>Kepada Yth. <strong>Tim Penjualan Supplier</strong>,</p>
    <p>Berdasarkan pemantauan sistem otomatis kami, stok <strong>{{ $komoditas }}</strong> kami sedang kritis.
    Kami menerbitkan <strong>Purchase Order (PO)</strong> dengan rincian sebagai berikut:</p>
    <div class="detail-box">
      <table>
        <tr><td>Komoditas</td><td>: {{ $komoditas }}</td></tr>
        <tr><td>Jumlah Pesanan</td><td>: {{ number_format($jumlah, 1) }} Ton</td></tr>
        <tr><td>Maksimal Kedatangan</td><td>: {{ $tanggalMaksimal }}</td></tr>
        <tr><td>Ketentuan Pembayaran</td><td>: DP 50% setelah Invoice terverifikasi</td></tr>
      </table>
    </div>
    <p>Sesuai sistem ERP Dharma Tech OS, <strong>DP 50% akan cair otomatis</strong> setelah Invoice Anda lolos proses verifikasi 3-Way Matching.</p>
    <p>Mohon konfirmasi penerimaan PO ini sesegera mungkin.</p>
    <p>Hormat kami,<br><strong>Dharma Tech OS — Sistem Pengadaan Otomatis</strong></p>
  </div>
  <div class="footer">
    Email ini dikirim secara otomatis oleh sistem ERP. Harap tidak membalas email ini.
  </div>
</div>
</body>
</html>
