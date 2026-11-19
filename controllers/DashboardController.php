
<?php
// controllers/DashboardController.php
// Controller Dashboard Owner dan Pegawai KolegaOps

require_once BASE_PATH . '/models/TransaksiModel.php';
require_once BASE_PATH . '/models/MenuModel.php';
require_once BASE_PATH . '/models/StokBahanModel.php';
require_once BASE_PATH . '/models/ShiftModel.php';
require_once BASE_PATH . '/models/PengeluaranModel.php';
require_once BASE_PATH . '/models/AnalisisProfitModel.php';

class DashboardController
{
    private PDO $pdo;
    private TransaksiModel $transaksiModel;
    private MenuModel $menuModel;
    private StokBahanModel $stokBahanModel;
    private ShiftModel $shiftModel;
    private PengeluaranModel $pengeluaranModel;
    private AnalisisProfitModel $profitModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->transaksiModel = new TransaksiModel($pdo);
        $this->menuModel = new MenuModel($pdo);
        $this->stokBahanModel = new StokBahanModel($pdo);
        $this->shiftModel = new ShiftModel($pdo);
        $this->pengeluaranModel = new PengeluaranModel($pdo);
        $this->profitModel = new AnalisisProfitModel($pdo);
    }

    /**
     * Menampilkan Dashboard Utama Owner, Super Admin, dan Admin.
     */
    public function dashboard_admin(): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        // Menggunakan zona waktu Indonesia.
        date_default_timezone_set('Asia/Jakarta');

        // Mengambil data dari database.
        $statHariIni = $this->transaksiModel->getStatistikHariIni();
        $rekap7Hari = $this->transaksiModel->getPendapatan7Hari();
        $transaksiTerbaru = $this->transaksiModel->getTransaksiTerbaru(4);
        $produkTerlaris = $this->menuModel->getProdukTerlarisHariIni(3);
        $stokKritisCount = $this->stokBahanModel->getStokKritisCount();
        $infoShift = $this->shiftModel->getInfoOperasionalFooter();

        // Menyiapkan data untuk View.
        $total_penjualan = format_rupiah($statHariIni['total_penjualan']);
        $persen_vs_kemarin = $statHariIni['persen_vs_kemarin'];
        $jumlah_transaksi = $statHariIni['jumlah_transaksi'];
        $rata_rata_transaksi = format_rupiah($statHariIni['rata_rata']);

        $stok_kritis_count = $stokKritisCount;
        $pendapatan_mingguan = $rekap7Hari['daftar_hari'];
        $total_pendapatan_mingguan = format_rupiah($rekap7Hari['total_mingguan']);

        $cash_hari_ini = format_rupiah($statHariIni['total_cash']);
        $qris_hari_ini = format_rupiah($statHariIni['total_qris']);

        $transaksi_terbaru = $transaksiTerbaru;
        $produk_terlaris = $produkTerlaris;
        $info_shift = $infoShift;

        $namaOwner = ucfirst((string) current_user('username'));
        $tanggal_hari_ini = format_tanggal(date('Y-m-d'));
        $waktu_update = date('H:i');

        require_once BASE_PATH . '/views/admin/dashboard.php';
    }

    /**
     * Menampilkan Dashboard Utama Kasir / Pegawai.
     */
    public function dashboard_pegawai(): void
    {
        require_role(['kasir', 'pegawai']);

        // Pastikan seluruh waktu yang digunakan mengikuti WIB.
        date_default_timezone_set('Asia/Jakarta');

        $idUser = (int) (current_user('id_user') ?? 0);
        $namaKasir = ucfirst(
            (string) (current_user('username') ?? 'Pegawai')
        );

        // Mengambil shift milik kasir khusus tanggal hari ini.
        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        /*
         * Jika belum ada shift hari ini, buka shift baru secara otomatis.
         *
         * Pukul 00.00 sampai 14.59 = Pagi.
         * Pukul 15.00 sampai 23.59 = Sore.
         *
         * Jika shift hari ini sudah ditutup, ShiftModel akan tetap
         * mengembalikan data shift tersebut sehingga tidak dibuat
         * shift baru setiap kali dashboard dibuka.
         */
        if (!$shiftAktif && $idUser > 0) {
            $jamSekarang = (int) date('H');

            if ($jamSekarang < 15) {
                $namaShiftDefault = 'Pagi';
            } else {
                $namaShiftDefault = 'Sore';
            }

            $newShiftId = $this->shiftModel->bukaShiftBaru(
                $idUser,
                $namaShiftDefault
            );

            // Ambil kembali data shift setelah proses pembukaan.
            if ($newShiftId > 0) {
                $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);
            }
        }

        // ID shift yang digunakan untuk mengambil data operasional.
        $idShift = $shiftAktif
            ? (int) $shiftAktif['id_shift']
            : 0;

        // Mengambil data penjualan dan operasional.
        $penjualanHariIni = $this->transaksiModel->getStatistikHariIni();

        $penjualanShiftSaya = $this->transaksiModel
            ->getPenjualanShiftKasir($idShift, $idUser);

        $rekapPembayaran = $penjualanShiftSaya;

        $transaksiTerbaru = $this->transaksiModel
            ->getTransaksiTerbaruKasir($idUser, 4);

        $stokKritis = $this->stokBahanModel->getDaftarStokKritis();

        $pengeluaranShift = ($idShift > 0)
            ? $this->pengeluaranModel->getPengeluaranByShift($idShift)
            : [];

        $totalPengeluaranShift = ($idShift > 0)
            ? $this->pengeluaranModel->getTotalPengeluaranByShift($idShift)
            : 0;

        // Menghitung rekonsiliasi kas laci.
        $modalAwal = 200000;

        $cashMasuk = (float) ($penjualanShiftSaya['total_cash'] ?? 0);

        $uangSeharusnyaLaci = $modalAwal
            + $cashMasuk
            - $totalPengeluaranShift;

        // Mengambil pesan notifikasi.
        $pesanSukses = get_flash('success');
        $pesanError = get_flash('error');

        // Menyiapkan tanggal dashboard berdasarkan WIB.
        $tanggalTeks = format_tanggal(date('Y-m-d'));

        // Memuat tampilan dashboard pegawai.
        require_once BASE_PATH . '/views/pegawai/dashboard.php';
    }

    /**
     * Memproses pencatatan pengeluaran operasional tunai.
     */
    public function tambahPengeluaran(): void
    {
        require_role(['kasir', 'pegawai']);

        date_default_timezone_set('Asia/Jakarta');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        if (isset($_POST['csrf_token']) && function_exists('csrf_verify')) {
            csrf_verify($_POST['csrf_token']);
        }

        $idUser = (int) (current_user('id_user') ?? 0);
        $idShift = (int) ($_POST['id_shift'] ?? 0);

        $keterangan = trim($_POST['keterangan'] ?? '');
        $nominal = (float) ($_POST['nominal'] ?? 0);

        // Jika ID shift tidak dikirim, ambil shift milik kasir hari ini.
        if ($idShift <= 0) {
            $shift = $this->shiftModel->getShiftKasirAktif($idUser);

            $idShift = $shift
                ? (int) $shift['id_shift']
                : 0;
        }

        if ($idShift <= 0) {
            set_flash(
                'error',
                'Tidak ditemukan sesi shift untuk mencatat pengeluaran.'
            );

            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        if ($keterangan === '' || $nominal <= 0) {
            set_flash(
                'error',
                'Keterangan dan nominal pengeluaran wajib diisi dengan benar.'
            );

            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        $sukses = $this->pengeluaranModel->tambahPengeluaran(
            $idShift,
            $keterangan,
            $nominal
        );

        if ($sukses) {
            set_flash(
                'success',
                "Pengeluaran '{$keterangan}' sebesar Rp " .
                number_format($nominal, 0, ',', '.') .
                ' berhasil dicatat.'
            );
        } else {
            set_flash(
                'error',
                'Gagal mencatat pengeluaran operasional ke database.'
            );
        }

        header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
        exit;
    }

    /**
     * Memproses penutupan shift kasir.
     */
    public function tutupShift(): void
    {
        require_role(['kasir', 'pegawai']);

        date_default_timezone_set('Asia/Jakarta');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        if (isset($_POST['csrf_token']) && function_exists('csrf_verify')) {
            csrf_verify($_POST['csrf_token']);
        }

        $idUser = (int) (current_user('id_user') ?? 0);
        $idShift = (int) ($_POST['id_shift'] ?? 0);
        $uangFisik = (float) ($_POST['uang_fisik'] ?? 0);

        // Jika ID shift tidak dikirim, ambil shift milik kasir hari ini.
        if ($idShift <= 0) {
            $shift = $this->shiftModel->getShiftKasirAktif($idUser);

            $idShift = $shift
                ? (int) $shift['id_shift']
                : 0;
        }

        if ($idShift <= 0) {
            set_flash('error', 'Sesi shift tidak ditemukan.');

            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        // Ambil rekap penjualan dan pengeluaran shift.
        $penjualanShift = $this->transaksiModel
            ->getPenjualanShiftKasir($idShift, $idUser);

        $totalPengeluaranShift = $this->pengeluaranModel
            ->getTotalPengeluaranByShift($idShift);

        $totalTunai = (float) ($penjualanShift['total_cash'] ?? 0);
        $totalQris = (float) ($penjualanShift['total_qris'] ?? 0);

        $berhasil = $this->shiftModel->tutupShift(
            $idShift,
            $uangFisik,
            $totalTunai,
            $totalQris,
            $totalPengeluaranShift
        );

        if ($berhasil) {
            set_flash(
                'success',
                'Shift kasir berhasil ditutup dan seluruh data transaksi telah direkap.'
            );
        } else {
            set_flash(
                'error',
                'Gagal menutup sesi shift di database.'
            );
        }

        header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
        exit;
    }

    /**
     * Endpoint default controller.
     */
    public function index(): void
    {
        require_login();

        $role = normalize_role((string) current_user('role'));

        if (in_array($role, ['super_admin', 'owner', 'admin'], true)) {
            $this->dashboard_admin();
            return;
        }

        if ($role === 'kasir') {
            $this->dashboard_pegawai();
            return;
        }

        header('Location: index.php?controller=auth&action=login');
        exit;
    }
}
