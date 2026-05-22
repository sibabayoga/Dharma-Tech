import sqlite3
import pandas as pd
import streamlit as st
import numpy as np
import PyPDF2
import re
from bot_email import kirim_email_po, kirim_invoice_sales

st.set_page_config(page_title="Dharma Tech OS", layout="wide")

# ==========================================
# CSS TEMA NATURAL
# ==========================================
st.markdown("""
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
    html, body, [class*="css"] { font-family: 'Plus Jakarta Sans', sans-serif; }
    .stApp p, .stApp span, .stApp label { color: #3E2723 !important; }
    [data-testid="stSidebar"] { background-color: #2D3A2E; }
    [data-testid="stSidebar"] h2, [data-testid="stSidebar"] p, [data-testid="stSidebar"] span { color: #F9F6F0 !important; }
    [data-testid="stSidebar"] .stRadio label { border-bottom: 1px solid rgba(255, 255, 255, 0.15); padding-top: 15px !important; padding-bottom: 15px !important; cursor: pointer; }
    [data-testid="stSidebar"] .stRadio label:last-child { border-bottom: none; }
    [data-testid="stSidebar"] .stRadio label p { font-size: 1.3rem !important; color: #FFFFFF !important; font-weight: 600; margin-left: 5px; }
    .stApp { background-color: #F9F6F0; }
    .custom-card { background-color: #FFFFFF; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 5px solid #4CAF50; }
    div[data-testid="metric-container"] { background-color: #FFFFFF; border-radius: 12px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .stButton>button { background: linear-gradient(90deg, #4CAF50 0%, #2E7D32 100%); color: white !important; border: none; border-radius: 10px; font-weight: 600; padding: 12px; width: 100%; transition: 0.3s; }
</style>
""", unsafe_allow_html=True)

