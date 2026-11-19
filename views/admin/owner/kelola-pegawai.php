
<?php
// views/owner/kelola-pegawai.php
// Tampilan Kelola Akun Admin, Super Admin, dan Pegawai - KolegaOps POS

$isSuperAdmin = $isSuperAdmin ?? (current_user('role') === 'super_admin');
$isOwner      = $isOwner ?? (current_user('role') === 'owner');

$daftarPegawai = $daftarPegawai ?? [];

$roleLabelHalaman = $isSuperAdmin
    ? 'Super Admin'
    : 'Owner · Pemilik Warkop';

// Hitung statistik berdasarkan akun yang memang boleh dilihat.
$totalAkun = count($daftarPegawai);
$totalAktif = 0;
$totalNonaktif = 0;
$totalSuperAdmin = 0;
$totalOwner = 0;
$totalAdmin = 0;
$totalKasir = 0;

$namaAktif = [];

foreach ($daftarPegawai as $akun) {
    $rawRole = strtolower(trim((string) ($akun['raw_role'] ?? '')));
    $status = strtolower(trim((string) ($akun['raw_status'] ?? $akun['status'] ?? '')));

    if ($status === 'aktif') {
        $totalAktif++;
        $namaAktif[] = $akun['nama'] ?? $akun['username'] ?? '-';
    } else {
        $totalNonaktif++;
    }

    if ($rawRole === 'super_admin') {
        $totalSuperAdmin++;
    } elseif ($rawRole === 'owner') {
        $totalOwner++;
    } elseif ($rawRole === 'admin') {
        $totalAdmin++;
    } elseif (in_array($rawRole, ['kasir', 'pegawai'], true)) {
        $totalKasir++;
    }
}

$namaAktifTeks = !empty($namaAktif)
    ? implode(', ', $namaAktif)
    : '-';

$jumlahAkunRole = $totalSuperAdmin . ' Super Admin · '
    . $totalOwner . ' Owner · '
    . $totalAdmin . ' Admin · '
    . $totalKasir . ' Kasir';

if (!function_exists('escapeKelolaPegawai')) {
    function escapeKelolaPegawai($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun - KolegaOps Warkop Kolega</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#122B70',
                            darkNavy: '#0A1B46',
                            lightNavy: '#1D3B8A',
                            skyBlue: '#38BDF8',
                            bgLight: '#F8FAFC'
                        }
                    }
                }
            }
        };
    </script>
</head>

