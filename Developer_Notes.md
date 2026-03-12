# Catatan Perubahan (Developer Notes) & Lessons Learned

## Overview
Dokumen ini mencatat perubahan-perubahan sistem terkini pada Asset Management System beserta alasan logisnya.

---

### 1. Perubahan Logika Autentikasi (Tridatu Netmon)
**Masalah Sebelumnya:**
Sistem memunculkan pesan error `Data truncated for column 'role'` setelah migrasi terbaru karena role baru divalidasi dengan strict enum (`super_admin`, `admin`, `operator`, `viewer`), sedangkan dari API kadang mereturn `staff`.

**Perbaikan & Pengembangan:**
- Implementasi `TridatuUserProvider` kustom yang menerapkan Ephemeral Session (GenericUser).
- **Lessons Learned:** Jika data user sudah ter-sentralisasi di sistem eksternal (Tridatu) yang memiliki cache efisien, kita tidak pantas dan tidak perlu menyimpan salinannya ganda di lokal `users` table. Hal ini mengurangi risiko sinkronisasi data yang gagal dan error struktur data di masa depan.

---

### 2. Penghapusan Foreign Key Constraint untuk User
**Masalah:**
```
Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`transactions_created_by_foreign`)
```
Karena kita sudah tidak menggunakan tabel `users` lokal, field `created_by` pada `transactions` (dan `performed_by` pada `asset_unit_logs`) yang mengarah ke tabel `users` lokal menjadi throw error.

**Perbaikan:**
- Pembuatan migration `2026_03_02_045055_drop_user_foreign_keys_from_transactions_and_logs.php` untuk men-drop foreign key mapping pada tabel `transactions` (`created_by`) dan `asset_unit_logs` (`performed_by`).
- **Lessons Learned:** Ketika mengubah pondasi otentikasi (mengandalkan eksternal ID), relasi atau Foreign Key Database internal menuju id user harus dilepas untuk menghindari strict constraint violation pada audit-trail.

---

### 3. Transaksi Fleksibel (Tanpa Barang Pengganti)
**Pernyataan Bisnis:**
"Secara bisnis ini gak selamanya ada barang pengganti, bisa aja pelanggan beli sendiri dll."

**Perbaikan:**
- Pembuatan filter opsional pada perulangan asset `uid` di `TransactionController@store`.
- Validasi array pada `uid` telah dilonggarkan karena pada aslinya flow lapangan untuk Transaksi `Stock Out` (atau `Retrieval` dan `Replacement`) tidak selalu dibarengi dengan adanya fisik perangkat yang direkam sistem.
- **Lessons Learned:** System Design sebaiknya tidak terlalu "kaku" pada physical representation of flow. Celah seperti pelanggan yang menyediakan alat pengganti secara mandiri harus diantisipasi dengan mengijinkan pembuatan log transaksi tanpa harus memuat array item detail fisik.

---

### 4. Perbaikan Pencabutan & Ganti Barang
**Masalah Sebelumnya:**
Ada bug ketika melakukan pencabutan barang yang menghasilkan note: `"Barang rusak & ditinggal di lokasi: undefined"`. Selain itu, barang yang berstatus kepemilikan `'beli_putus'` masih bisa dipilih sebagai objek yang bisa dicabut/dibawa pulang.

**Perbaikan & Pengembangan:**
- **Bug 'Undefined'**: Penambahan attribute `text` pada response JSON saat Controller me-*resolve* detail dari asset dari ID ke objek JSON. Field `text` sekarang berhasil ditangkap di _frontend_ (jQuery) dan nama barang muncul dengan baik.
- **Filter Barang Beli Putus**: Perbaikan pada metode API penarik daftar barang customer agar mengeksklusikan (`!=`) status kepemilikan `'sold_to_customer'` alias barang beli putus. Barang beli putus secara harfiah tidak boleh dan tidak bisa dicabut kembali ke gudang perusahaan.

---

### 5. Pencegahan Duplikasi Serial Number pada Pecahan Barang (Cable/Meteran)
**Masalah Sebelumnya:**
Ketika melakukan pemasangan barang _bulk_ (secara jumlah qty/meteran) yang menyebabkan stok aset terpecah (sistem me-*replicate()* baris barang dan memberinya status `deployed`). Pemberian akhiran Serial Number pada pecahannya hanya menggunakan format `$sn-CUST-$cid`. Akibatnya jika teknisi memecah / memasang barang _bulk_ yang persis sama dua kali ke pelanggan yang sama pada transaksi berbeda, *database* menolak _insert_ karena Serial Number-nya terduplikasi (Unique Constraint Violation).

- Penambahan fungsi suffix tambahan pengacak tipe angka `-rand(1000, 9999)` menggunakan logic *built-in* PHP agar berapapun pelanggan yang sama meminta pecahannya lagi pada waktu yang berbeda, sistem selalu bisa merekamnya menjadi *unique serial string* dalam tabel *asset units*.

