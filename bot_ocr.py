import PyPDF2

def ekstrak_data_invoice(nama_file):
    print(f"🤖 [BOT 3 - INVOICE OCR] Memulai pemindaian dokumen: {nama_file}...\n")
    
    try:
        # 1. Membuka dan membaca file PDF
        with open(nama_file, 'rb') as file:
            pembaca_pdf = PyPDF2.PdfReader(file)
            halaman_pertama = pembaca_pdf.pages[0]
            teks_hasil_scan = halaman_pertama.extract_text()
            
            print("📄 --- TEKS YANG BERHASIL DIBACA ---")
            print(teks_hasil_scan)
            print("-----------------------------------\n")
            
            # 2. Logika Pengecekan & Pembersihan Data
            print("🔍 Menganalisis elemen penting untuk 3-Way Matching...")
            
            # Membersihkan spasi yang berantakan dari hasil scan
            teks_bersih = teks_hasil_scan.replace(" ", "")
            
            # Sekarang bot mengecek dari teks yang sudah dibersihkan
            if "INV-" in teks_bersih:
                print("✅ [Validasi] Nomor Invoice ditemukan.")
            else:
                print("❌ [Peringatan] Nomor Invoice tidak terdeteksi!")
                
            if "KelapaSawit" in teks_bersih:
                print("✅ [Validasi] Komoditas sesuai dengan database.")
                
            if "Ton" in teks_hasil_scan: # Biarkan ini pakai teks asli
                print("✅ [Validasi] Satuan berat terdeteksi. Siap untuk 3-Way Matching.")
                
    # Bagian ini yang tadi tidak sengaja terhapus/bergeser
    except FileNotFoundError:
        print(f"❌ ERROR: File '{nama_file}' tidak ditemukan.")
    except Exception as e:
        print(f"Terjadi kesalahan teknis: {e}")

if __name__ == "__main__":
    # Menjalankan fungsi untuk membaca file dummy
    ekstrak_data_invoice('invoice_dummy.pdf')