
<?php
// models/TransaksiModel.php
// Model Transaksi Penjualan KolegaOps POS Warkop

class TransaksiModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getStatistikHariIni(): array
    {
        $sqlHariIni = "
            SELECT
                COALESCE(SUM(total), 0) AS total_penjualan,
                COUNT(id_transaksi) AS jumlah_transaksi,
                COALESCE(SUM(CASE WHEN LOWER(metode_pembayaran) IN ('tunai', 'cash') THEN total ELSE 0 END), 0) AS total_cash,
                COALESCE(SUM(CASE WHEN LOWER(metode_pembayaran) = 'qris' THEN total ELSE 0 END), 0) AS total_qris
            FROM transaksi
            WHERE DATE(create_at) = CURRENT_DATE
        ";

        $stmtHariIni = $this->pdo->query($sqlHariIni);

        $hariIni = $stmtHariIni->fetch(PDO::FETCH_ASSOC) ?: [
            'total_penjualan' => 0,
            'jumlah_transaksi' => 0,
            'total_cash' => 0,
            'total_qris' => 0,
        ];

        $sqlKemarin = "
            SELECT COALESCE(SUM(total), 0)
            FROM transaksi
            WHERE DATE(create_at) = CURRENT_DATE - INTERVAL '1 day'
        ";

        $totalKemarin = (float) $this->pdo->query($sqlKemarin)->fetchColumn();

        $totalHariIni = (float) $hariIni['total_penjualan'];
        $jumlahTrx = (int) $hariIni['jumlah_transaksi'];

        $rataRata = $jumlahTrx > 0
            ? $totalHariIni / $jumlahTrx
            : 0;

        $persenTeks = '0% vs kemarin';

        if ($totalKemarin > 0) {
            $selisih = (($totalHariIni - $totalKemarin) / $totalKemarin) * 100;
            $tanda = $selisih >= 0 ? '▲' : '▼';
            $persenTeks = sprintf('%s %.1f%% vs kemarin', $tanda, abs($selisih));
        } elseif ($totalHariIni > 0) {
            $persenTeks = '▲ 100% vs kemarin';
        }

        return [
            'total_penjualan' => $totalHariIni,
            'jumlah_transaksi' => $jumlahTrx,
            'total_cash' => (float) $hariIni['total_cash'],
            'total_qris' => (float) $hariIni['total_qris'],
            'rata_rata' => $rataRata,
            'persen_vs_kemarin' => $persenTeks,
        ];
    }

    public function getPendapatan7Hari(): array
    {
        $sql = "
            SELECT
                d.date::date AS tanggal,
                TO_CHAR(d.date, 'Dy') AS hari_eng,
                COALESCE(SUM(t.total), 0) AS total
            FROM generate_series(
                CURRENT_DATE - INTERVAL '6 days',
                CURRENT_DATE,
                INTERVAL '1 day'
            ) AS d(date)
            LEFT JOIN transaksi t ON DATE(t.create_at) = d.date::date
            GROUP BY d.date
            ORDER BY d.date ASC
        ";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $petaHari = [
            'Mon' => 'Sen',
            'Tue' => 'Sel',
            'Wed' => 'Rab',
            'Thu' => 'Kam',
            'Fri' => 'Jum',
            'Sat' => 'Sab',
            'Sun' => 'Min',
        ];

        $maxNominal = 0;
        $totalMingguan = 0;

        foreach ($rows as $r) {
            $val = (float) $r['total'];
            $totalMingguan += $val;

            if ($val > $maxNominal) {
                $maxNominal = $val;
            }
        }

        $hasil = [];
        $today = date('Y-m-d');

        foreach ($rows as $r) {
            $nominal = (float) $r['total'];
            $tgl = $r['tanggal'];

            $tinggiPersen = $maxNominal > 0
                ? round(($nominal / $maxNominal) * 100)
                : 12;

            if ($tinggiPersen < 12) {
                $tinggiPersen = 12;
            }

            if ($nominal >= 1000000) {
                $labelNominal = number_format($nominal / 1000000, 1, ',', '.') . ' jt';
            } elseif ($nominal >= 1000) {
                $labelNominal = number_format($nominal / 1000, 0, ',', '.') . ' rb';
            } else {
                $labelNominal = (string) $nominal;
            }

            $hasil[] = [
                'tanggal' => $tgl,
                'hari' => $petaHari[$r['hari_eng']] ?? $r['hari_eng'],
                'nominal' => $nominal,
                'label_nominal' => $labelNominal,
                'tinggi_persen' => $tinggiPersen,
                'is_today' => ($tgl === $today),
            ];
        }

        return [
            'daftar_hari' => $hasil,
            'total_mingguan' => $totalMingguan
        ];
    }

    public function getTransaksiTerbaru(int $limit = 4): array
    {
        $sql = "
            SELECT
                t.id_transaksi,
                t.kode_transaksi,
                t.total,
                t.metode_pembayaran,
                t.create_at,
                COALESCE(u.username, 'Kasir') AS kasir
            FROM transaksi t
            LEFT JOIN users u ON t.id_user = u.id_user
            ORDER BY t.create_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransaksiTerbaruKasir(int $id_user, int $limit = 4): array
    {
        $sql = "
            SELECT
                t.id_transaksi,
                t.kode_transaksi,
                t.total,
                t.metode_pembayaran,
                TO_CHAR(t.create_at, 'HH24:MI') AS waktu,
                t.create_at
            FROM transaksi t
            WHERE t.id_user = :id_user
              AND DATE(t.create_at) = CURRENT_DATE
            ORDER BY t.create_at DESC, t.id_transaksi DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_user', $id_user, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRiwayatTransaksiFilter(
        string $tanggal = 'Semua',
        string $shift = 'Semua'
    ): array {
        $conditions = [];
        $params = [];

        if ($tanggal !== 'Semua' && !empty($tanggal)) {
            $conditions[] = "DATE(t.create_at) = :tanggal";
            $params[':tanggal'] = $tanggal;
        }

        if ($shift !== 'Semua' && !empty($shift)) {
            $conditions[] = "(s.nama_shift ILIKE :shift OR u.username ILIKE :shift)";
            $params[':shift'] = '%' . $shift . '%';
        }

        $whereClause = !empty($conditions)
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        $sqlTrx = "
            SELECT
                t.id_transaksi,
                t.kode_transaksi,
                t.nama_customer,
                t.total_item,
                t.subtotal,
                t.diskon,
                t.total,
                t.metode_pembayaran,
                t.nominal_bayar,
                t.kembalian,
                t.catatan_transaksi,
                t.create_at,
                DATE(t.create_at) AS tanggal,
                TO_CHAR(t.create_at, 'HH24:MI') AS waktu,
                COALESCE(s.nama_shift, 'Umum') AS nama_shift,
                COALESCE(u.username, 'Kasir') AS nama_kasir
            FROM transaksi t
            LEFT JOIN shift s ON t.id_shift = s.id_shift
            LEFT JOIN users u ON t.id_user = u.id_user
            {$whereClause}
            ORDER BY t.create_at DESC, t.id_transaksi DESC
        ";

        $stmt = $this->pdo->prepare($sqlTrx);
        $stmt->execute($params);

        $daftarTrx = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($daftarTrx)) {
            return [];
        }

        $trxIds = array_column($daftarTrx, 'id_transaksi');
        $inClause = implode(',', array_fill(0, count($trxIds), '?'));

        $sqlItems = "
            SELECT
                dt.id_transaksi,
                dt.jumlah AS qty,
                dt.subtotal,
                dt.harga_satuan,
                COALESCE(m.nama_menu, 'Menu Dihapus') AS nama
            FROM detail_transaksi dt
            LEFT JOIN menu m ON dt.id_menu = m.id_menu
            WHERE dt.id_transaksi IN ({$inClause})
            ORDER BY dt.id_detail ASC
        ";

        $stmtItems = $this->pdo->prepare($sqlItems);
        $stmtItems->execute($trxIds);

        $allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $itemsMap = [];

        foreach ($allItems as $item) {
            $itemsMap[$item['id_transaksi']][] = [
                'nama' => $item['nama'],
                'qty' => (int) $item['qty'],
                'subtotal' => (float) $item['subtotal'],
                'harga_satuan' => (float) $item['harga_satuan'],
            ];
        }

        $hasil = [];

        foreach ($daftarTrx as $trx) {
            $tId = $trx['id_transaksi'];

            $hasil[] = [
                'id_transaksi' => (int) $trx['id_transaksi'],
                'id' => $trx['kode_transaksi'] ?: 'TRX-' . $trx['id_transaksi'],
                'kode' => $trx['kode_transaksi'],
                'waktu' => $trx['waktu'],
                'tanggal' => $trx['tanggal'],
                'tgl_teks' => function_exists('format_tanggal')
                    ? format_tanggal($trx['tanggal'])
                    : $trx['tanggal'],
                'shift' => $trx['nama_shift'] . ' · ' . ucfirst($trx['nama_kasir']),
                'nama_shift' => $trx['nama_shift'],
                'nama_kasir' => $trx['nama_kasir'],
                'metode' => strtolower(trim($trx['metode_pembayaran'])) === 'qris'
                    ? 'QRIS'
                    : 'Cash',
                'total' => (float) $trx['total'],
                'status' => 'Lunas',
                'customer' => $trx['nama_customer'] ?? '-',
                'items' => $itemsMap[$tId] ?? [],
            ];
        }

        return $hasil;
    }

    public function getTransaksiShiftAktif(string $tanggal, string $namaKasir): array
    {
        return $this->getRiwayatTransaksiFilter($tanggal, $namaKasir);
    }

    public function getRekapPembayaran(
        string $tanggal = 'Semua',
        string $shift = 'Semua'
    ): array {
        $conditions = [];
        $params = [];

        if ($tanggal !== 'Semua' && !empty($tanggal)) {
            $conditions[] = "DATE(t.create_at) = :tanggal";
            $params[':tanggal'] = $tanggal;
        }

        if ($shift !== 'Semua' && !empty($shift)) {
            $conditions[] = "(s.nama_shift ILIKE :shift OR u.username ILIKE :shift)";
            $params[':shift'] = '%' . $shift . '%';
        }

        $whereClause = !empty($conditions)
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN LOWER(t.metode_pembayaran) IN ('cash', 'tunai') THEN t.total ELSE 0 END), 0) AS total_cash,
                COALESCE(SUM(CASE WHEN LOWER(t.metode_pembayaran) = 'qris' THEN t.total ELSE 0 END), 0) AS total_qris,
                COALESCE(SUM(t.total), 0) AS total_omzet,
                COUNT(t.id_transaksi) AS jumlah_transaksi
            FROM transaksi t
            LEFT JOIN shift s ON t.id_shift = s.id_shift
            LEFT JOIN users u ON t.id_user = u.id_user
            {$whereClause}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_cash' => (float) ($row['total_cash'] ?? 0),
            'total_qris' => (float) ($row['total_qris'] ?? 0),
            'total_omzet' => (float) ($row['total_omzet'] ?? 0),
            'jumlah_transaksi' => (int) ($row['jumlah_transaksi'] ?? 0),
        ];
    }

    public function getRekapPembayaranShiftAktif(
        string $tanggal,
        string $namaKasir
    ): array {
        return $this->getRekapPembayaran($tanggal, $namaKasir);
    }

    public function getPenjualanShiftKasir(?int $id_shift, int $id_user): array
    {
        $conditions = ["t.id_user = :id_user"];
        $params = [':id_user' => $id_user];

        if ($id_shift && $id_shift > 0) {
            $conditions[] = "t.id_shift = :id_shift";
            $params[':id_shift'] = $id_shift;
        } else {
            $conditions[] = "DATE(t.create_at) = CURRENT_DATE";
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "
            SELECT
                COALESCE(SUM(t.total), 0) AS total_penjualan,
                COUNT(t.id_transaksi) AS jumlah_transaksi,
                COALESCE(SUM(CASE WHEN LOWER(t.metode_pembayaran) IN ('tunai', 'cash') THEN t.total ELSE 0 END), 0) AS total_cash,
                COALESCE(SUM(CASE WHEN LOWER(t.metode_pembayaran) = 'qris' THEN t.total ELSE 0 END), 0) AS total_qris
            FROM transaksi t
            {$whereClause}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_penjualan' => (float) ($row['total_penjualan'] ?? 0),
            'jumlah_transaksi' => (int) ($row['jumlah_transaksi'] ?? 0),
            'total_cash' => (float) ($row['total_cash'] ?? 0),
            'total_qris' => (float) ($row['total_qris'] ?? 0),
        ];
    }

    /**
     * Menyimpan transaksi, mengurangi stok menu dan bahan baku,
     * serta mencatat riwayat pengurangan stok dari POS.
     */
    public function simpanTransaksi(array $data): array
    {
        $kode = 'KG-' . date('ymd') . '-' .
            str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

        try {
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new RuntimeException('Daftar pesanan masih kosong.');
            }

            $idUser = (int) ($data['id_user'] ?? 0);
            $idShift = (int) ($data['id_shift'] ?? 0);

            if ($idUser <= 0 || $idShift <= 0) {
                throw new RuntimeException('Akun kasir atau shift tidak valid.');
            }

            $this->pdo->beginTransaction();

            // 1. PASTIKAN SHIFT MASIH AKTIF DAN MILIK KASIR
            $sqlCekShift = "
                SELECT id_shift
                FROM shift
                WHERE id_shift = :id_shift
                  AND id_user = :id_user
                  AND status = 'buka'
                FOR UPDATE
            ";

            $stmtCekShift = $this->pdo->prepare($sqlCekShift);
            $stmtCekShift->execute([
                ':id_shift' => $idShift,
                ':id_user' => $idUser,
            ]);

            if (!$stmtCekShift->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException(
                    'Shift sudah ditutup atau bukan milik akun kasir ini.'
                );
            }

            // 2. SIMPAN TRANSAKSI
            $sqlTrx = "
                INSERT INTO transaksi (
                    kode_transaksi,
                    nama_customer,
                    total_item,
                    subtotal,
                    diskon,
                    total,
                    metode_pembayaran,
                    nominal_bayar,
                    kembalian,
                    catatan_transaksi,
                    id_user,
                    id_shift
                ) VALUES (
                    :kode,
                    :nama_customer,
                    :total_item,
                    :subtotal,
                    :diskon,
                    :total,
                    :metode_pembayaran,
                    :nominal_bayar,
                    :kembalian,
                    :catatan_transaksi,
                    :id_user,
                    :id_shift
                )
                RETURNING id_transaksi
            ";

            $stmtTrx = $this->pdo->prepare($sqlTrx);

            $stmtTrx->execute([
                ':kode' => $kode,
                ':nama_customer' => $data['nama_customer'] ?? null,
                ':total_item' => (int) ($data['total_item'] ?? 0),
                ':subtotal' => (float) ($data['subtotal'] ?? 0),
                ':diskon' => (float) ($data['diskon'] ?? 0),
                ':total' => (float) ($data['total'] ?? 0),
                ':metode_pembayaran' => $data['metode_pembayaran'] ?? 'tunai',
                ':nominal_bayar' => (float) ($data['nominal_bayar'] ?? 0),
                ':kembalian' => (float) ($data['kembalian'] ?? 0),
                ':catatan_transaksi' => $data['catatan_transaksi'] ?? null,
                ':id_user' => $idUser,
                ':id_shift' => $idShift,
            ]);

            $idTransaksi = (int) $stmtTrx->fetchColumn();

            if ($idTransaksi <= 0) {
                throw new RuntimeException('Gagal menyimpan data transaksi.');
            }

            // 3. QUERY DETAIL TRANSAKSI
            $sqlDetail = "
                INSERT INTO detail_transaksi (
                    id_transaksi,
                    id_menu,
                    jumlah,
                    harga_satuan,
                    subtotal,
                    catatan_item
                ) VALUES (
                    :id_transaksi,
                    :id_menu,
                    :jumlah,
                    :harga_satuan,
                    :subtotal,
                    :catatan_item
                )
            ";

            $stmtDetail = $this->pdo->prepare($sqlDetail);

            // 4. QUERY STOK MENU
            $sqlCekMenu = "
                SELECT stok_menu
                FROM menu
                WHERE id_menu = :id_menu
                FOR UPDATE
            ";

            $stmtCekMenu = $this->pdo->prepare($sqlCekMenu);

            $sqlKurangMenu = "
                UPDATE menu
                SET stok_menu = stok_menu - :jumlah,
                    update_at = CURRENT_TIMESTAMP
                WHERE id_menu = :id_menu
                  AND stok_menu >= :jumlah_cek
            ";

            $stmtKurangMenu = $this->pdo->prepare($sqlKurangMenu);

            // 5. QUERY RESEP DAN BAHAN BAKU
            $sqlResep = "
                SELECT
                    r.id_bahan,
                    r.jumlah_pemakaian,
                    b.nama_bahan,
                    b.satuan,
                    b.stok_bahan,
                    b.minimum_stok
                FROM resep r
                INNER JOIN bahan_baku b
                    ON r.id_bahan = b.id_bahan
                WHERE r.id_menu = :id_menu
                ORDER BY r.id_bahan ASC
                FOR UPDATE OF b
            ";

            $stmtResep = $this->pdo->prepare($sqlResep);

            $sqlKurangBahan = "
                UPDATE bahan_baku
                SET
                    stok_bahan = stok_bahan - :kurang,
                    status_stok = CASE
                        WHEN stok_bahan - :kurang_cek <= COALESCE(minimum_stok, 0)
                        THEN 'menipis'
                        ELSE 'aman'
                    END,
                    update_at = CURRENT_TIMESTAMP
                WHERE id_bahan = :id_bahan
                  AND stok_bahan >= :kurang_batas
            ";

            $stmtKurangBahan = $this->pdo->prepare($sqlKurangBahan);

            // 6. QUERY RIWAYAT STOK POS
            // id_menu disimpan agar bahan dapat ditampilkan di bawah menu asalnya.
            $sqlRiwayatPOS = "
                INSERT INTO riwayat_stok (
                    id_bahan,
                    id_menu,
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
                ) VALUES (
                    :id_bahan,
                    :id_menu,
                    :id_user,
                    :id_transaksi,
                    :nama_bahan,
                    :satuan,
                    'keluar',
                    :jumlah,
                    :stok_sebelum,
                    :stok_sesudah,
                    'pos',
                    :keterangan
                )
            ";

            $stmtRiwayatPOS = $this->pdo->prepare($sqlRiwayatPOS);

            // 7. PROSES ITEM PESANAN
            foreach ($data['items'] as $item) {
                $idMenu = (int) ($item['id_menu'] ?? 0);
                $jumlah = (int) ($item['jumlah'] ?? 0);

                if ($idMenu <= 0 || $jumlah <= 0) {
                    throw new RuntimeException(
                        'Data menu atau jumlah pesanan tidak valid.'
                    );
                }

                $stmtDetail->execute([
                    ':id_transaksi' => $idTransaksi,
                    ':id_menu' => $idMenu,
                    ':jumlah' => $jumlah,
                    ':harga_satuan' => (float) ($item['harga_satuan'] ?? 0),
                    ':subtotal' => (float) ($item['subtotal'] ?? 0),
                    ':catatan_item' => $item['catatan_item'] ?? null,
                ]);

                // 7A. CEK DAN KURANGI STOK MENU
                $stmtCekMenu->execute([
                    ':id_menu' => $idMenu
                ]);

                $menu = $stmtCekMenu->fetch(PDO::FETCH_ASSOC);

                if (!$menu) {
                    throw new RuntimeException(
                        'Menu dengan ID ' . $idMenu . ' tidak ditemukan.'
                    );
                }

                if ($menu['stok_menu'] !== null) {
                    if ((float) $menu['stok_menu'] < $jumlah) {
                        throw new RuntimeException('Stok menu tidak mencukupi.');
                    }

                    $stmtKurangMenu->execute([
                        ':jumlah' => $jumlah,
                        ':id_menu' => $idMenu,
                        ':jumlah_cek' => $jumlah,
                    ]);

                    if ($stmtKurangMenu->rowCount() !== 1) {
                        throw new RuntimeException('Gagal memperbarui stok menu.');
                    }
                }

                // 7B. AMBIL RESEP MENU
                $stmtResep->execute([
                    ':id_menu' => $idMenu
                ]);

                $daftarResep = $stmtResep->fetchAll(PDO::FETCH_ASSOC);

                // 7C. KURANGI STOK DAN CATAT RIWAYAT BAHAN
                foreach ($daftarResep as $resep) {
                    $idBahan = (int) $resep['id_bahan'];

                    $totalPakai =
                        (float) $resep['jumlah_pemakaian'] * $jumlah;

                    $stokSebelum = (float) $resep['stok_bahan'];

                    if ($totalPakai <= 0) {
                        throw new RuntimeException('Takaran resep tidak valid.');
                    }

                    if ($stokSebelum < $totalPakai) {
                        throw new RuntimeException(
                            'Stok bahan baku ' .
                            $resep['nama_bahan'] .
                            ' tidak mencukupi.'
                        );
                    }

                    $stokSesudah = $stokSebelum - $totalPakai;

                    $stmtKurangBahan->execute([
                        ':kurang' => $totalPakai,
                        ':kurang_cek' => $totalPakai,
                        ':kurang_batas' => $totalPakai,
                        ':id_bahan' => $idBahan,
                    ]);

                    if ($stmtKurangBahan->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Gagal memperbarui stok bahan baku.'
                        );
                    }

                    // Catat riwayat dengan ID menu yang sedang diproses.
                    $stmtRiwayatPOS->execute([
                        ':id_bahan' => $idBahan,
                        ':id_menu' => $idMenu,
                        ':id_user' => $idUser,
                        ':id_transaksi' => $idTransaksi,
                        ':nama_bahan' => $resep['nama_bahan'],
                        ':satuan' => $resep['satuan'],
                        ':jumlah' => $totalPakai,
                        ':stok_sebelum' => $stokSebelum,
                        ':stok_sesudah' => $stokSesudah,
                        ':keterangan' => 'Pengurangan otomatis dari transaksi POS'
                    ]);
                }
            }

            // 8. SIMPAN SEMUA PERUBAHAN
            $this->pdo->commit();

            return [
                'success' => true,
                'id_transaksi' => $idTransaksi,
                'kode_transaksi' => $kode,
                'error' => '',
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log(
                '[KolegaOps] simpanTransaksi error: ' .
                $e->getMessage()
            );

            return [
                'success' => false,
                'id_transaksi' => 0,
                'kode_transaksi' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getDetailTransaksi(int $id_transaksi): ?array
    {
        $sqlTrx = "
            SELECT
                t.id_transaksi,
                t.id_user,
                t.kode_transaksi,
                t.nama_customer,
                t.total_item,
                t.subtotal,
                t.diskon,
                t.total,
                t.metode_pembayaran,
                t.nominal_bayar,
                t.kembalian,
                t.catatan_transaksi,
                t.create_at,
                TO_CHAR(t.create_at, 'HH24:MI') AS waktu,
                DATE(t.create_at) AS tanggal,
                COALESCE(s.nama_shift, 'Umum') AS nama_shift,
                COALESCE(u.username, 'Kasir') AS nama_kasir
            FROM transaksi t
            LEFT JOIN shift s ON t.id_shift = s.id_shift
            LEFT JOIN users u ON t.id_user = u.id_user
            WHERE t.id_transaksi = :id_transaksi
            LIMIT 1
        ";

        $stmtTrx = $this->pdo->prepare($sqlTrx);
        $stmtTrx->bindValue(':id_transaksi', $id_transaksi, PDO::PARAM_INT);
        $stmtTrx->execute();

        $trx = $stmtTrx->fetch(PDO::FETCH_ASSOC);

        if (!$trx) {
            return null;
        }

        $sqlItems = "
            SELECT
                dt.jumlah AS qty,
                dt.harga_satuan,
                dt.subtotal,
                dt.catatan_item,
                COALESCE(m.nama_menu, 'Menu Dihapus') AS nama,
                COALESCE(m.nama_menu, 'Menu Dihapus') AS nama_menu,
                COALESCE(m.kategori, 'Umum') AS kategori
            FROM detail_transaksi dt
            LEFT JOIN menu m ON dt.id_menu = m.id_menu
            WHERE dt.id_transaksi = :id_transaksi
            ORDER BY dt.id_detail ASC
        ";

        $stmtItems = $this->pdo->prepare($sqlItems);
        $stmtItems->bindValue(':id_transaksi', $id_transaksi, PDO::PARAM_INT);
        $stmtItems->execute();

        $trx['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $trx;
    }
}