---

### 6. Perbaikan Transaksi Duplikat (DB Transaction) & Tampilan Aset Beli Putus Pelanggan

# Catatan Perubahan (Developer Notes) & Lessons Learned

## Overview
Dokumen ini mencatat perubahan-perubahan sistem terkini pada Asset Management System beserta alasan logisnya.

---

### 1. Perubahan Logika Autentikasi (Tridatu Netmon)
**Masalah Sebelumnya:**
Sistem memunculkan pesan error `Data truncated for column 'role'` setelah migrasi terbaru karena role baru divalidasi dengan strict enum (`super_admin`, `admin`, `operator`, `viewer`), sedangkan dari API kadang mereturn `staff`.

**Perbaikan & Pengembangan:**
- Implementasi `TridatuUserProvider` kustom yang menerapkan Ephemeral Session (GenericUser).
- **Lessons Learned:** Jika data user sudah ter-sentralisasi di sistem eksternal (Tridatu) yang memiliki cache efisien, kita tidak pantas dan tidak perlu menyimpan salinannya ganda di lokal `users` table. Hal ini mengurangi risiko sinkronisasi data yang gagal dan error struktur data di masa depan.

---

### 2. Penghapusan Foreign Key Constraint untuk User
**Masalah:**
```
Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`transactions_created_by_foreign`)
```
Karena kita sudah tidak menggunakan tabel `users` lokal, field `created_by` pada `transactions` (dan `performed_by` pada `asset_unit_logs`) yang mengarah ke tabel `users` lokal menjadi throw error.

**Perbaikan:**
- Pembuatan migration `2026_03_02_045055_drop_user_foreign_keys_from_transactions_and_logs.php` untuk men-drop foreign key mapping pada tabel `transactions` (`created_by`) dan `asset_unit_logs` (`performed_by`).
- **Lessons Learned:** Ketika mengubah pondasi otentikasi (mengandalkan eksternal ID), relasi atau Foreign Key Database internal menuju id user harus dilepas untuk menghindari strict constraint violation pada audit-trail.

---

### 3. Transaksi Fleksibel (Tanpa Barang Pengganti)
**Pernyataan Bisnis:**
"Secara bisnis ini gak selamanya ada barang pengganti, bisa aja pelanggan beli sendiri dll."

**Perbaikan:**
- Pembuatan filter opsional pada perulangan asset `uid` di `TransactionController@store`.
- Validasi array pada `uid` telah dilonggarkan karena pada aslinya flow lapangan untuk Transaksi `Stock Out` (atau `Retrieval` dan `Replacement`) tidak selalu dibarengi dengan adanya fisik perangkat yang direkam sistem.
- **Lessons Learned:** System Design sebaiknya tidak terlalu "kaku" pada physical representation of flow. Celah seperti pelanggan yang menyediakan alat pengganti secara mandiri harus diantisipasi dengan mengijinkan pembuatan log transaksi tanpa harus memuat array item detail fisik.

---

### 4. Perbaikan Pencabutan & Ganti Barang
**Masalah Sebelumnya:**
Ada bug ketika melakukan pencabutan barang yang menghasilkan note: `"Barang rusak & ditinggal di lokasi: undefined"`. Selain itu, barang yang berstatus kepemilikan `'beli_putus'` masih bisa dipilih sebagai objek yang bisa dicabut/dibawa pulang.

**Perbaikan & Pengembangan:**
- **Bug 'Undefined'**: Penambahan attribute `text` pada response JSON saat Controller me-*resolve* detail dari asset dari ID ke objek JSON. Field `text` sekarang berhasil ditangkap di _frontend_ (jQuery) dan nama barang muncul dengan baik.
- **Filter Barang Beli Putus**: Perbaikan pada metode API penarik daftar barang customer agar mengeksklusikan (`!=`) status kepemilikan `'sold_to_customer'` alias barang beli putus. Barang beli putus secara harfiah tidak boleh dan tidak bisa dicabut kembali ke gudang perusahaan.

---

### 5. Pencegahan Duplikasi Serial Number pada Pecahan Barang (Cable/Meteran)
**Masalah Sebelumnya:**
Ketika melakukan pemasangan barang _bulk_ (secara jumlah qty/meteran) yang menyebabkan stok aset terpecah (sistem me-*replicate()* baris barang dan memberinya status `deployed`). Pemberian akhiran Serial Number pada pecahannya hanya menggunakan format `$sn-CUST-$cid`. Akibatnya jika teknisi memecah / memasang barang _bulk_ yang persis sama dua kali ke pelanggan yang sama pada transaksi berbeda, *database* menolak _insert_ karena Serial Number-nya terduplikasi (Unique Constraint Violation).

