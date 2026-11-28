
<?php
// controllers/TransaksiController.php
// Controller Point of Sales Kasir KolegaOps

require_once BASE_PATH . '/models/MenuModel.php';
require_once BASE_PATH . '/models/TransaksiModel.php';
require_once BASE_PATH . '/models/ShiftModel.php';
require_once BASE_PATH . '/models/StokBahanModel.php';

class TransaksiController
{
    private PDO $pdo;
    private MenuModel $menuModel;
    private TransaksiModel $transaksiModel;
    private ShiftModel $shiftModel;
    private StokBahanModel $stokBahanModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->menuModel = new MenuModel($pdo);
        $this->transaksiModel = new TransaksiModel($pdo);
        $this->shiftModel = new ShiftModel($pdo);
        $this->stokBahanModel = new StokBahanModel($pdo);
    }

    public function pos(): void
    {
        require_role(['pegawai', 'kasir']);

        date_default_timezone_set('Asia/Jakarta');

        $idUser = (int) (current_user('id_user') ?? 0);

        $namaKasir = (string) (
            current_user('username')
            ?? $_SESSION['user']['username']
            ?? $_SESSION['user']['nama']
            ?? 'Kasir'
        );

        $namaKasirLabel = ucfirst($namaKasir);
        $tanggalTeks = format_tanggal(date('Y-m-d'));

        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        $shiftBerjalan = !empty($shiftAktif)
            && ($shiftAktif['status'] ?? '') === 'buka'
            && (int) ($shiftAktif['id_user'] ?? 0) === $idUser;

        $namaShift = $shiftBerjalan
            ? (string) $shiftAktif['nama_shift']
            : 'Belum Dibuka';

        $idShift = $shiftBerjalan
            ? (int) $shiftAktif['id_shift']
            : 0;

        $daftarMenu = $this->menuModel->getAll();
        $daftarKategori = $this->menuModel->getKategoriAktif();

        $pesanSukses = get_flash('success');
        $pesanError = get_flash('error');

        require_once BASE_PATH . '/views/pegawai/kasir.php';
    }

    public function prosesCheckout(): void
    {
        require_role(['pegawai', 'kasir']);

        date_default_timezone_set('Asia/Jakarta');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        if (isset($_POST['csrf_token']) && function_exists('csrf_verify')) {
            csrf_verify($_POST['csrf_token']);
        }

        $idUser = (int) (current_user('id_user') ?? 0);

        if ($idUser <= 0) {
            set_flash('error', 'Akun kasir tidak valid.');
            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $shiftAktif = $this->shiftModel->getShiftKasirAktif($idUser);

        if (
            !$shiftAktif ||
            ($shiftAktif['status'] ?? '') !== 'buka' ||
            (int) ($shiftAktif['id_user'] ?? 0) !== $idUser
        ) {
            set_flash(
                'error',
                'Shift belum dibuka atau sudah ditutup. Transaksi tidak dapat diproses.'
            );

            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $idShift = (int) $shiftAktif['id_shift'];

        if ($idShift <= 0) {
            set_flash('error', 'Shift tidak valid.');
            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $itemsJson = trim($_POST['items_json'] ?? '');
        $itemsRaw = [];

        if ($itemsJson !== '') {
            $decoded = json_decode($itemsJson, true);

            if (is_array($decoded)) {
                $itemsRaw = $decoded;
            }
        }

        if (empty($itemsRaw)) {
            set_flash(
                'error',
                'Keranjang belanja kosong! Silakan pilih menu terlebih dahulu.'
            );

            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $itemsBersih = [];
        $subtotalServer = 0;
        $totalItemServer = 0;

        foreach ($itemsRaw as $item) {
            $idMenu = (int) ($item['id_menu'] ?? $item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? $item['jumlah'] ?? 0);

            if ($idMenu <= 0 || $qty <= 0) {
                set_flash('error', 'Data menu atau jumlah pesanan tidak valid.');
                header('Location: index.php?controller=transaksi&action=pos');
                exit;
            }

            $menu = $this->menuModel->getMenuById($idMenu);

            if (
                !$menu ||
                !in_array(
                    strtolower(trim((string) ($menu['status'] ?? ''))),
                    ['aktif', 'tersedia'],
                    true
                )
            ) {
                set_flash(
                    'error',
                    'Salah satu menu tidak tersedia. Silakan periksa keranjang.'
                );

                header('Location: index.php?controller=transaksi&action=pos');
                exit;
            }

            $harga = (float) $menu['harga'];
            $subtotalItem = $harga * $qty;

            $subtotalServer += $subtotalItem;
            $totalItemServer += $qty;

            $itemsBersih[] = [
                'id_menu' => $idMenu,
                'jumlah' => $qty,
                'harga_satuan' => $harga,
                'subtotal' => $subtotalItem,
                'catatan_item' => !empty($item['catatan'])
                    ? trim((string) $item['catatan'])
                    : null,
            ];
        }

        $metodeRaw = strtolower(
            trim((string) ($_POST['metode_pembayaran'] ?? 'tunai'))
        );

        $metodeBayar = $metodeRaw === 'qris'
            ? 'qris'
            : 'tunai';

        $diskon = max(0, (float) ($_POST['diskon'] ?? 0));
        $diskon = min($diskon, $subtotalServer);

        $total = max(0, $subtotalServer - $diskon);

        $nominalBayar = $metodeBayar === 'qris'
            ? $total
            : max(0, (float) ($_POST['nominal_bayar'] ?? 0));

        if ($metodeBayar === 'tunai' && $nominalBayar < $total) {
            set_flash(
                'error',
                'Uang yang diterima kurang dari total tagihan pesanan!'
            );

            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $kembalian = max(0, $nominalBayar - $total);

        $dataTransaksi = [
            'id_user' => $idUser,
            'id_shift' => $idShift,
            'nama_customer' => !empty($_POST['nama_customer'])
                ? trim((string) $_POST['nama_customer'])
                : null,
            'total_item' => $totalItemServer,
            'subtotal' => $subtotalServer,
            'diskon' => $diskon,
            'total' => $total,
            'metode_pembayaran' => $metodeBayar,
            'nominal_bayar' => $nominalBayar,
            'kembalian' => $kembalian,
            'catatan_transaksi' => !empty($_POST['catatan_transaksi'])
                ? trim((string) $_POST['catatan_transaksi'])
                : null,
            'items' => $itemsBersih,
        ];

        $hasil = $this->transaksiModel->simpanTransaksi($dataTransaksi);

        if (!empty($hasil['success'])) {
            $_SESSION['last_transaksi_id'] = $hasil['id_transaksi'];

            set_flash(
                'success',
                "Transaksi {$hasil['kode_transaksi']} berhasil diproses dan stok bahan baku otomatis terpotong!"
            );

            header(
                'Location: index.php?controller=transaksi&action=tagihan&id=' .
                $hasil['id_transaksi']
            );
        } else {
            set_flash(
                'error',
                'Gagal memproses transaksi: ' .
                ($hasil['error'] ?? 'Terjadi kesalahan sistem.')
            );

            header('Location: index.php?controller=transaksi&action=pos');
        }

        exit;
    }

    public function tagihan(): void
    {
        require_role(['pegawai', 'kasir']);

        date_default_timezone_set('Asia/Jakarta');

        $idUser = (int) (current_user('id_user') ?? 0);

        $idTransaksi = (int) (
            $_GET['id']
            ?? $_SESSION['last_transaksi_id']
            ?? 0
        );

        if ($idTransaksi <= 0) {
            set_flash('error', 'Tidak ada data transaksi yang dapat ditampilkan.');
            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $transaksi = $this->transaksiModel->getDetailTransaksi($idTransaksi);

        if (
            !$transaksi ||
            (int) ($transaksi['id_user'] ?? 0) !== $idUser
        ) {
            set_flash(
                'error',
                'Transaksi tidak ditemukan atau bukan milik akun kamu.'
            );

            header('Location: index.php?controller=transaksi&action=pos');
            exit;
        }

        $namaKasirLabel = ucfirst(
            (string) (
                $transaksi['nama_kasir']
                ?? current_user('username')
                ?? 'Kasir'
            )
        );

        $tanggalTeks = format_tanggal(
            (string) ($transaksi['tanggal'] ?? date('Y-m-d'))
        );

        $waktuTeks = (string) (
            $transaksi['waktu']
            ?? date('H:i')
        );

        require_once BASE_PATH . '/views/pegawai/tagihan.php';
    }

    public function index(): void
    {
        $this->pos();
    }
}
