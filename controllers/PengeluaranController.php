<?php
// controllers/PengeluaranController.php
// Controller Pengeluaran Operasional Warkop KolegaOps

require_once BASE_PATH . '/models/PengeluaranModel.php';
require_once BASE_PATH . '/models/ShiftModel.php';

class PengeluaranController
{
    private PDO $pdo;
    private PengeluaranModel $pengeluaranModel;
    private ShiftModel $shiftModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pengeluaranModel = new PengeluaranModel($pdo);
        $this->shiftModel = new ShiftModel($pdo);
    }

    /**
     * Halaman default diarahkan ke dashboard kasir atau dashboard owner sesuai role.
     */
    public function index(): void
    {
        require_login();
        $role = normalize_role((string) current_user('role'));

        if ($role === 'kasir') {
            header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
        } else {
            header('Location: index.php?controller=dashboard&action=dashboard_owner');
        }
        exit;
    }

    /**
     * Memproses pencatatan pengeluaran operasional (Kasir).
     */
    public function tambah(): void
    {
        require_role(['kasir', 'pegawai']);

        // Alihkan ke DashboardController yang menangani pencatatan shift aktif
        header('Location: index.php?controller=dashboard&action=dashboard_pegawai');
        exit;
    }
}
