<?php
// controllers/MenuController.php
// Controller Katalog Menu & Pengelolaan Produk untuk KolegaOps POS Warkop

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
     * Menampilkan daftar seluruh produk/menu katalog warkop
     */
    public function index(): void
    {
        // 1. Pengaman Hak Akses: Super Admin, Owner, dan Admin
        require_role(['super_admin', 'owner', 'admin']);

        // 2. Ambil data katalog produk riil dari PostgreSQL
        $daftarProduk = $this->menuModel->getAllMenu();

        // 3. Hitung statistik agregat
        $totalAktif = count(array_filter($daftarProduk, fn($p) => ($p['status_label'] ?? '') === 'Aktif'));

        $hargaList = array_map(fn($p) => (float) $p['harga'], $daftarProduk);
        $minHarga  = !empty($hargaList) ? min($hargaList) : 0;
        $maxHarga  = !empty($hargaList) ? max($hargaList) : 0;

        // Ambil flash messages
        $pesanSukses = get_flash('success');
        $pesanError  = get_flash('error');

        // 4. Muat tampilan View
        require_once BASE_PATH . '/views/admin/produk.php';
    }

    /**
     * Form penambahan produk baru
     */
    public function tambah(): void
    {
        require_role(['super_admin', 'owner', 'admin']);
        $this->form(null);
    }

    /**
     * Form pengubahan data produk
     *
     * @param int|null $id
     */
    public function edit($id = null): void
    {
        require_role(['super_admin', 'owner', 'admin']);
        $this->form($id);
    }

    /**
     * Menangani tampilan form tambah/edit produk sekaligus memproses penyimpanan POST
     *
     * @param int|null $id
     */
    public function form($id = null): void
    {
        // 1. Pengaman Hak Akses: Super Admin, Owner, dan Admin
        require_role(['super_admin', 'owner', 'admin']);

        $id = (int) ($id ?? $_GET['id'] ?? 0);
        $isEdit = ($id > 0);

        // Ambil daftar seluruh bahan baku untuk pilihan dropdown di form resep
        $stmtBahan = $this->pdo->query("SELECT id_bahan, nama_bahan, satuan FROM bahan_baku ORDER BY nama_bahan ASC");
        $daftarBahan = $stmtBahan->fetchAll(PDO::FETCH_ASSOC);

        $dataResepAktif = [];

        // Jika mode Edit, ambil data produk dan resep lama dari database
        if ($isEdit) {
            $dataProduk = $this->menuModel->getMenuById($id);
            if (!$dataProduk) {
                set_flash('error', 'Data produk tidak ditemukan di database!');
                header('Location: index.php?controller=menu&action=index');
                exit;
            }

            // Ambil data resep yang sudah tersimpan sebelumnya
            $stmtResep = $this->pdo->prepare("SELECT id_bahan, jumlah_pemakaian FROM resep WHERE id_menu = :id_menu");
            $stmtResep->execute([':id_menu' => $id]);
            $dataResepAktif = $stmtResep->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $dataProduk = [
                'id_menu'      => null,
                'nama_menu'    => '',
                'kategori'     => 'Minuman',
                'hpp'          => '',
                'harga'        => '',
                'status'       => 'Aktif',
                'status_label' => 'Aktif',
            ];
        }

        // 2. Tangani Aksi POST (Simpan Produk Baru / Perbarui Produk & Resepnya)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['csrf_token']) && function_exists('csrf_verify')) {
                csrf_verify($_POST['csrf_token']);
            }

            $nama     = trim($_POST['nama'] ?? '');
            $kategori = trim($_POST['kategori'] ?? 'Minuman');
            $hpp      = (float) ($_POST['hpp'] ?? 0);
            $harga    = (float) ($_POST['harga'] ?? 0);
            $status   = trim($_POST['status'] ?? 'Aktif');

            // Validasi Input
            if ($nama === '') {
                set_flash('error', 'Nama produk wajib diisi!');
                header('Location: index.php?controller=menu&action=' . ($isEdit ? "edit&id={$id}" : 'tambah'));
                exit;
            }

            if ($hpp < 0 || $harga < 0) {
                set_flash('error', 'HPP dan Harga jual tidak boleh bernilai negatif!');
                header('Location: index.php?controller=menu&action=' . ($isEdit ? "edit&id={$id}" : 'tambah'));
                exit;
            }

            $payload = [
                'id'       => $isEdit ? $id : null,
                'nama'     => $nama,
                'kategori' => $kategori,
                'hpp'      => $hpp,
                'harga'    => $harga,
                'status'   => $status,
                'stok'     => 0,
            ];

            // Simpan menu utama (mengembalikan ID menu yang baru/diubah)
            $idMenuTerakhir = $this->menuModel->simpanMenu($payload);

            if ($idMenuTerakhir) {
                $targetIdMenu = $isEdit ? $id : (int) $idMenuTerakhir;

                // ── Simpan / Perbarui Komposisi Resep (Berlaku untuk SEMUA Menu) ──
                if ($targetIdMenu > 0) {
                    // Hapus resep lama terlebih dahulu agar bersih dari duplikat/perubahan
                    $stmtDeleteResep = $this->pdo->prepare("DELETE FROM resep WHERE id_menu = :id_menu");
                    $stmtDeleteResep->execute([':id_menu' => $targetIdMenu]);

                    // Masukkan resep baru dari inputan form dinamis
                    if (isset($_POST['resep_bahan']) && is_array($_POST['resep_bahan'])) {
                        $stmtInsertResep = $this->pdo->prepare("
                            INSERT INTO resep (id_menu, id_bahan, jumlah_pemakaian) 
                            VALUES (:id_menu, :id_bahan, :jumlah_pemakaian)
                        ");

                        foreach ($_POST['resep_bahan'] as $index => $idBahan) {
                            $takaran = (float) ($_POST['resep_takaran'][$index] ?? 0);
                            if (!empty($idBahan) && $takaran > 0) {
                                $stmtInsertResep->execute([
                                    ':id_menu'          => $targetIdMenu,
                                    ':id_bahan'         => (int) $idBahan,
                                    ':jumlah_pemakaian' => $takaran
                                ]);
                            }
                        }
                    }
                }

                $pesan = $isEdit 
                    ? "Produk '{$nama}' beserta resepnya berhasil diperbarui!" 
                    : "Produk baru '{$nama}' dan komposisi resepnya berhasil ditambahkan!";
                set_flash('success', $pesan);
                header('Location: index.php?controller=menu&action=index');
                exit;
            } else {
                set_flash('error', 'Gagal menyimpan data produk ke database PostgreSQL.');
                header('Location: index.php?controller=menu&action=' . ($isEdit ? "edit&id={$id}" : 'tambah'));
                exit;
            }
        }

        // 3. Tampilkan Form Input (GET)
        $hppAwal   = is_numeric($dataProduk['hpp'] ?? 0) ? (float) $dataProduk['hpp'] : 0;
        $hargaAwal = is_numeric($dataProduk['harga'] ?? 0) ? (float) $dataProduk['harga'] : 0;
        $profitAwal = $hargaAwal - $hppAwal; // Perbaikan operator minus (-)
        $marginAwal = $hargaAwal > 0 ? ($profitAwal / $hargaAwal) * 100 : 0;

        $pesanError = get_flash('error');

        require_once BASE_PATH . '/views/admin/form-produk.php';
    }

    /**
     * Menghapus produk dari database
     *
     * @param int|null $id
     */
    public function hapus($id = null): void
    {
        // 1. Pengaman Hak Akses: Super Admin, Owner, dan Admin
        require_role(['super_admin', 'owner', 'admin']);

        $id = (int) ($id ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            set_flash('error', 'ID produk tidak valid!');
            header('Location: index.php?controller=menu&action=index');
            exit;
        }

        $item = $this->menuModel->getMenuById($id);
        $nama = $item['nama_menu'] ?? $item['nama'] ?? 'Produk';

        // Hapus relasi resep terlebih dahulu agar tidak kena foreign key constraint error
        $stmtDelResep = $this->pdo->prepare("DELETE FROM resep WHERE id_menu = :id_menu");
        $stmtDelResep->execute([':id_menu' => $id]);

        $berhasil = $this->menuModel->hapusMenu($id);

        if ($berhasil) {
            set_flash('success', "Produk '{$nama}' berhasil dihapus dari katalog.");
        } else {
            set_flash('error', "Gagal menghapus produk '{$nama}'.");
        }

        header('Location: index.php?controller=menu&action=index');
        exit;
    }
}