- Penambahan fungsi suffix tambahan pengacak tipe angka `-rand(1000, 9999)` menggunakan logic *built-in* PHP agar berapapun pelanggan yang sama meminta pecahannya lagi pada waktu yang berbeda, sistem selalu bisa merekamnya menjadi *unique serial string* dalam tabel *asset units*.

---

### 6. Perbaikan Transaksi Duplikat (DB Transaction) & Tampilan Aset Beli Putus Pelanggan
**Masalah:**
Sebelumnya, saat terjadi error database (misal SN *Duplicate*) di pertengahan loop update barang tipe *bulk*, kerangka awal struk Transaksi sudah terlanjur direkam ke database. Akibatnya ketika form di-submit ulang, transaksi jadi kosong atau berganda. 
Masalah berikutnya adalah ketika filter "Barang Beli Putus dilarang ditarik gudang" diaktifkan di *Backend*, daftar aset pelanggan tersebut jadi menghilang secara harfiah di antarmuka (UI).

**Perbaikan & Pengembangan:**
- **Penerapan Relasional Database Transaction:** Menambahkan logika pembungkus Laravel `DB::beginTransaction()`, `DB::commit()`, dan `DB::rollBack()` ke dalam fungsi `TransactionController@store`. Kode yang di-*wrap* dengan ini bersifat "all-or-nothing". Jika satu barang pun gagal di-insert / error constraint di tengah loop, maka struk mentah awal tersebut otomatis di-*rollback* total (dimusnahkan) tanpa menyimpan *half-baked data*!
- **Tampilan Informatif Beli Putus pada UI:** Pencabutan fungsi eksklusif filter "Barang Beli Putus" di controller *Backend* (`SelectController`). Sebagai substitusinya, perbaikan logika ditaruh *Front-End* (`create-transaction.blade.php`). Jika status kepemilikan aset pelanggan adalah `'sold_to_customer'`, maka tombol eksekusi fungsional *'Cabut / Ganti'* ditiadakan seketika dan diganti menjadi *Badge* indikator pasif: **Beli Putus (Milik Pelanggan)**. Hal ini menjamin transparansi pencatatan aset terpasang miliki pelanggan tetap bisa terbaca tetapi tombol tariknya terkunci aman dari ulah teknisi.
- **Penambahan Kolom Qty:** Kolom spesifik untuk 'Qty' (beserta satuannya seperti pcs/mtr) juga sudah ditambahkan pada tabel daftar aset pelanggan di halaman konfigurasi agar detail fisik alat terlihat langsung dari awal.
- **Perbaikan Satuan UOM & Label Status Fleksibel:** Mengganti kolom 'Status Saat Ini' yang monoton bernada *'Deployed'* menjadi badge variabel yang peka terhadap isi data riil *ownership_status* miliki aset tsb (Misal *Beli Putus* dengan warna ungu, *Sewa / Cicil* kuning, atau *Dipinjamkan* warna biru). Juga menangani masalah text *undefined* pada satuan quantity dengan benar-benar melirik database attribute bawaan *uom* (Unit of Measure) dari parent table asset types.
- **Penambahan Status Pinjam:** Menambahkan satu opsi baru yaitu 'Pinjam' pada menu *Dropdown* Kontrak Transaksi. Jika ditaruh dengan tipe ini, logikanya akan bersinergi secara setara dengan barang Default / Dipinjamkan, di mana `ownership_status`-nya direkam menjadi `company_owned`.

---

### 7. Perubahan Grouping Menu Monitoring: Dari Perusahaan Menjadi Pelanggan
**Masalah Sebelumya / Requirement Bisnis:**
Menu Monitoring sebelumnya berbasis "Perusahaan" dan men-group data transaksi dari tiap porsi unit divisi sub-perusahaan tersebut. Namun sesuai dengan kebutuhan operasional yang baru, kita memerlukan sudut pandang per-"Pelanggan" secara mendatar untuk lebih mudah memantau riwayat aktivitas barang keluar/masuk spesifik untuk setiap lokasi klien individu (seperti Villa A, dst).

**Perbaikan & Pengembangan:**
- **Refactoring Query Data:** Mengganti Eloquent Entity pembangun DataTables Controller `MonitorController@companyDatatable` agar menyasar table / relasi model `Customer::with('transactions', 'assetUnits')` alih-alih `Company::with('division')`.
- **Refactoring Modal History:** Pada saat mengeklik detail history/riwayat, query *database* diubah agar mem-filter `TransactionDetail` berdasarkan pencocokan parameter `customer_id` di tiket transaksinya.
- **Visual Sidebar dan Frontend:** Penamaan table header dan Sidebar diubah secara representatif dari "Monitoring Company" menjadi "Monitoring Pelanggan" dengan kolom tabel yang dikurangi dan dirapikan `(Nama Pelanggan, CID, Jumlah Transaksi)`.