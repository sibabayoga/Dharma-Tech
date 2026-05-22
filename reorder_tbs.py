import sqlite3

def cek_stok_dari_database():
    print("--- Memulai Pengecekan Sistem Auto-Reorder ---")
    
    # 1. Menghubungkan program ke database
    try:
        koneksi = sqlite3.connect('rantai_pasok.db')
        kursor = koneksi.cursor()
        
        # 2. Mengambil semua data barang dari tabel Stok_Gudang
        kursor.execute("SELECT nama_komoditas, stok_aktual, reorder_point, jumlah_pesanan FROM Stok_Gudang")
        semua_barang = kursor.fetchall()
        
        if not semua_barang:
            print("Database kosong. Belum ada barang di gudang.")
            return

        # 3. Looping (mengecek satu per satu barang jika ada banyak jenis)
        for barang in semua_barang:
            nama = barang[0]
            stok = barang[1]
            rop = barang[2]
            jml_pesan = barang[3]
            
            print(f"\nMemeriksa: {nama}")
            print(f"Stok saat ini: {stok} Ton (Batas aman: {rop} Ton)")
            
            # Logika Pembuatan Keputusan
            if stok <= rop:
                print("⚠️ PERINGATAN: Stok kritis!")
                buat_draft_po(nama, jml_pesan)
            else:
                print("✅ Stok aman.")
                
    except sqlite3.Error as e:
        print(f"Terjadi kesalahan saat membaca database: {e}")
    finally:
        # Selalu tutup koneksi database setelah selesai digunakan
        if koneksi:
            koneksi.close()

def buat_draft_po(komoditas, jumlah):
    print(f"--> [SYSTEM ACTION] Menyiapkan draf email PO ke Pemasok untuk {jumlah} Ton {komoditas}...")

if __name__ == "__main__":
    cek_stok_dari_database()