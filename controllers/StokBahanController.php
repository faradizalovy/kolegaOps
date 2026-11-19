
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

    /**
     * Mengambil role pengguna yang sedang login secara konsisten.
     */
    private function getRole(): string
    {
        return normalize_role((string) current_user('role'));
    }

    /**
     * Memeriksa apakah pengguna merupakan pengelola (Super Admin, Owner, Admin).
     */
    private function isPengelola(): bool
    {
        return in_array(
            $this->getRole(),
            ['super_admin', 'owner', 'admin'],
            true
        );
    }

    /**
     * Halaman utama pengelolaan stok bahan baku.
     */
    public function index(): void
    {
        require_role(['super_admin', 'owner', 'admin', 'kasir', 'pegawai']);

        $role = $this->getRole();

        $idUser = (int) current_user('id_user');

        // Mengambil seluruh daftar stok bahan baku.
        // Stok bahan baku merupakan data bersama.
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

        // Filter riwayat stok dari input pengguna.
        $filterRiwayat = [
            'search' => trim($_GET['search_riwayat'] ?? ''),
            'jenis' => $_GET['jenis_riwayat'] ?? 'semua',
            'sumber' => $_GET['sumber_riwayat'] ?? 'semua',
            'tanggal' => $_GET['tanggal_riwayat'] ?? ''
        ];

        /*
         * PEMBATASAN RIWAYAT STOK
         *
         * Kasir hanya dapat melihat aktivitas stok
         * yang tercatat atas nama akun miliknya sendiri.
         *
         * ID pengguna diambil dari session, bukan dari URL
         * atau input form agar tidak dapat dimanipulasi.
         *
         * Jika ID pengguna tidak tersedia, gunakan -1
         * agar tidak ada riwayat pengguna lain yang tampil.
         */
        if ($role === 'kasir') {
            $filterRiwayat['user_id'] = $idUser > 0
                ? $idUser
                : -1;
        }

        // Mengambil riwayat stok sesuai hak akses.
        $riwayatStok = $this->stokBahanModel->getRiwayatStok(
            $filterRiwayat
        );

        // Mengambil statistik sesuai filter dan hak akses.
        $statistikRiwayat = $this->stokBahanModel->getStatistikRiwayatStok(
            $filterRiwayat
        );

        $jumlahAktivitas = $statistikRiwayat['total_aktivitas'] ?? 0;
        $jumlahMasuk = $statistikRiwayat['stok_masuk'] ?? 0;
        $jumlahKeluar = $statistikRiwayat['stok_keluar'] ?? 0;

        $namaOwner = ucfirst(
            (string) current_user('username')
        );

        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));

        $pesanSuccess = get_flash('success');
        $pesanError = get_flash('error');

        $pesanSukses = $pesanSuccess;

        // Super Admin dan Admin menggunakan halaman stok Owner.
        // Kasir menggunakan halaman stok Pegawai.
        if ($this->isPengelola()) {
            require BASE_PATH . '/views/admin/stok.php';
        } else {
            require BASE_PATH . '/views/pegawai/stok.php';
        }
    }

    /**
     * Menambahkan bahan baku baru.
     * Hanya Super Admin, Owner, dan Admin yang dapat mengakses.
     */
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
                set_flash(
                    'success',
                    'Bahan baku berhasil ditambahkan.'
                );
            } else {
                set_flash(
                    'error',
                    'Gagal menambahkan bahan baku.'
                );
            }

            header(
                'Location: index.php?controller=stok_bahan&action=index'
            );
            exit;
        }

        $namaOwner = ucfirst(
            (string) current_user('username')
        );

        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));

        $pesanError = get_flash('error');

        require BASE_PATH . '/views/admin/tambah-stok.php';
    }

    /**
     * Memperbarui jumlah stok bahan baku secara manual.
     * Hanya Super Admin, Owner, dan Admin yang diperbolehkan.
     */
    public function update($id = null): void
    {
        // Pemeriksaan dilakukan di server.
        // Kasir tidak dapat mengubah stok meskipun membuka URL langsung.
        require_role(['super_admin', 'owner', 'admin']);

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

            $jenisUpdate = trim(
                $_POST['jenis_update'] ?? ''
            );

            $jumlahTambahan = (float) (
                $_POST['jumlah_tambahan'] ?? 0
            );

            $catatan = trim(
                $_POST['catatan'] ?? ''
            );

            if ($idStok <= 0) {
                set_flash(
                    'error',
                    'Pilihan bahan baku tidak valid!'
                );

                header(
                    'Location: index.php?controller=stok_bahan&action=index'
                );
                exit;
            }

            if (!in_array(
                $jenisUpdate,
                ['Penambahan stok', 'Pengurangan stok'],
                true
            )) {
                set_flash(
                    'error',
                    'Jenis perubahan stok tidak valid!'
                );

                header(
                    "Location: index.php?controller=stok_bahan&action=update&id={$idStok}"
                );
                exit;
            }

            if ($jumlahTambahan <= 0) {
                set_flash(
                    'error',
                    'Jumlah perubahan stok harus lebih besar dari 0!'
                );

                header(
                    "Location: index.php?controller=stok_bahan&action=update&id={$idStok}"
                );
                exit;
            }

            if ($catatan === '') {
                set_flash(
                    'error',
                    'Alasan atau catatan perubahan wajib diisi!'
                );

                header(
                    "Location: index.php?controller=stok_bahan&action=update&id={$idStok}"
                );
                exit;
            }

            $item = $this->stokBahanModel->getStokById($idStok);

            if (!$item) {
                set_flash(
                    'error',
                    'Data bahan baku tidak ditemukan!'
                );

                header(
                    'Location: index.php?controller=stok_bahan&action=index'
                );
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

                    header(
                        "Location: index.php?controller=stok_bahan&action=update&id={$idStok}"
                    );
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

                $formatStok = number_format(
                    $stokBaru,
                    3,
                    ',',
                    '.'
                );

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

            header(
                'Location: index.php?controller=stok_bahan&action=index'
            );
            exit;
        }

        // Bagian ini hanya dapat diakses Admin dan Super Admin.
        $idItem = (int) (
            $id ?? $_GET['id'] ?? 0
        );

        $daftarStok = $this->stokBahanModel->getAllStok();

        if (empty($daftarStok)) {
            set_flash(
                'error',
                'Belum ada data stok bahan baku.'
            );

            header(
                'Location: index.php?controller=stok_bahan&action=index'
            );
            exit;
        }

        $selectedStok = null;

        if ($idItem > 0) {
            $selectedStok = $this->stokBahanModel->getStokById(
                $idItem
            );
        }

        if (!$selectedStok) {
            $selectedStok = $daftarStok[0];
        }

        $namaOwner = ucfirst(
            (string) current_user('username')
        );

        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));

        $pesanError = get_flash('error');

        require BASE_PATH . '/views/admin/update-stok.php';
    }

    /**
     * Menghapus bahan baku.
     * Hanya Super Admin, Owner, dan Admin yang dapat mengakses.
     */
    public function hapus($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        $idStok = (int) (
            $id ?? $_GET['id'] ?? 0
        );

        if ($idStok <= 0) {
            set_flash(
                'error',
                'Data bahan baku tidak valid!'
            );

            header(
                'Location: index.php?controller=stok_bahan&action=index'
            );
            exit;
        }

        $item = $this->stokBahanModel->getStokById($idStok);

        if (!$item) {
            set_flash(
                'error',
                'Data bahan baku tidak ditemukan!'
            );

            header(
                'Location: index.php?controller=stok_bahan&action=index'
            );
            exit;
        }

        $hapus = $this->stokBahanModel->hapusStok($idStok);

        if ($hapus) {
            set_flash(
                'success',
                'Bahan baku berhasil dihapus.'
            );
        } else {
            set_flash(
                'error',
                'Gagal menghapus bahan baku.'
            );
        }

        header(
            'Location: index.php?controller=stok_bahan&action=index'
        );
        exit;
    }
}
