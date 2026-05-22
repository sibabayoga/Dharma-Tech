import sqlite3

def catat_truk_masuk():
    print("=== SISTEM PENERIMAAN BARANG (AUTO GRN) ===")
    
    # Meminta input manual dari operator (simulasi jembatan timbang)
    try:
        tambahan_stok = float(input("Berapa Ton Kelapa Sawit (TBS) yang baru tiba? : "))
    except ValueError:
        print("❌ Error: Masukkan angka yang valid!")
        return

    komoditas = 'Kelapa Sawit (TBS)'

    try:
        # 1. Buka brankas database
        koneksi = sqlite3.connect('rantai_pasok.db')
        kursor = koneksi.cursor()
        
        # 2. Update database (Tambahkan stok lama dengan stok baru)
        kursor.execute('''
            UPDATE Stok_Gudang 
            SET stok_aktual = stok_aktual + ? 
            WHERE nama_komoditas = ?
        ''', (tambahan_stok, komoditas))
        
        # 3. Simpan perubahan permanen ke database
        koneksi.commit()
        
        # 4. Cek total stok sekarang untuk ditampilkan
        kursor.execute("SELECT stok_aktual FROM Stok_Gudang WHERE nama_komoditas = ?", (komoditas,))
        stok_baru = kursor.fetchone()[0]
        
        print("\n✅ GRN BERHASIL DIBUAT!")
        print(f"Data tersimpan. Total stok {komoditas} di gudang sekarang: {stok_baru} Ton")
                
    except sqlite3.Error as e:
        print(f"Terjadi kesalahan database: {e}")
    finally:
        if koneksi:
            koneksi.close()

if __name__ == "__main__":
    catat_truk_masuk()