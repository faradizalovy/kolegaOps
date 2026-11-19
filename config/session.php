
<?php
// config/session.php
// Manajemen Sesi Login & Kontrol Akses KolegaOps
// Role: super_admin, admin, kasir

// -----------------------------------------------------------------------------
// 1. Inisialisasi Sesi Secara Aman
// -----------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {

    $savePath = session_save_path();

    if (empty($savePath) || !is_writable($savePath)) {
        $sysTemp = sys_get_temp_dir();

        if (is_writable($sysTemp)) {
            session_save_path($sysTemp);
        }
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );

    @session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => $isHttps,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => 1,
    ]);
}

// -----------------------------------------------------------------------------
// 2. Normalisasi & Definisi Role
// -----------------------------------------------------------------------------
if (!defined('ROLE_SUPER_ADMIN')) {
    define('ROLE_SUPER_ADMIN', 'super_admin');
    define('ROLE_OWNER',       'owner');
    define('ROLE_ADMIN',       'admin');
    define('ROLE_KASIR',       'kasir');
}

// Kelompok Hak Akses Standar KolegaOps:
// 1. Otoritas penuh Owner / Super Admin (Termasuk Kelola Akun & Analisis Profit)
if (!defined('ROLES_FULL_OWNER')) {
    define('ROLES_FULL_OWNER', ['super_admin', 'owner']);
}

// 2. Akses Operasional & Tampilan Owner-like (Dashboard, Produk, Stok Bahan, Laporan)
if (!defined('ROLES_OPERASIONAL')) {
    define('ROLES_OPERASIONAL', ['super_admin', 'owner', 'admin']);
}

// 3. Akses Kasir / Staf Operasional
if (!defined('ROLES_KASIR')) {
    define('ROLES_KASIR', ['kasir', 'pegawai']);
}

// 4. Seluruh Role Terdaftar
if (!defined('ROLES_ALL')) {
    define('ROLES_ALL', ['super_admin', 'owner', 'admin', 'kasir', 'pegawai']);
}

if (!function_exists('normalize_role')) {

    /**
     * Menyeragamkan nama role agar konsisten.
     *
     * Role resmi: super_admin, owner, admin, kasir.
     * pegawai lama dinormalisasi menjadi kasir.
     * pemilik lama dinormalisasi menjadi owner.
     */
    function normalize_role(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        if ($role === 'pegawai') {
            return 'kasir';
        }

        if ($role === 'pemilik') {
            return 'owner';
        }

        return $role;
    }
}

// -----------------------------------------------------------------------------
// 3. Fungsi Login: set_user_session
// -----------------------------------------------------------------------------
if (!function_exists('set_user_session')) {

    /**
     * Menyimpan data pengguna ke session setelah login berhasil.
     */
    function set_user_session(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $role = normalize_role($user['role'] ?? '');

        $_SESSION['user'] = [
            'id_user'  => (int) ($user['id_user'] ?? $user['id'] ?? 0),
            'username' => trim((string) ($user['username'] ?? '')),
            'role'     => $role,
        ];

        // Shortcut session
        $_SESSION['id_user']  = $_SESSION['user']['id_user'];
        $_SESSION['username'] = $_SESSION['user']['username'];
        $_SESSION['role']     = $role;
    }
}

// -----------------------------------------------------------------------------
// 4. Fungsi Cek Login: is_logged_in
// -----------------------------------------------------------------------------
if (!function_exists('is_logged_in')) {

    function is_logged_in(): bool
    {
        return (
            !empty($_SESSION['user']['id_user'])
            || !empty($_SESSION['id_user'])
        );
    }
}

