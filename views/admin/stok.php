
<?php
// views/owner/stok.php
// Tampilan Daftar Stok dan Riwayat Stok KolegaOps

$daftarStok = $daftarStok ?? [];
$riwayatStok = $riwayatStok ?? [];
$filterRiwayat = $filterRiwayat ?? [];

$jumlahAktivitas = $jumlahAktivitas ?? count($riwayatStok);
$jumlahMasuk = $jumlahMasuk ?? 0;
$jumlahKeluar = $jumlahKeluar ?? 0;

$totalItem = $totalItem ?? count($daftarStok);
$totalBahan = $totalBahan ?? $totalItem;
$totalMenu = $totalMenu ?? 0;
$totalAman = $totalAman ?? 0;
$totalKritis = $totalKritis ?? 0;
$kritisTeks = $kritisTeks ?? 'Tidak ada bahan yang perlu direstock';

$pesanSukses = $pesanSukses ?? null;
$pesanError = $pesanError ?? null;

function formatAngkaStok($angka): string
{
    return number_format((float) $angka, 1, ',', '.');
}

function eStok($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Kelompokkan riwayat POS berdasarkan transaksi dan menu.
$kelompokRiwayat = [];

foreach ($riwayatStok as $r) {
    if (($r['sumber'] ?? '') === 'pos') {
        $idTransaksi = $r['id_transaksi'] ?? 'tanpa-transaksi';
        $idMenu = $r['id_menu'] ?? 'menu-lama';
        $key = 'pos-' . $idTransaksi . '-' . $idMenu;

        if (!isset($kelompokRiwayat[$key])) {
            $kelompokRiwayat[$key] = [
                'tipe' => 'pos',
                'waktu' => $r['create_at'] ?? null,
                'kode_transaksi' => $r['kode_transaksi'] ?? null,
                'nama_menu' => $r['nama_menu'] ?? $r['kelompok_menu'] ?? 'Menu tidak teridentifikasi',
                'nama_user' => $r['nama_user'] ?? 'Pengguna',
                'bahan' => []
            ];
        }

        $kelompokRiwayat[$key]['bahan'][] = $r;
    } else {
        $key = 'manual-' . ($r['id_riwayat'] ?? uniqid());

        $kelompokRiwayat[$key] = [
            'tipe' => 'manual',
            'data' => $r
        ];
    }
}

// Urutkan aktivitas terbaru.
uasort($kelompokRiwayat, function ($a, $b) {
    $waktuA = $a['tipe'] === 'pos'
        ? ($a['waktu'] ?? '')
        : ($a['data']['create_at'] ?? '');

    $waktuB = $b['tipe'] === 'pos'
        ? ($b['waktu'] ?? '')
        : ($b['data']['create_at'] ?? '');

    return strtotime((string) $waktuB) <=> strtotime((string) $waktuA);
});

// Pagination 10 aktivitas per halaman.
$jumlahPerHalaman = 10;
$totalKelompok = count($kelompokRiwayat);
$totalHalaman = max(1, (int) ceil($totalKelompok / $jumlahPerHalaman));

$halamanAktif = max(1, (int) ($_GET['halaman'] ?? 1));
$halamanAktif = min($halamanAktif, $totalHalaman);

$offset = ($halamanAktif - 1) * $jumlahPerHalaman;
$riwayatHalaman = array_slice($kelompokRiwayat, $offset, $jumlahPerHalaman, true);

$nomorAwal = $totalKelompok > 0 ? $offset + 1 : 0;
$nomorAkhir = min($offset + $jumlahPerHalaman, $totalKelompok);

$parameterHalaman = $_GET;
$parameterHalaman['controller'] = 'stok_bahan';
$parameterHalaman['action'] = 'index';
$parameterHalaman['tab'] = 'riwayat';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Bahan - KolegaOps Warkop Kolega</title>

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

<body class="bg-brand-bgLight text-slate-800 min-h-screen flex text-base">

<?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>

<main class="flex-1 p-8 overflow-y-auto min-h-screen">

    <header class="flex items-center justify-between pb-6 border-b border-slate-200 mb-6 bg-white px-8 py-6 rounded-2xl shadow-sm">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Stok Bahan Baku</h2>
            <p class="text-sm text-slate-500 mt-1">
                Kondisi persediaan seluruh warkop &bull;
                <?= eStok($tanggal_hari_ini ?? date('d-m-Y')) ?>
            </p>
        </div>

        <a href="index.php?controller=stok_bahan&action=tambah"
           class="bg-brand-navy hover:bg-brand-lightNavy text-white px-6 py-3.5 rounded-xl font-bold shadow transition">
            + Tambah Bahan Baru
        </a>
    </header>

    <?php if (!empty($pesanSukses)): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-sm">
            <?= eStok($pesanSukses) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesanError)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-sm">
            <?= eStok($pesanError) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex gap-2 mb-6">
        <button type="button" id="tabDaftar" onclick="gantiTab('daftar')"
                class="tab-button px-6 py-3 rounded-lg font-bold text-sm">
            Daftar Stok
        </button>

        <button type="button" id="tabRiwayat" onclick="gantiTab('riwayat')"
                class="tab-button px-6 py-3 rounded-lg font-bold text-sm">
            Riwayat Stok
        </button>
    </div>

    <!-- DAFTAR STOK -->
    <section id="kontenDaftar">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

            <div class="bg-brand-navy text-white p-6 rounded-2xl shadow-sm">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-wider">TOTAL STOK TERCATAT</p>
                <h3 class="text-4xl font-extrabold my-3"><?= (int) $totalItem ?> item</h3>
                <p class="text-xs text-blue-200">
                    <?= (int) $totalBahan ?> bahan baku &bull;
                    <?= (int) $totalMenu ?> menu kemasan
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">STOK AMAN</p>
                <h3 class="text-3xl font-extrabold text-brand-navy my-3"><?= (int) $totalAman ?> item</h3>
                <p class="text-xs text-slate-500">Memenuhi batas minimum operasional</p>
            </div>

            <div class="bg-white p-6 rounded-2xl border <?= $totalKritis > 0 ? 'border-red-500' : 'border-slate-200' ?> shadow-sm">
                <p class="text-xs font-bold text-red-600 uppercase tracking-wider">PERLU RESTOCK</p>
                <h3 class="text-3xl font-extrabold text-red-600 my-3"><?= (int) $totalKritis ?> bahan</h3>
                <p class="text-xs text-red-500 font-medium" title="<?= eStok($kritisTeks) ?>">
                    <?= eStok($kritisTeks) ?>
                </p>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6">
            <div class="md:col-span-6">
                <input type="text" id="searchStok"
                       placeholder="Cari nama bahan atau item..."
                       oninput="filterTable()"
                       class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm">
            </div>

            <div class="md:col-span-3">
                <select id="filterJenis" onchange="filterTable()"
                        class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm shadow-sm">
                    <option value="">Semua jenis</option>
                    <option value="Bahan">Bahan Baku Mentah</option>
                    <option value="Menu">Menu Kemasan Siap Jual</option>
                </select>
            </div>

            <div class="md:col-span-3">
                <select id="filterStatus" onchange="filterTable()"
                        class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm shadow-sm">
                    <option value="">Semua status</option>
                    <option value="Aman">Status: Aman</option>
                    <option value="Kritis">Status: Kritis</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-900">Detail Stok Bahan Baku</h3>
                <span class="text-xs text-slate-400">Satuan mengikuti masing-masing item</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="stokTable">
                    <thead class="bg-brand-darkNavy text-white">
                        <tr>
                            <th class="p-4 pl-6">NAMA BAHAN/MENU</th>
                            <th class="p-4">STOK SAAT INI</th>
                            <th class="p-4">MINIMUM</th>
                            <th class="p-4">SATUAN</th>
                            <th class="p-4">STATUS</th>
                            <th class="p-4">AKSI</th>
                        </tr>
                    </thead>

                    <tbody class="font-medium text-slate-800">
                    <?php if (empty($daftarStok)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                Belum ada data stok bahan baku.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarStok as $idx => $s):
                            $bgRow = ($idx % 2 === 1) ? 'bg-blue-50/60' : 'bg-white';
                            $status = $s['status'] ?? 'Aman';
                            $jenis = $s['jenis'] ?? 'Bahan';
                        ?>
                            <tr class="stok-row <?= $bgRow ?> border-b border-slate-100 hover:bg-blue-100/40"
                                data-status="<?= eStok($status) ?>"
                                data-jenis="<?= eStok($jenis) ?>">

                                <td class="p-4 pl-6 font-bold text-brand-navy"><?= eStok($s['nama'] ?? '-') ?></td>
                                <td class="p-4 font-bold"><?= formatAngkaStok($s['stok'] ?? 0) ?></td>
                                <td class="p-4 text-slate-600"><?= formatAngkaStok($s['minimum'] ?? 0) ?></td>
                                <td class="p-4 text-slate-600"><?= eStok($s['satuan'] ?? '-') ?></td>

                                <td class="p-4">
                                    <?php if ($status === 'Aman'): ?>
                                        <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-xs">Aman</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-600 px-3 py-1 rounded-lg font-bold text-xs">Kritis</span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 whitespace-nowrap">
                                    <a href="index.php?controller=stok_bahan&action=update&id=<?= (int) $s['id'] ?>"
                                       class="text-brand-navy font-bold hover:underline">Update</a>

                                    <a href="index.php?controller=stok_bahan&action=hapus&id=<?= (int) $s['id'] ?>"
                                       class="text-red-600 font-bold hover:underline ml-4"
                                       onclick="return confirm('Yakin ingin menghapus bahan ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t flex justify-between text-sm text-slate-500">
                <span id="stokCount">Menampilkan <?= count($daftarStok) ?> dari <?= count($daftarStok) ?> item</span>
                <span class="font-bold text-brand-navy">Halaman 1 dari 1</span>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl text-sm text-blue-900">
            <strong>Petunjuk Operasional:</strong>
            Lakukan restock segera untuk bahan yang berstatus Kritis.
        </div>
    </section>

    <!-- RIWAYAT STOK -->
    <section id="kontenRiwayat" class="hidden">

        <div class="mb-4">
            <h3 class="text-xl font-extrabold text-slate-900">Riwayat Perubahan Stok</h3>
            <p class="text-sm text-slate-500 mt-1">Catatan perubahan persediaan bahan baku.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div class="bg-brand-navy text-white p-4 rounded-xl">
                <p class="text-xs text-blue-200 font-bold uppercase">TOTAL AKTIVITAS</p>
                <h3 class="text-2xl font-extrabold mt-1"><?= (int) $jumlahAktivitas ?></h3>
                <p class="text-xs text-blue-200 mt-1">Catatan perubahan stok</p>
            </div>

            <div class="bg-white border border-emerald-200 p-4 rounded-xl">
                <p class="text-xs text-emerald-600 font-bold uppercase">STOK MASUK</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 mt-1"><?= (int) $jumlahMasuk ?></h3>
                <p class="text-xs text-slate-500 mt-1">Penambahan persediaan</p>
            </div>

            <div class="bg-white border border-red-200 p-4 rounded-xl">
                <p class="text-xs text-red-600 font-bold uppercase">STOK KELUAR</p>
                <h3 class="text-2xl font-extrabold text-red-600 mt-1"><?= (int) $jumlahKeluar ?></h3>
                <p class="text-xs text-slate-500 mt-1">Pengurangan persediaan</p>
            </div>
        </div>

        <!-- FILTER RIWAYAT -->
        <form method="GET" action="index.php" id="formFilterRiwayat"
              class="bg-white border border-slate-200 rounded-xl p-4 mb-4">

            <input type="hidden" name="controller" value="stok_bahan">
            <input type="hidden" name="action" value="index">
            <input type="hidden" name="tab" value="riwayat">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                <div class="md:col-span-3 min-w-0">
                    <label for="searchRiwayat" class="block text-xs font-bold text-slate-500 mb-2">Pencarian</label>
                    <input type="text" name="search_riwayat" id="searchRiwayat"
                           value="<?= eStok($filterRiwayat['search'] ?? '') ?>"
                           placeholder="Cari bahan atau menu..." autocomplete="off"
                           class="w-full min-w-0 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-brand-skyBlue">
                </div>

                <div class="md:col-span-2 min-w-0">
                    <label for="jenisRiwayat" class="block text-xs font-bold text-slate-500 mb-2">Jenis</label>
                    <select name="jenis_riwayat" id="jenisRiwayat"
                            class="w-full min-w-0 border border-slate-200 rounded-lg px-2 py-2.5 text-sm">
                        <option value="semua" <?= ($filterRiwayat['jenis'] ?? 'semua') === 'semua' ? 'selected' : '' ?>>Semua jenis</option>
                        <option value="masuk" <?= ($filterRiwayat['jenis'] ?? '') === 'masuk' ? 'selected' : '' ?>>Stok Masuk</option>
                        <option value="keluar" <?= ($filterRiwayat['jenis'] ?? '') === 'keluar' ? 'selected' : '' ?>>Stok Keluar</option>
                    </select>
                </div>

                <div class="md:col-span-2 min-w-0">
                    <label for="sumberRiwayat" class="block text-xs font-bold text-slate-500 mb-2">Sumber</label>
                    <select name="sumber_riwayat" id="sumberRiwayat"
                            class="w-full min-w-0 border border-slate-200 rounded-lg px-2 py-2.5 text-sm">
                        <option value="semua" <?= ($filterRiwayat['sumber'] ?? 'semua') === 'semua' ? 'selected' : '' ?>>Semua sumber</option>
                        <option value="manual" <?= ($filterRiwayat['sumber'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
                        <option value="pos" <?= ($filterRiwayat['sumber'] ?? '') === 'pos' ? 'selected' : '' ?>>Transaksi POS</option>
                    </select>
                </div>

                <div class="md:col-span-3 min-w-0">
                    <label for="tanggalRiwayat" class="block text-xs font-bold text-slate-500 mb-2">Tanggal</label>
                    <input type="date" name="tanggal_riwayat" id="tanggalRiwayat"
                           value="<?= eStok($filterRiwayat['tanggal'] ?? '') ?>"
                           class="w-full min-w-0 border border-slate-200 rounded-lg px-2 py-2.5 text-sm">
                </div>

                <div class="md:col-span-2 min-w-0">
                    <a href="index.php?controller=stok_bahan&action=index&tab=riwayat"
                       class="flex items-center justify-center w-full whitespace-nowrap bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-lg px-3 py-2.5 transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- DAFTAR AKTIVITAS -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-4 py-3 border-b border-slate-100 bg-brand-darkNavy text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                <div>
                    <h3 class="font-bold text-sm">Daftar Aktivitas Stok</h3>
                    <p class="text-xs text-blue-200 mt-0.5">Riwayat penambahan dan pengurangan persediaan</p>
                </div>
                <span class="text-xs text-blue-100"><?= $totalKelompok ?> aktivitas ditemukan</span>
            </div>

            <div class="p-3 sm:p-4 space-y-2.5">

                <?php if (empty($riwayatHalaman)): ?>

                    <div class="py-10 text-center">
                        <p class="font-semibold text-slate-600">Belum ada aktivitas stok</p>
                        <p class="text-sm text-slate-400 mt-1">Tidak ada riwayat yang sesuai dengan filter.</p>
                    </div>

                <?php else: ?>

                    <?php foreach ($riwayatHalaman as $kelompok): ?>

                        <?php if ($kelompok['tipe'] === 'pos'): ?>

                            <?php
                                $waktuTampil = !empty($kelompok['waktu'])
                                    ? date('d-m-Y H:i', strtotime($kelompok['waktu']))
                                    : '-';
                            ?>

                            <div class="border border-blue-200 rounded-lg overflow-hidden">

                                <!-- HEADER POS -->
                                <div class="bg-blue-50 px-3 py-2 border-b border-blue-100">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-brand-navy text-sm">
                                                    <?= eStok($kelompok['nama_menu']) ?>
                                                </h4>
                                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">
                                                    Transaksi POS
                                                </span>
                                            </div>

                                            <p class="text-xs text-slate-500 mt-1">
                                                <?= eStok($kelompok['kode_transaksi'] ?? '-') ?>
                                                <span class="mx-1">|</span>
                                                <?= eStok($waktuTampil) ?>
                                            </p>
                                        </div>

                                        <div class="sm:text-right">
                                            <p class="text-xs text-slate-400">Pengguna</p>
                                            <p class="text-xs font-semibold text-slate-700">
                                                <?= eStok($kelompok['nama_user']) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-2 flex items-center gap-2 text-xs">
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold">
                                            Stok Keluar
                                        </span>
                                        <span class="text-slate-500">
                                            <?= count($kelompok['bahan']) ?> bahan digunakan
                                        </span>
                                    </div>
                                </div>

                                <!-- DETAIL BAHAN -->
                                <div class="p-3">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                        Rincian Penggunaan Bahan
                                    </p>

                                    <div class="space-y-1.5">
                                        <?php foreach ($kelompok['bahan'] as $b): ?>

                                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center bg-slate-50 rounded-md px-3 py-2">

                                                <div class="sm:col-span-4">
                                                    <span class="text-sm font-semibold text-slate-700">
                                                        <?= eStok($b['nama_bahan'] ?? '-') ?>
                                                    </span>
                                                </div>

                                                <div class="sm:col-span-3">
                                                    <p class="text-xs text-slate-400">Perubahan</p>
                                                    <p class="text-sm font-bold text-red-600">
                                                        -<?= formatAngkaStok($b['jumlah'] ?? 0) ?>
                                                        <?= eStok($b['satuan'] ?? '') ?>
                                                    </p>
                                                </div>

                                                <div class="sm:col-span-5">
                                                    <p class="text-xs text-slate-400">Stok sebelum dan sesudah</p>
                                                    <div class="flex items-center gap-2 text-sm">
                                                        <span class="text-slate-600">
                                                            <?= formatAngkaStok($b['stok_sebelum'] ?? 0) ?>
                                                        </span>
                                                        <span class="text-slate-400">→</span>
                                                        <span class="font-bold text-brand-navy">
                                                            <?= formatAngkaStok($b['stok_sesudah'] ?? 0) ?>
                                                        </span>
                                                        <span class="text-xs text-slate-500">
                                                            <?= eStok($b['satuan'] ?? '') ?>
                                                        </span>
                                                    </div>
                                                </div>

                                            </div>

                                        <?php endforeach; ?>
                                    </div>

                                    <p class="text-xs text-slate-400 mt-2">
                                        Pengurangan stok dilakukan otomatis setelah transaksi POS berhasil.
                                    </p>
                                </div>
                            </div>

                        <?php else: ?>

                            <?php
                                $r = $kelompok['data'];
                                $masuk = ($r['jenis'] ?? '') === 'masuk';

                                $waktuTampil = !empty($r['create_at'])
                                    ? date('d-m-Y H:i', strtotime($r['create_at']))
                                    : '-';
                            ?>

                            <div class="border <?= $masuk ? 'border-emerald-200' : 'border-red-200' ?> rounded-lg overflow-hidden">

                                <div class="<?= $masuk ? 'bg-emerald-50' : 'bg-red-50' ?> px-3 py-2 border-b <?= $masuk ? 'border-emerald-100' : 'border-red-100' ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

                                        <div>
                                            <h4 class="font-bold text-slate-800 text-sm">
                                                <?= eStok($r['nama_bahan'] ?? '-') ?>
                                            </h4>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                <?= eStok($waktuTampil) ?>
                                                <span class="mx-1">|</span>
                                                Aktivitas manual
                                            </p>
                                        </div>

                                        <span class="<?= $masuk ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?> px-2.5 py-1 rounded-md text-xs font-bold w-fit">
                                            <?= $masuk ? 'Stok Masuk' : 'Stok Keluar' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                                        <div>
                                            <p class="text-xs text-slate-400 mb-0.5">Perubahan Stok</p>
                                            <p class="text-base font-extrabold <?= $masuk ? 'text-emerald-600' : 'text-red-600' ?>">
                                                <?= $masuk ? '+' : '-' ?><?= formatAngkaStok($r['jumlah'] ?? 0) ?>
                                                <span class="text-xs font-semibold"><?= eStok($r['satuan'] ?? '') ?></span>
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400 mb-0.5">Stok Sebelum</p>
                                            <p class="text-sm font-semibold text-slate-700">
                                                <?= formatAngkaStok($r['stok_sebelum'] ?? 0) ?>
                                                <?= eStok($r['satuan'] ?? '') ?>
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400 mb-0.5">Stok Sesudah</p>
                                            <p class="text-sm font-bold text-brand-navy">
                                                <?= formatAngkaStok($r['stok_sesudah'] ?? 0) ?>
                                                <?= eStok($r['satuan'] ?? '') ?>
                                            </p>
                                        </div>

                                    </div>

                                    <div class="mt-2 pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:justify-between gap-1">
                                        <p class="text-xs text-slate-500">
                                            Pengguna:
                                            <span class="font-semibold text-slate-700">
                                                <?= eStok($r['nama_user'] ?? 'Pengguna tidak diketahui') ?>
                                            </span>
                                        </p>

                                        <?php if (!empty($r['keterangan']) && $r['keterangan'] !== '-'): ?>
                                            <p class="text-xs text-slate-500 sm:text-right">
                                                <?= eStok($r['keterangan']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        <?php endif; ?>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <!-- PAGINATION -->
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

                <p class="text-xs text-slate-500">
                    Menampilkan
                    <strong class="text-slate-700"><?= $nomorAwal ?>–<?= $nomorAkhir ?></strong>
                    dari
                    <strong class="text-slate-700"><?= $totalKelompok ?></strong>
                    aktivitas
                </p>

                <?php if ($totalHalaman > 1): ?>
                    <div class="flex items-center gap-1.5">

                        <?php if ($halamanAktif > 1): ?>
                            <?php
                                $parameterSebelumnya = $parameterHalaman;
                                $parameterSebelumnya['halaman'] = $halamanAktif - 1;
                            ?>
                            <a href="index.php?<?= eStok(http_build_query($parameterSebelumnya)) ?>"
                               class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                &laquo; Sebelumnya
                            </a>
                        <?php endif; ?>

                        <?php
                            $mulaiHalaman = max(1, $halamanAktif - 2);
                            $akhirHalaman = min($totalHalaman, $halamanAktif + 2);
                        ?>

                        <?php for ($i = $mulaiHalaman; $i <= $akhirHalaman; $i++): ?>
                            <?php
                                $parameterNomor = $parameterHalaman;
                                $parameterNomor['halaman'] = $i;
                            ?>

                            <a href="index.php?<?= eStok(http_build_query($parameterNomor)) ?>"
                               class="min-w-8 text-center px-3 py-1.5 rounded-lg text-xs font-bold border
                               <?= $i === $halamanAktif
                                   ? 'bg-brand-navy text-white border-brand-navy'
                                   : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($halamanAktif < $totalHalaman): ?>
                            <?php
                                $parameterBerikutnya = $parameterHalaman;
                                $parameterBerikutnya['halaman'] = $halamanAktif + 1;
                            ?>
                            <a href="index.php?<?= eStok(http_build_query($parameterBerikutnya)) ?>"
                               class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                Berikutnya &raquo;
                            </a>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <p class="text-xs text-slate-400">
                    Halaman <?= $halamanAktif ?> dari <?= $totalHalaman ?>
                </p>
            </div>

        </div>
    </section>

</main>

<script>
    function gantiTab(tab) {
        const daftar = document.getElementById('kontenDaftar');
        const riwayat = document.getElementById('kontenRiwayat');
        const tombolDaftar = document.getElementById('tabDaftar');
        const tombolRiwayat = document.getElementById('tabRiwayat');

        const aktif =
            'tab-button px-6 py-3 rounded-lg font-bold text-sm bg-brand-navy text-white';

        const tidakAktif =
            'tab-button px-6 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-100';

        if (tab === 'riwayat') {
            daftar.classList.add('hidden');
            riwayat.classList.remove('hidden');
            tombolRiwayat.className = aktif;
            tombolDaftar.className = tidakAktif;
        } else {
            riwayat.classList.add('hidden');
            daftar.classList.remove('hidden');
            tombolDaftar.className = aktif;
            tombolRiwayat.className = tidakAktif;
        }
    }

    function filterTable() {
        const search = document.getElementById('searchStok').value.toLowerCase().trim();
        const jenisFilter = document.getElementById('filterJenis').value;
        const statusFilter = document.getElementById('filterStatus').value;
        const rows = document.querySelectorAll('.stok-row');

        let visibleCount = 0;

        rows.forEach(row => {
            const namaBahan = row.cells[0].textContent.toLowerCase();
            const jenis = row.getAttribute('data-jenis');
            const status = row.getAttribute('data-status');

            const cocokSearch = search === '' || namaBahan.includes(search);
            const cocokJenis = jenisFilter === '' || jenis === jenisFilter;
            const cocokStatus = statusFilter === '' || status === statusFilter;

            if (cocokSearch && cocokJenis && cocokStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const totalAwal = <?= count($daftarStok) ?>;

        document.getElementById('stokCount').textContent =
            `Menampilkan ${visibleCount} dari ${totalAwal} item`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formFilterRiwayat');
        const search = document.getElementById('searchRiwayat');
        const jenis = document.getElementById('jenisRiwayat');
        const sumber = document.getElementById('sumberRiwayat');
        const tanggal = document.getElementById('tanggalRiwayat');

        if (!form || !search || !jenis || !sumber || !tanggal) {
            return;
        }

        let timerPencarian;

        search.addEventListener('input', function () {
            clearTimeout(timerPencarian);
            timerPencarian = setTimeout(function () {
                form.requestSubmit();
            }, 400);
        });

        jenis.addEventListener('change', function () {
            form.requestSubmit();
        });

        sumber.addEventListener('change', function () {
            form.requestSubmit();
        });

        tanggal.addEventListener('change', function () {
            form.requestSubmit();
        });
    });

    const parameterURL = new URLSearchParams(window.location.search);

    if (parameterURL.get('tab') === 'riwayat') {
        gantiTab('riwayat');
    } else {
        gantiTab('daftar');
    }
</script>

</body>
</html>
