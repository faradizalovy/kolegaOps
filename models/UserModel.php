
<?php
// models/UserModel.php
// Model Pengguna untuk KolegaOps POS Warkop
// Role: super_admin, admin, kasir

class UserModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // -------------------------------------------------------------------------
    // 1. Normalisasi Role
    // -------------------------------------------------------------------------

    private function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));

        return $role;
    }

    private function isValidRole(string $role): bool
    {
        return in_array(
            $this->normalizeRole($role),
            ['super_admin', 'admin', 'owner', 'kasir'],
            true
        );
    }

    private function isValidStatus(string $status): bool
    {
        return in_array(
            strtolower(trim($status)),
            ['aktif', 'nonaktif'],
            true
        );
    }

    // -------------------------------------------------------------------------
    // 2. Mengambil Data Pengguna untuk Login
    // -------------------------------------------------------------------------

    public function findByUsername(string $username): ?array
    {
        $sql = "
            SELECT
                id_user,
                username,
                password,
                role,
                status,
                create_at,
                update_at
            FROM users
            WHERE LOWER(username) = LOWER(:username)
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':username' => trim($username)
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    // -------------------------------------------------------------------------
    // 3. Mengambil Pengguna Berdasarkan ID
    // -------------------------------------------------------------------------

    public function findById(int $id_user): ?array
    {
        $sql = "
            SELECT
                id_user,
                username,
                role,
                status,
                create_at,
                update_at
            FROM users
            WHERE id_user = :id_user
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function getUserById(int $id): ?array
    {
        return $this->findById($id);
    }

    // -------------------------------------------------------------------------
    // 4. Mengambil Seluruh Pengguna
    // -------------------------------------------------------------------------

    public function getAll(): array
    {
        $sql = "
            SELECT
                id_user,
                username,
                role,
                status,
                create_at,
                update_at
            FROM users
            ORDER BY id_user ASC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // 5. Mengambil Daftar Akun untuk Halaman Kelola Akun
    // -------------------------------------------------------------------------

    /**
     * Mengambil daftar akun untuk halaman Kelola Akun.
     * Hanya Super Admin dan Owner yang berhak mengakses fitur ini.
     *
     * Super Admin:
     * - Melihat akun Super Admin, Owner, Admin, dan Kasir.
     *
     * Owner:
     * - Melihat akun Super Admin, Owner, Admin, dan Kasir.
     */
    public function getAllUsers(?string $viewerRole = null): array
    {
        $viewerRole = $this->normalizeRole($viewerRole ?? '');

        if (!in_array($viewerRole, ['super_admin', 'owner'], true)) {
            return [];
        }

        $where = "
            WHERE LOWER(u.role) IN (
                'super_admin',
                'owner',
                'admin',
                'kasir',
                'pegawai'
            )
        ";

        $sql = "
            SELECT
                u.id_user AS id,
                u.username,
                INITCAP(u.username) AS nama,

                CASE
                    WHEN LOWER(u.role) = 'super_admin'
                        THEN 'Super Admin'
                    WHEN LOWER(u.role) = 'owner'
                        THEN 'Owner'
                    WHEN LOWER(u.role) = 'admin'
                        THEN 'Admin'
                    WHEN LOWER(u.role) IN ('kasir', 'pegawai')
                        THEN 'Kasir'
                    ELSE 'Tidak Diketahui'
                END AS role,

                LOWER(u.role) AS raw_role,

                CASE
                    WHEN LOWER(u.status) = 'aktif'
                        THEN 'Aktif'
                    ELSE 'Nonaktif'
                END AS status,

                LOWER(u.status) AS raw_status,
                u.create_at,
                u.update_at,

                CASE
                    WHEN LOWER(u.role) IN ('super_admin', 'owner', 'admin')
                        THEN '—'
                    WHEN s.id_shift IS NOT NULL AND s.status = 'buka'
                        THEN s.nama_shift || ' · Aktif'
                    WHEN s.id_shift IS NOT NULL AND s.status = 'tutup'
                        THEN s.nama_shift || ' · Ditutup'
                    ELSE '—'
                END AS shift

            FROM users u

            LEFT JOIN shift s
                ON u.id_user = s.id_user
                AND s.tanggal = CURRENT_DATE

            {$where}

            ORDER BY
                CASE
                    WHEN LOWER(u.role) = 'super_admin' THEN 0
                    WHEN LOWER(u.role) = 'owner' THEN 1
                    WHEN LOWER(u.role) = 'admin' THEN 2
                    ELSE 3
                END,
                u.id_user ASC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // 6. Memeriksa Hak Akses untuk Membuat Akun
    // -------------------------------------------------------------------------

    private function canManageRole(string $actorRole, string $targetRole): bool
    {
        $actorRole = $this->normalizeRole($actorRole);
        $targetRole = $this->normalizeRole($targetRole);

        if ($actorRole === 'super_admin') {
            return in_array($targetRole, ['owner', 'admin', 'kasir'], true);
        }

        if ($actorRole === 'owner') {
            return in_array($targetRole, ['admin', 'kasir'], true);
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // 7. Menyimpan Akun Baru
    // -------------------------------------------------------------------------

    public function simpanUser(array $data, string $actorRole)
    {
        $actorRole = $this->normalizeRole($actorRole);

        $username = trim($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $role = $this->normalizeRole($data['role'] ?? 'kasir');
        $status = strtolower(trim($data['status'] ?? 'aktif'));

        if ($username === '' || $password === '') {
            return false;
        }

        if (strlen($username) > 50 || strlen($password) < 4) {
            return false;
        }

        if (!$this->isValidRole($role) || !$this->isValidStatus($status)) {
            return false;
        }

        if (!$this->canManageRole($actorRole, $role)) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            return false;
        }

        try {
            $sql = "
                INSERT INTO users (
                    username,
                    password,
                    role,
                    status
                )
                VALUES (
                    :username,
                    :password,
                    :role,
                    :status
                )
                RETURNING id_user
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword,
                ':role' => $role,
                ':status' => $status,
            ]);

            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log('[UserModel::simpanUser] ' . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // 8. Memeriksa Target Akun yang Akan Diubah
    // -------------------------------------------------------------------------

    private function canManageTarget(
        int $targetId,
        string $actorRole,
        string $newRole
    ): bool {

        $actorRole = $this->normalizeRole($actorRole);
        $newRole = $this->normalizeRole($newRole);

        $target = $this->findById($targetId);

        if (!$target) {
            return false;
        }

        $targetRole = $this->normalizeRole($target['role'] ?? '');

        // Semua akun Super Admin dilindungi dan tidak dapat diubah lewat form ini
        if ($targetRole === 'super_admin') {
            return false;
        }

        if ($actorRole === 'super_admin') {
            return in_array($targetRole, ['owner', 'admin', 'kasir'], true)
                && in_array($newRole, ['owner', 'admin', 'kasir'], true);
        }

        if ($actorRole === 'owner') {
            return in_array($targetRole, ['admin', 'kasir'], true)
                && in_array($newRole, ['admin', 'kasir'], true);
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // 9. Memperbarui Akun Pengguna
    // -------------------------------------------------------------------------

    public function updateUser(
        int $id,
        array $data,
        string $actorRole
    ): bool {

        if ($id <= 0) {
            return false;
        }

        $username = trim($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $role = $this->normalizeRole($data['role'] ?? 'kasir');
        $status = strtolower(trim($data['status'] ?? 'aktif'));

        if ($username === '' || strlen($username) > 50) {
            return false;
        }

        if ($password !== '' && strlen($password) < 4) {
            return false;
        }

        if (!$this->isValidRole($role) || !$this->isValidStatus($status)) {
            return false;
        }

        if (!$this->canManageTarget($id, $actorRole, $role)) {
            return false;
        }

        try {
            $params = [
                ':username' => $username,
                ':role' => $role,
                ':status' => $status,
                ':id' => $id,
            ];

            if ($password !== '') {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($hashedPassword === false) {
                    return false;
                }

                $sql = "
                    UPDATE users
                    SET
                        username = :username,
                        password = :password,
                        role = :role,
                        status = :status,
                        update_at = CURRENT_TIMESTAMP
                    WHERE id_user = :id
                      AND LOWER(role) <> 'super_admin'
                ";

                $params[':password'] = $hashedPassword;

            } else {
                $sql = "
                    UPDATE users
                    SET
                        username = :username,
                        role = :role,
                        status = :status,
                        update_at = CURRENT_TIMESTAMP
                    WHERE id_user = :id
                      AND LOWER(role) <> 'super_admin'
                ";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log('[UserModel::updateUser] ' . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // 10. Menghapus Akun Pengguna
    // -------------------------------------------------------------------------

    public function hapusUser(int $id, string $actorRole): bool
    {
        if ($id <= 0) {
            return false;
        }

        $actorRole = $this->normalizeRole($actorRole);
        $target = $this->findById($id);

        if (!$target) {
            return false;
        }

        $targetRole = $this->normalizeRole($target['role'] ?? '');

        // Semua akun Super Admin tidak boleh dihapus.
        if ($targetRole === 'super_admin') {
            return false;
        }

        if (!in_array($actorRole, ['super_admin', 'owner'], true)) {
            return false;
        }

        if ($actorRole === 'owner' && !in_array($targetRole, ['admin', 'kasir'], true)) {
            return false;
        }

        if (
            $actorRole === 'super_admin'
            && !in_array($targetRole, ['owner', 'admin', 'kasir'], true)
        ) {
            return false;
        }

        try {
            $sql = "
                DELETE FROM users
                WHERE id_user = :id
                  AND LOWER(role) <> 'super_admin'
            ";

            if ($actorRole === 'owner') {
                $sql .= "
                    AND LOWER(role) IN ('admin', 'kasir', 'pegawai')
                ";
            }

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':id' => $id
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log('[UserModel::hapusUser] ' . $e->getMessage());
            return false;
        }
    }
}
