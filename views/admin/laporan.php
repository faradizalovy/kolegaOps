
<?php
// views/owner/laporan.php
// Tampilan Laporan Finansial & Rekapitulasi Transaksi Keseluruhan Owner - KolegaOps POS Warkop
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Keseluruhan - KolegaOps Warkop Kolega</title>

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

<body class="bg-brand-bgLight text-slate-800 min-h-screen flex text-base relative">

  <!-- Sidebar Navigasi KolegaOps -->
  <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>

  <!-- Area Konten Utama -->
  <main class="flex-1 p-8 overflow-y-auto min-h-screen">

    <!-- Header Halaman -->
    <header class="flex items-center justify-between pb-6 border-b border-slate-200 mb-6 bg-white px-8 py-6 rounded-2xl shadow-sm">
      <div>
        <h2 class="text-3xl font-extrabold text-slate-900">
          Laporan Keseluruhan
        </h2>

        <p class="text-sm text-slate-500 mt-1">
          Rekapitulasi seluruh transaksi seharian penuh lintas shift pegawai
        </p>
      </div>

      <div class="text-xs font-semibold text-slate-400">
        Diperbarui <?= htmlspecialchars($waktuUpdate) ?> WIB
      </div>
    </header>

    <!-- 3 Kartu Statistik Headline -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

      <!-- Kartu 1: Total Transaksi -->
      <div class="bg-brand-navy text-white p-6 rounded-2xl shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold text-blue-200 uppercase tracking-wider">
          TOTAL TRANSAKSI
        </p>

        <div class="my-2">
          <h3 class="text-4xl font-extrabold">
            <?= (int) $jumlahTrxFilter ?>
            <span class="text-lg font-normal">Struk</span>
          </h3>
        </div>

        <p class="text-xs text-blue-200 truncate"
           title="<?= htmlspecialchars($filterTanggalTeks) ?> | <?= htmlspecialchars($filterShift) ?>">
          <?= htmlspecialchars($filterTanggalTeks) ?>
          &bull; Shift: <?= htmlspecialchars($filterShift) ?>
        </p>
      </div>

      <!-- Kartu 2: Total Omzet -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
          TOTAL PENJUALAN (OMZET)
        </p>

        <div class="my-2">
          <h3 class="text-3xl font-extrabold text-brand-navy">
            Rp <?= number_format($totalOmzetFilter, 0, ',', '.') ?>
          </h3>
        </div>

        <p class="text-xs text-slate-500">
          Total uang masuk kotor (Cash + QRIS)
        </p>
      </div>

      <!-- Kartu 3: Penjualan Bersih -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
          PENJUALAN &ndash; PENGELUARAN
        </p>

        <div class="my-2">
          <h3 class="text-3xl font-extrabold <?= $labaBersih >= 0 ? 'text-brand-navy' : 'text-red-600' ?>">
            Rp <?= number_format($labaBersih, 0, ',', '.') ?>
          </h3>
        </div>

        <p class="text-xs text-slate-500">
          Setelah beban operasional (Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?>)
        </p>
      </div>

    </div>

    <!-- Filter Bar Form -->
    <form method="GET"
          action="index.php"
          class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6">

      <input type="hidden" name="controller" value="laporan">
      <input type="hidden" name="action" value="laporanOwner">

      <div class="md:col-span-4 relative">
        <input
          type="text"
          id="searchTrx"
          placeholder="Cari nomor transaksi..."
          class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm"
        >
      </div>

      <div class="md:col-span-3">
        <input
          type="date"
          name="tanggal"
          value="<?= htmlspecialchars((string) $filterTanggal) ?>"
          class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700 font-medium cursor-pointer"
        >
      </div>

      <div class="md:col-span-2">
        <select
          name="shift"
          class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-brand-skyBlue shadow-sm text-slate-700 font-medium">

          <option value="Semua" <?= $filterShift === 'Semua' ? 'selected' : '' ?>>
            Semua Shift
          </option>

          <option value="Pagi" <?= $filterShift === 'Pagi' ? 'selected' : '' ?>>
            Shift Pagi
          </option>

          <option value="Sore" <?= $filterShift === 'Sore' ? 'selected' : '' ?>>
            Shift Sore
          </option>

          <option value="Malam" <?= $filterShift === 'Malam' ? 'selected' : '' ?>>
            Shift Malam
          </option>
        </select>
      </div>

      <div class="md:col-span-1">
        <button
          type="submit"
          class="w-full bg-brand-navy hover:bg-brand-lightNavy text-white py-3.5 rounded-xl font-bold text-sm shadow transition cursor-pointer">
          Terapkan
        </button>
      </div>

      <div class="md:col-span-2 text-right">
        <button
          type="button"
          onclick="cetakLaporanExcel()"
          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-4 rounded-xl font-bold text-sm shadow transition cursor-pointer flex items-center justify-center space-x-2">

          <svg xmlns="http://www.w3.org/2000/svg"
               width="24"
               height="24"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="2"
               stroke-linecap="round"
               stroke-linejoin="round">

            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/>
            <rect x="6" y="14" width="12" height="8" rx="1"/>
          </svg>

          <span>Cetak Laporan</span>
        </button>
      </div>

    </form>

    <!-- Rekapitulasi Shift dan Pengeluaran -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

      <!-- Tabel Rekapitulasi Pembayaran -->
      <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h3 class="font-bold text-slate-900 text-base">
            Rekapitulasi Penjualan
          </h3>

          <span class="text-xs text-slate-500 font-semibold">
            Filter Shift: <?= htmlspecialchars($filterShift) ?>
          </span>
        </div>

        <table class="w-full text-left text-sm">
          <thead class="bg-brand-darkNavy text-white">
            <tr>
              <th class="p-3.5 pl-6">KOMPONEN</th>
              <th class="p-3.5 pr-6 text-right">NOMINAL</th>
            </tr>
          </thead>

          <tbody class="font-medium text-slate-800 divide-y divide-slate-100">

            <tr class="bg-white">
              <td class="p-3.5 pl-6 text-brand-navy font-bold">
                Total Cash Masuk (Tunai)
              </td>

              <td class="p-3.5 pr-6 text-right font-bold">
                Rp <?= number_format($totalCash, 0, ',', '.') ?>
              </td>
            </tr>

            <tr class="bg-blue-100/70">
              <td class="p-3.5 pl-6 text-brand-navy font-bold">
                Total QRIS Masuk
              </td>

              <td class="p-3.5 pr-6 text-right font-bold">
                Rp <?= number_format($totalQris, 0, ',', '.') ?>
              </td>
            </tr>

            <tr class="bg-slate-50 font-bold">
              <td class="p-3.5 pl-6 text-slate-900">
                Total Omzet Keseluruhan
              </td>

              <td class="p-3.5 pr-6 text-right text-brand-navy">
                Rp <?= number_format($totalOmzetFilter, 0, ',', '.') ?>
              </td>
            </tr>

          </tbody>
        </table>
      </div>

      <!-- Tabel Pengeluaran Operasional -->
      <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">

        <div>
          <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-900 text-base">
              Pengeluaran Operasional
            </h3>

            <span class="text-xs text-slate-400 font-semibold">
              <?= count($pengeluaranList) ?> pos biaya
            </span>
          </div>

          <table class="w-full text-left text-sm">
            <thead class="bg-brand-darkNavy text-white">
              <tr>
                <th class="p-3.5 pl-6">KEPERLUAN</th>
                <th class="p-3.5 pr-6 text-right">NOMINAL</th>
              </tr>
            </thead>

            <tbody class="font-medium text-slate-800 divide-y divide-slate-100">

              <?php if (empty($pengeluaranList)): ?>

                <tr>
                  <td colspan="2" class="p-4 pl-6 text-slate-400 italic text-center">
                    Belum ada catatan pengeluaran.
                  </td>
                </tr>

              <?php else: ?>

                <?php foreach ($pengeluaranList as $idx => $p):
                  $bgRow = ($idx % 2 === 1) ? 'bg-blue-100/70' : 'bg-white';
                ?>

                  <tr class="<?= $bgRow ?>">
                    <td class="p-3.5 pl-6 text-slate-700">
                      <?= htmlspecialchars($p['keterangan']) ?>

                      <?php if (!empty($p['nama_shift'])): ?>
                        <span class="text-[10px] text-slate-400 font-normal">
                          (<?= htmlspecialchars($p['nama_shift']) ?>)
                        </span>
                      <?php endif; ?>
                    </td>

                    <td class="p-3.5 pr-6 text-right font-medium">
                      Rp <?= number_format((float) $p['nominal'], 0, ',', '.') ?>
                    </td>
                  </tr>

                <?php endforeach; ?>

              <?php endif; ?>

            </tbody>
          </table>
        </div>

        <div class="p-3.5 px-6 bg-slate-50 border-t border-slate-100 flex justify-between items-center font-bold text-sm">
          <span class="text-slate-900">Total Pengeluaran</span>

          <span class="text-red-600">
            Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?>
          </span>
        </div>

      </div>

    </div>

    <!-- Tabel Riwayat Transaksi Keseluruhan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">

      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <h3 class="font-bold text-slate-900 text-base">
          Riwayat Transaksi Keseluruhan
        </h3>

        <span class="text-xs text-slate-400 font-semibold">
          Klik "Detail / Struk" untuk melihat rincian item
        </span>
      </div>

      <div class="overflow-x-auto">

        <table class="w-full text-left text-sm" id="tabelRiwayat">

          <thead class="bg-brand-darkNavy text-white">
            <tr>
              <th class="p-4 pl-6">NOMOR TRANSAKSI</th>
              <th class="p-4">WAKTU & TANGGAL</th>
              <th class="p-4">SHIFT & PEGAWAI</th>
              <th class="p-4">METODE</th>
              <th class="p-4">TOTAL</th>
              <th class="p-4">STATUS</th>
              <th class="p-4 pr-6 text-right">AKSI</th>
            </tr>
          </thead>

          <tbody class="font-medium text-slate-800 divide-y divide-slate-100">

            <?php if (!empty($filteredTransaksi)): ?>

              <?php foreach ($filteredTransaksi as $index => $trx):
                $bgRow = ($index % 2 === 1) ? 'bg-blue-100/70' : 'bg-white';
              ?>

                <tr class="trx-row <?= $bgRow ?> hover:bg-blue-50/50 transition">

                  <td class="p-4 pl-6 font-bold text-brand-navy">
                    <?= htmlspecialchars($trx['id']) ?>
                  </td>

                  <td class="p-4 text-slate-600">
                    <?= htmlspecialchars($trx['waktu']) ?>
                    &bull;

                    <span class="text-xs text-slate-500 font-semibold">
                      <?= htmlspecialchars($trx['tgl_teks']) ?>
                    </span>
                  </td>

                  <td class="p-4 text-slate-700 font-semibold">
                    <?= htmlspecialchars($trx['shift']) ?>
                  </td>

                  <td class="p-4 text-slate-600">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold <?= $trx['metode'] === 'QRIS' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-800' ?>">
                      <?= htmlspecialchars($trx['metode']) ?>
                    </span>
                  </td>

                  <td class="p-4 font-bold text-emerald-600">
                    Rp <?= number_format($trx['total'], 0, ',', '.') ?>
                  </td>

                  <td class="p-4">
                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-xs inline-block">
                      <?= htmlspecialchars($trx['status']) ?>
                    </span>
                  </td>

                  <td class="p-4 pr-6 text-right">
                    <button
                      type="button"
                      onclick='bukaStruk(<?= json_encode($trx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                      class="text-brand-navy font-bold hover:underline bg-transparent border-none cursor-pointer inline-flex items-center space-x-1">

                      <span>Detail / Struk</span>
                      <span>&rarr;</span>
                    </button>
                  </td>

                </tr>

              <?php endforeach; ?>

            <?php else: ?>

              <tr>
                <td colspan="7" class="p-8 text-center text-slate-400 font-medium italic">
                  Tidak ada data transaksi untuk tanggal atau shift yang dipilih.
                </td>
              </tr>

            <?php endif; ?>

          </tbody>

        </table>

      </div>

      <!-- Footer Pagination -->
      <div id="paginationInfo"
           class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-slate-500 font-medium">

        <span id="jumlahTransaksi">
          Menampilkan seluruh transaksi
        </span>

        <div id="paginationButtons" class="flex flex-wrap items-center justify-center gap-2">
        </div>

      </div>

    </div>

  </main>

  <!-- Modal Detail Struk Transaksi -->
  <div id="modalStruk"
       class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-150">

      <div class="bg-brand-darkNavy text-white px-6 py-4 flex justify-between items-center">

        <div>
          <h3 class="font-extrabold text-base">
            Struk Transaksi Warkop Kolega
          </h3>

          <p id="modalId" class="text-xs text-blue-300 font-mono mt-0.5"></p>
        </div>

        <button
          type="button"
          onclick="tutupStruk()"
          class="text-slate-300 hover:text-white font-bold text-2xl leading-none cursor-pointer focus:outline-none">
          &times;
        </button>

      </div>

      <div class="p-6 space-y-4">

        <div class="border-b border-dashed border-slate-300 pb-3 text-xs text-slate-500 space-y-1.5">

          <div class="flex justify-between">
            <span>Waktu:</span>
            <span id="modalWaktu" class="font-semibold text-slate-800"></span>
          </div>

          <div class="flex justify-between">
            <span>Shift / Pegawai:</span>
            <span id="modalShift" class="font-semibold text-slate-800"></span>
          </div>

          <div class="flex justify-between">
            <span>Metode Bayar:</span>
            <span id="modalMetode" class="font-semibold text-slate-800"></span>
          </div>

          <div class="flex justify-between items-center">
            <span>Status:</span>

            <span id="modalStatus"
                  class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-[11px]">
            </span>
          </div>

        </div>

        <div>
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
            Rincian Item Pembelian
          </h4>

          <div id="modalItemsList" class="space-y-2 max-h-48 overflow-y-auto pr-1"></div>
        </div>

        <div class="border-t border-slate-200 pt-3 flex justify-between items-center">
          <span class="font-bold text-slate-700 text-sm">
            TOTAL PEMBAYARAN
          </span>

          <span id="modalTotal" class="font-extrabold text-brand-navy text-xl"></span>
        </div>

      </div>

      <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end space-x-3">

        <button
          type="button"
          onclick="tutupStruk()"
          class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-5 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer">
          Tutup
        </button>

        <button
          type="button"
          onclick="window.print()"
          class="bg-brand-navy hover:bg-brand-lightNavy text-white px-5 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer">
          Cetak Struk
        </button>

      </div>

    </div>

  </div>

  <!-- JavaScript Pagination, Pencarian, Modal, dan Ekspor Excel -->
  <script>

    // =====================================================
    // 1. PAGINATION DAN PENCARIAN TRANSAKSI
    // =====================================================

    const searchTrx = document.getElementById('searchTrx');
    const tabelRiwayat = document.getElementById('tabelRiwayat');

    const semuaBaris = Array.from(
      tabelRiwayat.querySelectorAll('.trx-row')
    );

    const jumlahPerHalaman = 10;

    let halamanAktif = 1;
    let transaksiTampil = [...semuaBaris];

    const jumlahTransaksi = document.getElementById('jumlahTransaksi');
    const paginationButtons = document.getElementById('paginationButtons');

    // Menampilkan transaksi sesuai halaman aktif
    function tampilkanTransaksi() {

      const totalTransaksi = transaksiTampil.length;

      const totalHalaman = Math.ceil(
        totalTransaksi / jumlahPerHalaman
      );

      // Menjaga nomor halaman tetap valid
      if (totalHalaman === 0) {
        halamanAktif = 1;
      } else if (halamanAktif > totalHalaman) {
        halamanAktif = totalHalaman;
      }

      // Sembunyikan semua transaksi
      semuaBaris.forEach(function(row) {
        row.style.display = 'none';
      });

      // Menghitung batas transaksi yang ditampilkan
      const awal = (halamanAktif - 1) * jumlahPerHalaman;

      const akhir = Math.min(
        awal + jumlahPerHalaman,
        totalTransaksi
      );

      // Menampilkan maksimal 10 transaksi
      for (let i = awal; i < akhir; i++) {
        transaksiTampil[i].style.display = '';
      }

      // Memperbarui informasi transaksi
      if (totalTransaksi === 0) {

        jumlahTransaksi.innerText =
          'Tidak ada transaksi yang ditemukan.';

      } else {

        jumlahTransaksi.innerText =
          'Menampilkan ' + (awal + 1) +
          '–' + akhir +
          ' dari ' + totalTransaksi + ' transaksi';

      }

      // Mengosongkan tombol pagination sebelumnya
      paginationButtons.innerHTML = '';

      // Tidak menampilkan tombol jika hanya ada satu halaman
      if (totalHalaman <= 1) {
        return;
      }

      // Tombol halaman sebelumnya
      const tombolSebelumnya = document.createElement('button');

      tombolSebelumnya.type = 'button';
      tombolSebelumnya.innerText = '← Sebelumnya';

      tombolSebelumnya.disabled = halamanAktif === 1;

      tombolSebelumnya.className =
        'px-3 py-2 rounded-lg border border-slate-200 font-semibold text-xs ' +
        (
          halamanAktif === 1
            ? 'text-slate-300 cursor-not-allowed'
            : 'text-brand-navy hover:bg-blue-50'
        );

      tombolSebelumnya.addEventListener('click', function() {

        if (halamanAktif > 1) {

          halamanAktif--;

          tampilkanTransaksi();

        }

      });

      paginationButtons.appendChild(tombolSebelumnya);

      // Tombol nomor halaman
      for (let i = 1; i <= totalHalaman; i++) {

        const tombolHalaman = document.createElement('button');

        tombolHalaman.type = 'button';
        tombolHalaman.innerText = i;

        tombolHalaman.className =
          'w-9 h-9 rounded-lg font-bold text-xs transition ' +
          (
            i === halamanAktif
              ? 'bg-brand-navy text-white'
              : 'border border-slate-200 text-slate-600 hover:bg-blue-50'
          );

        tombolHalaman.addEventListener('click', function() {

          halamanAktif = i;

          tampilkanTransaksi();

        });

        paginationButtons.appendChild(tombolHalaman);

      }

      // Tombol halaman selanjutnya
      const tombolSelanjutnya = document.createElement('button');

      tombolSelanjutnya.type = 'button';
      tombolSelanjutnya.innerText = 'Selanjutnya →';

      tombolSelanjutnya.disabled =
        halamanAktif === totalHalaman;

      tombolSelanjutnya.className =
        'px-3 py-2 rounded-lg border border-slate-200 font-semibold text-xs ' +
        (
          halamanAktif === totalHalaman
            ? 'text-slate-300 cursor-not-allowed'
            : 'text-brand-navy hover:bg-blue-50'
        );

      tombolSelanjutnya.addEventListener('click', function() {

        if (halamanAktif < totalHalaman) {

          halamanAktif++;

          tampilkanTransaksi();

        }

      });

      paginationButtons.appendChild(tombolSelanjutnya);

    }

    // Pencarian real-time nomor transaksi
    searchTrx.addEventListener('input', function() {

      const keyword = this.value.toLowerCase().trim();

      transaksiTampil = semuaBaris.filter(function(row) {

        return row.innerText.toLowerCase().includes(keyword);

      });

      // Kembali ke halaman pertama setelah pencarian
      halamanAktif = 1;

      tampilkanTransaksi();

    });

    // Tampilkan halaman pertama saat laporan dibuka
    tampilkanTransaksi();


    // =====================================================
    // 2. FUNGSI MEMBUKA MODAL DETAIL STRUK
    // =====================================================

    function bukaStruk(trx) {

      document.getElementById('modalId').innerText = trx.id;

      document.getElementById('modalWaktu').innerText =
        trx.waktu + ' • ' + trx.tgl_teks;

      document.getElementById('modalShift').innerText =
        trx.shift;

      document.getElementById('modalMetode').innerText =
        trx.metode;

      document.getElementById('modalStatus').innerText =
        trx.status;

      document.getElementById('modalTotal').innerText =
        'Rp ' + Number(trx.total).toLocaleString('id-ID');

      const listContainer =
        document.getElementById('modalItemsList');

      listContainer.innerHTML = '';

      if (trx.items && trx.items.length > 0) {

        for (let i = 0; i < trx.items.length; i++) {

          const item = trx.items[i];

          const row = document.createElement('div');

          row.className =
            'flex justify-between items-center text-sm py-1.5 border-b border-slate-100';

          const namaItem = document.createElement('span');

          namaItem.className = 'text-slate-700 font-medium';

          namaItem.appendChild(
            document.createTextNode(item.nama + ' ')
          );

          const qty = document.createElement('span');

          qty.className = 'text-xs text-slate-400';

          qty.textContent = '(' + item.qty + 'x)';

          namaItem.appendChild(qty);

          const subtotal = document.createElement('span');

          subtotal.className = 'text-slate-900 font-bold';

          subtotal.textContent =
            'Rp ' + Number(item.subtotal).toLocaleString('id-ID');

          row.appendChild(namaItem);
          row.appendChild(subtotal);

          listContainer.appendChild(row);

        }

      } else {

        listContainer.innerHTML =
          '<p class="text-xs text-slate-400 italic py-2">' +
          'Tidak ada rincian item pada transaksi ini.' +
          '</p>';

      }

      const modal = document.getElementById('modalStruk');

      modal.classList.remove('hidden');
      modal.classList.add('flex');

    }


    // =====================================================
    // 3. FUNGSI MENUTUP MODAL DETAIL STRUK
    // =====================================================

    function tutupStruk() {

      const modal = document.getElementById('modalStruk');

      modal.classList.remove('flex');
      modal.classList.add('hidden');

    }

    // Tutup modal menggunakan tombol Escape
    window.addEventListener('keydown', function(e) {

      if (e.key === 'Escape') {
        tutupStruk();
      }

    });

    // Tutup modal jika bagian luar modal diklik
    document.getElementById('modalStruk').addEventListener('click', function(e) {

      if (e.target === this) {
        tutupStruk();
      }

    });


    // =====================================================
    // 4. FUNGSI EKSPOR SELURUH DATA RIWAYAT KE EXCEL
    // =====================================================

    function cetakLaporanExcel() {

      const table = document.getElementById('tabelRiwayat');

      if (!table) {
        return;
      }

      // Salin tabel agar tampilan asli tidak berubah
      const cloneTable = table.cloneNode(true);

      const semuaBarisClone =
        cloneTable.querySelectorAll('.trx-row');

      // Hapus baris yang tidak sesuai pencarian
      semuaBarisClone.forEach(function(row, index) {

        const barisAsli = semuaBaris[index];

        const masihDitampilkan = transaksiTampil.includes(barisAsli);

        if (!masihDitampilkan) {
          row.remove();
        }

      });

      // Hilangkan atribut display none dari data hasil pencarian
      cloneTable.querySelectorAll('.trx-row').forEach(function(row) {
        row.style.display = '';
      });

      const html = cloneTable.outerHTML;

      const url =
        'data:application/vnd.ms-excel;charset=utf-8,' +
        encodeURIComponent(html);

      const downloadLink = document.createElement('a');

      downloadLink.href = url;

      downloadLink.download =
        'Laporan-Warkop-Kolega-' +
        new Date().toISOString().slice(0, 10) +
        '.xls';

      document.body.appendChild(downloadLink);

      downloadLink.click();

      document.body.removeChild(downloadLink);

    }

  </script>

</body>
</html>