<body class="bg-brand-bgLight text-slate-800 min-h-screen flex text-base relative">

    <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto min-h-screen">

        <!-- HEADER -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-slate-200 mb-6 bg-white px-8 py-6 rounded-2xl shadow-sm">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900">
                    Kelola Akun
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Pengelolaan akun dan hak akses Warkop Kolega
                    &bull; <?= escapeKelolaPegawai($roleLabelHalaman) ?>
                </p>
            </div>

            <div>
                <button
                    type="button"
                    onclick="bukaFormPegawai('tambah')"
                    class="bg-brand-navy hover:bg-brand-lightNavy text-white px-6 py-3.5 rounded-xl font-bold text-sm shadow transition cursor-pointer"
                >
                    + Tambah Akun
                </button>
            </div>
        </header>

        <!-- NOTIFIKASI -->
        <?php if (!empty($pesanSukses)): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-sm flex items-center gap-3">
                <span>✅</span>
                <span><?= escapeKelolaPegawai($pesanSukses) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesanError)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-sm flex items-center gap-3">
                <span>⚠️</span>
                <span><?= escapeKelolaPegawai($pesanError) ?></span>
            </div>
        <?php endif; ?>

        <!-- STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

            <div class="bg-brand-navy text-white p-6 rounded-2xl shadow-sm flex flex-col justify-between">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-wider">
                    TOTAL AKUN
                </p>

                <div class="my-2">
                    <h3 class="text-4xl font-extrabold">
                        <?= $totalAkun ?>
                        <span class="text-lg font-normal">Akun</span>
                    </h3>
                </div>

                <p class="text-xs text-blue-200">
                    <?= escapeKelolaPegawai($jumlahAkunRole) ?>
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    AKUN AKTIF
                </p>

                <div class="my-2">
                    <h3 class="text-3xl font-extrabold text-emerald-600">
                        <?= $totalAktif ?> Akun
                    </h3>
                </div>

                <p
                    class="text-xs text-slate-500 truncate"
                    title="<?= escapeKelolaPegawai($namaAktifTeks) ?>"
                >
                    <?= escapeKelolaPegawai($namaAktifTeks) ?>
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    AKUN NONAKTIF
                </p>

                <div class="my-2">
                    <h3 class="text-3xl font-extrabold text-slate-400">
                        <?= $totalNonaktif ?> Akun
                    </h3>
                </div>

                <p class="text-xs text-slate-500">
                    Tidak dapat mengakses aplikasi
                </p>
            </div>

        </div>

        <!-- FILTER -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6">

            <div class="md:col-span-6">
                <input
                    type="text"
                    id="filterSearch"
                    onkeyup="filterTabelPegawai()"
                    placeholder="Cari nama atau username..."
                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm"
                >
            </div>

            <div class="md:col-span-3">
                <select
                    id="filterRole"
                    onchange="filterTabelPegawai()"
                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700 font-medium cursor-pointer"
                >
                    <option value="">Semua role</option>
                    <option value="Super Admin">Super Admin</option>
                    <option value="Owner">Owner</option>
                    <option value="Admin">Admin</option>
                    <option value="Kasir">Kasir</option>
                </select>
            </div>

            <div class="md:col-span-3">
                <select
                    id="filterStatus"
                    onchange="filterTabelPegawai()"
                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700 font-medium cursor-pointer"
                >
                    <option value="">Semua status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>

        </div>

        <!-- TABEL AKUN -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">

            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 bg-slate-50/50">
                <h3 class="font-bold text-slate-900 text-base">
                    Daftar Akun
                </h3>

                <span class="text-xs text-slate-400 font-semibold">
                    Pengelolaan oleh <?= escapeKelolaPegawai($roleLabelHalaman) ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="tabelPegawai">

                    <thead class="bg-brand-darkNavy text-white">
                        <tr>
                            <th class="p-4 pl-6">NAMA</th>
                            <th class="p-4">USERNAME</th>
                            <th class="p-4">ROLE</th>
                            <th class="p-4">STATUS AKUN</th>
                            <th class="p-4">SHIFT HARI INI</th>
                            <th class="p-4 pr-6 text-right">AKSI</th>
                        </tr>
                    </thead>

                    <tbody class="font-medium text-slate-800 divide-y divide-slate-100">

                        <?php if (empty($daftarPegawai)): ?>

                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 italic">
                                    Belum ada akun yang dapat ditampilkan.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($daftarPegawai as $index => $peg): ?>

                                <?php
                                    $bgRow = ($index % 2 === 1)
                                        ? 'bg-blue-100/70'
                                        : 'bg-white';

                                    $idPegawai = (int) ($peg['id'] ?? $peg['id_user'] ?? 0);

                                    $idPenggunaLogin = (int) (
                                        current_user('id_user')
                                        ?? current_user('id')
                                        ?? 0
                                    );

                                    $isCurrentUser = (
                                        $idPegawai > 0
                                        && $idPegawai === $idPenggunaLogin
                                    );

                                    $rolePegawai = (string) ($peg['role'] ?? 'Pegawai');
                                    $rawRole = strtolower(trim((string) ($peg['raw_role'] ?? '')));
                                    $statusPegawai = (string) ($peg['status'] ?? 'Nonaktif');

                                    $isProtectedSuperAdmin = ($rawRole === 'super_admin');
                                    $isTargetOwner = ($rawRole === 'owner');
                                    $isTargetAdmin = ($rawRole === 'admin');
                                    $isTargetKasir = in_array($rawRole, ['kasir', 'pegawai'], true);

                                    // Akun Super Admin tidak boleh dikelola siapa pun.
                                    // Super Admin dapat mengelola Owner, Admin, dan Kasir.
                                    // Owner dapat mengelola Admin dan Kasir.
                                    $bolehKelola = !$isProtectedSuperAdmin
                                        && (
                                            $isSuperAdmin
                                            || ($isOwner && in_array($rawRole, ['admin', 'kasir', 'pegawai'], true))
                                        );
                                ?>

                                <tr
                                    class="pegawai-row <?= $bgRow ?> hover:bg-blue-50/60 transition"
                                    data-nama="<?= escapeKelolaPegawai(strtolower((string) ($peg['nama'] ?? ''))) ?>"
                                    data-username="<?= escapeKelolaPegawai(strtolower((string) ($peg['username'] ?? ''))) ?>"
                                    data-role="<?= escapeKelolaPegawai($rolePegawai) ?>"
                                    data-status="<?= escapeKelolaPegawai($statusPegawai) ?>"
                                >

                                    <td class="p-4 pl-6 font-bold text-brand-navy">
                                        <?= escapeKelolaPegawai($peg['nama'] ?? $peg['username'] ?? '-') ?>

                                        <?php if ($isCurrentUser): ?>
                                            <span class="ml-2 text-[10px] bg-blue-100 text-brand-navy px-2 py-0.5 rounded-full font-bold">
                                                Anda
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-slate-600 font-mono">
                                        <?= escapeKelolaPegawai($peg['username'] ?? '-') ?>
                                    </td>

                                    <td class="p-4 text-slate-700 font-semibold">
                                        <?php if ($isProtectedSuperAdmin): ?>
                                            <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-800 px-3 py-1 rounded-lg text-xs font-bold">
                                                Super Admin
                                            </span>
                                        <?php elseif ($isTargetOwner): ?>
                                            <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 px-3 py-1 rounded-lg text-xs font-bold">
                                                Owner
                                            </span>
                                        <?php elseif ($isTargetAdmin): ?>
                                            <span class="inline-flex items-center gap-1 bg-blue-100 text-brand-navy px-3 py-1 rounded-lg text-xs font-bold">
                                                Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 px-3 py-1 rounded-lg text-xs font-semibold">
                                                Kasir
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4">
                                        <span class="<?= strtolower($statusPegawai) === 'aktif'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-200 text-slate-600' ?> px-3 py-1 rounded-lg font-bold text-xs inline-block"
                                        >
                                            <?= escapeKelolaPegawai($statusPegawai) ?>
                                        </span>
                                    </td>

                                    <td class="p-4 text-slate-600">
                                        <?= escapeKelolaPegawai($peg['shift'] ?? '-') ?>
                                    </td>

                                    <td class="p-4 pr-6 text-right whitespace-nowrap">

                                        <?php if ($isProtectedSuperAdmin): ?>

                                            <span class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-3 py-2 rounded-lg">
                                                Terlindungi
                                            </span>

                                        <?php elseif ($bolehKelola): ?>

                                            <button
                                                type="button"
                                                onclick='bukaFormPegawai("edit", <?= json_encode(
                                                    $peg,
                                                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                                ) ?>)'
                                                class="text-brand-navy font-bold hover:underline bg-transparent border-none cursor-pointer inline-flex items-center gap-1"
                                            >
                                                <span>Edit</span>
                                                <span>&rarr;</span>
                                            </button>

                                            <?php if (!$isCurrentUser): ?>
                                                <a
                                                    href="index.php?controller=pegawai&action=hapus&id=<?= $idPegawai ?>"
                                                    class="text-red-600 font-bold hover:text-red-800 hover:underline inline-flex items-center ml-4"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');"
                                                >
                                                    Hapus
                                                </a>
                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="text-xs text-slate-400 italic">
                                                Tidak dapat dikelola
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center text-sm text-slate-500 font-medium">
                <span id="infoJumlah">
                    Menampilkan <?= count($daftarPegawai) ?> dari <?= count($daftarPegawai) ?> akun
                </span>

                <span class="font-bold text-brand-navy">
                    Halaman 1 dari 1
                </span>
            </div>

        </div>

        <!-- INFORMASI HAK AKSES -->
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-2xl text-xs text-blue-900 font-medium">

            <p class="font-bold mb-2">
                💡 Petunjuk Hak Akses
            </p>

            <?php if ($isSuperAdmin): ?>

                <p>
                    Super Admin memiliki otoritas penuh melihat seluruh akun serta mengelola
                    akun Owner, Admin, dan Kasir. Seluruh akun Super Admin
                    bersifat terlindungi dan tidak dapat diedit, dinonaktifkan,
                    atau dihapus melalui halaman ini.
                </p>

            <?php else: ?>

                <p>
                    Owner memiliki wewenang untuk mengelola akun Admin dan Kasir.
                    Seluruh akun Super Admin bersifat terlindungi dan tidak dapat diedit
                    atau dihapus melalui form ini.
                </p>

            <?php endif; ?>

            <p class="mt-2">
                Menonaktifkan atau menghapus akun tidak boleh menghapus
                riwayat transaksi maupun laporan shift yang pernah dicatat.
            </p>

        </div>

    </main>

    <!-- MODAL TAMBAH DAN EDIT AKUN -->
    <div
        id="modalPegawai"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">

            <div class="bg-brand-darkNavy text-white px-6 py-4 flex justify-between items-center">
                <h3 id="modalTitle" class="font-extrabold text-base">
                    Tambah Akun Baru
                </h3>

                <button
                    type="button"
                    onclick="tutupFormPegawai()"
                    class="text-slate-300 hover:text-white font-bold text-2xl leading-none cursor-pointer"
                >
                    &times;
                </button>
            </div>

            <form
                method="POST"
                action="index.php?controller=pegawai&action=simpan"
                class="p-6 space-y-4"
            >

                <?php if (function_exists('csrf_token')): ?>
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapeKelolaPegawai(csrf_token()) ?>"
                    >
                <?php endif; ?>

                <input
                    type="hidden"
                    id="inputIdUser"
                    name="id_user"
                    value="0"
                >

                <!-- USERNAME -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                        Username Login <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="inputUsername"
                        name="username"
                        required
                        maxlength="50"
                        autocomplete="off"
                        placeholder="Contoh: andi.kasir"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-skyBlue font-medium"
                    >

                    <p class="text-[11px] text-slate-400 mt-1">
                        Digunakan untuk masuk ke sistem.
                    </p>
                </div>

                <!-- ROLE DAN STATUS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                            Role Hak Akses <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="inputRole"
                            name="role"
                            required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-skyBlue font-medium text-slate-700 cursor-pointer"
                        >

                            <?php if ($isSuperAdmin): ?>
                                <option value="owner">Owner (Pemilik Bisnis)</option>
                                <option value="admin">Admin (Operasional)</option>
                            <?php else: ?>
                                <option value="admin">Admin (Operasional)</option>
                            <?php endif; ?>

                            <option value="kasir">Kasir (Pegawai)</option>

                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                            Status Akun <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="inputStatus"
                            name="status"
                            required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-skyBlue font-medium text-slate-700 cursor-pointer"
                        >
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                </div>

                <!-- PASSWORD -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                        Password
                        <span id="labelPassHint" class="lowercase font-normal text-slate-400">
                            (wajib untuk akun baru)
                        </span>
                    </label>

                    <input
                        type="password"
                        id="inputPassword"
                        name="password"
                        minlength="4"
                        autocomplete="new-password"
                        placeholder="Minimal 4 karakter"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-skyBlue font-medium"
                    >

                    <p id="labelPassSub" class="text-[11px] text-slate-400 mt-1">
                        Password minimal 4 karakter.
                    </p>
                </div>

                <!-- TOMBOL -->
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">

                    <button
                        type="button"
                        onclick="tutupFormPegawai()"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-5 py-3 rounded-xl font-bold text-xs transition cursor-pointer"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="bg-brand-navy hover:bg-brand-lightNavy text-white px-6 py-3 rounded-xl font-bold text-xs transition cursor-pointer shadow"
                    >
                        Simpan Data
                    </button>

                </div>

            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        const totalAkunTabel = <?= count($daftarPegawai) ?>;
        const isSuperAdminPage = <?= $isSuperAdmin ? 'true' : 'false' ?>;

        function bukaFormPegawai(mode, data = null) {
            const modal = document.getElementById('modalPegawai');
            const title = document.getElementById('modalTitle');

            const inputIdUser = document.getElementById('inputIdUser');
            const inputUsername = document.getElementById('inputUsername');
            const inputRole = document.getElementById('inputRole');
            const inputStatus = document.getElementById('inputStatus');
            const inputPassword = document.getElementById('inputPassword');

            const labelPassHint = document.getElementById('labelPassHint');
            const labelPassSub = document.getElementById('labelPassSub');

            if (mode === 'tambah') {
                title.innerText = 'Tambah Akun Baru';

                inputIdUser.value = '0';
                inputUsername.value = '';
                inputRole.value = isSuperAdminPage ? 'admin' : 'kasir';
                inputStatus.value = 'aktif';

                inputUsername.readOnly = false;
                inputPassword.value = '';
                inputPassword.required = true;

                labelPassHint.innerText = '(wajib diisi)';
                labelPassSub.innerText = 'Password minimal 4 karakter.';

            } else if (mode === 'edit' && data) {
                title.innerText = 'Edit Akun · ' + (data.nama || data.username || '');

                inputIdUser.value = data.id || data.id_user || '0';
                inputUsername.value = data.username || '';

                const roleData = String(data.raw_role || data.role || '').toLowerCase();

                if (roleData === 'owner') {
                    inputRole.value = 'owner';
                } else if (roleData === 'admin') {
                    inputRole.value = 'admin';
                } else {
                    inputRole.value = 'kasir';
                }

                inputStatus.value = String(data.raw_status || data.status || 'aktif').toLowerCase();

                inputUsername.readOnly = false;
                inputPassword.value = '';
                inputPassword.required = false;

                labelPassHint.innerText = '(opsional, kosongkan jika tidak diubah)';
                labelPassSub.innerText = 'Kosongkan kolom ini jika tidak ingin mengubah password akun.';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function tutupFormPegawai() {
            const modal = document.getElementById('modalPegawai');

            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                tutupFormPegawai();
            }
        });

        document.getElementById('modalPegawai').addEventListener('click', function(event) {
            if (event.target === this) {
                tutupFormPegawai();
            }
        });

        function filterTabelPegawai() {
            const keyword = document.getElementById('filterSearch').value.toLowerCase().trim();
            const selectedRole = document.getElementById('filterRole').value;
            const selectedStatus = document.getElementById('filterStatus').value;

            const rows = document.querySelectorAll('.pegawai-row');

            let visibleCount = 0;

            rows.forEach(function(row) {
                const nama = row.getAttribute('data-nama') || '';
                const username = row.getAttribute('data-username') || '';
                const role = row.getAttribute('data-role') || '';
                const status = row.getAttribute('data-status') || '';

                const matchKeyword =
                    keyword === '' ||
                    nama.includes(keyword) ||
                    username.includes(keyword);

                const matchRole =
                    selectedRole === '' ||
                    role === selectedRole;

                const matchStatus =
                    selectedStatus === '' ||
                    status === selectedStatus;

                if (matchKeyword && matchRole && matchStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('infoJumlah').innerText =
                `Menampilkan ${visibleCount} dari ${totalAkunTabel} akun`;
        }
    </script>

</body>
</html>
