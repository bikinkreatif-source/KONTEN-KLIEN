# API Ruang Konten

Base URL produksi: `https://ruangkonten.web.id/api`

Semua endpoint selain `POST /auth/token` membutuhkan header:

```http
Authorization: Bearer ACCESS_TOKEN
Accept: application/json
```

Gunakan HTTPS. Token berlaku 30 hari dan terikat pada akun Ruang Konten yang menerbitkannya. Jangan kirim token lewat chat, masukkan ke URL, atau simpan di kode publik.

## Akses akun dan cakupan data

| Peran akun pemilik token | Data yang dapat dibaca |
| --- | --- |
| `pm` | Semua klien dan konten pada satu instalasi Ruang Konten |
| `team` | Klien yang ditugaskan ke anggota tim tersebut |
| `client` | Klien milik akun tersebut |

Instalasi saat ini belum memiliki kolom atau batas `agency_id`. Karena itu akun `pm` berarti Project Manager untuk seluruh data dalam instalasi ini. Pastikan respons penerbitan token menunjukkan akun integrasi yang benar dan `role: "pm"` bila AI perlu membaca semua klien. Respons `GET /contents` dengan `data: []` berarti tidak ada konten yang cocok untuk akun dan filter tersebut; respons itu sendiri tidak menunjukkan apakah token dari agensi lain.

## Menerbitkan dan mencabut token

`POST /auth/token` menerima kredensial akun melalui JSON dan mengembalikan token satu kali.

```sh
curl -X POST https://ruangkonten.web.id/api/auth/token \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"akun-integrasi@ruangkonten.web.id","password":"PASSWORD","name":"AI Project Manager"}'
```

Respons sukses `201` memuat `access_token`, `expires_at`, dan `user` dengan `id`, `name`, `email`, dan `role`. Jangan menyimpan token dari contoh ke source code.

Cabut token yang sedang digunakan:

```http
DELETE /api/auth/token
```

## Endpoint baca

| Method dan path | Isi |
| --- | --- |
| `GET /clients` | Klien yang boleh dilihat akun, beserta `contents_count` |
| `POST /clients` | Membuat klien baru (PM saja), opsional sekaligus menugaskan tim |
| `GET /contents` | Konten terfilter dan terurut berdasarkan tanggal/jam tayang, dengan pagination |
| `GET /contents/{id}` | Detail konten, relasi klien, PIC, dan riwayat status/revisi yang tercatat |

Semua endpoint baca memerlukan Bearer token. Contoh:

```sh
curl 'https://ruangkonten.web.id/api/clients' \
  -H 'Authorization: Bearer ACCESS_TOKEN' \
  -H 'Accept: application/json'
```

## Membuat klien

`POST /clients` hanya dapat digunakan oleh akun dengan `role: "pm"`. Kirim JSON berikut; tanggal wajib berformat `YYYY-MM-DD`. Kuota format dan daftar tim bersifat opsional. Jika kuota tidak dikirim, nilainya menjadi `0`. `team_ids` berisi ID akun tim yang sudah dibuat di aplikasi.

```sh
curl -X POST https://ruangkonten.web.id/api/clients \
  -H 'Authorization: Bearer ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "Klien Contoh",
    "start_date": "2026-11-01",
    "end_date": "2026-11-30",
    "quota_single": 4,
    "quota_carousel": 4,
    "quota_reels": 2,
    "quota_story": 8,
    "pillars": "Edukasi\nPromosi",
    "information": "Target audiens keluarga muda.",
    "instagram": "https://instagram.com/contoh",
    "tiktok": "https://tiktok.com/@contoh",
    "facebook": "https://facebook.com/contoh",
    "drive_url": "https://drive.google.com/drive/folders/CONTOH",
    "team_ids": [3, 5]
  }'
```

Respons sukses `201 Created` memiliki properti `data` yang berisi ID dan informasi klien serta `team_members`. Logo belum dapat dikirim lewat endpoint JSON ini; unggah melalui aplikasi. `403` berarti token bukan milik PM, sedangkan `422` berarti validasi payload gagal.

## Filter konten dan pagination

