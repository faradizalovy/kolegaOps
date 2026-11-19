
-- =============================================================================
-- KOLEGAOPS - DATABASE SCHEMA (POSTGRESQL)
-- Aplikasi Point of Sales (POS) Warkop "KolegaOps"
-- =============================================================================

-- PERHATIAN:
-- Script ini digunakan untuk membuat database baru dari awal.
-- DROP TABLE akan menghapus tabel beserta seluruh datanya.
-- Jangan jalankan ulang pada database aktif yang masih digunakan.

-- =============================================================================
-- 1. HAPUS TABEL LAMA
-- =============================================================================

DROP TABLE IF EXISTS riwayat_stok CASCADE;
DROP TABLE IF EXISTS detail_transaksi CASCADE;
DROP TABLE IF EXISTS transaksi CASCADE;
DROP TABLE IF EXISTS resep CASCADE;
DROP TABLE IF EXISTS bahan_baku CASCADE;
DROP TABLE IF EXISTS menu CASCADE;
DROP TABLE IF EXISTS pengeluaran_shift CASCADE;
DROP TABLE IF EXISTS shift CASCADE;
DROP TABLE IF EXISTS users CASCADE;


-- =============================================================================
-- 2. TABEL USERS (MASTER PENGGUNA / PEGAWAI)
-- =============================================================================

