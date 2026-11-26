
<?php
// controllers/MenuController.php
// Controller Katalog Menu, Resep, dan Kustomisasi Produk KolegaOps

require_once BASE_PATH . '/models/MenuModel.php';

class MenuController
{
    private PDO $pdo;
    private MenuModel $menuModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->menuModel = new MenuModel($pdo);
    }

    /**
     * Menampilkan daftar seluruh produk.
     */
    public function index(): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        $daftarProduk = $this->menuModel->getAllMenu();

        $totalAktif = count(array_filter(
            $daftarProduk,
            fn($p) => ($p['status_label'] ?? '') === 'Aktif'
        ));

        $hargaList = array_map(
            fn($p) => (float) $p['harga'],
            $daftarProduk
        );

        $minHarga = !empty($hargaList) ? min($hargaList) : 0;
        $maxHarga = !empty($hargaList) ? max($hargaList) : 0;

        $pesanSukses = get_flash('success');
        $pesanError = get_flash('error');

        require_once BASE_PATH . '/views/admin/produk.php';
    }

    /**
     * Form tambah produk.
     */
    public function tambah(): void
    {
        require_role(['super_admin', 'owner', 'admin']);
        $this->form(null);
    }

    /**
     * Form edit produk.
     */
    public function edit($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin']);
        $this->form($id);
    }

    /**
     * Menampilkan dan memproses form produk, resep, dan kustomisasi.
     */
    public function form($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        $id = (int) ($id ?? $_GET['id'] ?? 0);
        $isEdit = $id > 0;

        // Ambil daftar bahan baku untuk form resep.
        $stmtBahan = $this->pdo->query("
            SELECT id_bahan, nama_bahan, satuan
            FROM bahan_baku
            ORDER BY nama_bahan ASC
        ");

        $daftarBahan = $stmtBahan->fetchAll(PDO::FETCH_ASSOC);

        $dataResepAktif = [];
        $dataKustomisasiAktif = [];

        // Ambil data produk saat mode edit.
        if ($isEdit) {
            $dataProduk = $this->menuModel->getMenuById($id);

            if (!$dataProduk) {
                set_flash('error', 'Data produk tidak ditemukan!');
                header('Location: index.php?controller=menu&action=index');
                exit;
            }

            // Samakan nama key agar tetap kompatibel dengan view lama.
            $dataProduk['id_menu'] = $dataProduk['id'];
            $dataProduk['nama_menu'] = $dataProduk['nama'];

            // Ambil resep produk.
            $stmtResep = $this->pdo->prepare("
                SELECT id_bahan, jumlah_pemakaian
                FROM resep
                WHERE id_menu = :id_menu
                ORDER BY id_resep ASC
            ");

            $stmtResep->execute([':id_menu' => $id]);
            $dataResepAktif = $stmtResep->fetchAll(PDO::FETCH_ASSOC);

            // Ambil kelompok kustomisasi dan pilihan produk.
            $dataKustomisasiAktif = $this->menuModel
                ->getKustomisasiByMenu($id, false);

        } else {
            $dataProduk = [
                'id' => null,
                'id_menu' => null,
                'nama' => '',
                'nama_menu' => '',
                'kategori' => 'Minuman',
                'hpp' => '',
                'harga' => '',
                'status' => 'Aktif',
                'status_label' => 'Aktif',
                'stok' => 0,
            ];
        }

        // =====================================================
        // PROSES FORM POST
        // =====================================================

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (isset($_POST['csrf_token']) && function_exists('csrf_verify')) {
                csrf_verify($_POST['csrf_token']);
            }

            $nama = trim($_POST['nama'] ?? $_POST['nama_menu'] ?? '');
            $kategori = trim($_POST['kategori'] ?? 'Minuman');
            $hpp = (float) ($_POST['hpp'] ?? 0);
            $harga = (float) ($_POST['harga'] ?? 0);
            $status = trim($_POST['status'] ?? 'Aktif');

            // Validasi produk.
            if ($nama === '') {
                set_flash('error', 'Nama produk wajib diisi!');
                header('Location: index.php?controller=menu&action=' . ($isEdit ? "edit&id={$id}" : 'tambah'));
                exit;
            }

            if ($hpp < 0 || $harga < 0) {
                set_flash('error', 'HPP dan harga jual tidak boleh negatif!');
                header('Location: index.php?controller=menu&action=' . ($isEdit ? "edit&id={$id}" : 'tambah'));
                exit;
            }

            $payload = [
                'id' => $isEdit ? $id : null,
                'nama' => $nama,
                'kategori' => $kategori,
                'hpp' => $hpp,
                'harga' => $harga,
                'status' => $status,
                'stok' => (int) ($_POST['stok'] ?? 0),
            ];

            try {
                $this->pdo->beginTransaction();

                // 1. Simpan produk utama.
                $idMenu = $this->menuModel->simpanMenu($payload);

                if (!$idMenu) {
                    throw new RuntimeException('Gagal menyimpan produk.');
                }

                $idMenu = (int) $idMenu;

                // 2. Perbarui resep produk.
                $stmtDeleteResep = $this->pdo->prepare("
                    DELETE FROM resep
                    WHERE id_menu = :id_menu
                ");

                $stmtDeleteResep->execute([':id_menu' => $idMenu]);

                if (
                    isset($_POST['resep_bahan']) &&
                    is_array($_POST['resep_bahan'])
                ) {
                    $stmtInsertResep = $this->pdo->prepare("
                        INSERT INTO resep (
                            id_menu,
                            id_bahan,
                            jumlah_pemakaian
                        )
                        VALUES (
                            :id_menu,
                            :id_bahan,
                            :jumlah_pemakaian
                        )
                    ");

                    foreach ($_POST['resep_bahan'] as $index => $idBahan) {
                        $takaran = (float) (
                            $_POST['resep_takaran'][$index] ?? 0
                        );

                        if ((int) $idBahan > 0 && $takaran > 0) {
                            $stmtInsertResep->execute([
                                ':id_menu' => $idMenu,
                                ':id_bahan' => (int) $idBahan,
                                ':jumlah_pemakaian' => $takaran,
                            ]);
                        }
                    }
                }

                // 3. Simpan kustomisasi jika form mengirim bagian kustomisasi.
                //
                // Form nantinya perlu memiliki input hidden:
                // name="kustomisasi_dikirim" value="1"
                //
                // Jika bagian ini tidak dikirim, kustomisasi lama dipertahankan.

                if (isset($_POST['kustomisasi_dikirim'])) {

                    // Hapus kelompok lama. Pilihan di dalamnya ikut terhapus.
                    $stmtHapusKustomisasi = $this->pdo->prepare("
                        DELETE FROM opsi_kustomisasi
                        WHERE id_menu = :id_menu
                    ");

                    $stmtHapusKustomisasi->execute([
                        ':id_menu' => $idMenu,
                    ]);

                    $daftarKustomisasi = $_POST['kustomisasi'] ?? [];

                    if (is_array($daftarKustomisasi)) {

                        foreach ($daftarKustomisasi as $kelompok) {

                            $namaOpsi = trim(
                                $kelompok['nama_opsi'] ?? ''
                            );

                            // Lewati kelompok kosong.
                            if ($namaOpsi === '') {
                                continue;
                            }

                            $tipePilihan = $kelompok['tipe_pilihan'] ?? 'single';

                            if (!in_array(
                                $tipePilihan,
                                ['single', 'multiple'],
                                true
                            )) {
                                throw new RuntimeException(
                                    'Tipe pilihan kustomisasi tidak valid.'
                                );
                            }

                            // Simpan kelompok kustomisasi.
                            $idOpsi = $this->menuModel
                                ->simpanKelompokKustomisasi([
                                    'id_menu' => $idMenu,
                                    'nama_opsi' => $namaOpsi,
                                    'tipe_pilihan' => $tipePilihan,
                                    'wajib_dipilih' => $kelompok['wajib_dipilih'] ?? false,
                                    'urutan' => (int) ($kelompok['urutan'] ?? 0),
                                    'status' => $kelompok['status'] ?? true,
                                ]);

                            if (!$idOpsi) {
                                throw new RuntimeException(
                                    'Gagal menyimpan kelompok ' . $namaOpsi
                                );
                            }

                            // Simpan pilihan dalam kelompok.
                            $daftarPilihan = $kelompok['pilihan'] ?? [];

                            if (!is_array($daftarPilihan)) {
                                continue;
                            }

                            foreach ($daftarPilihan as $pilihan) {

                                $namaPilihan = trim(
                                    $pilihan['nama_pilihan'] ?? ''
                                );

                                if ($namaPilihan === '') {
                                    continue;
                                }

                                $hargaTambahan = (float) (
                                    $pilihan['harga_tambahan'] ?? 0
                                );

                                if ($hargaTambahan < 0) {
                                    throw new RuntimeException(
                                        'Harga tambahan tidak boleh negatif.'
                                    );
                                }

                                $idDetail = $this->menuModel
                                    ->simpanPilihanKustomisasi([
                                        'id_opsi' => (int) $idOpsi,
                                        'nama_pilihan' => $namaPilihan,
                                        'harga_tambahan' => $hargaTambahan,
                                        'urutan' => (int) ($pilihan['urutan'] ?? 0),
                                        'status' => $pilihan['status'] ?? true,
                                    ]);

                                if (!$idDetail) {
                                    throw new RuntimeException(
                                        'Gagal menyimpan pilihan ' . $namaPilihan
                                    );
                                }
                            }
                        }
                    }
                }

                // 4. Semua penyimpanan berhasil.
                $this->pdo->commit();

                $pesan = $isEdit
                    ? "Produk '{$nama}', resep, dan pengaturannya berhasil diperbarui!"
                    : "Produk '{$nama}' berhasil ditambahkan beserta resep dan pengaturannya!";

                set_flash('success', $pesan);

                header('Location: index.php?controller=menu&action=index');
                exit;

            } catch (Throwable $e) {

                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                error_log(
                    '[MenuController::form] ' . $e->getMessage()
                );

                set_flash(
                    'error',
                    'Gagal menyimpan data produk. Periksa kembali isian dan koneksi database.'
                );

                header('Location: index.php?controller=menu&action=' . ($isEdit ? "edit&id={$id}" : 'tambah'));
                exit;
            }
        }

        // =====================================================
        // DATA UNTUK TAMPILAN FORM
        // =====================================================

        $hppAwal = is_numeric($dataProduk['hpp'] ?? 0)
            ? (float) $dataProduk['hpp']
            : 0;

        $hargaAwal = is_numeric($dataProduk['harga'] ?? 0)
            ? (float) $dataProduk['harga']
            : 0;

        $profitAwal = $hargaAwal - $hppAwal;

        $marginAwal = $hargaAwal > 0
            ? ($profitAwal / $hargaAwal) * 100
            : 0;

        $pesanError = get_flash('error');

        require_once BASE_PATH . '/views/admin/form-produk.php';
    }

    /**
     * Menghapus produk dari katalog.
     */
    public function hapus($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin']);

        $id = (int) ($id ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            set_flash('error', 'ID produk tidak valid!');
            header('Location: index.php?controller=menu&action=index');
            exit;
        }

        $item = $this->menuModel->getMenuById($id);
        $nama = $item['nama'] ?? 'Produk';

        try {
            $this->pdo->beginTransaction();

            // Hapus resep.
            $stmtResep = $this->pdo->prepare("
                DELETE FROM resep
                WHERE id_menu = :id_menu
            ");

            $stmtResep->execute([':id_menu' => $id]);

            // Hapus produk.
            // Kelompok dan pilihan kustomisasi ikut terhapus
            // melalui ON DELETE CASCADE pada database.
            $berhasil = $this->menuModel->hapusMenu($id);

            if (!$berhasil) {
                throw new RuntimeException('Produk gagal dihapus.');
            }

            $this->pdo->commit();

            set_flash(
                'success',
                "Produk '{$nama}' berhasil dihapus dari katalog."
            );

        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log(
                '[MenuController::hapus] ' . $e->getMessage()
            );

            set_flash(
                'error',
                "Produk '{$nama}' gagal dihapus. Produk mungkin masih digunakan dalam transaksi."
            );
        }

        header('Location: index.php?controller=menu&action=index');
        exit;
    }
}
