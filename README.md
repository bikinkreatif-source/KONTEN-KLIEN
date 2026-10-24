# Ruang Konten · Bikin Kreatif

Aplikasi Laravel untuk mengelola content plan klien social media agency.

## Kebutuhan hosting

- PHP 8.2 atau lebih baru, Composer 2, dan MySQL/MariaDB.
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `curl`, `dom`, `filter`, `hash`, `json`, `session`, `tokenizer`, dan `xml`.
- Web server Apache dengan `mod_rewrite` atau Nginx yang diarahkan ke folder `public`.
- HTTPS aktif dan akses tulis untuk `storage/` serta `bootstrap/cache/`.
- Batas unggah PHP minimal 1 MB untuk logo klien (aplikasi membatasi logo hingga 350 KB).

## Deploy ke hosting PHP

1. Buat database MySQL/MariaDB dan pengguna database di panel hosting.
2. Unggah seluruh source aplikasi ke folder privat di akun hosting. Atur document root domain ke folder `public` di dalam aplikasi. Jangan jadikan root source Laravel sebagai document root.
3. Siapkan `.env` dari `.env.example`, lalu isi domain HTTPS, kredensial database, dan identitas admin PM pertama. Simpan `.env` di luar folder publik dan jangan unggah ke Git.
4. Di terminal hosting, dari root aplikasi jalankan:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan optimize
   ```

   `key:generate` hanya dijalankan saat instalasi pertama. Jangan mengganti `APP_KEY` pada instalasi yang sudah berisi data.
5. Sesudah seeding pertama, hapus `ADMIN_PASSWORD` dari environment hosting. Kredensial admin tersimpan sebagai hash di database. Login menggunakan email dan password yang dipakai saat seeding.
6. Pastikan permission folder `storage/` dan `bootstrap/cache/` dapat ditulis oleh PHP. Buka domain dengan HTTPS, masuk sebagai PM, buat akun tim, lalu tambahkan atau periksa data klien.
7. Atur backup terjadwal untuk database serta `storage/app/public` (logo klien). Uji pemulihan backup sebelum menerima data produksi.

### Environment produksi

Isi nilai berikut di `.env` hosting. Jangan salin kredensial demo lokal.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ruangkonten.web.id
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=pengguna_database
DB_PASSWORD="kata-sandi-database"

ADMIN_NAME="Project Manager"
ADMIN_EMAIL=pm@ruangkonten.web.id
ADMIN_PASSWORD="kata-sandi-awal-yang-kuat"

FILESYSTEM_DISK=public
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Gunakan `APP_KEY` yang dihasilkan Laravel. `.env.example` hanya template; file `.env` lokal tidak ikut diunggah ke Git karena diabaikan oleh `.gitignore`.

### Jika hosting tidak menyediakan Composer

Jalankan `composer install --no-dev --prefer-dist --optimize-autoloader` pada mesin build dengan versi PHP yang sesuai, lalu unggah folder `vendor/` bersama aplikasi. Folder `vendor/` sengaja tidak disimpan di Git. Karena aplikasi memakai CSS statis di `public/app.css`, proses build Node/Vite tidak dibutuhkan untuk halaman saat ini.

Jika panel hosting tidak mengizinkan document root diarahkan ke folder `public`, minta konfigurasi document root kepada provider sebelum upload. Jangan memindahkan `index.php` ke root source atau membuat folder internal Laravel dapat diakses publik.

## Setup lokal

Kebutuhan: PHP 8.2+, Composer, dan SQLite.

```sh
composer install
cp .env.example .env
```

Untuk development, ubah `.env` lokal ke `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000`, `DB_CONNECTION=sqlite`, dan isi `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Buat file `database/database.sqlite`, lalu jalankan:

```sh
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Data klien, konten contoh, dan akun demo tim hanya dibuat oleh seeder pada environment `local`. Atur `ADMIN_EMAIL` dan `ADMIN_PASSWORD` lokal Anda sendiri sebelum menjalankan seeder.

## Fitur

- Akun Project Manager, tim, dan klien dengan pembatasan akses berbasis peran.
- Planner klien dengan tanggal posting, kuota format, pilar, informasi, tautan sosial, folder Drive, dan logo.
- Kalender konten, penugasan tim per klien, status produksi, review, revisi, serta halaman publik read-only.
- Dashboard khusus PM, panduan sesuai peran, dan tampilan responsif.

Konten produksi dan aset besar diarahkan melalui tautan Google Drive. Aplikasi hanya menyimpan logo klien.

## API integrasi

REST API tersedia di `https://ruangkonten.web.id/api`. Semua endpoint selain penerbitan token membutuhkan HTTPS dan header `Authorization: Bearer <access_token>`. Token berlaku 30 hari, hanya nilai hash yang disimpan, dan token asli hanya dikirim sekali saat diterbitkan. Cabut token aktif dengan `DELETE /api/auth/token`.

### Menerbitkan token

Kirim kredensial akun aplikasi melalui koneksi HTTPS. Gunakan akun integrasi khusus dengan hak akses sekecil mungkin.

```sh
curl -X POST https://ruangkonten.web.id/api/auth/token \
  -H 'Content-Type: application/json' \
  -d '{"email":"integrasi@contoh.id","password":"GANTI_DENGAN_PASSWORD","name":"Asisten konten"}'
```

Simpan nilai `access_token` dengan aman. Jangan masukkan token ke source code publik, log, atau browser yang bisa diakses umum.

### Endpoint

| Method | Path | Fungsi |
| --- | --- | --- |
| `GET` | `/api/clients` | Daftar klien yang boleh diakses akun |
| `GET` | `/api/contents` | Daftar konten; filter opsional `client_id`, `status`, `from`, `to`, `assigned_to_me` |
| `GET` | `/api/contents/{id}` | Detail konten, klien, PIC, dan riwayat status |
| `POST` | `/api/contents` | Membuat konten untuk PM atau tim |
| `PUT` / `PATCH` | `/api/contents/{id}` | Memperbarui konten untuk PM atau tim |
| `DELETE` | `/api/contents/{id}` | Menghapus konten (PM saja) |
| `DELETE` | `/api/auth/token` | Mencabut token yang digunakan |

Contoh membuat konten:

```json
{
  "client_id": 1,
  "title": "Tips memilih rumah pertama",
  "type": "Carousel",
  "publish_date": "2026-11-02",
  "publish_time": "10:00",
  "status": "Ide",
  "pillar": "Edukasi",
  "brief": "Bahas tiga hal yang perlu dicek pembeli rumah pertama.",
  "caption": "Sedang mencari rumah pertama? ...",
  "reference_url": "https://example.com/referensi",
  "drive_url": "https://drive.google.com/drive/folders/CONTOH"
}
```

`publish_date` wajib berada dalam periode aktif klien. Format status: `Ide`, `Dalam proses`, `Menunggu review`, `Menunggu persetujuan klien`, `Perlu revisi`, `Disetujui`, `Terjadwal`, atau `Tayang`. Akun tim hanya melihat klien yang ditugaskan kepadanya, akun klien hanya dapat membaca kontennya sendiri, dan Project Manager dapat mengakses semua klien. Akun klien tidak dapat membuat, mengubah, atau menghapus konten.

Contoh mengambil konten yang ditugaskan kepada akun:

```sh
curl 'https://ruangkonten.web.id/api/contents?assigned_to_me=1&from=2026-11-01&to=2026-11-30' \
  -H 'Authorization: Bearer TOKEN_ANDA' \
  -H 'Accept: application/json'
```
