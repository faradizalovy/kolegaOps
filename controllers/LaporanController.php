
<?php
// controllers/LaporanController.php
// Controller untuk Laporan Keseluruhan, Analisis Profit, dan Dashboard Owner KolegaOps

require_once BASE_PATH . '/models/TransaksiModel.php';
require_once BASE_PATH . '/models/MenuModel.php';
require_once BASE_PATH . '/models/StokBahanModel.php';
require_once BASE_PATH . '/models/ShiftModel.php';
require_once BASE_PATH . '/models/PengeluaranModel.php';
require_once BASE_PATH . '/models/AnalisisProfitModel.php';

class LaporanController
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
        $this->transaksiModel   = new TransaksiModel($pdo);
        $this->menuModel        = new MenuModel($pdo);
        $this->stokBahanModel   = new StokBahanModel($pdo);
        $this->shiftModel       = new ShiftModel($pdo);
        $this->pengeluaranModel = new PengeluaranModel($pdo);
        $this->profitModel      = new AnalisisProfitModel($pdo);
    }

    /**
     * Menampilkan laporan keseluruhan untuk Super Admin dan Admin.
     */
    public function laporanAdmin(): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        $filterTanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $filterShift   = $_GET['shift'] ?? 'Semua';

        $filteredTransaksi = $this->transaksiModel->getRiwayatTransaksiFilter($filterTanggal, $filterShift);
        $rekapPembayaran   = $this->transaksiModel->getRekapPembayaran($filterTanggal, $filterShift);
        $pengeluaranList   = $this->pengeluaranModel->getPengeluaranByTanggal($filterTanggal, $filterShift);
        $totalPengeluaran  = $this->pengeluaranModel->getTotalPengeluaranByTanggal($filterTanggal, $filterShift);

        $totalOmzetFilter = (float) ($rekapPembayaran['total_omzet'] ?? 0);
        $jumlahTrxFilter  = (int) ($rekapPembayaran['jumlah_transaksi'] ?? 0);
        $totalCash        = (float) ($rekapPembayaran['total_cash'] ?? 0);
        $totalQris        = (float) ($rekapPembayaran['total_qris'] ?? 0);
        $labaBersih       = $totalOmzetFilter - $totalPengeluaran;

        $filterTanggalTeks = ($filterTanggal === 'Semua')
            ? 'Semua Tanggal'
            : format_tanggal($filterTanggal);

        $waktuUpdate = date('H:i');
        $namaOwner   = ucfirst((string) current_user('username'));

        require BASE_PATH . '/views/admin/laporan.php';
    }

    /**
     * Menampilkan laporan shift khusus pegawai/kasir.
     */
    public function laporanPegawai(): void
    {
        require_role(['pegawai', 'kasir']);

        $idUser = (int) (current_user('id_user') ?? 0);

        $namaKasir = (string) (
            current_user('username')
            ?? $_SESSION['user']['username']
            ?? $_SESSION['user']['nama']
            ?? 'Kasir'
        );

        $namaKasirLabel    = ucfirst($namaKasir);
        $filterTanggal     = date('Y-m-d');
        $filterTanggalTeks = format_tanggal($filterTanggal);
        $waktuUpdate       = date('H:i');

        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        if (!$shiftAktif) {
            $jamSekarang = (int) date('H');
            $namaShiftDefault = ($jamSekarang < 15) ? 'Pagi' : 'Sore';

            $this->shiftModel->bukaShiftBaru($idUser, $namaShiftDefault);
            $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);
        }

        $namaShiftAktif = $shiftAktif
            ? (string) $shiftAktif['nama_shift']
            : 'Shift Berjalan';

        $filteredTransaksi = $this->transaksiModel->getTransaksiShiftAktif($filterTanggal, $namaKasir);
        $rekapPembayaran   = $this->transaksiModel->getRekapPembayaranShiftAktif($filterTanggal, $namaKasir);
        $pengeluaranList   = $this->pengeluaranModel->getPengeluaranShiftAktif($filterTanggal, $namaKasir);
        $totalPengeluaran  = $this->pengeluaranModel->getTotalPengeluaranShiftAktif($filterTanggal, $namaKasir);

        $totalOmzetFilter = (float) ($rekapPembayaran['total_omzet'] ?? 0);
        $jumlahTrxFilter  = (int) ($rekapPembayaran['jumlah_transaksi'] ?? 0);
        $totalCash        = (float) ($rekapPembayaran['total_cash'] ?? 0);
        $totalQris        = (float) ($rekapPembayaran['total_qris'] ?? 0);
        $labaBersih       = $totalOmzetFilter - $totalPengeluaran;

        require BASE_PATH . '/views/pegawai/laporan_shift.php';
    }

    /**
     * Menampilkan analisis kontribusi profit menu.
     * Khusus Super Admin dan Owner (Admin & Kasir dilarang).
     */
    public function profit(): void
    {
        require_role(['super_admin', 'owner']);

        $filterTanggal  = $_GET['tanggal'] ?? date('Y-m-d');
        $filterKategori = $_GET['kategori'] ?? 'Semua';

        $filteredProfit = $this->profitModel->getRankingProfit($filterTanggal, $filterKategori);
        $daftarKategori = $this->profitModel->getDaftarKategori();

        $totalProfitBersih = array_sum(array_column($filteredProfit, 'total_profit'));
        $totalPorsiTerjual = array_sum(array_column($filteredProfit, 'terjual'));

        $rataRataProfitPorsi = $totalPorsiTerjual > 0
            ? (int) round($totalProfitBersih / $totalPorsiTerjual)
            : 0;

        $filterTanggalTeks = ($filterTanggal === 'Semua')
            ? 'Semua Tanggal'
            : format_tanggal($filterTanggal);

        $namaOwner = ucfirst((string) current_user('username'));

        require BASE_PATH . '/views/admin/owner/analisis-profit.php';
    }

    /**
     * Alias method analisisProfit.
     */
    public function analisisProfit(): void
    {
        $this->profit();
    }

    /**
     * Halaman default controller laporan.
     */
    public function index(): void
    {
        require_login();

        $role = normalize_role((string) current_user('role'));

        if (in_array($role, ['super_admin', 'owner', 'admin'], true)) {
            $this->laporanAdmin();
            return;
        }

        if ($role === 'kasir') {
            $this->laporanPegawai();
            return;
        }

        header('Location: index.php?controller=auth&action=login');
        exit;
    }
}
