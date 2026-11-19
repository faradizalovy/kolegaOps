
<?php
// controllers/AuthController.php
// Controller Autentikasi Login dan Logout KolegaOps
// Role: super_admin, admin, kasir

require_once BASE_PATH . '/models/UserModel.php';

class AuthController
{
    private PDO $pdo;
    private UserModel $userModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new UserModel($pdo);
    }

    // -------------------------------------------------------------------------
    // 1. Menampilkan Halaman Login
    // -------------------------------------------------------------------------

    public function login(): void
    {
        if (is_logged_in()) {
            $this->redirectByRole(current_user('role'));
        }

        $error = get_flash('error') ?? '';

        require_once BASE_PATH . '/views/auth/login.php';
    }

    // -------------------------------------------------------------------------
    // 2. Memproses Login
    // -------------------------------------------------------------------------

    public function process(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        // Verifikasi CSRF
        if (function_exists('csrf_verify')) {
            csrf_verify($_POST['csrf_token'] ?? '');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Harap isi username dan password!';
            require_once BASE_PATH . '/views/auth/login.php';
            return;
        }

        try {
            $user = $this->userModel->findByUsername($username);

            // Akun tidak ditemukan
            if (!$user) {
                $error = 'Username atau password salah!';
                require_once BASE_PATH . '/views/auth/login.php';
                return;
            }

            // Periksa status akun
            $status = strtolower(trim((string) ($user['status'] ?? '')));

            if ($status !== 'aktif') {
                $error = 'Akun Anda sedang dinonaktifkan. Silakan hubungi Admin Warkop.';
                require_once BASE_PATH . '/views/auth/login.php';
                return;
            }

            // Verifikasi password
            if (!password_verify($password, (string) $user['password'])) {
                $error = 'Username atau password salah!';
                require_once BASE_PATH . '/views/auth/login.php';
                return;
            }

            // Pastikan role akun dikenali
            $role = normalize_role((string) ($user['role'] ?? ''));

            $allowedRoles = [
                'super_admin',
                'owner',
                'admin',
                'kasir'
            ];

            if (!in_array($role, $allowedRoles, true)) {
                $error = 'Role akun tidak dikenali. Silakan hubungi administrator sistem.';
                require_once BASE_PATH . '/views/auth/login.php';
                return;
            }

            // Simpan session login
            $user['role'] = $role;
            set_user_session($user);

            // Arahkan sesuai role
            $this->redirectByRole($role);

        } catch (PDOException $e) {
            error_log('[AuthController::process] ' . $e->getMessage());

            // Jangan tampilkan detail database kepada pengguna.
            $error = 'Terjadi kendala saat menghubungkan ke database. Silakan coba kembali.';
            require_once BASE_PATH . '/views/auth/login.php';
            return;
        }
    }

    // -------------------------------------------------------------------------
    // 3. Logout
    // -------------------------------------------------------------------------

    public function logout(): void
    {
        destroy_session();

        header('Location: index.php?controller=auth&action=login');
        exit;
    }

    // -------------------------------------------------------------------------
    // 4. Pengalihan Berdasarkan Role
    // -------------------------------------------------------------------------

    private function redirectByRole(?string $role): void
    {
        $roleClean = normalize_role($role);

        if (in_array(
            $roleClean,
            ['super_admin', 'owner', 'admin'],
            true
        )) {
            header(
                'Location: index.php?controller=dashboard&action=dashboard_admin'
            );
            exit;
        }

        if ($roleClean === 'kasir') {
            header(
                'Location: index.php?controller=dashboard&action=dashboard_pegawai'
            );
            exit;
        }

        // Role tidak dikenal
        destroy_session();

        header('Location: index.php?controller=auth&action=login');
        exit;
    }
}
