import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from datetime import datetime, timedelta

# ==========================================
# BOT 1: PROCUREMENT (Kirim PO ke Supplier)
# ==========================================
def kirim_email_po(email_tujuan, komoditas, jumlah):
    print(f"🤖 [BOT 1 - PROCUREMENT] Menyiapkan draf Purchase Order untuk {jumlah:.1f} Ton...")
    
    # --- PAKAI GMAIL PRIBADI YANG SUDAH BERHASIL TADI ---
    email_pengirim = "dustintirza@gmail.com"  
    password_aplikasi = "tvhfvaxfggqckxie"  

    batas_waktu = datetime.now() + timedelta(days=3)
    tanggal_maksimal = batas_waktu.strftime("%d %B %Y")

    pesan = MIMEMultipart()
    pesan['From'] = f"Dharma Tech OS <{email_pengirim}>"
    pesan['To'] = email_tujuan
    pesan['Subject'] = f"URGENT: Purchase Order - {komoditas}"

    isi_email = f"""
    Kepada Yth. Tim Penjualan Supplier,
    
    Berdasarkan pemantauan sistem otomatis kami, stok {komoditas} kami sedang kritis. 
    Kami menerbitkan Purchase Order (PO) dengan rincian:
    - Komoditas           : {komoditas}
    - Jumlah Pesanan      : {jumlah:.1f} Ton
    - Maksimal Kedatangan : {tanggal_maksimal}
    
    Sesuai sistem ERP Dharma Tech OS, DP 50% akan cair otomatis setelah Invoice lolos verifikasi.
    """
    pesan.attach(MIMEText(isi_email, 'plain'))

    try:
        server = smtplib.SMTP('smtp.gmail.com', 587)
        server.starttls()
        server.login(email_pengirim, password_aplikasi)
        server.send_message(pesan)
        server.quit()
        print(f"✅ SUKSES! PO {jumlah:.1f} Ton terkirim ke Supplier.")
    except Exception as e:
        print(f"❌ GAGAL PO. Error: {e}")


# ==========================================
# BOT 2: SALES (Kirim Invoice ke Klien)
# ==========================================
def kirim_invoice_sales(email_klien, nama_klien, item, jumlah, total_harga):
    print(f"🤖 [BOT 2 - SALES RPA] Menghitung Tagihan untuk {nama_klien}...")
    
    # --- PAKAI GMAIL PRIBADI YANG SUDAH BERHASIL TADI ---
    email_pengirim = "dustintirza@gmail.com"  
    password_aplikasi = "tvhfvaxfggqckxie" 

    pesan = MIMEMultipart()
    pesan['From'] = f"Dharma Tech Billing <{email_pengirim}>"
    pesan['To'] = email_klien
    pesan['Subject'] = f"Sales Invoice - {item} - {nama_klien}"

    isi_email = f"""
    Halo {nama_klien},
    
    Terima kasih telah memesan {item} melalui Dharma Tech OS.
    Pesanan Anda sebesar {jumlah:.1f} Ton telah kami proses.
    
    RINGKASAN TAGIHAN:
    - Total Tagihan  : Rp {total_harga:,.0f}
    - Status Barang  : Dalam Proses Pengiriman
    
    Silakan melakukan pembayaran sesuai termin yang berlaku.
    """
    pesan.attach(MIMEText(isi_email, 'plain'))

    try:
        server = smtplib.SMTP('smtp.gmail.com', 587)
        server.starttls()
        server.login(email_pengirim, password_aplikasi)
        server.send_message(pesan)
        server.quit()
        print(f"✅ SUKSES! Invoice terkirim ke Klien: {email_klien}")
    except Exception as e:
        print(f"❌ GAGAL Invoice. Error: {e}")


# ==========================================
# --- SIMULASI RPA ENTERPRISE (DI JALANKAN VIA TERMINAL) ---
# ==========================================
if __name__ == "__main__":
    print("\n--- DHARMA TECH OS: RPA SIMULATION START ---\n")
    
    # CASE 1: SIMULASI PROCUREMENT (PENGADAAN)
    kapasitas_max = 250.0
    stok_tbs_sekarang = 15.0
    butuh_po = kapasitas_max - stok_tbs_sekarang
    
    # Kirim PO ke email kampus kamu (seolah-olah kamu jadi supplier)
    kirim_email_po("dustin3194pradana@apps.ipb.ac.id", "Kelapa Sawit (TBS)", butuh_po)
    
    print("-" * 30)
    
    # CASE 2: SIMULASI SALES (PENJUALAN)
    # Misal kita jual 10 Ton CPO ke klien Ananda
    kuantitas_jual = 10.0
    harga_per_ton = 15000000
    total_invoice = kuantitas_jual * harga_per_ton
    
    # Kirim Invoice ke email Ananda (sesuai yang kamu mau di input dashboard)
    kirim_invoice_sales("andnaurah@apps.ipb.ac.id", "Ananda Naurah", "Crude Palm Oil (CPO)", kuantitas_jual, total_invoice)
    
    print("\n--- SIMULASI SELESAI ---\n")