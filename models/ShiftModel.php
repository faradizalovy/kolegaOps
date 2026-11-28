
<?php
// models/ShiftModel.php
// Model Pengelolaan Shift Per Kasir KolegaOps

class ShiftModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function getWaktuIndonesia(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone('Asia/Jakarta')
        );
    }

    private function tentukanNamaShift(DateTimeImmutable $waktu): string
    {
        $jam = (int) $waktu->format('G');

        if ($jam >= 6 && $jam < 12) {
            return 'Pagi';
        }

        if ($jam >= 12 && $jam < 15) {
            return 'Siang';
        }

        if ($jam >= 15 && $jam < 18) {
            return 'Sore';
        }

        return 'Malam';
    }

    // Mengambil shift terakhir milik akun tertentu.
    public function getShiftAktif(int $id_user): ?array
    {
        if ($id_user <= 0) {
            return null;
        }

        $sql = "
            SELECT
                s.id_shift,
                s.id_user,
                s.nama_shift,
                s.tanggal,
                s.status,
                s.waktu_buka,
                s.waktu_tutup,
                s.total_penjualan_tunai,
                s.total_penjualan_qris,
                s.total_pengeluaran,
                s.saldo_akhir,
                COALESCE(u.username, 'Kasir') AS nama_kasir
            FROM shift s
            LEFT JOIN users u ON s.id_user = u.id_user
            WHERE s.id_user = :id_user
            ORDER BY s.id_shift DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user
        ]);

        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shift ?: null;
    }

    // Mengambil shift yang sedang aktif milik akun login.
    public function getShiftKasirAktif(int $id_user): ?array
    {
        if ($id_user <= 0) {
            return null;
        }

        $sql = "
            SELECT
                s.id_shift,
                s.id_user,
                s.nama_shift,
                s.tanggal,
                s.status,
                s.waktu_buka,
                s.waktu_tutup,
                s.total_penjualan_tunai,
                s.total_penjualan_qris,
                s.total_pengeluaran,
                s.saldo_akhir,
                COALESCE(u.username, 'Kasir') AS nama_kasir
            FROM shift s
            LEFT JOIN users u ON s.id_user = u.id_user
            WHERE s.id_user = :id_user
              AND s.status = 'buka'
            ORDER BY s.id_shift DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user
        ]);

        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shift ?: null;
    }

    // Membuka shift baru untuk akun yang sedang login.
    public function bukaShiftBaru(
        int $id_user,
        string $nama_shift = ''
    ): int {
        if ($id_user <= 0) {
            return 0;
        }

        try {
            $this->pdo->beginTransaction();

            // Pastikan akun tersedia.
            $stmtUser = $this->pdo->prepare("
                SELECT id_user
                FROM users
                WHERE id_user = :id_user
                  AND status = 'aktif'
                FOR UPDATE
            ");

            $stmtUser->execute([
                ':id_user' => $id_user
            ]);

            if (!$stmtUser->fetchColumn()) {
                $this->pdo->rollBack();
                return 0;
            }

            // Periksa shift aktif milik akun tersebut.
            $stmtShift = $this->pdo->prepare("
                SELECT id_shift
                FROM shift
                WHERE id_user = :id_user
                  AND status = 'buka'
                LIMIT 1
            ");

            $stmtShift->execute([
                ':id_user' => $id_user
            ]);

            if ($stmtShift->fetchColumn()) {
                $this->pdo->rollBack();
                return 0;
            }

            $waktuSekarang = $this->getWaktuIndonesia();

            $namaShiftOtomatis = $this->tentukanNamaShift(
                $waktuSekarang
            );

            $sql = "
                INSERT INTO shift (
                    nama_shift,
                    tanggal,
                    waktu_buka,
                    status,
                    id_user,
                    total_penjualan_tunai,
                    total_penjualan_qris,
                    total_pengeluaran
                )
                VALUES (
                    :nama_shift,
                    :tanggal,
                    :waktu_buka,
                    'buka',
                    :id_user,
                    0,
                    0,
                    0
                )
                RETURNING id_shift
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':nama_shift' => $namaShiftOtomatis,
                ':tanggal' => $waktuSekarang->format('Y-m-d'),
                ':waktu_buka' => $waktuSekarang->format('Y-m-d H:i:s'),
                ':id_user' => $id_user
            ]);

            $idShift = (int) $stmt->fetchColumn();

            $this->pdo->commit();

            return $idShift;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log(
                '[ShiftModel::bukaShiftBaru] ' . $e->getMessage()
            );

            return 0;
        }
    }

    // Menutup shift milik akun yang sedang login.
    public function tutupShift(
        int $id_shift,
        int $id_user
    ): bool {
        if ($id_shift <= 0 || $id_user <= 0) {
            return false;
        }

        try {
            $waktuSekarang = $this->getWaktuIndonesia()
                ->format('Y-m-d H:i:s');

            $sql = "
                UPDATE shift
                SET
                    status = 'tutup',
                    waktu_tutup = :waktu_tutup,
                    update_at = :update_at
                WHERE id_shift = :id_shift
                  AND id_user = :id_user
                  AND status = 'buka'
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':waktu_tutup' => $waktuSekarang,
                ':update_at' => $waktuSekarang,
                ':id_shift' => $id_shift,
                ':id_user' => $id_user
            ]);

            return $stmt->rowCount() > 0;

        } catch (Throwable $e) {
            error_log(
                '[ShiftModel::tutupShift] ' . $e->getMessage()
            );

            return false;
        }
    }

    // Mengambil total pengeluaran operasional hari ini.
    public function getInfoOperasionalFooter(int $id_user): string
    {
        $tanggalHariIni = $this->getWaktuIndonesia()->format('Y-m-d');

        $shift = $this->getShiftKasirAktif($id_user);

        $stmtExp = $this->pdo->prepare("
            SELECT COALESCE(SUM(ps.nominal), 0)
            FROM pengeluaran_shift ps
            INNER JOIN shift s ON ps.id_shift = s.id_shift
            WHERE s.id_user = :id_user
              AND s.tanggal = :tanggal
        ");

        $stmtExp->execute([
            ':id_user' => $id_user,
            ':tanggal' => $tanggalHariIni
        ]);

        $totalPengeluaran = (float) $stmtExp->fetchColumn();

        $nominalPengeluaran = number_format(
            $totalPengeluaran,
            0,
            ',',
            '.'
        );

        if ($shift) {
            $namaShift = htmlspecialchars(
                $shift['nama_shift'],
                ENT_QUOTES,
                'UTF-8'
            );

            $namaKasir = htmlspecialchars(
                $shift['nama_kasir'],
                ENT_QUOTES,
                'UTF-8'
            );

            return "Shift {$namaShift} sedang berlangsung oleh {$namaKasir} · Pengeluaran Rp {$nominalPengeluaran}.";
        }

        return "Belum ada shift aktif pada akun ini · Pengeluaran Rp {$nominalPengeluaran}.";
    }
}
