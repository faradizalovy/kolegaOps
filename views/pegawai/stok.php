
<?php
// views/pegawai/stok.php
// Tampilan Stok Bahan Baku dan Riwayat Stok Pegawai KolegaOps

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

function formatAngkaStokPegawai($angka): string
{
    return number_format((float) $angka, 1, ',', '.');
}

function eStokPegawai($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
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

    <!-- HEADER -->
    <header class="flex items-center justify-between pb-6 border-b border-slate-200 mb-6 bg-white px-8 py-6 rounded-2xl shadow-sm">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900">Stok Bahan Baku</h2>
            <p class="text-sm text-slate-500 mt-1">
                Kondisi persediaan seluruh warkop &bull;
                <?= eStokPegawai($tanggal_hari_ini ?? date('d-m-Y')) ?>
            </p>
        </div>
    </header>

    <!-- NOTIFIKASI -->
    <?php if (!empty($pesanSukses)): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-sm">
            ✅ <?= eStokPegawai($pesanSukses) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesanError)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-sm">
            ⚠️ <?= eStokPegawai($pesanError) ?>
        </div>
    <?php endif; ?>

    <!-- TAB -->
    <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex gap-2 mb-6">
        <button type="button" id="tabDaftar"
                onclick="gantiTabPegawai('daftar')"
                class="tab-button px-6 py-3 rounded-lg font-bold text-sm">
            Daftar Stok
        </button>

        <button type="button" id="tabRiwayat"
                onclick="gantiTabPegawai('riwayat')"
                class="tab-button px-6 py-3 rounded-lg font-bold text-sm">
            Riwayat Stok
        </button>
    </div>

    <!-- ====================================== -->
    <!-- TAB DAFTAR STOK -->
    <!-- ====================================== -->

    <section id="kontenDaftar">

        <!-- STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

            <div class="bg-brand-navy text-white p-6 rounded-2xl shadow-sm">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-wider">
                    TOTAL STOK TERCATAT
                </p>
                <h3 class="text-4xl font-extrabold my-3">
                    <?= (int) $totalItem ?> item
                </h3>
                <p class="text-xs text-blue-200">
                    <?= (int) $totalBahan ?> bahan baku &bull;
                    <?= (int) $totalMenu ?> menu kemasan
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    STOK AMAN
                </p>
                <h3 class="text-3xl font-extrabold text-brand-navy my-3">
                    <?= (int) $totalAman ?> item
                </h3>
                <p class="text-xs text-slate-500">
                    Memenuhi batas minimum operasional
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border <?= $totalKritis > 0 ? 'border-red-500' : 'border-slate-200' ?> shadow-sm">
                <p class="text-xs font-bold text-red-600 uppercase tracking-wider">
                    PERLU RESTOCK
                </p>
                <h3 class="text-3xl font-extrabold text-red-600 my-3">
                    <?= (int) $totalKritis ?> bahan
                </h3>
                <p class="text-xs text-red-500 font-medium"
                   title="<?= eStokPegawai($kritisTeks) ?>">
                    <?= eStokPegawai($kritisTeks) ?>
                </p>
            </div>

        </div>

        <!-- FILTER STOK -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6">

            <div class="md:col-span-6">
                <input type="text" id="searchStokPegawai"
                       placeholder="Cari nama bahan atau item..."
                       oninput="filterTablePegawai()"
                       class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm">
            </div>

            <div class="md:col-span-3">
                <select id="filterJenisPegawai"
                        onchange="filterTablePegawai()"
                        class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm shadow-sm">
                    <option value="">Semua jenis</option>
                    <option value="Bahan">Bahan Baku Mentah</option>
                    <option value="Menu">Menu Kemasan Siap Jual</option>
                </select>
            </div>

            <div class="md:col-span-3">
                <select id="filterStatusPegawai"
                        onchange="filterTablePegawai()"
                        class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm shadow-sm">
                    <option value="">Semua status</option>
                    <option value="Aman">Status: Aman</option>
                    <option value="Kritis">Status: Kritis</option>
                </select>
            </div>

        </div>

        <!-- TABEL STOK -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">

            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-900">
                    Detail Stok Bahan Baku
                </h3>
                <span class="text-xs text-slate-400">
                    Satuan mengikuti masing-masing item
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">

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

                            <tr class="stok-row-pegawai <?= $bgRow ?> border-b border-slate-100 hover:bg-blue-100/40"
                                data-status="<?= eStokPegawai($status) ?>"
                                data-jenis="<?= eStokPegawai($jenis) ?>">

                                <td class="p-4 pl-6 font-bold text-brand-navy">
                                    <?= eStokPegawai($s['nama'] ?? '-') ?>
                                </td>

                                <td class="p-4 font-bold">
                                    <?= formatAngkaStokPegawai($s['stok'] ?? 0) ?>
                                </td>

                                <td class="p-4 text-slate-600">
                                    <?= formatAngkaStokPegawai($s['minimum'] ?? 0) ?>
                                </td>

                                <td class="p-4 text-slate-600">
                                    <?= eStokPegawai($s['satuan'] ?? '-') ?>
                                </td>

                                <td class="p-4">
                                    <?php if ($status === 'Aman'): ?>
                                        <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-xs">
                                            Aman
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-600 px-3 py-1 rounded-lg font-bold text-xs">
                                            Kritis
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 whitespace-nowrap">
                                    <a href="index.php?controller=stok_bahan&action=update&id=<?= (int) $s['id'] ?>"
                                       class="text-brand-navy font-bold hover:underline">
                                        Update
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 text-sm text-slate-500">
                <span id="stokCountPegawai">
                    Menampilkan <?= count($daftarStok) ?> dari <?= count($daftarStok) ?> item
                </span>
            </div>

        </div>

        <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl text-sm text-blue-900">
            💡 <strong>Petunjuk Operasional:</strong>
            Lakukan pembaruan stok sesuai kondisi persediaan sebenarnya.
            Perhatikan bahan yang berstatus Kritis agar segera dilakukan restock.
        </div>

    </section>

    <!-- ====================================== -->
    <!-- TAB RIWAYAT STOK -->
    <!-- ====================================== -->

    <section id="kontenRiwayat" class="hidden">

        <div class="mb-5">
            <h3 class="text-xl font-extrabold text-slate-900">
                Riwayat Perubahan Stok
            </h3>
            <p class="text-sm text-slate-500 mt-1">
                Catatan aktivitas penambahan dan pengurangan stok bahan baku.
            </p>
        </div>

        <!-- STATISTIK RIWAYAT -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">

            <div class="bg-brand-navy text-white p-6 rounded-2xl">
                <p class="text-xs text-blue-200 font-bold uppercase">
                    TOTAL AKTIVITAS
                </p>
                <h3 class="text-4xl font-extrabold mt-3">
                    <?= (int) $jumlahAktivitas ?>
                </h3>
                <p class="text-xs text-blue-200 mt-2">
                    Jumlah catatan perubahan stok
                </p>
            </div>

            <div class="bg-white border border-emerald-200 p-6 rounded-2xl">
                <p class="text-xs text-emerald-600 font-bold uppercase">
                    STOK MASUK
                </p>
                <h3 class="text-4xl font-extrabold text-emerald-600 mt-3">
                    <?= (int) $jumlahMasuk ?>
                </h3>
                <p class="text-xs text-slate-500 mt-2">
                    Aktivitas penambahan stok
                </p>
            </div>

            <div class="bg-white border border-red-200 p-6 rounded-2xl">
                <p class="text-xs text-red-600 font-bold uppercase">
                    STOK KELUAR
                </p>
                <h3 class="text-4xl font-extrabold text-red-600 mt-3">
                    <?= (int) $jumlahKeluar ?>
                </h3>
                <p class="text-xs text-slate-500 mt-2">
                    Aktivitas pengurangan stok
                </p>
            </div>

        </div>

        <!-- FILTER RIWAYAT OTOMATIS -->
        <form method="GET" action="index.php"
              id="formFilterRiwayatPegawai"
              class="bg-white border border-slate-200 rounded-2xl p-5 mb-6">

            <input type="hidden" name="controller" value="stok_bahan">
            <input type="hidden" name="action" value="index">
            <input type="hidden" name="tab" value="riwayat">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-2">
                        Cari bahan atau alasan
                    </label>
                    <input type="text"
                           name="search_riwayat"
                           id="searchRiwayatPegawai"
                           value="<?= eStokPegawai($filterRiwayat['search'] ?? '') ?>"
                           placeholder="Nama bahan atau keterangan..."
                           autocomplete="off"
                           class="w-full border border-slate-200 rounded-lg px-3 py-3 text-sm focus:outline-none focus:border-brand-skyBlue">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">
                        Jenis perubahan
                    </label>
                    <select name="jenis_riwayat"
                            id="jenisRiwayatPegawai"
                            class="w-full border border-slate-200 rounded-lg px-3 py-3 text-sm">
                        <option value="semua" <?= ($filterRiwayat['jenis'] ?? 'semua') === 'semua' ? 'selected' : '' ?>>
                            Semua jenis
                        </option>
                        <option value="masuk" <?= ($filterRiwayat['jenis'] ?? '') === 'masuk' ? 'selected' : '' ?>>
                            Stok Masuk
                        </option>
                        <option value="keluar" <?= ($filterRiwayat['jenis'] ?? '') === 'keluar' ? 'selected' : '' ?>>
                            Stok Keluar
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">
                        Sumber
                    </label>
                    <select name="sumber_riwayat"
                            id="sumberRiwayatPegawai"
                            class="w-full border border-slate-200 rounded-lg px-3 py-3 text-sm">
                        <option value="semua" <?= ($filterRiwayat['sumber'] ?? 'semua') === 'semua' ? 'selected' : '' ?>>
                            Semua sumber
                        </option>
                        <option value="manual" <?= ($filterRiwayat['sumber'] ?? '') === 'manual' ? 'selected' : '' ?>>
                            Update Manual
                        </option>
                        <option value="pos" <?= ($filterRiwayat['sumber'] ?? '') === 'pos' ? 'selected' : '' ?>>
                            Transaksi POS
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">
                        Tanggal aktivitas
                    </label>
                    <input type="date"
                           name="tanggal_riwayat"
                           id="tanggalRiwayatPegawai"
                           value="<?= eStokPegawai($filterRiwayat['tanggal'] ?? '') ?>"
                           class="w-full border border-slate-200 rounded-lg px-3 py-3 text-sm">
                </div>

            </div>

            <a href="index.php?controller=stok_bahan&action=index&tab=riwayat"
               class="inline-block text-sm text-brand-navy font-bold mt-4 hover:underline">
                Reset Filter
            </a>

        </form>

        <!-- TABEL RIWAYAT -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-900">
                    Daftar Aktivitas Stok
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Riwayat perubahan stok berdasarkan waktu pencatatan.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">

                    <thead class="bg-brand-darkNavy text-white">
                        <tr>
                            <th class="p-4">WAKTU</th>
                            <th class="p-4">BAHAN BAKU</th>
                            <th class="p-4">JENIS</th>
                            <th class="p-4">PERUBAHAN</th>
                            <th class="p-4">STOK SEBELUM</th>
                            <th class="p-4">STOK SESUDAH</th>
                            <th class="p-4">SUMBER</th>
                            <th class="p-4">KETERANGAN</th>
                            <th class="p-4">PENGGUNA</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($riwayatStok)): ?>
                        <tr>
                            <td colspan="9" class="p-10 text-center text-slate-400">
                                Tidak ada riwayat stok yang sesuai dengan filter.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($riwayatStok as $r): ?>
                            <?php
                                $masuk = ($r['jenis'] ?? '') === 'masuk';

                                $waktuTampil = !empty($r['create_at'])
                                    ? date('d-m-Y H:i', strtotime($r['create_at']))
                                    : '-';

                                $namaPengguna = $r['nama_user'] ?? 'Pengguna tidak diketahui';
                            ?>

                            <tr class="border-b border-slate-100 hover:bg-slate-50">

                                <td class="p-4 whitespace-nowrap text-slate-600">
                                    <?= eStokPegawai($waktuTampil) ?>
                                </td>

                                <td class="p-4 font-bold text-brand-navy">
                                    <?= eStokPegawai($r['nama_bahan'] ?? '-') ?>
                                </td>

                                <td class="p-4">
                                    <?php if ($masuk): ?>
                                        <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg text-xs font-bold">
                                            Stok Masuk
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-lg text-xs font-bold">
                                            Stok Keluar
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 font-extrabold <?= $masuk ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= $masuk ? '+' : '-' ?><?= formatAngkaStokPegawai($r['jumlah'] ?? 0) ?>
                                    <?= eStokPegawai($r['satuan'] ?? '') ?>
                                </td>

                                <td class="p-4 text-slate-600">
                                    <?= formatAngkaStokPegawai($r['stok_sebelum'] ?? 0) ?>
                                </td>

                                <td class="p-4 font-bold text-slate-800">
                                    <?= formatAngkaStokPegawai($r['stok_sesudah'] ?? 0) ?>
                                </td>

                                <td class="p-4">
                                    <?php if (($r['sumber'] ?? '') === 'pos'): ?>
                                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-lg text-xs font-bold">
                                            Transaksi POS
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-lg text-xs font-bold">
                                            Update Manual
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-slate-600 min-w-48">
                                    <?= eStokPegawai($r['keterangan'] ?? '-') ?>

                                    <?php if (!empty($r['kode_transaksi'])): ?>
                                        <div class="text-xs text-blue-600 mt-1">
                                            Transaksi: <?= eStokPegawai($r['kode_transaksi']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($r['nama_menu'])): ?>
                                        <div class="text-xs text-slate-400 mt-1">
                                            Menu: <?= eStokPegawai($r['nama_menu']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-slate-600">
                                    <?= eStokPegawai($namaPengguna) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 text-sm text-slate-500">
                Menampilkan <?= count($riwayatStok) ?> aktivitas riwayat stok.
            </div>

        </div>

    </section>

</main>

<script>
    function gantiTabPegawai(tab) {
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

    function filterTablePegawai() {
        const search = document.getElementById('searchStokPegawai').value.toLowerCase().trim();
        const jenisFilter = document.getElementById('filterJenisPegawai').value;
        const statusFilter = document.getElementById('filterStatusPegawai').value;
        const rows = document.querySelectorAll('.stok-row-pegawai');

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

        document.getElementById('stokCountPegawai').textContent =
            `Menampilkan ${visibleCount} dari ${totalAwal} item`;
    }

    // Pencarian dan filter riwayat otomatis.
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formFilterRiwayatPegawai');
        const search = document.getElementById('searchRiwayatPegawai');
        const jenis = document.getElementById('jenisRiwayatPegawai');
        const sumber = document.getElementById('sumberRiwayatPegawai');
        const tanggal = document.getElementById('tanggalRiwayatPegawai');

        let timerPencarian;

        if (!form) return;

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
        gantiTabPegawai('riwayat');
    } else {
        gantiTabPegawai('daftar');
    }
</script>

</body>
</html>
