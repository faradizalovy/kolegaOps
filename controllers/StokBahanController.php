
<?php
// controllers/StokBahanController.php
// Controller Pengelolaan Stok Bahan Baku dan Riwayat Stok KolegaOps

require_once BASE_PATH . '/models/StokBahanModel.php';

class StokBahanController
{
    private PDO $pdo;
    private StokBahanModel $stokBahanModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->stokBahanModel = new StokBahanModel($pdo);
    }

    private function getRole(): string
    {
        return normalize_role((string) current_user('role'));
    }

    private function isPengelola(): bool
    {
        return in_array(
            $this->getRole(),
            ['super_admin', 'owner', 'admin'],
            true
        );
    }

    public function index(): void
    {
        require_role(['super_admin', 'owner', 'admin', 'kasir', 'pegawai']);

        $role = $this->getRole();
        $idUser = (int) current_user('id_user');

        $daftarStok = $this->stokBahanModel->getAllStok();
        $jumlahStokKritis = $this->stokBahanModel->getStokKritisCount();
        $daftarStokKritis = $this->stokBahanModel->getDaftarStokKritis();

        $totalItem = count($daftarStok);
        $totalBahan = count($daftarStok);
        $totalMenu = 0;
        $totalKritis = count($daftarStokKritis);
        $totalAman = $totalItem - $totalKritis;

        $namaBahanKritis = [];

        foreach ($daftarStokKritis as $itemKritis) {
            $namaBahanKritis[] = $itemKritis['nama_bahan']
                ?? 'Bahan tidak diketahui';
        }

        $kritisTeks = !empty($namaBahanKritis)
            ? implode(', ', $namaBahanKritis)
            : 'Tidak ada bahan yang perlu direstock';

        $filterRiwayat = [
            'search' => trim($_GET['search_riwayat'] ?? ''),
            'jenis' => $_GET['jenis_riwayat'] ?? 'semua',
            'sumber' => $_GET['sumber_riwayat'] ?? 'semua',
            'tanggal' => $_GET['tanggal_riwayat'] ?? ''
        ];

        if ($role === 'kasir') {
            $filterRiwayat['user_id'] = $idUser > 0 ? $idUser : -1;
        }

        $riwayatStok = $this->stokBahanModel->getRiwayatStok(
            $filterRiwayat
        );

        $statistikRiwayat = $this->stokBahanModel->getStatistikRiwayatStok(
            $filterRiwayat
        );

        $jumlahAktivitas = $statistikRiwayat['total_aktivitas'] ?? 0;
        $jumlahMasuk = $statistikRiwayat['stok_masuk'] ?? 0;
        $jumlahKeluar = $statistikRiwayat['stok_keluar'] ?? 0;

        $namaOwner = ucfirst((string) current_user('username'));
        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));

        $pesanSuccess = get_flash('success');
        $pesanError = get_flash('error');
        $pesanSukses = $pesanSuccess;

        if ($this->isPengelola()) {
            require BASE_PATH . '/views/admin/stok.php';
        } else {
            require BASE_PATH . '/views/pegawai/stok.php';
        }
    }

    public function tambah(): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                isset($_POST['csrf_token']) &&
                function_exists('csrf_verify')
            ) {
                csrf_verify($_POST['csrf_token']);
            }

            $simpan = $this->stokBahanModel->tambahStokBahan($_POST);

            if ($simpan) {
                set_flash('success', 'Bahan baku berhasil ditambahkan.');
            } else {
                set_flash('error', 'Gagal menambahkan bahan baku.');
            }

            header('Location: index.php?controller=stok_bahan&action=index');
            exit;
        }

        $namaOwner = ucfirst((string) current_user('username'));
        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));
        $pesanError = get_flash('error');

        require BASE_PATH . '/views/admin/tambah-stok.php';
    }

    /**
     * Memperbarui stok.
     * Super Admin, Owner, Admin, dan Kasir diperbolehkan.
     */
    public function update($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin', 'kasir', 'pegawai']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                isset($_POST['csrf_token']) &&
                function_exists('csrf_verify')
            ) {
                csrf_verify($_POST['csrf_token']);
            }

            $idStok = (int) (
                $_POST['id_stok']
                ?? $id
                ?? $_GET['id']
                ?? 0
            );

            $jenisUpdate = trim($_POST['jenis_update'] ?? '');
            $jumlahTambahan = (float) ($_POST['jumlah_tambahan'] ?? 0);
            $catatan = trim($_POST['catatan'] ?? '');

            if ($idStok <= 0) {
                set_flash('error', 'Pilihan bahan baku tidak valid!');
                header('Location: index.php?controller=stok_bahan&action=index');
                exit;
            }

            if (!in_array(
                $jenisUpdate,
                ['Penambahan stok', 'Pengurangan stok'],
                true
            )) {
                set_flash('error', 'Jenis perubahan stok tidak valid!');
                header("Location: index.php?controller=stok_bahan&action=update&id={$idStok}");
                exit;
            }

            if ($jumlahTambahan <= 0) {
                set_flash('error', 'Jumlah perubahan stok harus lebih besar dari 0!');
                header("Location: index.php?controller=stok_bahan&action=update&id={$idStok}");
                exit;
            }

            if ($catatan === '') {
                set_flash('error', 'Alasan atau catatan perubahan wajib diisi!');
                header("Location: index.php?controller=stok_bahan&action=update&id={$idStok}");
                exit;
            }

            $item = $this->stokBahanModel->getStokById($idStok);

            if (!$item) {
                set_flash('error', 'Data bahan baku tidak ditemukan!');
                header('Location: index.php?controller=stok_bahan&action=index');
                exit;
            }

            $stokAwal = (float) $item['stok'];

            if ($jenisUpdate === 'Penambahan stok') {
                $stokBaru = $stokAwal + $jumlahTambahan;
            } else {
                if ($jumlahTambahan > $stokAwal) {
                    set_flash(
                        'error',
                        'Pengurangan stok gagal! Jumlah yang dikurangi melebihi stok tersedia.'
                    );

                    header("Location: index.php?controller=stok_bahan&action=update&id={$idStok}");
                    exit;
                }

                $stokBaru = $stokAwal - $jumlahTambahan;
            }

            $simpan = $this->stokBahanModel->updateStok(
                $idStok,
                $stokBaru,
                $catatan
            );

            if ($simpan) {
                $namaBahan = htmlspecialchars(
                    $item['nama'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                $satuan = htmlspecialchars(
                    $item['satuan'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                $formatStok = number_format($stokBaru, 3, ',', '.');

                set_flash(
                    'success',
                    "Stok {$namaBahan} berhasil diperbarui menjadi {$formatStok} {$satuan}."
                );
            } else {
                set_flash(
                    'error',
                    'Gagal memperbarui stok atau menyimpan riwayat perubahan stok!'
                );
            }

            header('Location: index.php?controller=stok_bahan&action=index');
            exit;
        }

        $idItem = (int) ($id ?? $_GET['id'] ?? 0);
        $daftarStok = $this->stokBahanModel->getAllStok();

        if (empty($daftarStok)) {
            set_flash('error', 'Belum ada data stok bahan baku.');
            header('Location: index.php?controller=stok_bahan&action=index');
            exit;
        }

        $selectedStok = null;

        if ($idItem > 0) {
            $selectedStok = $this->stokBahanModel->getStokById($idItem);
        }

        if (!$selectedStok) {
            $selectedStok = $daftarStok[0];
        }

        $namaOwner = ucfirst((string) current_user('username'));
        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));
        $pesanError = get_flash('error');

        require BASE_PATH . '/views/admin/update-stok.php';
    }

    public function hapus($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        $idStok = (int) ($id ?? $_GET['id'] ?? 0);

        if ($idStok <= 0) {
            set_flash('error', 'Data bahan baku tidak valid!');
            header('Location: index.php?controller=stok_bahan&action=index');
            exit;
        }

        $item = $this->stokBahanModel->getStokById($idStok);

        if (!$item) {
            set_flash('error', 'Data bahan baku tidak ditemukan!');
            header('Location: index.php?controller=stok_bahan&action=index');
            exit;
        }

        $hapus = $this->stokBahanModel->hapusStok($idStok);

        if ($hapus) {
            set_flash('success', 'Bahan baku berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus bahan baku.');
        }

        header('Location: index.php?controller=stok_bahan&action=index');
        exit;
    }
}
