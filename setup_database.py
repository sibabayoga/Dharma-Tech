import sqlite3

def buat_database():
    # Membuat atau menyambung ke file database bernama 'rantai_pasok.db'
    koneksi = sqlite3.connect('rantai_pasok.db')
    kursor = koneksi.cursor()

    # Membuat Tabel Stok TBS
    kursor.execute('''
        CREATE TABLE IF NOT EXISTS Stok_Gudang (
            id_barang INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_komoditas TEXT,
            stok_aktual REAL,
            reorder_point REAL,
            jumlah_pesanan REAL
        )
    ''')

    # Memasukkan data awal ke dalam tabel (sebagai contoh agar tidak kosong)
    # Kita menggunakan "IGNORE" atau mengecek agar data tidak ganda jika dijalankan berkali-kali
    kursor.execute('SELECT COUNT(*) FROM Stok_Gudang')
    if kursor.fetchone()[0] == 0:
        kursor.execute('''
            INSERT INTO Stok_Gudang (nama_komoditas, stok_aktual, reorder_point, jumlah_pesanan)
            VALUES ('Kelapa Sawit (TBS)', 35.0, 50.0, 100.0)
        ''')
        koneksi.commit()
        print("Data awal Kelapa Sawit (TBS) berhasil dimasukkan.")

    koneksi.close()
    print("✅ Database 'rantai_pasok.db' dan tabel 'Stok_Gudang' sudah siap digunakan!")

if __name__ == "__main__":
    buat_database()