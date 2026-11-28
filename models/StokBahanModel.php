
<?php
// models/StokBahanModel.php
// Model Pengelolaan Stok Bahan Baku dan Riwayat Stok KolegaOps

class StokBahanModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function getUserId(): ?int
    {
        if (function_exists('current_user')) {
            $userId = current_user('id_user');

            if ($userId !== null && $userId !== '') {
                return (int) $userId;
            }
        }

        return null;
    }

    private function tentukanStatus(float $stok, float $minimum): string
    {
        if ($stok <= 0) {
            return 'habis';
        }

        return $stok >= $minimum ? 'aman' : 'menipis';
    }

    public function getAllStok(): array
    {
        $sql = "
            SELECT
                id_bahan AS id,
                nama_bahan AS nama,
                stok_bahan AS stok,
                minimum_stok AS minimum,
                satuan,
                CASE
                    WHEN stok_bahan >= minimum_stok THEN 'Aman'
                    ELSE 'Kritis'
                END AS status,
                CASE
                    WHEN LOWER(satuan) IN
                        ('pcs', 'bungkus', 'botol', 'pak', 'kemasan')
                    THEN 'Menu'
                    ELSE 'Bahan'
                END AS jenis,
                update_at
            FROM bahan_baku
            ORDER BY
                CASE WHEN stok_bahan < minimum_stok THEN 0 ELSE 1 END,
                id_bahan ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tambahStokBahan(array $data)
    {
        $nama = trim($data['nama_bahan'] ?? '');
        $stokAwal = (float) ($data['stok_awal'] ?? 0);
        $minimumStok = (float) ($data['minimum_stok'] ?? 0);
        $satuan = trim($data['satuan'] ?? 'pcs');
        $catatan = trim($data['catatan'] ?? '');

        if ($nama === '' || $stokAwal < 0 || $minimumStok < 0) {
            return false;
        }

        if ($satuan === '') {
            $satuan = 'pcs';
        }

        try {
            $this->pdo->beginTransaction();

            $statusStok = $this->tentukanStatus(
                $stokAwal,
                $minimumStok
            );

            $sql = "
                INSERT INTO bahan_baku (
                    nama_bahan,
                    stok_bahan,
                    minimum_stok,
                    satuan,
                    status_stok
                )
                VALUES (
                    :nama_bahan,
                    :stok_bahan,
                    :minimum_stok,
                    :satuan,
                    :status_stok
                )
                RETURNING id_bahan
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nama_bahan' => $nama,
                ':stok_bahan' => $stokAwal,
                ':minimum_stok' => $minimumStok,
                ':satuan' => $satuan,
                ':status_stok' => $statusStok
            ]);

            $newId = (int) $stmt->fetchColumn();

            if ($stokAwal > 0) {
                $this->catatRiwayat([
                    'id_bahan' => $newId,
                    'id_user' => $this->getUserId(),
                    'nama_bahan' => $nama,
                    'satuan' => $satuan,
                    'jenis' => 'masuk',
                    'jumlah' => $stokAwal,
                    'stok_sebelum' => 0,
                    'stok_sesudah' => $stokAwal,
                    'sumber' => 'manual',
                    'keterangan' => $catatan !== ''
                        ? $catatan
                        : 'Stok awal bahan baru ditambahkan'
                ]);
            }

            $this->pdo->commit();

            return $newId;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('[StokBahanModel::tambahStokBahan] ' . $e->getMessage());

            return false;
        }
    }

    public function getStokById(int $id): ?array
    {
        $sql = "
            SELECT
                id_bahan AS id,
                nama_bahan AS nama,
                stok_bahan AS stok,
                minimum_stok AS minimum,
                satuan,
                status_stok,
                CASE
                    WHEN stok_bahan >= minimum_stok THEN 'Aman'
                    ELSE 'Kritis'
                END AS status,
                CASE
                    WHEN LOWER(satuan) IN
                        ('pcs', 'bungkus', 'botol', 'pak', 'kemasan')
                    THEN 'Menu'
                    ELSE 'Bahan'
                END AS jenis,
                update_at
            FROM bahan_baku
            WHERE id_bahan = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function catatRiwayat(array $data): void
    {
        $sql = "
            INSERT INTO riwayat_stok (
                id_bahan,
                id_user,
                id_transaksi,
                nama_bahan,
                satuan,
                jenis,
                jumlah,
                stok_sebelum,
                stok_sesudah,
                sumber,
                keterangan
            )
            VALUES (
                :id_bahan,
                :id_user,
                :id_transaksi,
                :nama_bahan,
                :satuan,
                :jenis,
                :jumlah,
                :stok_sebelum,
                :stok_sesudah,
                :sumber,
                :keterangan
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_bahan' => $data['id_bahan'] ?? null,
            ':id_user' => $data['id_user'] ?? null,
            ':id_transaksi' => $data['id_transaksi'] ?? null,
            ':nama_bahan' => $data['nama_bahan'],
            ':satuan' => $data['satuan'],
            ':jenis' => $data['jenis'],
            ':jumlah' => $data['jumlah'],
            ':stok_sebelum' => $data['stok_sebelum'],
            ':stok_sesudah' => $data['stok_sesudah'],
            ':sumber' => $data['sumber'],
            ':keterangan' => $data['keterangan'] ?? null
        ]);
    }

    public function hapusStok(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM bahan_baku
                WHERE id_bahan = :id
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log('[StokBahanModel::hapusStok] ' . $e->getMessage());

            return false;
        }
    }

    public function updateStok(
        int $id,
        float $stokBaru,
        ?string $catatan = null
    ): bool {
        if ($stokBaru < 0) {
            $stokBaru = 0;
        }

        try {
            $this->pdo->beginTransaction();

            $sqlAmbil = "
                SELECT
                    id_bahan,
                    nama_bahan,
                    stok_bahan,
                    minimum_stok,
                    satuan
                FROM bahan_baku
                WHERE id_bahan = :id
                FOR UPDATE
            ";

            $stmtAmbil = $this->pdo->prepare($sqlAmbil);
            $stmtAmbil->execute([':id' => $id]);

            $item = $stmtAmbil->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $this->pdo->rollBack();
                return false;
            }

            $stokLama = (float) $item['stok_bahan'];
            $minimumStok = (float) $item['minimum_stok'];
            $selisih = $stokBaru - $stokLama;

            if (abs($selisih) < 0.000001) {
                $this->pdo->commit();
                return true;
            }

            $statusBaru = $this->tentukanStatus(
                $stokBaru,
                $minimumStok
            );

            $sqlUpdate = "
                UPDATE bahan_baku
                SET
                    stok_bahan = :stok,
                    status_stok = :status_stok,
                    update_at = CURRENT_TIMESTAMP
                WHERE id_bahan = :id
            ";

            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':stok' => $stokBaru,
                ':status_stok' => $statusBaru,
                ':id' => $id
            ]);

            $this->catatRiwayat([
                'id_bahan' => $id,
                'id_user' => $this->getUserId(),
                'nama_bahan' => $item['nama_bahan'],
                'satuan' => $item['satuan'],
                'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                'jumlah' => abs($selisih),
                'stok_sebelum' => $stokLama,
                'stok_sesudah' => $stokBaru,
                'sumber' => 'manual',
                'keterangan' => $catatan ?: 'Penyesuaian stok manual'
            ]);

            $this->pdo->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('[StokBahanModel::updateStok] ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Filter riwayat stok berdasarkan pencarian,
     * jenis, sumber, tanggal, dan pengguna.
     */
    private function buatFilterRiwayat(array $filter): array
    {
        $where = [];
        $params = [];

        $search = trim($filter['search'] ?? '');

        if ($search !== '') {
            $where[] = "(
                r.nama_bahan ILIKE :search_bahan
                OR COALESCE(r.keterangan, '') ILIKE :search_keterangan
                OR COALESCE(m.nama_menu, '') ILIKE :search_menu
                OR COALESCE(t.kode_transaksi, '') ILIKE :search_transaksi
            )";

            $params[':search_bahan'] = '%' . $search . '%';
            $params[':search_keterangan'] = '%' . $search . '%';
            $params[':search_menu'] = '%' . $search . '%';
            $params[':search_transaksi'] = '%' . $search . '%';
        }

        $jenis = $filter['jenis'] ?? 'semua';

        if (in_array($jenis, ['masuk', 'keluar'], true)) {
            $where[] = 'r.jenis = :jenis';
            $params[':jenis'] = $jenis;
        }

        $sumber = $filter['sumber'] ?? 'semua';

        if (in_array($sumber, ['manual', 'pos'], true)) {
            $where[] = 'r.sumber = :sumber';
            $params[':sumber'] = $sumber;
        }

        $tanggal = trim($filter['tanggal'] ?? '');

        if ($tanggal !== '') {
            $tanggalValid = DateTime::createFromFormat('!Y-m-d', $tanggal);

            if (
                $tanggalValid !== false &&
                $tanggalValid->format('Y-m-d') === $tanggal
            ) {
                $where[] = "
                    r.create_at >= CAST(:tanggal_mulai AS DATE)
                    AND r.create_at < (
                        CAST(:tanggal_selesai AS DATE) + INTERVAL '1 day'
                    )
                ";

                $params[':tanggal_mulai'] = $tanggal;
                $params[':tanggal_selesai'] = $tanggal;
            }
        }

        // Riwayat kasir hanya menampilkan aktivitas miliknya.
        if (isset($filter['user_id']) && (int) $filter['user_id'] > 0) {
            $where[] = 'r.id_user = :filter_user_id';
            $params[':filter_user_id'] = (int) $filter['user_id'];
        }

        $sqlWhere = !empty($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        return [$sqlWhere, $params];
    }

    public function getRiwayatStok(array $filter = []): array
    {
        [$where, $params] = $this->buatFilterRiwayat($filter);

        $sql = "
            SELECT
                r.id_riwayat,
                r.id_bahan,
                r.id_user,
                r.id_transaksi,
                r.id_menu,
                r.nama_bahan,
                r.satuan,
                r.jenis,
                r.jumlah,
                r.stok_sebelum,
                r.stok_sesudah,
                r.sumber,
                r.keterangan,
                r.create_at,

                COALESCE(
                    to_jsonb(u)->>'nama_lengkap',
                    to_jsonb(u)->>'nama_user',
                    to_jsonb(u)->>'nama',
                    to_jsonb(u)->>'username',
                    'Pengguna'
                ) AS nama_user,

                t.kode_transaksi,
                m.nama_menu,

                CASE
                    WHEN r.sumber = 'pos' AND r.id_menu IS NOT NULL
                    THEN m.nama_menu
                    WHEN r.sumber = 'pos'
                    THEN 'Menu tidak teridentifikasi'
                    ELSE NULL
                END AS kelompok_menu

            FROM riwayat_stok r
            LEFT JOIN users u ON u.id_user = r.id_user
            LEFT JOIN transaksi t ON t.id_transaksi = r.id_transaksi
            LEFT JOIN menu m ON m.id_menu = r.id_menu

            {$where}

            ORDER BY
                r.create_at DESC,
                r.id_transaksi DESC NULLS LAST,
                r.id_menu ASC NULLS LAST,
                r.id_riwayat DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatistikRiwayatStok(array $filter = []): array
    {
        [$where, $params] = $this->buatFilterRiwayat($filter);

        $sql = "
            SELECT
                COUNT(*) AS total_aktivitas,

                COUNT(*) FILTER (
                    WHERE r.jenis = 'masuk'
                ) AS stok_masuk,

                COUNT(*) FILTER (
                    WHERE r.jenis = 'keluar'
                ) AS stok_keluar

            FROM riwayat_stok r
            LEFT JOIN transaksi t ON t.id_transaksi = r.id_transaksi
            LEFT JOIN menu m ON m.id_menu = r.id_menu
            {$where}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $hasil = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_aktivitas' => (int) ($hasil['total_aktivitas'] ?? 0),
            'stok_masuk' => (int) ($hasil['stok_masuk'] ?? 0),
            'stok_keluar' => (int) ($hasil['stok_keluar'] ?? 0)
        ];
    }

    public function getStokKritisCount(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM bahan_baku
            WHERE stok_bahan < minimum_stok
               OR status_stok IN ('menipis', 'habis')
        ";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function getDaftarStokKritis(): array
    {
        $sql = "
            SELECT
                id_bahan,
                nama_bahan,
                stok_bahan,
                satuan,
                minimum_stok,
                status_stok
            FROM bahan_baku
            WHERE stok_bahan < minimum_stok
               OR status_stok IN ('menipis', 'habis')
            ORDER BY stok_bahan ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBahanPemakaianByTransaksi(int $id_transaksi): array
    {
        $sql = "
            SELECT
                r.id_bahan,
                b.nama_bahan,
                b.stok_bahan,
                b.minimum_stok,
                b.satuan,
                r.jumlah_pemakaian AS takaran_per_porsi,
                dt.jumlah AS porsi_terjual,
                (r.jumlah_pemakaian * dt.jumlah) AS total_pemakaian
            FROM detail_transaksi dt
            JOIN resep r ON dt.id_menu = r.id_menu
            JOIN bahan_baku b ON r.id_bahan = b.id_bahan
            WHERE dt.id_transaksi = :id_transaksi
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_transaksi' => $id_transaksi]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pengurangan stok melalui POS.
     * Riwayat POS dicatat oleh TransaksiModel agar tidak duplikat.
     */
    public function updateStokOtomatisViapOS(
        int $id_bahan,
        float $total_pemakaian
    ): bool {
        if ($total_pemakaian <= 0) {
            return false;
        }

        $sql = "
            UPDATE bahan_baku
            SET
                stok_bahan = stok_bahan - :total_pemakaian,
                status_stok = CASE
                    WHEN (stok_bahan - :total_pemakaian) <= 0
                        THEN 'habis'
                    WHEN (stok_bahan - :total_pemakaian) < minimum_stok
                        THEN 'menipis'
                    ELSE 'aman'
                END,
                update_at = CURRENT_TIMESTAMP
            WHERE id_bahan = :id_bahan
              AND stok_bahan >= :total_pemakaian
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':total_pemakaian' => $total_pemakaian,
            ':id_bahan' => $id_bahan
        ]);

        return $stmt->rowCount() > 0;
    }
}