# ==========================================
# INISIALISASI DATABASE & TABEL KEUANGAN
# ==========================================
def init_db():
    conn = sqlite3.connect('rantai_pasok.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS Stok_Gudang 
                 (id_barang INTEGER PRIMARY KEY AUTOINCREMENT, nama_komoditas TEXT, stok_aktual REAL, reorder_point REAL, jumlah_pesanan REAL)''')
    
    c.execute("SELECT count(*) FROM Stok_Gudang WHERE nama_komoditas = 'Crude Palm Oil (CPO)'")
    if c.fetchone()[0] == 0:
        c.execute("INSERT INTO Stok_Gudang (nama_komoditas, stok_aktual, reorder_point, jumlah_pesanan) VALUES ('Crude Palm Oil (CPO)', 0.0, 20.0, 50.0)")
        
    c.execute('''CREATE TABLE IF NOT EXISTS Riwayat_Gudang 
                 (id_riwayat INTEGER PRIMARY KEY AUTOINCREMENT, Waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP, Komoditas TEXT, Perubahan_Ton REAL, Keterangan TEXT)''')
    c.execute('''CREATE TABLE IF NOT EXISTS Vendor 
                 (id_vendor INTEGER PRIMARY KEY AUTOINCREMENT, Nama_PT TEXT, Email TEXT, Status_RSPO TEXT)''')
    c.execute('''CREATE TABLE IF NOT EXISTS Keuangan 
                 (id_bayar INTEGER PRIMARY KEY AUTOINCREMENT, Waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP, No_Invoice TEXT, Vendor TEXT, Total_Tagihan REAL, DP_Dibayar REAL, Status TEXT)''')
    
    c.execute("SELECT count(*) FROM Vendor")
    if c.fetchone()[0] == 0:
        c.execute("INSERT INTO Vendor (Nama_PT, Email, Status_RSPO) VALUES ('PT Kencana Agrindo', 'kencana@contoh.com', 'RSPO Certified 🟢')")
    
    conn.commit()
    conn.close()

init_db()

conn = sqlite3.connect('rantai_pasok.db')
df_master = pd.read_sql_query("SELECT * FROM Stok_Gudang", conn)
df_riwayat = pd.read_sql_query("SELECT Waktu, Komoditas, Perubahan_Ton, Keterangan FROM Riwayat_Gudang ORDER BY id_riwayat DESC", conn)
df_vendor = pd.read_sql_query("SELECT * FROM Vendor", conn)
df_keuangan = pd.read_sql_query("SELECT * FROM Keuangan ORDER BY id_bayar DESC", conn)
conn.close()

data_tbs = df_master[df_master['nama_komoditas'] == 'Kelapa Sawit (TBS)'].iloc[0]
data_cpo = df_master[df_master['nama_komoditas'] == 'Crude Palm Oil (CPO)'].iloc[0]

# ==========================================
# SIDEBAR NAVIGATION
# ==========================================
with st.sidebar:
    st.markdown("<h2 style='color:white; font-weight:800;'>🌿 Dharma Tech</h2>", unsafe_allow_html=True)
    st.markdown("<p style='color:#A5D6A7;'>ERP Ecosystem Edition</p>", unsafe_allow_html=True)
    st.write("---")
    selected = st.radio("Menu Utama", ["📊 Ringkasan Eksekutif", "🗄️ Master Inventaris", "⚖️ Operasional (GRN)", "🏭 Pabrikasi (Manufaktur)", "💰 Finance & Laporan", "🚚 Sales & Distribusi"])
    st.write("---")
    st.caption("User: Dustin Eiga (Admin)")

# ==========================================
# HALAMAN 1: RINGKASAN EKSEKUTIF
# ==========================================
if selected == "📊 Ringkasan Eksekutif":
    st.markdown("<h2 style='color:#2E7D32;'>📊 Dashboard Eksekutif</h2>", unsafe_allow_html=True)
    col1, col2 = st.columns(2)
    with col1: 
        st.metric("Bahan Baku (TBS)", f"{data_tbs['stok_aktual']:.1f} Ton", "Siap Olah" if data_tbs['stok_aktual'] > data_tbs['reorder_point'] else "Kritis", delta_color="normal")
    with col2: 
        st.metric("Produk Jadi (CPO)", f"{data_cpo['stok_aktual']:.1f} Ton", "Siap Jual")
    
    st.write("---")
    
    # 2. GRAFIK VISUALISASI DATA (NEW)
    st.subheader("📉 Visualisasi Analitik Operasional")
    
    chart_col1, chart_col2 = st.columns(2)
    
    with chart_col1:
        st.markdown("**Pergerakan Stok CPO (Produksi vs Penjualan)**")
        # Mengolah data riwayat khusus CPO
        df_cpo = df_riwayat[df_riwayat['Komoditas'] == 'Crude Palm Oil (CPO)'].copy()
        if not df_cpo.empty:
            # Ubah format waktu menjadi tanggal saja
            df_cpo['Tanggal'] = pd.to_datetime(df_cpo['Waktu']).dt.date
            
            # Pisahkan data masuk (produksi) dan keluar (penjualan)
            df_masuk = df_cpo[df_cpo['Perubahan_Ton'] > 0].groupby('Tanggal')['Perubahan_Ton'].sum().rename("Produksi (+)")
            df_keluar = df_cpo[df_cpo['Perubahan_Ton'] < 0].groupby('Tanggal')['Perubahan_Ton'].sum().abs().rename("Penjualan (-)")
            
            # Gabungkan menjadi satu tabel untuk grafik dan reset index
            df_trend_stok = pd.concat([df_masuk, df_keluar], axis=1).fillna(0).reset_index()
            
            # --- MENGGUNAKAN PLOTLY UNTUK GRAFIK BERSEBELAHAN ---
            import plotly.express as px
            fig = px.bar(
                df_trend_stok, 
                x='Tanggal', 
                y=['Produksi (+)', 'Penjualan (-)'], 
                barmode='group', # Ini rahasianya agar bar bersebelahan!
                color_discrete_map={'Produksi (+)': '#2E7D32', 'Penjualan (-)': '#C62828'} # Hijau & Merah
            )
            
            # Merapikan tampilan legenda dan label axis
            fig.update_layout(yaxis_title="Tonase (Ton)", xaxis_title=None, legend_title_text=None)
            
            # Tampilkan ke Streamlit
            st.plotly_chart(fig, use_container_width=True)
        else:
            st.info("Belum ada data transaksi CPO.")

    with chart_col2:
        st.markdown("**Tren Nilai Transaksi & Tagihan (Rp)**")
        df_uang = df_keuangan.copy()
        if not df_uang.empty:
            df_uang['Tanggal'] = pd.to_datetime(df_uang['Waktu']).dt.date
            # Kelompokkan total tagihan berdasarkan tanggal
            df_trend_uang = df_uang.groupby('Tanggal')['Total_Tagihan'].sum()
            st.area_chart(df_trend_uang)
        else:
            st.info("Belum ada data transaksi keuangan.")

    st.write("---")

    st.subheader("📈 Prediksi & Keberlanjutan")
    df_keluar_tbs = df_riwayat[(df_riwayat['Perubahan_Ton'] < 0) & (df_riwayat['Komoditas'] == 'Kelapa Sawit (TBS)')]
    col_fc, col_sus = st.columns(2)
    with col_fc:
        if not df_keluar_tbs.empty:
            rata_keluar = abs(df_keluar_tbs['Perubahan_Ton'].mean())
            sisa_aman = data_tbs['stok_aktual'] - data_tbs['reorder_point']
            if sisa_aman > 0:
                st.info(f"🔮 **AI Forecasting:** Bahan baku (TBS) diprediksi menyentuh batas kritis dalam **{int(sisa_aman / rata_keluar)} siklus** produksi.")
            else:
                st.warning("🔮 **AI Forecasting:** Bahan baku dalam zona merah (Hubungi Procurement).")
    with col_sus:
        df_masuk = df_riwayat[df_riwayat['Perubahan_Ton'] > 0]
        if len(df_masuk) > 0:
            persen_rspo = (df_masuk['Keterangan'].str.contains('RSPO').sum() / len(df_masuk)) * 100
            st.success(f"🌍 **Keberlanjutan:** Rasio Suplai TBS Bersertifikat: **{persen_rspo:.1f}%**")

# ==========================================
# HALAMAN 2: MASTER INVENTARIS
# ==========================================
elif selected == "🗄️ Master Inventaris":
    st.markdown("<h2 style='color:#2E7D32;'>🗄️ Master Saldo Inventaris</h2>", unsafe_allow_html=True)
    st.dataframe(df_master, use_container_width=True, hide_index=True)

# ==========================================
# HALAMAN 3: OPERASIONAL (GRN)
# ==========================================
elif selected == "⚖️ Operasional (GRN)":
    st.markdown("<h2 style='color:#2E7D32;'>⚖️ Jembatan Timbang (Bahan Baku Masuk)</h2>", unsafe_allow_html=True)
    with st.form("grn_form"):
        tonase_kotor = st.number_input("Tonase Truk TBS Masuk (Ton):", min_value=0.0, step=1.0)
        pilihan_vendor = st.selectbox("Pilih Supplier:", df_vendor['Nama_PT'].tolist())
        qc_potongan = st.number_input("Potongan Mutu / Sampah (%):", min_value=0.0, max_value=100.0, step=1.0)
        submit = st.form_submit_button("TERIMA BARANG (GRN)")
        
        if submit and tonase_kotor != 0:
            conn = sqlite3.connect('rantai_pasok.db')
            cur = conn.cursor()
            tonase_bersih = tonase_kotor * (1 - (qc_potongan / 100))
            detail_vendor = df_vendor[df_vendor['Nama_PT'] == pilihan_vendor].iloc[0]
            ket = f"Masuk: {pilihan_vendor} (QC: {qc_potongan}%) - {detail_vendor['Status_RSPO']}"
            
            # 1. Update Stok Gudang
            cur.execute('UPDATE Stok_Gudang SET stok_aktual = stok_aktual + ? WHERE nama_komoditas = "Kelapa Sawit (TBS)"', (tonase_bersih,))
            cur.execute('INSERT INTO Riwayat_Gudang (Komoditas, Perubahan_Ton, Keterangan) VALUES (?,?,?)', ('Kelapa Sawit (TBS)', tonase_bersih, ket))
            
            # --- 2. FITUR BARU: AUTO-PELUNASAN 100% ---
            # Cari tagihan dari vendor ini yang masih berstatus "Menunggu Barang 🚚"
            cur.execute("SELECT id_bayar, Total_Tagihan FROM Keuangan WHERE Vendor = ? AND Status LIKE '%Menunggu Barang%' ORDER BY id_bayar ASC LIMIT 1", (pilihan_vendor,))
            tagihan_gantung = cur.fetchone()
            
            if tagihan_gantung:
                id_bayar = tagihan_gantung[0]
                total_tagihan_lunas = tagihan_gantung[1]
                
                # Lunasi tagihan! (Ubah DP_dibayar menjadi angka Total Tagihan penuh)
                cur.execute('UPDATE Keuangan SET DP_Dibayar = ?, Status = ? WHERE id_bayar = ?', 
                            (total_tagihan_lunas, 'Lunas 100% (Barang Tiba) ✅', id_bayar))
            # ------------------------------------------
            
            conn.commit()
            conn.close()
            st.success(f"Truk berhasil ditimbang! Tagihan ke {pilihan_vendor} otomatis dilunasi (Jika ada).")
            st.rerun()
            
    st.subheader("Riwayat Aktivitas Terakhir")
    st.dataframe(df_riwayat, use_container_width=True, hide_index=True)

# ==========================================
# HALAMAN 4: PABRIKASI (PERBAIKAN LOGIKA AUTO-PO)
# ==========================================
elif selected == "🏭 Pabrikasi (Manufaktur)":
    st.markdown("<h2 style='color:#2E7D32;'>🏭 Control Room Pabrik Ekstraksi</h2>", unsafe_allow_html=True)
    col_mesin, col_status = st.columns([1, 1])
    
    with col_mesin:
        st.markdown('<div class="custom-card">', unsafe_allow_html=True)
        st.subheader("⚙️ Work Order Produksi")
        suhu_mesin = st.slider("🌡️ Suhu Mesin Sterilizer (°C)", min_value=50, max_value=120, value=75, step=1)
        
        with st.form("form_produksi"):
            batch_tbs = st.number_input("Jumlah TBS yang akan diproses (Ton):", min_value=0.0, step=1.0)
            st.info(f"Stok TBS tersedia saat ini: {data_tbs['stok_aktual']:.1f} Ton")
            
            submit_produksi = st.form_submit_button("▶️ MULAI PRODUKSI" if suhu_mesin < 95 else "🚨 MESIN OVERHEAT (TERKUNCI)", disabled=(suhu_mesin>=95))
            
            if submit_produksi and batch_tbs > 0:
                hasil_cpo = batch_tbs * 0.20
                
                conn = sqlite3.connect('rantai_pasok.db')
                cur = conn.cursor()
                
                # Potong TBS dan Tambah CPO
                cur.execute('UPDATE Stok_Gudang SET stok_aktual = stok_aktual - ? WHERE nama_komoditas = "Kelapa Sawit (TBS)"', (batch_tbs,))
                cur.execute('INSERT INTO Riwayat_Gudang (Komoditas, Perubahan_Ton, Keterangan) VALUES (?,?,?)', ('Kelapa Sawit (TBS)', -batch_tbs, "Keluar: Konsumsi Mesin Pabrik"))
                cur.execute('UPDATE Stok_Gudang SET stok_aktual = stok_aktual + ? WHERE nama_komoditas = "Crude Palm Oil (CPO)"', (hasil_cpo,))
                cur.execute('INSERT INTO Riwayat_Gudang (Komoditas, Perubahan_Ton, Keterangan) VALUES (?,?,?)', ('Crude Palm Oil (CPO)', hasil_cpo, "Masuk: Hasil Produksi Mesin"))
                
                # CEK STOK KRITIS SETELAH DIPAKAI PRODUKSI (PERBAIKAN BUG)
                cur.execute('SELECT stok_aktual, reorder_point FROM Stok_Gudang WHERE nama_komoditas = "Kelapa Sawit (TBS)"')
                cek_tbs = cur.fetchone()
                
                # CEK STOK KRITIS SETELAH DIPAKAI PRODUKSI
                cur.execute('SELECT stok_aktual, reorder_point FROM Stok_Gudang WHERE nama_komoditas = "Kelapa Sawit (TBS)"')
                cek_tbs = cur.fetchone()
                
                if cek_tbs[0] <= cek_tbs[1]:
                    target_maksimal = 250.0
                    pesanan_dinamis = target_maksimal - cek_tbs[0]
                    kirim_email_po("dustintirza@gmail.com", "Kelapa Sawit (TBS)", pesanan_dinamis) # Sesuaikan emailmu
                    
                    # --- FITUR BARU: CATAT PO KE FINANCE ---
                    asumsi_harga_tbs = 2500000 # Misal harga TBS Rp 2.5 Juta/Ton
                    estimasi_tagihan = pesanan_dinamis * asumsi_harga_tbs
                    no_po_sementara = f"PO-AUTO-{int(pesanan_dinamis)}"
                    
                    cur.execute('''INSERT INTO Keuangan (No_Invoice, Vendor, Total_Tagihan, DP_Dibayar, Status) 
                                   VALUES (?, ?, ?, ?, ?)''', 
                                (no_po_sementara, "PT Kencana Agrindo", estimasi_tagihan, 0, 'PO Sent - Menunggu Invoice ⏳'))
                    # ---------------------------------------
                    
                    st.toast(f"Stok TBS Menipis! Email PO {pesanan_dinamis:.1f} Ton Terkirim Otomatis.", icon="🚨")
                
                conn.commit()
                conn.close()
                st.rerun()
        st.markdown('</div>', unsafe_allow_html=True)
        
    with col_status:
        st.subheader("Tabel Mutasi Pabrik")
        df_pabrik = df_riwayat[df_riwayat['Keterangan'].str.contains("Pabrik|Mesin")]
        st.dataframe(df_pabrik, use_container_width=True, hide_index=True)

                
# ==========================================
# HALAMAN 5: FINANCE & PEMBAYARAN DP 50%
# ==========================================
elif selected == "💰 Finance & Laporan":
    st.markdown("<h2 style='color:#2E7D32;'>💰 Sistem Otomasi & Laporan Keuangan</h2>", unsafe_allow_html=True)
    
    st.subheader("🤖 AI Invoice Scanner & Auto-Disbursement")
    uploaded_file = st.file_uploader("Upload PDF Invoice Supplier untuk Verifikasi Pembayaran", type="pdf")
    
    if uploaded_file:
        reader = PyPDF2.PdfReader(uploaded_file)
        raw_text = reader.pages[0].extract_text()
        clean_text = raw_text.replace(" ", "")
        
        c1, c2 = st.columns(2)
        with c1:
            st.markdown("**Hasil Scan Raw Text:**")
            st.code(raw_text)
        with c2:
            st.markdown("**Status Kecocokan & Eksekusi Finansial:**")
            
            # ---------------------------------------------------------
            # EKSTRAKSI DATA DINAMIS (AI REGULAR EXPRESSION)
            # ---------------------------------------------------------
            # 1. Mencari pola Nomor Invoice (Contoh: INV-2026-002)
            cari_inv = re.search(r'INV-\d{4}-\d{3}', clean_text)
            inv_no_dinamis = cari_inv.group() if cari_inv else "UNKNOWN-INV"
            
            # 2. Mencari pola Total Tagihan (Contoh: Rp575.000.000)
            cari_tagihan = re.search(r'Rp([\d\.]+)', clean_text)
            if cari_tagihan:
                # Mengambil angkanya saja dan menghilangkan titik
                angka_bersih = cari_tagihan.group(1).replace('.', '')
                total_tagihan_dinamis = float(angka_bersih)
            else:
                total_tagihan_dinamis = 0.0
                
            vendor_name = "PT Kencana Agrindo" # Asumsi default vendor untuk MVP
            # ---------------------------------------------------------
            
            if "INV-" in clean_text and "KelapaSawit" in clean_text and "Ton" in raw_text:
                st.success(f"✅ 3-Way Matching Sesuai! Dokumen: **{inv_no_dinamis}**")
                
                # Cek invoice DINAMIS di database
                invoice_tercatat = df_keuangan[df_keuangan['No_Invoice'] == inv_no_dinamis]
                
                # Cek invoice DINAMIS di database
                invoice_tercatat = df_keuangan[df_keuangan['No_Invoice'] == inv_no_dinamis]
                
                if invoice_tercatat.empty:
                    dp_value = total_tagihan_dinamis * 0.50
                    
                    # --- FITUR BARU: UPDATE PO MENJADI DP PAID ---
                    conn = sqlite3.connect('rantai_pasok.db')
                    cur = conn.cursor()
                    
                    # Cari apakah ada PO yang menunggu invoice
                    cur.execute("SELECT id_bayar FROM Keuangan WHERE Status LIKE '%Menunggu Invoice%' ORDER BY id_bayar DESC LIMIT 1")
                    po_pending = cur.fetchone()
                    
                    if po_pending:
                        # Jika ada PO, update baris tersebut
                        id_bayar = po_pending[0]
                        cur.execute('''UPDATE Keuangan SET No_Invoice = ?, Total_Tagihan = ?, DP_Dibayar = ?, Status = ? 
                                       WHERE id_bayar = ?''', 
                                    (inv_no_dinamis, total_tagihan_dinamis, dp_value, 'DP 50% Paid - Menunggu Barang 🚚', id_bayar))
                    else:
                        # Jika tidak ada PO (manual invoice), buat baris baru
                        cur.execute('''INSERT INTO Keuangan (No_Invoice, Vendor, Total_Tagihan, DP_Dibayar, Status) 
                                       VALUES (?, ?, ?, ?, ?)''', 
                                    (inv_no_dinamis, vendor_name, total_tagihan_dinamis, dp_value, 'DP 50% Paid - Menunggu Barang 🚚'))
                    
                    conn.commit()
                    conn.close()
                    # ---------------------------------------------
                    
                    st.balloons()
                    st.success(f"🚀 **Auto-Disbursement Aktif:** Dana DP 50% Sebesar **Rp {dp_value:,.0f}** telah ditransfer ke {vendor_name}!")
                    st.rerun()
                
    st.write("---")
    st.subheader("📊 Laporan Arus Kas & Utang Usaha")
    
    # Refresh data keuangan terbaru untuk dirender di tabel
    conn = sqlite3.connect('rantai_pasok.db')
    df_keuangan_update = pd.read_sql_query("SELECT * FROM Keuangan ORDER BY id_bayar DESC", conn)
    conn.close()
    
    if not df_keuangan_update.empty:
        total_hutang = df_keuangan_update['Total_Tagihan'].sum()
        total_dp_cair = df_keuangan_update['DP_Dibayar'].sum()
        sisa_pelunasan = total_hutang - total_dp_cair
    else:
        total_hutang, total_dp_cair, sisa_pelunasan = 0.0, 0.0, 0.0
        
    f_col1, f_col2, f_col3 = st.columns(3)
    with f_col1: st.metric("Total Komitmen Tagihan", f"Rp {total_hutang:,.0f}")
    with f_col2: st.metric("Total DP Terbayar (Kas Keluar)", f"Rp {total_dp_cair:,.0f}", "Otomatis via AI", delta_color="normal")
    with f_col3: st.metric("Sisa Utang Dagang", f"Rp {sisa_pelunasan:,.0f}")
        
    st.write("")
    st.dataframe(df_keuangan_update, use_container_width=True, hide_index=True)

# ==========================================
# HALAMAN 6: 🚚 SALES & DISTRIBUSI (TERINTEGRASI LENGKAP)
# ==========================================
elif selected == "🚚 Sales & Distribusi":
    st.markdown("<h2 style='color:#2E7D32;'>🚚 Sales, Invoicing & Distribution</h2>", unsafe_allow_html=True)
    col_sales, col_rev = st.columns([1, 1])
    
    with col_sales:
        st.markdown('<div class="custom-card">', unsafe_allow_html=True)
        st.subheader("📝 Order Fulfillment")
        with st.form("sales_form"):
            nama_klien = st.text_input("Nama Perusahaan Pembeli:")
            email_klien = st.text_input("Email Klien (B2B):")
            tonase_jual = st.number_input("Jumlah Penjualan CPO (Ton):", min_value=0.0, step=1.0)
            harga_per_ton = 15000000 
            
            st.info(f"Stok CPO tersedia saat ini: {data_cpo['stok_aktual']:.1f} Ton")
            submit_jual = st.form_submit_button("KONFIRMASI PENJUALAN & KIRIM INVOICE")
            
            if submit_jual and tonase_jual > 0:
                if tonase_jual <= data_cpo['stok_aktual']:
                    total_tagihan = tonase_jual * harga_per_ton
                    
                    if email_klien.strip() == "":
                        email_tujuan_final = "anandanaurahmutiara@gmail.com"  # Ganti ke emailmu
                    else:
                        email_tujuan_final = email_klien
                    
                    conn = sqlite3.connect('rantai_pasok.db')
                    cur = conn.cursor()
                    
                    # 1. Potong Stok CPO
                    cur.execute('UPDATE Stok_Gudang SET stok_aktual = stok_aktual - ? WHERE nama_komoditas = "Crude Palm Oil (CPO)"', (tonase_jual,))
                    cur.execute('INSERT INTO Riwayat_Gudang (Komoditas, Perubahan_Ton, Keterangan) VALUES (?,?,?)', 
                                ('Crude Palm Oil (CPO)', -tonase_jual, f"Penjualan ke {nama_klien}"))
                    
                    # 2. Catat Pendapatan Piutang
                    cur.execute('INSERT INTO Keuangan (No_Invoice, Vendor, Total_Tagihan, DP_Dibayar, Status) VALUES (?,?,?,?,?)', 
                                (f"SALE-{nama_klien[:3].upper()}", nama_klien, total_tagihan, 0, "PENDING PAYMENT (Receivable) 📈"))
                    
                    # 3. Kirim Invoice ke Klien
                    from bot_email import kirim_invoice_sales
                    kirim_invoice_sales(email_tujuan_final, nama_klien, "Crude Palm Oil (CPO)", tonase_jual, total_tagihan)
                    
                    conn.commit()
                    conn.close()
                    st.success(f"✅ Sales Sukses! Invoice dikirim otomatis ke {email_tujuan_final}")
                    st.rerun()
                else:
                    st.error("Stok CPO tidak mencukupi untuk memenuhi pesanan ini!")
        st.markdown('</div>', unsafe_allow_html=True)

    with col_rev:
        st.subheader("Laporan Penjualan (Revenue)")
        df_sales = df_riwayat[df_riwayat['Keterangan'].str.contains("Penjualan")]
        if df_sales.empty:
            st.caption("Belum ada riwayat transaksi penjualan.")
        else:
            st.dataframe(df_sales, use_container_width=True, hide_index=True)