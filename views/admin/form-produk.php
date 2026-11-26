
<?php
// views/admin/form-produk.php
// Form Tambah & Edit Produk, Resep, dan Kustomisasi Menu - KolegaOps

$daftarKustomisasiForm = $dataKustomisasiAktif ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Produk' : 'Tambah Produk' ?> - KolegaOps Warkop Kolega</title>

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
        <header class="flex items-center justify-between pb-5 border-b border-slate-200 mb-8 bg-white px-8 py-6 rounded-2xl shadow-sm">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900">
                    <?= $isEdit ? 'Edit Produk' : 'Tambah Produk Baru' ?>
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Produk / <?= $isEdit ? 'Perbarui data menu, resep, dan kustomisasi' : 'Tambahkan produk baru ke katalog' ?>
                </p>
            </div>

            <a href="index.php?controller=menu&action=index"
               class="text-slate-600 hover:text-brand-navy font-semibold text-sm flex items-center space-x-1 transition">
                <span>←</span>
                <span>Kembali ke Daftar</span>
            </a>
        </header>

        <!-- PESAN ERROR -->
        <?php if (!empty($pesanError)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-sm">
                <?= htmlspecialchars($pesanError) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- FORM UTAMA -->
            <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

                <h3 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-4 mb-6">
                    <?= $isEdit ? 'Informasi Produk & Pengaturan' : 'Informasi Produk Baru' ?>
                </h3>

                <form action="index.php?controller=menu&action=<?= $isEdit ? 'edit&id=' . (int) ($dataProduk['id_menu'] ?? $dataProduk['id'] ?? 0) : 'tambah' ?>"
                      method="POST"
                      class="space-y-6">

                    <?php if (function_exists('csrf_token')): ?>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?php endif; ?>

                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id"
                               value="<?= (int) ($dataProduk['id_menu'] ?? $dataProduk['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <!-- PENANDA FORM KUSTOMISASI -->
                    <input type="hidden" name="kustomisasi_dikirim" value="1">

                    <!-- NAMA PRODUK -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                               name="nama"
                               id="inputNama"
                               value="<?= htmlspecialchars((string) ($dataProduk['nama_menu'] ?? $dataProduk['nama'] ?? '')) ?>"
                               placeholder="Contoh: Kopi Susu Tradisional"
                               oninput="updatePreview()"
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm"
                               required>
                    </div>

                    <!-- KATEGORI DAN SATUAN -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Kategori <span class="text-red-500">*</span>
                            </label>

                            <select name="kategori"
                                    id="inputKategori"
                                    onchange="updatePreview()"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700">

                                <option value="Kopi" <?= (($dataProduk['kategori'] ?? '') === 'Kopi') ? 'selected' : '' ?>>Kopi</option>
                                <option value="Minuman" <?= (($dataProduk['kategori'] ?? 'Minuman') === 'Minuman') ? 'selected' : '' ?>>Minuman</option>
                                <option value="Makanan" <?= (($dataProduk['kategori'] ?? '') === 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                                <option value="Camilan" <?= (($dataProduk['kategori'] ?? '') === 'Camilan') ? 'selected' : '' ?>>Camilan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Satuan Jual
                            </label>

                            <select name="satuan"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700">
                                <option value="Gelas">Gelas</option>
                                <option value="Cangkir">Cangkir</option>
                                <option value="Porsi">Porsi</option>
                                <option value="Botol">Botol</option>
                                <option value="Mangkok">Mangkok</option>
                                <option value="Pcs">Pcs</option>
                            </select>
                        </div>

                    </div>

                    <!-- HPP DAN HARGA JUAL -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                HPP per Unit (Rp) <span class="text-red-500">*</span>
                            </label>

                            <input type="number"
                                   name="hpp"
                                   id="inputHpp"
                                   value="<?= isset($dataProduk['hpp']) ? (float) $dataProduk['hpp'] : ($hppAwal ?? '') ?>"
                                   placeholder="Contoh: 3500"
                                   min="0"
                                   step="100"
                                   oninput="updatePreview()"
                                   class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm"
                                   required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Harga Jual (Rp) <span class="text-red-500">*</span>
                            </label>

                            <input type="number"
                                   name="harga"
                                   id="inputHarga"
                                   value="<?= isset($dataProduk['harga']) ? (float) $dataProduk['harga'] : ($hargaAwal ?? '') ?>"
                                   placeholder="Contoh: 8000"
                                   min="0"
                                   step="100"
                                   oninput="updatePreview()"
                                   class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm"
                                   required>
                        </div>

                    </div>

                    <!-- ====================================== -->
                    <!-- KOMPOSISI RESEP DAN BAHAN BAKU -->
                    <!-- ====================================== -->

                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4">

                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <div>
                                <h4 class="font-bold text-slate-900 text-base">
                                    Komposisi Resep & Bahan Baku
                                </h4>
                                <p class="text-xs text-slate-500 mt-1">
                                    Tentukan bahan baku yang terpotong otomatis saat menu terjual.
                                </p>
                            </div>

                            <button type="button"
                                    onclick="tambahBarisResep()"
                                    class="bg-brand-navy hover:bg-brand-lightNavy text-white px-4 py-2 rounded-xl text-xs font-bold transition">
                                + Tambah Bahan
                            </button>
                        </div>

                        <div id="containerResep" class="space-y-3">

                            <?php if (!empty($dataResepAktif)): ?>

                                <?php foreach ($dataResepAktif as $res): ?>

                                    <div class="resep-row grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">

                                        <div class="md:col-span-7">
                                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">
                                                Pilih Bahan Baku
                                            </label>

                                            <select name="resep_bahan[]"
                                                    class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium">

                                                <option value="">-- Pilih Bahan --</option>

                                                <?php foreach ($daftarBahan as $b): ?>
                                                    <option value="<?= (int) $b['id_bahan'] ?>"
                                                        <?= ((int) $b['id_bahan'] === (int) $res['id_bahan']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($b['nama_bahan']) ?>
                                                        (<?= htmlspecialchars($b['satuan']) ?>)
                                                    </option>
                                                <?php endforeach; ?>

                                            </select>
                                        </div>

                                        <div class="md:col-span-4">
                                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">
                                                Takaran / Porsi
                                            </label>

                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   name="resep_takaran[]"
                                                   value="<?= htmlspecialchars((string) $res['jumlah_pemakaian']) ?>"
                                                   placeholder="Contoh: 20"
                                                   class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium">
                                        </div>

                                        <div class="md:col-span-1 flex items-end justify-center pt-5">
                                            <button type="button"
                                                    onclick="hapusBarisResep(this)"
                                                    class="text-red-500 hover:text-red-700 p-2 font-bold">
                                                ×
                                            </button>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="resep-row grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">

                                    <div class="md:col-span-7">
                                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">
                                            Pilih Bahan Baku
                                        </label>

                                        <select name="resep_bahan[]"
                                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium">

                                            <option value="">-- Pilih Bahan --</option>

                                            <?php foreach ($daftarBahan as $b): ?>
                                                <option value="<?= (int) $b['id_bahan'] ?>">
                                                    <?= htmlspecialchars($b['nama_bahan']) ?>
                                                    (<?= htmlspecialchars($b['satuan']) ?>)
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="md:col-span-4">
                                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">
                                            Takaran / Porsi
                                        </label>

                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               name="resep_takaran[]"
                                               placeholder="Contoh: 20"
                                               class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium">
                                    </div>

                                    <div class="md:col-span-1 flex items-end justify-center pt-5">
                                        <button type="button"
                                                onclick="hapusBarisResep(this)"
                                                class="text-red-500 hover:text-red-700 p-2 font-bold">
                                            ×
                                        </button>
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- ====================================== -->
                    <!-- PENGATURAN KUSTOMISASI MENU -->
                    <!-- ====================================== -->

                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-5">

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">

                            <div>
                                <h4 class="font-bold text-slate-900 text-base">
                                    Pengaturan Kustomisasi Menu
                                </h4>

                                <p class="text-xs text-slate-500 mt-1">
                                    Atur pilihan tambahan yang dapat dipilih pelanggan melalui kasir.
                                </p>
                            </div>

                            <button type="button"
                                    onclick="tambahKelompokKustomisasi()"
                                    class="bg-brand-navy hover:bg-brand-lightNavy text-white px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap">
                                + Tambah Kelompok
                            </button>

                        </div>

                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                            <p class="text-xs text-blue-800 leading-relaxed">
                                <strong>Single:</strong> Kasir hanya dapat memilih satu pilihan dalam kelompok.
                                <br>
                                <strong>Multiple:</strong> Kasir dapat memilih beberapa pilihan sekaligus.
                                <br>
                                <strong>Wajib dipilih:</strong> Pelanggan harus memilih minimal satu pilihan sebelum pesanan ditambahkan.
                            </p>
                        </div>

                        <div id="containerKustomisasi" class="space-y-5"></div>

                        <div id="pesanKustomisasiKosong"
                             class="text-center border-2 border-dashed border-slate-300 rounded-xl p-8">

                            <p class="font-semibold text-slate-600 text-sm">
                                Belum ada kustomisasi menu
                            </p>

                            <p class="text-xs text-slate-400 mt-1">
                                Klik tombol Tambah Kelompok untuk membuat pilihan menu.
                            </p>

                        </div>

                    </div>

                    <!-- STATUS PRODUK -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Status Produk <span class="text-red-500">*</span>
                        </label>

                        <select name="status"
                                id="inputStatus"
                                onchange="updatePreview()"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700">

                            <option value="Aktif" <?= (($dataProduk['status'] ?? 'Aktif') === 'Aktif') ? 'selected' : '' ?>>
                                Aktif (Tersedia)
                            </option>

                            <option value="Non-Aktif" <?= (($dataProduk['status'] ?? '') === 'Non-Aktif') ? 'selected' : '' ?>>
                                Non-Aktif (Habis/Disembunyikan)
                            </option>

                        </select>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <div class="flex items-center space-x-4 pt-4 border-t border-slate-100">

                        <button type="submit"
                                class="bg-brand-navy hover:bg-brand-lightNavy text-white px-7 py-3.5 rounded-xl font-bold text-base shadow transition">
                            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' ?>
                        </button>

                        <a href="index.php?controller=menu&action=index"
                           class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-6 py-3.5 rounded-xl font-bold text-base transition text-center">
                            Batal
                        </a>

                    </div>

                </form>
            </div>

            <!-- ====================================== -->
            <!-- PRATINJAU PRODUK -->
            <!-- ====================================== -->

            <div class="lg:col-span-4 space-y-6">

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <h3 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3 mb-4">
                        Pratinjau Produk
                    </h3>

                    <div class="space-y-4">

                        <h4 id="prevNama"
                            class="text-xl font-extrabold text-brand-navy">
                            <?= htmlspecialchars((string) ($dataProduk['nama_menu'] ?? 'Nama Produk')) ?>
                        </h4>

                        <p id="prevInfoKat"
                           class="text-sm text-slate-500 font-medium">
                            <?= htmlspecialchars((string) ($dataProduk['kategori'] ?? 'Minuman')) ?> • Aktif
                        </p>

                        <div class="pt-3 border-t border-slate-100 space-y-1">

                            <p id="prevHarga" class="text-base font-bold text-slate-800">
                                Harga jual Rp 0
                            </p>

                            <p id="prevHpp" class="text-sm text-slate-600">
                                HPP Rp 0
                            </p>

                        </div>

                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <h3 class="font-bold text-slate-900 text-base mb-2">
                        Informasi Kustomisasi
                    </h3>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Pilihan kustomisasi yang dibuat di sini nantinya akan ditampilkan pada kasir ketika produk dipilih.
                        Harga tambahan akan dihitung bersama harga dasar produk saat transaksi.
                    </p>

                </div>

            </div>

        </div>
    </main>

    <!-- ====================================== -->
    <!-- JAVASCRIPT -->
    <!-- ====================================== -->

    <script>
        // Data kustomisasi dari database ketika produk diedit.
        const dataKustomisasiAwal = <?= json_encode(
            $daftarKustomisasiForm,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        // Mengamankan teks sebelum dimasukkan ke HTML.
        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function(char) {
                const entities = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };

                return entities[char];
            });
        }

        function nilaiBoolean(value) {
            return value === true ||
                   value === 1 ||
                   value === '1' ||
                   value === 't' ||
                   value === 'true';
        }

        // ======================================
        // RESEP
        // ======================================

        function tambahBarisResep() {
            const container = document.getElementById('containerResep');
            const barisPertama = container.querySelector('.resep-row');

            if (!barisPertama) return;

            const rowBaru = barisPertama.cloneNode(true);

            rowBaru.querySelector('select').value = '';
            rowBaru.querySelector('input').value = '';

            container.appendChild(rowBaru);
        }

        function hapusBarisResep(button) {
            const container = document.getElementById('containerResep');

            if (container.querySelectorAll('.resep-row').length > 1) {
                button.closest('.resep-row').remove();
            } else {
                alert('Minimal harus ada satu baris resep.');
            }
        }

        // ======================================
        // KELOMPOK KUSTOMISASI
        // ======================================

        function tambahKelompokKustomisasi(data = {}) {
            const container = document.getElementById('containerKustomisasi');

            const index = container.querySelectorAll('.kelompok-kustomisasi').length;

            const namaOpsi = escapeHtml(data.nama_opsi || '');
            const tipe = data.tipe_pilihan === 'multiple' ? 'multiple' : 'single';
            const wajib = nilaiBoolean(data.wajib_dipilih);

            const kelompok = document.createElement('div');

            kelompok.className = 'kelompok-kustomisasi bg-white border border-slate-200 rounded-xl p-5 shadow-sm';

            kelompok.innerHTML = `
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <h5 class="font-bold text-slate-900 text-sm">
                            Kelompok Kustomisasi
                        </h5>
                        <p class="text-xs text-slate-400 mt-1">
                            Atur nama dan jenis pilihan.
                        </p>
                    </div>

                    <button type="button"
                            onclick="hapusKelompokKustomisasi(this)"
                            class="text-red-500 hover:text-red-700 text-sm font-bold">
                        Hapus Kelompok
                    </button>
                </div>

                <div class="space-y-4">

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Nama Kelompok
                        </label>

                        <input type="text"
                               class="input-nama-kelompok w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm"
                               placeholder="Contoh: Level Gula"
                               value="${namaOpsi}"
                               required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">
                                Jenis Pilihan
                            </label>

                            <select class="input-tipe-kelompok w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm">
                                <option value="single" ${tipe === 'single' ? 'selected' : ''}>
                                    Single (Satu Pilihan)
                                </option>

                                <option value="multiple" ${tipe === 'multiple' ? 'selected' : ''}>
                                    Multiple (Banyak Pilihan)
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center pt-5">
                            <input type="hidden"
                                   class="input-wajib-hidden"
                                   value="0">

                            <input type="checkbox"
                                   class="input-wajib mr-2 h-4 w-4 accent-blue-700"
                                   value="1"
                                   ${wajib ? 'checked' : ''}>

                            <label class="text-sm font-medium text-slate-700">
                                Wajib dipilih
                            </label>
                        </div>

                    </div>

                    <div class="border-t border-slate-100 pt-4">

                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h6 class="font-bold text-slate-800 text-sm">
                                    Daftar Pilihan
                                </h6>
                                <p class="text-xs text-slate-400 mt-1">
                                    Tambahkan pilihan beserta harga tambahannya.
                                </p>
                            </div>

                            <button type="button"
                                    onclick="tambahPilihanKustomisasi(this)"
                                    class="bg-blue-50 hover:bg-blue-100 text-brand-navy px-3 py-2 rounded-lg text-xs font-bold">
                                + Tambah Pilihan
                            </button>
                        </div>

                        <div class="container-pilihan space-y-3"></div>

                    </div>

                </div>
            `;

            container.appendChild(kelompok);

            const daftarPilihan = Array.isArray(data.pilihan) ? data.pilihan : [];

            daftarPilihan.forEach(function(pilihan) {
                tambahPilihanKustomisasi(
                    kelompok.querySelector('button[onclick="tambahPilihanKustomisasi(this)"]'),
                    pilihan
                );
            });

            perbaruiNamaInputKustomisasi();
            perbaruiTampilanKosong();
        }

        function hapusKelompokKustomisasi(button) {
            const kelompok = button.closest('.kelompok-kustomisasi');

            if (confirm('Hapus kelompok kustomisasi ini beserta semua pilihannya?')) {
                kelompok.remove();

                perbaruiNamaInputKustomisasi();
                perbaruiTampilanKosong();
            }
        }

        // ======================================
        // PILIHAN KUSTOMISASI
        // ======================================

        function tambahPilihanKustomisasi(button, data = {}) {
            const kelompok = button.closest('.kelompok-kustomisasi');
            const container = kelompok.querySelector('.container-pilihan');

            const namaPilihan = escapeHtml(data.nama_pilihan || '');
            const hargaTambahan = Number(data.harga_tambahan || 0);

            const pilihan = document.createElement('div');

            pilihan.className = 'pilihan-kustomisasi grid grid-cols-1 md:grid-cols-12 gap-3 items-end bg-slate-50 border border-slate-100 p-3 rounded-lg';

            pilihan.innerHTML = `
                <div class="md:col-span-6">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">
                        Nama Pilihan
                    </label>

                    <input type="text"
                           class="input-nama-pilihan w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm"
                           placeholder="Contoh: Less Sugar"
                           value="${namaPilihan}"
                           required>
                </div>

                <div class="md:col-span-5">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">
                        Harga Tambahan (Rp)
                    </label>

                    <input type="number"
                           min="0"
                           step="500"
                           class="input-harga-pilihan w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm"
                           placeholder="0"
                           value="${hargaTambahan}">
                </div>

                <div class="md:col-span-1 flex justify-center pb-1">
                    <button type="button"
                            onclick="hapusPilihanKustomisasi(this)"
                            class="text-red-500 hover:text-red-700 text-xl font-bold">
                        ×
                    </button>
                </div>
            `;

            container.appendChild(pilihan);

            perbaruiNamaInputKustomisasi();
        }

        function hapusPilihanKustomisasi(button) {
            const pilihan = button.closest('.pilihan-kustomisasi');
            const container = pilihan.parentElement;

            pilihan.remove();

            perbaruiNamaInputKustomisasi();
        }

        // ======================================
        // PENAMAAN INPUT UNTUK DIKIRIM KE PHP
        // ======================================

        function perbaruiNamaInputKustomisasi() {
            const kelompok = document.querySelectorAll('.kelompok-kustomisasi');

            kelompok.forEach(function(itemKelompok, indexKelompok) {

                itemKelompok.querySelector('.input-nama-kelompok').name =
                    `kustomisasi[${indexKelompok}][nama_opsi]`;

                itemKelompok.querySelector('.input-tipe-kelompok').name =
                    `kustomisasi[${indexKelompok}][tipe_pilihan]`;

                itemKelompok.querySelector('.input-wajib-hidden').name =
                    `kustomisasi[${indexKelompok}][wajib_dipilih]`;

                itemKelompok.querySelector('.input-wajib').name =
                    `kustomisasi[${indexKelompok}][wajib_dipilih]`;

                itemKelompok.querySelectorAll('.pilihan-kustomisasi').forEach(function(itemPilihan, indexPilihan) {

                    itemPilihan.querySelector('.input-nama-pilihan').name =
                        `kustomisasi[${indexKelompok}][pilihan][${indexPilihan}][nama_pilihan]`;

                    itemPilihan.querySelector('.input-harga-pilihan').name =
                        `kustomisasi[${indexKelompok}][pilihan][${indexPilihan}][harga_tambahan]`;

                });

            });
        }

        function perbaruiTampilanKosong() {
            const container = document.getElementById('containerKustomisasi');
            const pesan = document.getElementById('pesanKustomisasiKosong');

            pesan.style.display =
                container.querySelectorAll('.kelompok-kustomisasi').length === 0
                    ? 'block'
                    : 'none';
        }

        // ======================================
        // LIVE PREVIEW
        // ======================================

        function updatePreview() {
            const nama = document.getElementById('inputNama').value.trim() || 'Nama Produk';
            const kategori = document.getElementById('inputKategori').value;
            const status = document.getElementById('inputStatus').value;
            const hpp = parseFloat(document.getElementById('inputHpp').value) || 0;
            const harga = parseFloat(document.getElementById('inputHarga').value) || 0;

            document.getElementById('prevNama').textContent = nama;

            document.getElementById('prevInfoKat').textContent =
                `${kategori} • ${status}`;

            document.getElementById('prevHarga').textContent =
                `Harga jual Rp ${harga.toLocaleString('id-ID')}`;

            document.getElementById('prevHpp').textContent =
                `HPP Rp ${hpp.toLocaleString('id-ID')}`;
        }

        // ======================================
        // INISIALISASI HALAMAN
        // ======================================

        document.addEventListener('DOMContentLoaded', function() {

            // Tampilkan data kustomisasi yang sudah tersimpan.
            if (Array.isArray(dataKustomisasiAwal)) {
                dataKustomisasiAwal.forEach(function(kelompok) {
                    tambahKelompokKustomisasi(kelompok);
                });
            }

            perbaruiNamaInputKustomisasi();
            perbaruiTampilanKosong();
            updatePreview();

            // Pastikan nama input selalu sesuai setelah form disubmit.
            const form = document.querySelector('form');

            form.addEventListener('submit', function() {
                perbaruiNamaInputKustomisasi();
            });

        });
    </script>

</body>
</html>
