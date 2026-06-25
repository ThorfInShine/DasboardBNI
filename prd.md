# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## SAKAI

### 1. Informasi Dokumen
* **Nama Produk:** SAKAI
* **Versi:** 1.0
* **Status:** Draft / Siap Direview
* **Teknologi Utama:** Laravel 13 (Backend), MySQL (Database)

---

### 2. Ringkasan Produk & Sasaran
Sistem ini adalah platform manajemen data berbasis web yang dirancang untuk mempermudah pengguna dalam memantau, mengelola, dan menganalisis data secara efisien. Platform ini menyediakan visualisasi data real-time, manajemen data berbasis peran (RBAC), serta fungsionalitas impor data massal untuk mendukung pengambilan keputusan yang cepat dan tepat.

---

### 3. Peran Pengguna (User Roles & Permissions)

Sistem mengimplementasikan kontrol akses berbasis peran (Role-Based Access Control) dengan pembagian sebagai berikut:

| Fitur / Hak Akses | Viewer | Admin |
| :--- | :---: | :---: |
| Login & Lihat Profil | Ya | Ya |
| Akses Dashboard & Grafik | Ya | Ya |
| Lihat Tabel Data (Search, Filter, Sort, Pagination) | Ya | Ya |
| Operasi CRUD Data Master | Tidak | Ya |
| Import Data (CSV & XLSX) | Tidak | Ya |
| Manajemen Akun (Tambah/Hapus/Reset Akun) | Tidak | Ya |

---

### 4. Struktur Halaman & Persyaratan Fungsional

