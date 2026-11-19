<?php
// views/owner/form-produk.php
// Form Tambah & Edit Produk / Menu Owner - KolegaOps POS Warkop
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
    }
  </script>
</head>
<body class="bg-brand-bgLight text-slate-800 min-h-screen flex text-base">

  <!-- Sidebar Navigasi KolegaOps -->
  <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>

  <!-- Area Konten Utama -->
  <main class="flex-1 p-8 overflow-y-auto min-h-screen">
    
    <!-- Header Halaman -->
    <header class="flex items-center justify-between pb-5 border-b border-slate-200 mb-8 bg-white px-8 py-6 rounded-2xl shadow-sm">
      <div>
        <h2 class="text-3xl font-extrabold text-slate-900"><?= $isEdit ? 'Edit Produk' : 'Tambah Produk Baru' ?></h2>
        <p class="text-sm text-slate-500 mt-1">
          Produk / <?= $isEdit ? 'Perbarui data menu & resep' : 'Draft belum disimpan' ?>
        </p>
      </div>
      <a href="index.php?controller=menu&action=index" 
         class="text-slate-600 hover:text-brand-navy font-semibold text-sm flex items-center space-x-1 transition">
        <span>←</span>
        <span>Kembali ke Daftar</span>
      </a>
    </header>

    <!-- Notifikasi Flash Error jika ada -->
    <?php if (!empty($pesanError)): ?>
      <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-sm flex items-center space-x-3">
        <span>⚠️</span>
        <span><?= htmlspecialchars($pesanError) ?></span>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Kolom Kiri: Form Input Data Produk & Resep -->
      <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <h3 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-4 mb-6">
          <?= $isEdit ? 'Informasi Produk & Komposisi' : 'Informasi Produk Baru & Komposisi' ?>
        </h3>
        
        <form action="index.php?controller=menu&action=<?= $isEdit ? 'edit&id=' . (int) ($dataProduk['id_menu'] ?? $dataProduk['id'] ?? 0) : 'tambah' ?>" 
              method="POST" class="space-y-6">
          
          <!-- CSRF Token Security -->
          <?php if (function_exists('csrf_token')): ?>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <?php endif; ?>

          <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) ($dataProduk['id_menu'] ?? $dataProduk['id'] ?? 0) ?>">
          <?php endif; ?>
          
          <!-- Input Nama Produk -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Nama Produk <span class="text-red-500">*</span>
            </label>
            <input type="text" name="nama" id="inputNama" 
                   value="<?= htmlspecialchars((string) ($dataProduk['nama_menu'] ?? $dataProduk['nama'] ?? '')) ?>" 
                   placeholder="Contoh: Kopi Susu Tradisional" 
                   oninput="updatePreview()" 
                   class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm" 
                   required>
          </div>

          <!-- Kategori & Satuan Jual -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Kategori <span class="text-red-500">*</span>
              </label>
              <select name="kategori" id="inputKategori" onchange="updatePreview()" 
                      class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700">
                <option value="Kopi" <?= (($dataProduk['kategori'] ?? '') === 'Kopi') ? 'selected' : '' ?>>Kopi</option>
                <option value="Minuman" <?= (($dataProduk['kategori'] ?? '') === 'Minuman') ? 'selected' : '' ?>>Minuman</option>
                <option value="Makanan" <?= (($dataProduk['kategori'] ?? '') === 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                <option value="Camilan" <?= (($dataProduk['kategori'] ?? '') === 'Camilan') ? 'selected' : '' ?>>Camilan</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Satuan Jual</label>
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

          <!-- HPP & Harga Jual -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                HPP per Unit (Rp) <span class="text-red-500">*</span>
              </label>
              <input type="number" name="hpp" id="inputHpp" 
                     value="<?= isset($dataProduk['hpp']) ? (float) $dataProduk['hpp'] : ($hppAwal ?? '') ?>" 
                     placeholder="Contoh: 3500" min="0" step="100" oninput="updatePreview()" 
                     class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Harga Jual (Rp) <span class="text-red-500">*</span>
              </label>
              <input type="number" name="harga" id="inputHarga" 
                     value="<?= isset($dataProduk['harga']) ? (float) $dataProduk['harga'] : ($hargaAwal ?? '') ?>" 
                     placeholder="Contoh: 8000" min="0" step="100" oninput="updatePreview()" 
                     class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm" required>
            </div>
          </div>

          <!-- KOMPOSISI RESEP & BAHAN BAKU DINAMIS -->
          <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div>
                <h4 class="font-bold text-slate-900 text-base">Komposisi Resep & Bahan Baku</h4>
                <p class="text-xs text-slate-500 mt-0.5">Tentukan bahan baku yang terpotong otomatis saat menu ini terjual.</p>
              </div>
              <button type="button" onclick="tambahBarisResep()" 
                      class="bg-brand-navy hover:bg-brand-lightNavy text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                + Tambah Bahan
              </button>
            </div>

            <div id="containerResep" class="space-y-3">
              <?php if (!empty($dataResepAktif)): ?>
                <?php foreach ($dataResepAktif as $res): ?>
                  <div class="resep-row grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <div class="md:col-span-7">
                      <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Pilih Bahan Baku</label>
                      <select name="resep_bahan[]" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-brand-skyBlue text-slate-700">
                        <option value="">-- Pilih Bahan --</option>
                        <?php foreach ($daftarBahan as $b): ?>
                          <option value="<?= $b['id_bahan']; ?>" <?= ($b['id_bahan'] == $res['id_bahan']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nama_bahan']); ?> (<?= $b['satuan']; ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="md:col-span-4">
                      <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Takaran / Porsi</label>
                      <input type="number" step="0.01" name="resep_takaran[]" value="<?= $res['jumlah_pemakaian']; ?>" placeholder="Contoh: 20" 
                             class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-brand-skyBlue">
                    </div>
                    <div class="md:col-span-1 flex items-end justify-center pt-5">
                      <button type="button" onclick="hapusBarisResep(this)" class="text-red-500 hover:text-red-700 p-2 font-bold transition cursor-pointer" title="Hapus Baris">×</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="resep-row grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                  <div class="md:col-span-7">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Pilih Bahan Baku</label>
                    <select name="resep_bahan[]" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-brand-skyBlue text-slate-700">
                      <option value="">-- Pilih Bahan --</option>
                      <?php if (!empty($daftarBahan)): ?>
                        <?php foreach ($daftarBahan as $b): ?>
                          <option value="<?= $b['id_bahan']; ?>"><?= htmlspecialchars($b['nama_bahan']); ?> (<?= $b['satuan']; ?>)</option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </div>
                  <div class="md:col-span-4">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Takaran / Porsi</label>
                    <input type="number" step="0.01" name="resep_takaran[]" placeholder="Contoh: 20" 
                           class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-brand-skyBlue">
                  </div>
                  <div class="md:col-span-1 flex items-end justify-center pt-5">
                    <button type="button" onclick="hapusBarisResep(this)" class="text-red-500 hover:text-red-700 p-2 font-bold transition cursor-pointer" title="Hapus Baris">×</button>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Status Produk -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Status Produk <span class="text-red-500">*</span>
            </label>
            <select name="status" id="inputStatus" onchange="updatePreview()" 
                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-base font-medium focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700">
              <option value="Aktif" <?= (($dataProduk['status'] ?? 'Aktif') === 'Aktif') ? 'selected' : '' ?>>Aktif (Tersedia)</option>
              <option value="Non-Aktif" <?= (($dataProduk['status'] ?? '') === 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif (Habis/Disembunyikan)</option>
            </select>
          </div>

          <!-- Tombol Aksi Simpan / Batal -->
          <div class="flex items-center space-x-4 pt-4 border-t border-slate-100">
            <button type="submit" class="bg-brand-navy hover:bg-brand-lightNavy text-white px-7 py-3.5 rounded-xl font-bold text-base shadow transition cursor-pointer">
              <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' ?>
            </button>
            <a href="index.php?controller=menu&action=index" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-6 py-3.5 rounded-xl font-bold text-base transition text-center inline-block">
              Batal
            </a>
          </div>

        </form>
      </div>

      <!-- Kolom Kanan: Pratinjau Real-Time -->
      <div class="lg:col-span-4 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h3 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3 mb-4">Pratinjau Produk</h3>
          <div class="space-y-4">
            <h4 id="prevNama" class="text-xl font-extrabold text-brand-navy"><?= htmlspecialchars((string) ($dataProduk['nama_menu'] ?? 'Nama Produk')) ?></h4>
            <p id="prevInfoKat" class="text-sm text-slate-500 font-medium"><?= htmlspecialchars((string) ($dataProduk['kategori'] ?? 'Minuman')) ?> • Aktif</p>
            <div class="pt-3 border-t border-slate-100 space-y-1">
              <p id="prevHarga" class="text-base font-bold text-slate-800">Harga jual Rp 0</p>
              <p id="prevHpp" class="text-sm text-slate-600">HPP Rp 0</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Skrip Dinamis Baris Resep & Live Preview -->
  <script>
    function tambahBarisResep() {
      const container = document.getElementById('containerResep');
      const rowBaru = container.children[0].cloneNode(true);
      rowBaru.querySelector('select').value = '';
      rowBaru.querySelector('input').value = '';
      container.appendChild(rowBaru);
    }

    function hapusBarisResep(button) {
      const container = document.getElementById('containerResep');
      if (container.children.length > 1) {
        button.closest('.resep-row').remove();
      } else {
        alert('Minimal harus ada 1 bahan resep.');
      }
    }

    function updatePreview() {
      const nama = document.getElementById('inputNama').value.trim() || 'Nama Produk';
      const kategori = document.getElementById('inputKategori').value;
      const status = document.getElementById('inputStatus').value;
      const hpp = parseFloat(document.getElementById('inputHpp').value) || 0;
      const harga = parseFloat(document.getElementById('inputHarga').value) || 0;
      
      document.getElementById('prevNama').textContent = nama;
      document.getElementById('prevInfoKat').textContent = `${kategori} • ${status}`;
      document.getElementById('prevHarga').textContent = `Harga jual Rp ${harga.toLocaleString('id-ID')}`;
      document.getElementById('prevHpp').textContent = `HPP Rp ${hpp.toLocaleString('id-ID')}`;
    }
  </script>
</body>
</html>