// -----------------------------------------------------------------------------
// 5. Mengambil Data Pengguna: current_user
// -----------------------------------------------------------------------------
if (!function_exists('current_user')) {

    /**
     * Mengambil informasi pengguna yang sedang login.
     *
     * Contoh:
     * current_user()
     * current_user('username')
     * current_user('role')
     */
    function current_user(?string $key = null)
    {
        if (!is_logged_in()) {
            return null;
        }

        $user = $_SESSION['user'] ?? [
            'id_user'  => $_SESSION['id_user'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'role'     => $_SESSION['role'] ?? '',
        ];

        // Normalisasi role session lama
        $user['role'] = normalize_role($user['role'] ?? '');

        if ($key !== null) {
            return $user[$key] ?? null;
        }

        return $user;
    }
}

// -----------------------------------------------------------------------------
// 6. Fungsi Cek Login: require_login
// -----------------------------------------------------------------------------
if (!function_exists('require_login')) {

    function require_login(
        string $loginUrl = 'index.php?controller=auth&action=login'
    ): void {

        if (!is_logged_in()) {
            header("Location: {$loginUrl}");
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 7. Pemeriksaan Hak Akses: require_role & requireRole
// -----------------------------------------------------------------------------
if (!function_exists('get_dashboard_url_by_role')) {

    /**
     * Mendapatkan URL dashboard yang tepat berdasarkan role pengguna.
     */
    function get_dashboard_url_by_role(?string $role = null): string
    {
        $role = normalize_role($role ?? (string) current_user('role'));

        if ($role === 'kasir') {
            return 'index.php?controller=dashboard&action=dashboard_pegawai';
        }

        if (in_array($role, ['super_admin', 'owner', 'admin'], true)) {
            return 'index.php?controller=dashboard&action=dashboard_owner';
        }

        return 'index.php?controller=auth&action=login';
    }
}

if (!function_exists('render_403_page')) {

    /**
     * Menampilkan halaman 403 Forbidden yang aman dan ramah pengguna.
     */
    function render_403_page(string $pesan = 'Anda tidak memiliki hak akses untuk halaman ini.'): void
    {
        http_response_code(403);
        $userRole = normalize_role((string) current_user('role'));
        $dashboardUrl = get_dashboard_url_by_role($userRole);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 - Akses Ditolak | KolegaOps</title>
            <style>
                * { box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background-color: #f1f5f9;
                    color: #334155;
                    margin: 0;
                    padding: 1.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .error-card {
                    background: #ffffff;
                    max-width: 480px;
                    width: 100%;
                    padding: 2.5rem;
                    border-radius: 14px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
                    text-align: center;
                    border: 1px solid #e2e8f0;
                }
                .badge-code {
                    display: inline-block;
                    padding: 0.35rem 0.85rem;
                    background-color: #fee2e2;
                    color: #dc2626;
                    font-weight: 700;
                    font-size: 0.875rem;
                    border-radius: 9999px;
                    margin-bottom: 1rem;
                }
                h1 {
                    font-size: 1.65rem;
                    font-weight: 700;
                    color: #0f172a;
                    margin: 0 0 0.75rem;
                }
                p {
                    font-size: 0.95rem;
                    color: #64748b;
                    line-height: 1.6;
                    margin: 0 0 1.5rem;
                }
                .role-info {
                    font-size: 0.85rem;
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    padding: 0.5rem 0.75rem;
                    border-radius: 8px;
                    margin-bottom: 1.5rem;
                    color: #475569;
                }
                .btn-home {
                    display: inline-block;
                    background-color: #0f172a;
                    color: #ffffff;
                    text-decoration: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 8px;
                    font-size: 0.9rem;
                    font-weight: 500;
                    transition: background-color 0.2s ease;
                }
                .btn-home:hover {
                    background-color: #1e293b;
                }
                .footer-note {
                    margin-top: 1.5rem;
                    font-size: 0.8rem;
                    color: #94a3b8;
                }
            </style>
        </head>
        <body>
            <div class="error-card">
                <span class="badge-code">Error 403</span>
                <h1>Akses Dibatasi</h1>
                <p><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($userRole !== ''): ?>
                    <div class="role-info">
                        Role Anda saat ini: <strong><?= htmlspecialchars(strtoupper($userRole), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-home">Kembali ke Dashboard</a>
                <div class="footer-note">KolegaOps POS Warkop &bull; Otorisasi & Hak Akses</div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

if (!function_exists('require_role')) {

    /**
     * Membatasi akses berdasarkan role pengguna.
     *
     * Aturan Hak Akses:
     * - SUPER ADMIN: Otoritas penuh, setara owner (semua fitur operasional, kelola akun, dan analisis profit).
     * - OWNER: Otoritas penuh operasional dan manajemen (dashboard, produk, stok, laporan, kelola akun, dan analisis profit).
     * - ADMIN: Akses operasional mirip owner (dashboard, produk, stok, laporan), TETAPI TIDAK memiliki akses ke kelola akun dan analisis profit.
     * - KASIR: Akses operasional staf (dashboard kasir, pos transaksi, stok kasir, laporan shift, tagihan).
     *
     * Contoh:
     * require_role(['super_admin', 'owner']);
     * require_role(['super_admin', 'owner', 'admin']);
     * require_role(['kasir', 'pegawai']);
     */
    function require_role($allowed_roles, ?string $redirectUrl = null): void
    {
        require_login();

        $allowed = is_array($allowed_roles)
            ? $allowed_roles
            : [$allowed_roles];

        $allowed = array_map(
            function ($role) {
                return normalize_role((string) $role);
            },
            $allowed
        );

        $userRole = normalize_role(
            (string) current_user('role')
        );

        // Super Admin memiliki otoritas penuh ke seluruh fitur admin/owner
        if (
            $userRole === 'super_admin'
            && (
                in_array('super_admin', $allowed, true)
                || in_array('owner', $allowed, true)
                || in_array('admin', $allowed, true)
            )
        ) {
            return;
        }

        // Jika role user terdaftar di daftar role yang diizinkan
        if (in_array($userRole, $allowed, true)) {
            return;
        }

        // Akses ditolak
        if ($redirectUrl !== null) {
            set_flash('error', 'Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman tersebut.');
            header("Location: {$redirectUrl}");
            exit;
        }

        render_403_page('Anda tidak memiliki izin (hak akses) untuk membuka fitur atau halaman ini.');
    }
}

if (!function_exists('requireRole')) {
    /**
     * Alias camelCase untuk require_role.
     */
    function requireRole($allowed_roles, ?string $redirectUrl = null): void
    {
        require_role($allowed_roles, $redirectUrl);
    }
}

// -----------------------------------------------------------------------------
// 8. Helper Pengecekan Role & Otorisasi
// -----------------------------------------------------------------------------
if (!function_exists('is_super_admin')) {

    function is_super_admin(): bool
    {
        return normalize_role((string) current_user('role')) === 'super_admin';
    }
}

if (!function_exists('is_owner')) {

    function is_owner(): bool
    {
        return normalize_role((string) current_user('role')) === 'owner';
    }
}

if (!function_exists('is_admin')) {

    function is_admin(): bool
    {
        return normalize_role((string) current_user('role')) === 'admin';
    }
}

if (!function_exists('is_kasir')) {

    function is_kasir(): bool
    {
        return normalize_role((string) current_user('role')) === 'kasir';
    }
}

if (!function_exists('is_owner_or_super_admin')) {

    /**
     * Memeriksa apakah pengguna memiliki hak akses penuh (Super Admin atau Owner).
     * Digunakan untuk fitur sensitif seperti Kelola Akun dan Analisis Profit.
     */
    function is_owner_or_super_admin(): bool
    {
        return in_array(
            normalize_role((string) current_user('role')),
            ['super_admin', 'owner'],
            true
        );
    }
}

if (!function_exists('is_operational_user')) {

    /**
     * Memeriksa apakah pengguna memiliki hak akses operasional manajemen warkop.
     */
    function is_operational_user(): bool
    {
        return in_array(
            normalize_role((string) current_user('role')),
            ['super_admin', 'owner', 'admin'],
            true
        );
    }
}

if (!function_exists('has_role')) {

    /**
     * Memeriksa apakah user yang sedang login memiliki role tertentu.
     */
    function has_role($roles): bool
    {
        if (!is_logged_in()) {
            return false;
        }

        $allowed = is_array($roles) ? $roles : [$roles];
        $allowed = array_map('normalize_role', $allowed);

        $userRole = normalize_role((string) current_user('role'));

        if ($userRole === 'super_admin' && (in_array('owner', $allowed, true) || in_array('admin', $allowed, true))) {
            return true;
        }

        return in_array($userRole, $allowed, true);
    }
}

if (!function_exists('hasRole')) {
    function hasRole($roles): bool
    {
        return has_role($roles);
    }
}

if (!function_exists('has_any_role')) {

    /**
     * Memeriksa apakah user memiliki salah satu dari role yang diberikan.
     */
    function has_any_role(array $roles): bool
    {
        return has_role($roles);
    }
}

if (!function_exists('hasAnyRole')) {
    function hasAnyRole(array $roles): bool
    {
        return has_role($roles);
    }
}

// -----------------------------------------------------------------------------
// 9. Fungsi Logout: destroy_session
// -----------------------------------------------------------------------------
if (!function_exists('destroy_session')) {

    function destroy_session(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

// -----------------------------------------------------------------------------
// 10. Flash Message
// -----------------------------------------------------------------------------
if (!function_exists('set_flash')) {

    function set_flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('get_flash')) {

    function get_flash(string $key): ?string
    {
        if (isset($_SESSION['flash'][$key])) {

            $message = $_SESSION['flash'][$key];

            unset($_SESSION['flash'][$key]);

            return $message;
        }

        return null;
    }
}