#### A. Halaman Login (Autentikasi)
* **Deskripsi:** Pintu masuk utama pengguna ke dalam sistem.
* **Spesifikasi Fitur:**
    * Form input standar: Email dan Password.
    * Validasi keamanan sisi server menggunakan fitur bawaan Laravel 13 (termasuk proteksi CSRF dan pembatasan laju login/*rate limiting* untuk mencegah *brute force*).

#### B. Halaman Dashboard (Visualisasi Data)
* **Deskripsi:** Memuat ringkasan informasi eksekutif dalam bentuk visual yang mudah dipahami.
* **Spesifikasi Fitur:**
    * **Widget Ringkasan:** Menampilkan metrik atau total akumulasi data dalam bentuk kartu informasi (*cards*).
    * **Komponen Grafik:**
        * *Line Chart:* Menampilkan tren perkembangan data berdasarkan urutan waktu historis.
        * *Bar Chart:* Menampilkan perbandingan volume data antar kategori.
        * *Pie Chart:* Menampilkan persentase atau distribusi proporsi data.
    * **Sinkronisasi Otomatis:** Setiap kali ada data baru yang masuk (baik lewat input manual maupun impor file), dashboard harus langsung memperbarui angka dan grafik secara otomatis saat halaman diakses atau dimuat ulang tanpa membutuhkan proses kalkulasi manual tambahan.

#### C. Halaman Manajemen Data (Tabel & CRUD)
* **Deskripsi:** Pusat kendali operasional data di mana data ditampilkan secara terstruktur.
* **Spesifikasi Fitur:**
    * **Operasi CRUD (Khusus Admin):** Menyediakan tombol Tambah Data, Edit Data per baris, dan Hapus Data.
    * **Komponen Tabel Interaktif (Admin & Viewer):**
        * *Pencarian (Search):* Kolom input untuk menyaring data secara instan berdasarkan kata kunci tertentu.
        * *Penyaringan (Filter):* Dropdown untuk menyaring data berdasarkan parameter spesifik (seperti rentang tanggal atau kategori).
        * *Pengurutan (Sorting):* Fitur klik pada kepala kolom untuk mengurutkan data secara *ascending* atau *descending*.
        * *Paginasi (Pagination):* Memecah baris data ke dalam beberapa halaman (misal: 10, 25, 50 baris per halaman) untuk menjaga kecepatan *load* aplikasi di sisi klien.

#### D. Halaman Import Data (Khusus Admin)
* **Deskripsi:** Fasilitas unggah data massal untuk efisiensi waktu operasional.
* **Spesifikasi Fitur:**
    * **Format Berkas pendukung:** Mengakomodasi file beresolusi `.csv` dan `.xlsx`.
    * **Mekanisme Pemrosesan:**
        * Sistem melakukan validasi format dan tipe data sebelum melakukan *insert* ke database MySQL.
        * Data yang valid akan langsung disimpan ke database dan memicu pembaruan otomatis pada visualisasi dashboard.
    * **Manajemen Riwayat:** Dashboard harus dikondisikan untuk selalu menampilkan status data paling mutakhir (kondisi terkini), namun arsitektur database wajib tetap menyimpan seluruh rekam jejak atau riwayat data lama (*historical data*) untuk kebutuhan audit atau pelacakan di kemudian hari (tidak ditimpa atau dihapus permanen).

#### E. Halaman Profil
* **Deskripsi:** Area mandiri bagi pengguna aktif untuk mengelola kredensial mereka.
* **Spesifikasi Fitur:**
    * Menampilkan informasi profil dasar (Nama, Email, dan Role).
    * Fasilitas untuk memperbarui nama dan mengubah password akun pribadi.

#### F. Modul Khusus: Manajemen Akun (Khusus Admin)
* **Deskripsi:** Panel administrasi pengguna yang melekat pada hak akses Admin.
* **Spesifikasi Fitur:**
    * *Tambah Akun:* Admin dapat mendaftarkan akun baru dan langsung menentukan rolenya sebagai Viewer atau sesama Admin.
    * *Hapus Akun:* Menghapus hak akses pengguna lain dari sistem.
    * *Reset Password:* Admin memiliki wewenang untuk mengganti password pengguna lain jika terjadi kasus lupa password pada pengguna tersebut.

---

### 5. Arsitektur Teknis & Ketentuan Database

* **Backend Framework (Laravel 13):**
    * Memanfaatkan implementasi Eloquent ORM untuk komunikasi dengan database yang aman dari SQL Injection.
    * Proses pengolahan file CSV/XLSX disarankan menggunakan library standar industri seperti `maatwebsite/excel` atau komponen pembaca *stream* berkinerja tinggi guna mencegah isu kehabisan memori (*out of memory*) di server saat menangani berkas besar.
* **Database (MySQL):**
    * Skema tabel utama harus mengimplementasikan *indexing* pada kolom-kolom yang sering digunakan dalam proses pencarian, penyaringan (*filtering*), dan pengurutan (*sorting*).
    * Penyimpanan data historis diimplementasikan melalui skema tabel log terpisah atau pemanfaatan kolom penanda versi/status aktif, sehingga data lama tetap terjaga validitasnya sementara query dashboard tetap berjalan cepat karena hanya mengambil status terbaru.

---

### 6. Kriteria Penerimaan (Acceptance Criteria)

1. **Validasi Hak Akses:** Pengguna dengan role *Viewer* yang mencoba mengakses endpoint atau URL modifikasi data (seperti halaman import atau manajemen akun) secara paksa harus langsung diarahkan ke halaman error "403 Forbidden".
2. **Integritas Data Impor:** Ketika file CSV/XLSX berhasil diimpor, jumlah baris data yang masuk ke database harus sama persis dengan jumlah baris valid yang ada di dalam dokumen. Jika terdapat baris yang korup atau salah format, sistem harus membatalkan seluruh transaksi (*database rollback*) atau memisahkan baris eror tersebut dan memunculkan laporan kesalahan yang informatif.
3. **Konsistensi Visual:** Perubahan data yang dilakukan melalui halaman Manajemen Data (baik tambah, ubah, maupun hapus) harus langsung tercermin pada grafik di Dashboard pada detik yang sama saat halaman dashboard dimuat ulang.