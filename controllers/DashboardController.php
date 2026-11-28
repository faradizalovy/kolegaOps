
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

    public function dashboard_admin(): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        date_default_timezone_set('Asia/Jakarta');

        $statHariIni = $this->transaksiModel->getStatistikHariIni();
        $rekap7Hari = $this->transaksiModel->getPendapatan7Hari();
        $transaksiTerbaru = $this->transaksiModel->getTransaksiTerbaru(4);
        $produkTerlaris = $this->menuModel->getProdukTerlarisHariIni(3);
        $stokKritisCount = $this->stokBahanModel->getStokKritisCount();

        $idUser = (int) (current_user('id_user') ?? 0);
        $infoShift = $this->shiftModel->getInfoOperasionalFooter($idUser);

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

    public function dashboard_pegawai(): void
    {
        require_role(['kasir', 'pegawai']);

        date_default_timezone_set('Asia/Jakarta');

        $idUser = (int) (current_user('id_user') ?? 0);

        $namaKasir = ucfirst(
            (string) (current_user('username') ?? 'Pegawai')
        );

        // Mengambil shift terakhir dan shift aktif milik akun login.
        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);
        $shiftTerakhir = $this->shiftModel->getShiftAktif($idUser);

        $idShift = $shiftAktif
            ? (int) $shiftAktif['id_shift']
            : 0;

        $penjualanHariIni = $this->transaksiModel
            ->getStatistikHariIni();

        $penjualanShiftSaya = ($idShift > 0)
            ? $this->transaksiModel->getPenjualanShiftKasir(
                $idShift,
                $idUser
            )
            : [];

        $rekapPembayaran = $penjualanShiftSaya;

        $transaksiTerbaru = $this->transaksiModel
            ->getTransaksiTerbaruKasir($idUser, 4);

        $stokKritis = $this->stokBahanModel
            ->getDaftarStokKritis();

        $pengeluaranShift = ($idShift > 0)
            ? $this->pengeluaranModel->getPengeluaranByShift($idShift)
            : [];

        $totalPengeluaranShift = ($idShift > 0)
            ? $this->pengeluaranModel->getTotalPengeluaranByShift($idShift)
            : 0;

        $pesanSukses = get_flash('success');
        $pesanError = get_flash('error');

        $tanggalTeks = format_tanggal(date('Y-m-d'));

        require_once BASE_PATH . '/views/pegawai/dashboard.php';
    }

    public function bukaShift(): void
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

        if ($idUser <= 0) {
            set_flash('error', 'Akun pegawai tidak valid.');
            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        if ($shiftAktif !== null) {
            set_flash(
                'error',
                'Kamu masih memiliki shift yang sedang berjalan.'
            );

            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        $idShiftBaru = $this->shiftModel->bukaShiftBaru($idUser);

        if ($idShiftBaru > 0) {
            $shiftBaru = $this->shiftModel->getShiftKasirAktif($idUser);
            $namaShiftBaru = $shiftBaru['nama_shift'] ?? 'Shift';

            set_flash(
                'success',
                "Shift {$namaShiftBaru} berhasil dibuka."
            );
        } else {
            set_flash(
                'error',
                'Shift gagal dibuka. Silakan coba kembali.'
            );
        }

        header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
        exit;
    }

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

        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        if (
            !$shiftAktif ||
            (int) $shiftAktif['id_shift'] !== $idShift
        ) {
            set_flash(
                'error',
                'Shift tidak aktif. Pengeluaran tidak dapat dicatat.'
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

        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        if (
            !$shiftAktif ||
            (int) $shiftAktif['id_shift'] !== $idShift
        ) {
            set_flash(
                'error',
                'Shift tidak ditemukan atau sudah ditutup.'
            );

            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
            exit;
        }

        $berhasil = $this->shiftModel->tutupShift(
            $idShift,
            $idUser
        );

        if ($berhasil) {
            set_flash(
                'success',
                'Shift berhasil ditutup. Rekap transaksi dan pengeluaran tetap tersimpan.'
            );
        } else {
            set_flash(
                'error',
                'Gagal menutup shift. Silakan coba kembali.'
            );
        }

        header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
        exit;
    }

    public function index(): void
    {
        require_login();

        $role = normalize_role((string) current_user('role'));

        if (in_array($role, ['super_admin', 'owner', 'admin'], true)) {
            $this->dashboard_admin();
            return;
        }

        if (in_array($role, ['kasir', 'pegawai'], true)) {
            $this->dashboard_pegawai();
            return;
        }

        header('Location: index.php?controller=auth&action=login');
        exit;
    }
}