CREATE TABLE users (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL
        CHECK (role IN ('super_admin', 'owner', 'admin', 'kasir')),
    status VARCHAR(20) NOT NULL DEFAULT 'aktif',
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================================
-- 3. TABEL SHIFT (SESI OPERASIONAL KASIR)
-- =============================================================================

CREATE TABLE shift (
    id_shift SERIAL PRIMARY KEY,
    nama_shift VARCHAR(10) NOT NULL,
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    waktu_buka TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    waktu_tutup TIMESTAMP NULL,
    total_penjualan_tunai DECIMAL(12,2) DEFAULT 0,
    total_penjualan_qris DECIMAL(12,2) DEFAULT 0,
    total_pengeluaran DECIMAL(12,2) DEFAULT 0,
    saldo_akhir DECIMAL(12,2) DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'buka',
    id_user INT NOT NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_shift_user
        FOREIGN KEY (id_user)
        REFERENCES users(id_user)
        ON DELETE RESTRICT
);

CREATE INDEX idx_shift_id_user ON shift(id_user);
CREATE INDEX idx_shift_status ON shift(status);
CREATE INDEX idx_shift_tanggal ON shift(tanggal);


-- =============================================================================
-- 4. TABEL PENGELUARAN_SHIFT (PENGELUARAN OPERASIONAL)
-- =============================================================================

CREATE TABLE pengeluaran_shift (
    id_pengeluaran SERIAL PRIMARY KEY,
    keterangan VARCHAR(100) NOT NULL,
    nominal DECIMAL(12,2) NOT NULL,
    waktu_catat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_shift INT NOT NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pengeluaran_shift
        FOREIGN KEY (id_shift)
        REFERENCES shift(id_shift)
        ON DELETE CASCADE
);

CREATE INDEX idx_pengeluaran_id_shift
    ON pengeluaran_shift(id_shift);


-- =============================================================================
-- 5. TABEL MENU (KATALOG PRODUK)
-- =============================================================================

CREATE TABLE menu (
    id_menu SERIAL PRIMARY KEY,
    nama_menu VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    hpp DECIMAL(12,2) NOT NULL,
    stok_menu INT DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'tersedia',
    gambar VARCHAR(255) NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_menu_kategori ON menu(kategori);
CREATE INDEX idx_menu_status ON menu(status);


-- =============================================================================
-- 6. TABEL BAHAN_BAKU (INVENTARIS BAHAN MENTAH)
-- =============================================================================

CREATE TABLE bahan_baku (
    id_bahan SERIAL PRIMARY KEY,
    nama_bahan VARCHAR(100) NOT NULL,
    stok_bahan DECIMAL(12,2) NOT NULL DEFAULT 0,
    satuan VARCHAR(20) NOT NULL,
    minimum_stok DECIMAL(12,2) NOT NULL DEFAULT 0,
    status_stok VARCHAR(20) NOT NULL DEFAULT 'aman',
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================================
-- 7. TABEL RESEP (KOMPOSISI BAHAN BAKU SETIAP MENU)
-- =============================================================================

CREATE TABLE resep (
    id_resep SERIAL PRIMARY KEY,
    id_menu INT NOT NULL,
    id_bahan INT NOT NULL,
    jumlah_pemakaian DECIMAL(12,2) NOT NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_resep_menu
        FOREIGN KEY (id_menu)
        REFERENCES menu(id_menu)
        ON DELETE CASCADE,

    CONSTRAINT fk_resep_bahan
        FOREIGN KEY (id_bahan)
        REFERENCES bahan_baku(id_bahan)
        ON DELETE RESTRICT,

    CONSTRAINT uq_menu_bahan
        UNIQUE (id_menu, id_bahan)
);

CREATE INDEX idx_resep_id_menu ON resep(id_menu);
CREATE INDEX idx_resep_id_bahan ON resep(id_bahan);


-- =============================================================================
-- 8. TABEL TRANSAKSI (TRANSAKSI PENJUALAN POS)
-- =============================================================================

CREATE TABLE transaksi (
    id_transaksi SERIAL PRIMARY KEY,
    kode_transaksi VARCHAR(255) UNIQUE NOT NULL,
    nama_customer VARCHAR(100) NULL,
    total_item INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    diskon DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    metode_pembayaran VARCHAR(30) NOT NULL,
    nominal_bayar DECIMAL(12,2) NOT NULL,
    kembalian DECIMAL(12,2) NOT NULL,
    catatan_transaksi VARCHAR(255) NULL,
    id_user INT NOT NULL,
    id_shift INT NOT NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_transaksi_user
        FOREIGN KEY (id_user)
        REFERENCES users(id_user)
        ON DELETE RESTRICT,

    CONSTRAINT fk_transaksi_shift
        FOREIGN KEY (id_shift)
        REFERENCES shift(id_shift)
        ON DELETE RESTRICT
);

CREATE INDEX idx_transaksi_id_user ON transaksi(id_user);
CREATE INDEX idx_transaksi_id_shift ON transaksi(id_shift);
CREATE INDEX idx_transaksi_kode ON transaksi(kode_transaksi);
CREATE INDEX idx_transaksi_create_at ON transaksi(create_at);


-- =============================================================================
-- 9. TABEL DETAIL_TRANSAKSI (RINCIAN ITEM PENJUALAN)
-- =============================================================================

CREATE TABLE detail_transaksi (
    id_detail SERIAL PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_menu INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    catatan_item VARCHAR(255) NULL,

    CONSTRAINT fk_detail_transaksi
        FOREIGN KEY (id_transaksi)
        REFERENCES transaksi(id_transaksi)
        ON DELETE CASCADE,

    CONSTRAINT fk_detail_menu
        FOREIGN KEY (id_menu)
        REFERENCES menu(id_menu)
        ON DELETE RESTRICT
);

CREATE INDEX idx_detail_id_transaksi
    ON detail_transaksi(id_transaksi);

CREATE INDEX idx_detail_id_menu
    ON detail_transaksi(id_menu);


-- =============================================================================
-- 10. TABEL RIWAYAT_STOK (RIWAYAT PERUBAHAN STOK BAHAN BAKU)
-- =============================================================================

CREATE TABLE riwayat_stok (
    id_riwayat SERIAL PRIMARY KEY,

    id_bahan INTEGER NULL,
    id_user INTEGER NULL,
    id_transaksi INTEGER NULL,

    nama_bahan VARCHAR(150) NOT NULL,
    satuan VARCHAR(30) NOT NULL,

    jenis VARCHAR(10) NOT NULL
        CHECK (jenis IN ('masuk', 'keluar')),

    jumlah NUMERIC(18,3) NOT NULL
        CHECK (jumlah > 0),

    stok_sebelum NUMERIC(18,3) NOT NULL,
    stok_sesudah NUMERIC(18,3) NOT NULL,

    sumber VARCHAR(20) NOT NULL
        CHECK (sumber IN ('manual', 'pos')),

    keterangan TEXT,

    create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_riwayat_stok_bahan
        FOREIGN KEY (id_bahan)
        REFERENCES bahan_baku(id_bahan)
        ON DELETE SET NULL,

    CONSTRAINT fk_riwayat_stok_user
        FOREIGN KEY (id_user)
        REFERENCES users(id_user)
        ON DELETE SET NULL,

    CONSTRAINT fk_riwayat_stok_transaksi
        FOREIGN KEY (id_transaksi)
        REFERENCES transaksi(id_transaksi)
        ON DELETE SET NULL
);

CREATE INDEX idx_riwayat_stok_bahan
    ON riwayat_stok(id_bahan);

CREATE INDEX idx_riwayat_stok_user
    ON riwayat_stok(id_user);

CREATE INDEX idx_riwayat_stok_transaksi
    ON riwayat_stok(id_transaksi);

CREATE INDEX idx_riwayat_stok_create_at
    ON riwayat_stok(create_at DESC);


-- =============================================================================
-- 11. DATA AWAL USERS
-- =============================================================================

-- Password menggunakan hash bcrypt.
-- Akun Super Admin dibuat melalui fitur pembuatan akun Super Admin aplikasi.

INSERT INTO users (username, password, role, status) VALUES
(
    'super_admin',
    '$2y$10$D3GvFXArHb/8Ik8RQ5NJNeJyqohmVQHcjDqt/0ukOe.EEH9vT9t0u',
    'super_admin',
    'aktif'
),
(
    'owner',
    '$2y$10$D3GvFXArHb/8Ik8RQ5NJNeJyqohmVQHcjDqt/0ukOe.EEH9vT9t0u',
    'owner',
    'aktif'
),
(
    'admin',
    '$2y$10$D3GvFXArHb/8Ik8RQ5NJNeJyqohmVQHcjDqt/0ukOe.EEH9vT9t0u',
    'admin',
    'aktif'
),
(
    'kasir',
    '$2y$10$KZJgPxJ7St12dOw63IWohe6NqPVdkG/kAdpJCCAeSYFps/mGE8Aou',
    'kasir',
    'aktif'
)
ON CONFLICT (username) DO NOTHING;


-- =============================================================================
-- 12. DATA AWAL BAHAN BAKU
-- =============================================================================

INSERT INTO bahan_baku
    (id_bahan, nama_bahan, stok_bahan, satuan, minimum_stok, status_stok)
VALUES
    (1, 'Biji Kopi Robusta Dampit', 2500.00, 'gr', 500.00, 'aman'),
    (2, 'Susu Kental Manis', 3000.00, 'ml', 600.00, 'aman'),
    (3, 'Gula Pasir', 5000.00, 'gr', 1000.00, 'aman'),
    (4, 'Teh Tubruk Melati', 1500.00, 'gr', 300.00, 'aman'),
    (5, 'Mie Instan Goreng', 40.00, 'pcs', 10.00, 'aman'),
    (6, 'Telur Ayam', 30.00, 'butir', 10.00, 'aman'),
    (7, 'Coklat Bubuk Warkop', 2000.00, 'gr', 400.00, 'aman'),
    (8, 'Roti Tawar', 20.00, 'pcs', 5.00, 'aman'),
    (9, 'Keju Parut & Meises', 1000.00, 'gr', 200.00, 'aman')
ON CONFLICT (id_bahan) DO NOTHING;


-- =============================================================================
-- 13. DATA AWAL MENU
-- =============================================================================

INSERT INTO menu
    (id_menu, nama_menu, kategori, harga, hpp, stok_menu, status)
VALUES
    (1, 'Kopi Hitam Tubruk', 'Kopi', 5000.00, 2000.00, 100, 'tersedia'),
    (2, 'Kopi Susu Tradisional', 'Kopi', 8000.00, 3500.00, 100, 'tersedia'),
    (3, 'Es Teh Manis', 'Minuman', 4000.00, 1500.00, 100, 'tersedia'),
    (4, 'Es Coklat Warkop', 'Minuman', 9000.00, 4000.00, 50, 'tersedia'),
    (5, 'Mie Goreng + Telur', 'Makanan', 10000.00, 5500.00, 40, 'tersedia'),
    (6, 'Roti Bakar Coklat Keju', 'Camilan', 12000.00, 6000.00, 30, 'tersedia')
ON CONFLICT (id_menu) DO NOTHING;


-- =============================================================================
-- 14. DATA RESEP (KOMPOSISI BAHAN BAKU SETIAP MENU)
-- =============================================================================

INSERT INTO resep
    (id_menu, id_bahan, jumlah_pemakaian)
VALUES
    -- 1. Kopi Hitam Tubruk
    (1, 1, 15.00),
    (1, 3, 10.00),

    -- 2. Kopi Susu Tradisional
    (2, 1, 15.00),
    (2, 2, 30.00),
    (2, 3, 5.00),

    -- 3. Es Teh Manis
    (3, 4, 10.00),
    (3, 3, 20.00),

    -- 4. Es Coklat Warkop
    (4, 7, 35.00),
    (4, 2, 25.00),

    -- 5. Mie Goreng + Telur
    (5, 5, 1.00),
    (5, 6, 1.00),

    -- 6. Roti Bakar Coklat Keju
    (6, 8, 2.00),
    (6, 9, 30.00)

ON CONFLICT (id_menu, id_bahan) DO NOTHING;
