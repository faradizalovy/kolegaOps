
<?php
// views/pegawai/dashboard.php
// Dashboard Pegawai & Kasir KolegaOps

$shiftAda = !empty($shiftAktif);
$shiftBerjalan = $shiftAda && ($shiftAktif['status'] ?? '') === 'buka';

$namaShift = $shiftAktif['nama_shift'] ?? 'Belum Ada Shift';

$namaPembukaShift = ucfirst(
    (string) ($shiftAktif['nama_kasir'] ?? 'Kasir')
);

$jumlahStokKritis = is_array($stokKritis ?? null)
    ? count($stokKritis)
    : 0;

$idShift = $shiftBerjalan
    ? (int) ($shiftAktif['id_shift'] ?? 0)
    : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Pegawai - KolegaOps Warkop Kolega</title>
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

<body class="bg-brand-bgLight text-slate-800 min-h-screen flex text-sm">

  <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>

  <main class="flex-1 min-w-0 p-8 overflow-y-auto min-h-screen">

    <!-- Header -->
    <header class="flex items-center justify-between pb-5 border-b border-slate-200 mb-6 bg-white px-8 py-5 rounded-2xl shadow-sm">
      <div>
        <h2 class="text-2xl font-extrabold text-slate-900">Dashboard</h2>
        <p class="text-xs text-slate-500 mt-1">
          Ringkasan hari ini &bull; <?= htmlspecialchars($tanggalTeks) ?>
        </p>
      </div>

      <div class="text-xs font-bold text-slate-500">
        Diperbarui secara real-time
      </div>
    </header>

    <!-- Notifikasi -->
    <?php if (!empty($pesanSukses)): ?>
      <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-xs">
        <?= htmlspecialchars($pesanSukses) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($pesanError)): ?>
      <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-xs">
        <?= htmlspecialchars($pesanError) ?>
      </div>
    <?php endif; ?>

    <!-- Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

      <!-- Penjualan Hari Ini -->
      <div class="bg-brand-navy text-white p-6 rounded-2xl shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold text-blue-200 uppercase tracking-wider">
          PENJUALAN HARI INI
        </p>

        <div class="my-3">
          <h3 class="text-3xl font-extrabold">
            Rp <?= number_format((float) ($penjualanHariIni['total_penjualan'] ?? 0), 0, ',', '.') ?>
          </h3>
        </div>

        <p class="text-xs text-blue-200">
          Seluruh kasir &bull;
          <?= (int) ($penjualanHariIni['jumlah_transaksi'] ?? 0) ?>
          transaksi lunas
        </p>
      </div>

      <!-- Penjualan Shift Kasir -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
          PENJUALAN SHIFT SAYA
        </p>

        <div class="my-2">
          <h3 class="text-3xl font-extrabold text-brand-navy">
            Rp <?= number_format((float) ($penjualanShiftSaya['total_penjualan'] ?? 0), 0, ',', '.') ?>
          </h3>
        </div>

        <p class="text-xs text-slate-500">
          <?= htmlspecialchars($namaKasir) ?> &bull;
          <?= (int) ($penjualanShiftSaya['jumlah_transaksi'] ?? 0) ?>
          transaksi lunas
        </p>
      </div>

      <!-- Stok Kritis -->
      <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-6 rounded-2xl border border-orange-200 shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold text-orange-700 uppercase tracking-wider">
          STOK KRITIS PERHATIAN
        </p>

        <div class="my-2">
          <h3 class="text-3xl font-extrabold text-orange-800">
            <?= $jumlahStokKritis ?> bahan
          </h3>
        </div>

        <p class="text-xs text-orange-600 font-medium">
          Segera koordinasikan restock bahan
        </p>
      </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

      <!-- Kolom Kiri -->
      <div class="lg:col-span-7 space-y-6">

        <!-- Status Shift Bersama -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">

          <?php if ($shiftBerjalan): ?>

            <div class="flex flex-wrap items-center justify-between gap-4">

              <div>
                <div class="flex flex-wrap items-center gap-3">
                  <h3 class="font-extrabold text-slate-900 text-base">
                    Shift <?= htmlspecialchars($namaShift) ?>
                  </h3>

                  <span class="bg-emerald-100 text-emerald-700 font-bold px-3 py-1.5 rounded-full text-xs">
                    Aktif Berjalan
                  </span>
                </div>

                <p class="text-xs text-slate-600 mt-2 font-medium">
                  Dibuka oleh <?= htmlspecialchars($namaPembukaShift) ?>
                </p>

                <p class="text-xs text-slate-500 mt-1">
                  Semua kasir dapat bertugas pada shift ini.
                </p>

                <?php if (!empty($shiftAktif['waktu_buka'])): ?>
                  <p class="text-xs text-slate-400 mt-1">
                    Dibuka pukul
                    <?= htmlspecialchars(date('H:i', strtotime($shiftAktif['waktu_buka']))) ?>
                  </p>
                <?php endif; ?>
              </div>

              <button
                type="button"
                onclick="bukaModalKonfirmasiTutup()"
                class="rounded-xl bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 text-xs font-bold shadow transition">
                Tutup Shift
              </button>

            </div>

          <?php else: ?>

            <div class="mb-5">
              <div class="flex items-center gap-3">
                <h3 class="font-extrabold text-slate-900 text-base">
                  Shift Belum Dibuka
                </h3>

                <span class="bg-amber-100 text-amber-700 font-bold px-3 py-1.5 rounded-full text-xs">
                  Belum Aktif
                </span>
              </div>
            </div>

            <button
              type="button"
              onclick="bukaModalKonfirmasiBuka()"
              class="w-full sm:w-auto rounded-xl bg-brand-navy hover:bg-brand-lightNavy text-white px-6 py-3 text-xs font-bold shadow transition">
              Buka Shift Sekarang
            </button>

          <?php endif; ?>

        </div>

        <!-- Transaksi Terbaru -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

          <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-900 text-base">
              Transaksi terbaru saya
            </h3>

            <a
              href="index.php?controller=laporan&action=laporanPegawai"
              class="text-xs font-bold text-brand-navy hover:underline">
              Semua &rarr;
            </a>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-brand-darkNavy text-white text-xs uppercase tracking-wider">
                <tr>
                  <th class="p-3.5 pl-6">WAKTU</th>
                  <th class="p-3.5">NOMOR TRANSAKSI</th>
                  <th class="p-3.5">TOTAL</th>
                  <th class="p-3.5 pr-6 text-right">STATUS</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                <?php if (empty($transaksiTerbaru)): ?>
                  <tr>
                    <td colspan="4" class="p-6 text-center text-slate-400 italic">
                      Belum ada transaksi.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($transaksiTerbaru as $trx): ?>
                    <tr class="hover:bg-blue-50/50 transition">
                      <td class="p-3.5 pl-6 text-slate-500">
                        <?= htmlspecialchars($trx['waktu']) ?>
                      </td>

                      <td class="p-3.5 font-bold text-brand-navy">
                        <?= htmlspecialchars($trx['kode_transaksi']) ?>
                      </td>

                      <td class="p-3.5 font-extrabold">
                        Rp <?= number_format((float) $trx['total'], 0, ',', '.') ?>
                      </td>

                      <td class="p-3.5 pr-6 text-right">
                        <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-xs inline-block">
                          Lunas
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

        </div>

        <!-- Pembayaran Shift Kasir -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

          <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-900 text-base">
              Pembayaran shift saya
            </h3>
          </div>

          <table class="w-full text-left text-sm">
            <thead class="bg-brand-darkNavy text-white text-xs uppercase tracking-wider">
              <tr>
                <th class="p-3.5 pl-6">METODE PEMBAYARAN</th>
                <th class="p-3.5 pr-6 text-right">TOTAL PENJUALAN</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <tr>
                <td class="p-3.5 pl-6 font-bold text-slate-700">
                  Cash (Tunai)
                </td>

                <td class="p-3.5 pr-6 text-right font-extrabold text-brand-navy">
                  Rp <?= number_format((float) ($rekapPembayaran['total_cash'] ?? 0), 0, ',', '.') ?>
                </td>
              </tr>

              <tr>
                <td class="p-3.5 pl-6 font-bold text-slate-700">
                  QRIS (Nontunai)
                </td>

                <td class="p-3.5 pr-6 text-right font-extrabold text-brand-navy">
                  Rp <?= number_format((float) ($rekapPembayaran['total_qris'] ?? 0), 0, ',', '.') ?>
                </td>
              </tr>
            </tbody>
          </table>

        </div>

      </div>

      <!-- Kolom Kanan -->
      <div class="lg:col-span-5 space-y-6">

        <!-- Stok Perlu Perhatian -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

          <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-900 text-base">
              Stok perlu perhatian
            </h3>

            <a
              href="index.php?controller=stok_bahan&action=index"
              class="text-xs font-bold text-brand-navy hover:underline">
              Lihat stok &rarr;
            </a>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-brand-darkNavy text-white text-xs uppercase tracking-wider">
                <tr>
                  <th class="p-3 pl-6">BAHAN BAKU MENU</th>
                  <th class="p-3">SISA</th>
                  <th class="p-3 pr-6 text-right">MINIMUM</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                <?php if (empty($stokKritis)): ?>
                  <tr>
                    <td colspan="3" class="p-4 text-center text-slate-400 italic">
                      Semua stok bahan dalam kondisi aman.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach (array_slice($stokKritis, 0, 4) as $s): ?>
                    <tr class="hover:bg-amber-50/40 transition">
                      <td class="p-3 pl-6 font-bold text-orange-600">
                        <?= htmlspecialchars($s['nama_bahan']) ?>
                      </td>

                      <td class="p-3 font-semibold">
                        <?= number_format((float) $s['stok_bahan'], 0, ',', '.') ?>
                        <?= htmlspecialchars($s['satuan']) ?>
                      </td>

                      <td class="p-3 pr-6 text-right text-slate-500">
                        <?= number_format((float) $s['minimum_stok'], 0, ',', '.') ?>
                        <?= htmlspecialchars($s['satuan']) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

        </div>

        <!-- Pengeluaran Shift Bersama -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

          <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-900 text-base">
              Pengeluaran shift
            </h3>

            <?php if ($shiftBerjalan): ?>
              <button
                type="button"
                onclick="bukaModalTambahPengeluaran()"
                class="text-xs font-bold bg-brand-navy text-white px-3.5 py-2 rounded-xl shadow hover:bg-brand-lightNavy transition">
                Tambah Pengeluaran +
              </button>
            <?php endif; ?>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-brand-darkNavy text-white text-xs uppercase tracking-wider">
                <tr>
                  <th class="p-3 pl-6">KETERANGAN KEPERLUAN</th>
                  <th class="p-3 pr-6 text-right">NOMINAL</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                <?php if (empty($pengeluaranShift)): ?>
                  <tr>
                    <td colspan="2" class="p-4 text-center text-slate-400 italic">
                      Belum ada pengeluaran dicatat pada shift ini.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($pengeluaranShift as $p): ?>
                    <tr>
                      <td class="p-3 pl-6">
                        <?= htmlspecialchars($p['keterangan']) ?>
                      </td>

                      <td class="p-3 pr-6 text-right font-bold text-red-600">
                        Rp <?= number_format((float) $p['nominal'], 0, ',', '.') ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="p-3.5 px-6 bg-slate-50 border-t border-slate-100 flex justify-between items-center font-bold text-xs">
            <span class="text-slate-700">
              Total Pengeluaran Shift
            </span>

            <span class="text-red-600 text-sm">
              Rp <?= number_format((float) $totalPengeluaranShift, 0, ',', '.') ?>
            </span>
          </div>

        </div>

      </div>
    </div>

    <!-- Modal Konfirmasi Buka Shift -->
    <?php if (!$shiftBerjalan): ?>
    <div
      id="modalKonfirmasiBuka"
      class="fixed inset-0 bg-slate-950/50 hidden items-center justify-center z-50 p-4">

      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center space-y-4">

        <div class="mx-auto w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
          <svg class="w-7 h-7 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>

        <div>
          <h3 class="text-lg font-extrabold text-slate-900">
            Buka Shift?
          </h3>

          <p class="text-xs text-slate-500 mt-2">
            Apakah kamu yakin ingin membuka shift sekarang?
          </p>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-2">

          <button
            type="button"
            onclick="tutupModalKonfirmasiBuka()"
            class="w-full py-2.5 font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl transition">
            Batal
          </button>

          <form
            action="index.php?controller=dashboard&action=bukaShift"
            method="POST">

            <?php if (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <?php endif; ?>

            <button
              type="submit"
              class="w-full py-2.5 font-bold text-white bg-brand-navy hover:bg-brand-lightNavy rounded-xl shadow transition">
              Ya, Buka Shift
            </button>

          </form>

        </div>

      </div>
    </div>
    <?php endif; ?>

    <!-- Modal Tambah Pengeluaran -->
    <?php if ($shiftBerjalan): ?>
    <div
      id="modalPengeluaran"
      class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">

      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-xs space-y-4">

        <div>
          <h3 class="text-lg font-extrabold text-slate-900">
            Catat Pengeluaran Shift
          </h3>

          <p class="text-slate-500 mt-1">
            Catat keperluan operasional selama shift berlangsung.
          </p>
        </div>

        <form
          action="index.php?controller=dashboard&action=tambahPengeluaran"
          method="POST"
          class="space-y-3">

          <?php if (function_exists('csrf_token')): ?>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <?php endif; ?>

          <input type="hidden" name="id_shift" value="<?= $idShift ?>">

          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Keterangan Keperluan *
            </label>

            <input
              type="text"
              name="keterangan"
              required
              placeholder="Contoh: Beli es batu atau gas"
              class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-brand-navy">
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Nominal (Rp) *
            </label>

            <input
              type="number"
              name="nominal"
              required
              min="1000"
              step="500"
              placeholder="Contoh: 15000"
              class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-brand-navy">
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              onclick="tutupModalTambahPengeluaran()"
              class="px-4 py-2.5 font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
              Batal
            </button>

            <button
              type="submit"
              class="px-5 py-2.5 font-bold text-white bg-brand-navy hover:bg-brand-lightNavy rounded-xl shadow">
              Simpan Pengeluaran
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Tutup Shift -->
    <div
      id="modalKonfirmasiTutup"
      class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">

      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center space-y-4">

        <h3 class="text-lg font-extrabold text-slate-900">
          Konfirmasi Tutup Shift
        </h3>

        <p class="text-xs text-slate-500">
          Apakah Anda yakin ingin menutup Shift <?= htmlspecialchars($namaShift) ?>? Setelah ditutup, transaksi baru tidak dapat dimasukkan ke shift ini.
        </p>

        <div class="grid grid-cols-2 gap-3 pt-2">

          <button
            type="button"
            onclick="tutupModalKonfirmasiTutup()"
            class="w-full py-2.5 font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl">
            Batal
          </button>

          <form
            action="index.php?controller=dashboard&action=tutupShift"
            method="POST">

            <?php if (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <?php endif; ?>

            <input type="hidden" name="id_shift" value="<?= $idShift ?>">

            <button
              type="submit"
              class="w-full py-2.5 font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow">
              Ya, Tutup Shift
            </button>

          </form>

        </div>

      </div>
    </div>
    <?php endif; ?>

  </main>

  <script>
    function bukaModalKonfirmasiBuka() {
      const modal = document.getElementById('modalKonfirmasiBuka');

      if (!modal) return;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function tutupModalKonfirmasiBuka() {
      const modal = document.getElementById('modalKonfirmasiBuka');

      if (!modal) return;

      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    function bukaModalTambahPengeluaran() {
      const modal = document.getElementById('modalPengeluaran');

      if (!modal) return;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function tutupModalTambahPengeluaran() {
      const modal = document.getElementById('modalPengeluaran');

      if (!modal) return;

      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    function bukaModalKonfirmasiTutup() {
      const modal = document.getElementById('modalKonfirmasiTutup');

      if (!modal) return;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function tutupModalKonfirmasiTutup() {
      const modal = document.getElementById('modalKonfirmasiTutup');

      if (!modal) return;

      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    window.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        tutupModalKonfirmasiBuka();
        tutupModalTambahPengeluaran();
        tutupModalKonfirmasiTutup();
      }
    });

    document.querySelectorAll(
      '#modalKonfirmasiBuka, #modalPengeluaran, #modalKonfirmasiTutup'
    ).forEach(function(modal) {
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.classList.add('hidden');
          modal.classList.remove('flex');
        }
      });
    });
  </script>

</body>
</html>
