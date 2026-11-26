
<?php
// models/MenuModel.php
// Model Katalog Menu, Resep, dan Kustomisasi Produk KolegaOps

class MenuModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Mengambil seluruh data menu beserta keuntungan.
     */
    public function getAllMenu(): array
    {
        $sql = "
            SELECT
                id_menu AS id,
                nama_menu AS nama,
                kategori,
                hpp,
                harga,
                (harga - hpp) AS keuntungan,
                stok_menu AS stok,
                status,
                CASE
                    WHEN LOWER(status) IN ('aktif', 'tersedia') THEN 'Aktif'
                    ELSE 'Non-Aktif'
                END AS status_label,
                update_at
            FROM menu
            ORDER BY
                CASE
                    WHEN LOWER(status) IN ('aktif', 'tersedia') THEN 0
                    ELSE 1
                END,
                kategori ASC,
                nama_menu ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil detail satu produk berdasarkan ID.
     */
    public function getMenuById(int $id): ?array
    {
        $sql = "
            SELECT
                id_menu AS id,
                nama_menu AS nama,
                kategori,
                hpp,
                harga,
                (harga - hpp) AS keuntungan,
                stok_menu AS stok,
                status,
                CASE
                    WHEN LOWER(status) IN ('aktif', 'tersedia') THEN 'Aktif'
                    ELSE 'Non-Aktif'
                END AS status_label,
                update_at
            FROM menu
            WHERE id_menu = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Menyimpan produk baru atau memperbarui produk.
     * Mengembalikan ID menu jika berhasil.
     */
    public function simpanMenu(array $data): int|false
    {
        $id = isset($data['id']) && (int) $data['id'] > 0
            ? (int) $data['id']
            : null;

        $nama = trim($data['nama'] ?? $data['nama_menu'] ?? '');
        $kategori = trim($data['kategori'] ?? 'Minuman');
        $hpp = max(0, (float) ($data['hpp'] ?? 0));
        $harga = max(0, (float) ($data['harga'] ?? 0));
        $status = trim($data['status'] ?? 'Aktif');
        $stok = max(0, (int) ($data['stok'] ?? $data['stok_menu'] ?? 0));

        if ($nama === '') {
            return false;
        }

        try {
            if ($id !== null) {
                $sql = "
                    UPDATE menu
                    SET nama_menu = :nama,
                        kategori = :kategori,
                        hpp = :hpp,
                        harga = :harga,
                        status = :status,
                        stok_menu = :stok,
                        update_at = CURRENT_TIMESTAMP
                    WHERE id_menu = :id
                ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':kategori' => $kategori,
                    ':hpp' => $hpp,
                    ':harga' => $harga,
                    ':status' => $status,
                    ':stok' => $stok,
                    ':id' => $id,
                ]);

                return $stmt->rowCount() >= 0 ? $id : false;
            }

            $sql = "
                INSERT INTO menu (
                    nama_menu,
                    kategori,
                    hpp,
                    harga,
                    status,
                    stok_menu
                )
                VALUES (
                    :nama,
                    :kategori,
                    :hpp,
                    :harga,
                    :status,
                    :stok
                )
                RETURNING id_menu
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nama' => $nama,
                ':kategori' => $kategori,
                ':hpp' => $hpp,
                ':harga' => $harga,
                ':status' => $status,
                ':stok' => $stok,
            ]);

            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log('[MenuModel::simpanMenu] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus produk.
     */
    public function hapusMenu(int $id): bool
    {
        try {
            $sql = "DELETE FROM menu WHERE id_menu = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log('[MenuModel::hapusMenu] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengambil produk terlaris hari ini.
     * Jika belum ada transaksi hari ini, gunakan data keseluruhan.
     */
    public function getProdukTerlarisHariIni(int $limit = 3): array
    {
        $sql = "
            SELECT
                m.id_menu,
                m.nama_menu,
                COALESCE(SUM(dt.jumlah), 0) AS terjual,
                COALESCE(SUM(dt.subtotal), 0) AS total_penjualan
            FROM detail_transaksi dt
            JOIN menu m ON dt.id_menu = m.id_menu
            JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
            WHERE DATE(t.create_at) = CURRENT_DATE
            GROUP BY m.id_menu, m.nama_menu
            ORDER BY terjual DESC, total_penjualan DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            $sqlAllTime = "
                SELECT
                    m.id_menu,
                    m.nama_menu,
                    COALESCE(SUM(dt.jumlah), 0) AS terjual,
                    COALESCE(SUM(dt.subtotal), 0) AS total_penjualan
                FROM menu m
                LEFT JOIN detail_transaksi dt
                    ON m.id_menu = dt.id_menu
                GROUP BY m.id_menu, m.nama_menu
                ORDER BY terjual DESC, m.nama_menu ASC
                LIMIT :limit
            ";

            $stmtAll = $this->pdo->prepare($sqlAllTime);
            $stmtAll->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmtAll->execute();

            $results = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        }

        return $results;
    }

    /**
     * Mengambil katalog produk aktif untuk POS kasir.
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                id_menu AS id,
                nama_menu AS nama,
                kategori,
                harga,
                hpp,
                stok_menu AS stok,
                status,
                gambar
            FROM menu
            WHERE LOWER(status) IN ('aktif', 'tersedia')
            ORDER BY
                CASE
                    WHEN kategori ILIKE '%kopi%' THEN 1
                    WHEN kategori ILIKE '%milk%' THEN 2
                    WHEN kategori ILIKE '%hot%'
                      OR kategori ILIKE '%ice%'
                      OR kategori ILIKE '%teh%'
                      OR kategori ILIKE '%minuman%' THEN 3
                    WHEN kategori ILIKE '%snack%'
                      OR kategori ILIKE '%camilan%' THEN 4
                    WHEN kategori ILIKE '%nakam%'
                      OR kategori ILIKE '%makanan%' THEN 5
                    ELSE 6
                END,
                nama_menu ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil kategori produk aktif.
     */
    public function getKategoriAktif(): array
    {
        $sql = "
            SELECT DISTINCT kategori
            FROM menu
            WHERE LOWER(status) IN ('aktif', 'tersedia')
            ORDER BY kategori ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    // =====================================================
    // PENGELOLAAN KUSTOMISASI PRODUK
    // =====================================================

    /**
     * Mengambil kelompok kustomisasi beserta pilihan-pilihannya.
     *
     * Contoh:
     * Level Gula
     * - Normal
     * - Less Sugar
     * - No Sugar
     *
     * @param int $idMenu ID produk
     * @param bool $hanyaAktif Filter kelompok dan pilihan aktif
     */
    public function getKustomisasiByMenu(
        int $idMenu,
        bool $hanyaAktif = false
    ): array {
        $filterKelompok = $hanyaAktif
            ? "AND ok.status = TRUE"
            : "";

        $filterPilihan = $hanyaAktif
            ? "AND dok.status = TRUE"
            : "";

        $sql = "
            SELECT
                ok.id_opsi,
                ok.id_menu,
                ok.nama_opsi,
                ok.tipe_pilihan,
                ok.wajib_dipilih,
                ok.urutan AS urutan_kelompok,
                ok.status AS status_kelompok,

                dok.id_detail_opsi,
                dok.nama_pilihan,
                dok.harga_tambahan,
                dok.urutan AS urutan_pilihan,
                dok.status AS status_pilihan

            FROM opsi_kustomisasi ok

            LEFT JOIN detail_opsi_kustomisasi dok
                ON ok.id_opsi = dok.id_opsi
                $filterPilihan

            WHERE ok.id_menu = :id_menu
                $filterKelompok

            ORDER BY
                ok.urutan ASC,
                ok.id_opsi ASC,
                dok.urutan ASC,
                dok.id_detail_opsi ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_menu' => $idMenu]);

        $hasil = [];
        $indexKelompok = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $idOpsi = (int) $row['id_opsi'];

            if (!isset($indexKelompok[$idOpsi])) {
                $indexKelompok[$idOpsi] = count($hasil);

                $hasil[] = [
                    'id_opsi' => $idOpsi,
                    'id_menu' => (int) $row['id_menu'],
                    'nama_opsi' => $row['nama_opsi'],
                    'tipe_pilihan' => $row['tipe_pilihan'],
                    'wajib_dipilih' => $row['wajib_dipilih'],
                    'urutan' => (int) $row['urutan_kelompok'],
                    'status' => $row['status_kelompok'],
                    'pilihan' => [],
                ];
            }

            if ($row['id_detail_opsi'] !== null) {
                $index = $indexKelompok[$idOpsi];

                $hasil[$index]['pilihan'][] = [
                    'id_detail_opsi' => (int) $row['id_detail_opsi'],
                    'nama_pilihan' => $row['nama_pilihan'],
                    'harga_tambahan' => (float) $row['harga_tambahan'],
                    'urutan' => (int) $row['urutan_pilihan'],
                    'status' => $row['status_pilihan'],
                ];
            }
        }

        return $hasil;
    }

    /**
     * Menyimpan atau mengubah kelompok kustomisasi.
     */
    public function simpanKelompokKustomisasi(array $data): int|false
    {
        $idOpsi = isset($data['id_opsi']) && (int) $data['id_opsi'] > 0
            ? (int) $data['id_opsi']
            : null;

        $idMenu = (int) ($data['id_menu'] ?? 0);
        $nama = trim($data['nama_opsi'] ?? '');
        $tipe = $data['tipe_pilihan'] ?? 'single';
        $wajib = filter_var(
            $data['wajib_dipilih'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $urutan = (int) ($data['urutan'] ?? 0);
        $status = filter_var(
            $data['status'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($idMenu <= 0 || $nama === '') {
            return false;
        }

        if (!in_array($tipe, ['single', 'multiple'], true)) {
            return false;
        }

        try {
            if ($idOpsi !== null) {
                $sql = "
                    UPDATE opsi_kustomisasi
                    SET nama_opsi = :nama,
                        tipe_pilihan = :tipe,
                        wajib_dipilih = :wajib,
                        urutan = :urutan,
                        status = :status
                    WHERE id_opsi = :id_opsi
                      AND id_menu = :id_menu
                ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':tipe' => $tipe,
                    ':wajib' => $wajib,
                    ':urutan' => $urutan,
                    ':status' => $status,
                    ':id_opsi' => $idOpsi,
                    ':id_menu' => $idMenu,
                ]);

                return $stmt->rowCount() >= 0 ? $idOpsi : false;
            }

            $sql = "
                INSERT INTO opsi_kustomisasi (
                    id_menu,
                    nama_opsi,
                    tipe_pilihan,
                    wajib_dipilih,
                    urutan,
                    status
                )
                VALUES (
                    :id_menu,
                    :nama,
                    :tipe,
                    :wajib,
                    :urutan,
                    :status
                )
                RETURNING id_opsi
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_menu' => $idMenu,
                ':nama' => $nama,
                ':tipe' => $tipe,
                ':wajib' => $wajib,
                ':urutan' => $urutan,
                ':status' => $status,
            ]);

            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log(
                '[MenuModel::simpanKelompokKustomisasi] ' .
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Menyimpan atau mengubah pilihan kustomisasi.
     */
    public function simpanPilihanKustomisasi(array $data): int|false
    {
        $idDetail = isset($data['id_detail_opsi'])
            && (int) $data['id_detail_opsi'] > 0
            ? (int) $data['id_detail_opsi']
            : null;

        $idOpsi = (int) ($data['id_opsi'] ?? 0);
        $nama = trim($data['nama_pilihan'] ?? '');
        $harga = (float) ($data['harga_tambahan'] ?? 0);
        $urutan = (int) ($data['urutan'] ?? 0);
        $status = filter_var(
            $data['status'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($idOpsi <= 0 || $nama === '' || $harga < 0) {
            return false;
        }

        try {
            if ($idDetail !== null) {
                $sql = "
                    UPDATE detail_opsi_kustomisasi
                    SET nama_pilihan = :nama,
                        harga_tambahan = :harga,
                        urutan = :urutan,
                        status = :status
                    WHERE id_detail_opsi = :id_detail
                      AND id_opsi = :id_opsi
                ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':harga' => $harga,
                    ':urutan' => $urutan,
                    ':status' => $status,
                    ':id_detail' => $idDetail,
                    ':id_opsi' => $idOpsi,
                ]);

                return $stmt->rowCount() >= 0 ? $idDetail : false;
            }

            $sql = "
                INSERT INTO detail_opsi_kustomisasi (
                    id_opsi,
                    nama_pilihan,
                    harga_tambahan,
                    urutan,
                    status
                )
                VALUES (
                    :id_opsi,
                    :nama,
                    :harga,
                    :urutan,
                    :status
                )
                RETURNING id_detail_opsi
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_opsi' => $idOpsi,
                ':nama' => $nama,
                ':harga' => $harga,
                ':urutan' => $urutan,
                ':status' => $status,
            ]);

            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log(
                '[MenuModel::simpanPilihanKustomisasi] ' .
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Menghapus kelompok kustomisasi.
     * Pilihan di dalamnya ikut terhapus melalui ON DELETE CASCADE.
     */
    public function hapusKelompokKustomisasi(
        int $idOpsi,
        int $idMenu
    ): bool {
        try {
            $sql = "
                DELETE FROM opsi_kustomisasi
                WHERE id_opsi = :id_opsi
                  AND id_menu = :id_menu
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_opsi' => $idOpsi,
                ':id_menu' => $idMenu,
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log(
                '[MenuModel::hapusKelompokKustomisasi] ' .
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Menghapus satu pilihan kustomisasi.
     */
    public function hapusPilihanKustomisasi(
        int $idDetail,
        int $idOpsi
    ): bool {
        try {
            $sql = "
                DELETE FROM detail_opsi_kustomisasi
                WHERE id_detail_opsi = :id_detail
                  AND id_opsi = :id_opsi
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_detail' => $idDetail,
                ':id_opsi' => $idOpsi,
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log(
                '[MenuModel::hapusPilihanKustomisasi] ' .
                $e->getMessage()
            );
            return false;
        }
    }
}