`GET /contents` menerima parameter berikut. Filter dapat digabungkan.

| Parameter | Format | Keterangan |
| --- | --- | --- |
| `client_id` | integer | Hanya konten untuk ID klien tersebut |
| `status` | teks | Status persis: `Ide`, `Dalam proses`, `Menunggu review`, `Menunggu persetujuan klien`, `Perlu revisi`, `Disetujui`, `Terjadwal`, atau `Tayang` |
| `from` | `YYYY-MM-DD` | Tanggal tayang mulai, inklusif |
| `to` | `YYYY-MM-DD` | Tanggal tayang akhir, inklusif |
| `assigned_to_me` | `1` atau `0` | Jika `1`, hanya konten yang PIC-nya adalah akun token |
| `page` | integer | Nomor halaman; default `1` |

API menggunakan 100 baris per halaman. Ikuti URL `next_page_url` dari respons sampai nilainya `null` untuk mengambil seluruh hasil. Contoh:

```sh
curl 'https://ruangkonten.web.id/api/contents?client_id=12&status=Perlu%20revisi&from=2026-10-01&to=2026-10-31&page=1' \
  -H 'Authorization: Bearer ACCESS_TOKEN' \
  -H 'Accept: application/json'
```

Respons daftar mengikuti format paginator Laravel:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "...",
  "from": null,
  "last_page": 1,
  "last_page_url": "...",
  "links": [],
  "next_page_url": null,
  "path": "...",
  "per_page": 100,
  "prev_page_url": null,
  "to": null,
  "total": 0
}
```

## Informasi konten

Objek konten menyertakan kolom konten dan relasi berikut:

- `client`: ID dan nama klien.
- `assignee`: ID, nama, email, dan divisi PIC pada endpoint detail; daftar memuat ID, nama, dan divisi PIC.
- `publish_date` dan `publish_time`: jadwal tayang. Waktu dapat kosong.
- `due_date`: deadline pengerjaan, dapat kosong.
- `status`: status produksi/approval terkini.
- `brief`, `caption`, `pillar`, `funnel`, `reference_url`, `drive_url`, `next_step`, `note`, dan `checklist`: detail kerja konten yang tersedia.
- `revisions`: riwayat perubahan status yang dicatat, termasuk `action`, waktu pencatatan, dan pengguna terkait. Riwayat dibuat ketika konten dibuat dan ketika status berubah.

Status `Menunggu persetujuan klien`, `Perlu revisi`, `Disetujui`, dan `Tayang` membantu membaca tahap approval dan publikasi. API saat ini tidak menyediakan entitas approval terpisah atau catatan approval formal; gunakan status dan riwayat status yang tersedia.

## Status respons dan kendala koneksi

| HTTP | Makna |
| --- | --- |
| `200` | Berhasil; `data` kosong berarti tidak ada baris yang cocok dengan akses/filter |
| `401` | Header token tidak ada, tidak valid, atau kedaluwarsa |
| `403` | Peran atau akun tidak memiliki izin pada data tersebut |
| `422` | Parameter tidak valid |
| `502` | Respons gateway/proxy; belum cukup untuk menyimpulkan token benar atau salah |

Endpoint `GET /clients` yang dibuka tanpa autentikasi mengembalikan `401` JSON dari aplikasi. Itu memastikan route aplikasi merespons dari jalur browser yang diperiksa. Error `502` yang dilaporkan dari lingkungan AI perlu diperiksa dari lingkungan itu atau dicocokkan dengan access/error log hosting pada waktu request; log hosting belum dapat saya baca dari sesi ini, jadi belum bisa memastikan request tersebut mencapai Laravel.

## Endpoint tulis

| Method dan path | Izin |
| --- | --- |
| `POST /clients` | PM saja |
| `POST /contents` | PM atau tim pada klien yang ditugaskan |
| `PUT` / `PATCH /contents/{id}` | PM atau tim pada klien yang ditugaskan |
| `DELETE /contents/{id}` | PM saja |

Akun klien hanya memiliki akses baca. Konten yang dibuat harus berada pada periode aktif klien; nilai `assignee_id` tim juga harus ditugaskan ke planner klien tersebut.
