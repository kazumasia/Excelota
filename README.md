# OTA - Excel Table Manager

Project kecil buat ngolah file Excel di web.
Flow-nya sederhana: upload file -> data kebaca jadi tabel -> bisa search/edit/delete -> export lagi ke Excel.

## Fitur yang ada

- Upload `.xlsx`, `.xls`, `.csv`
- Header otomatis ngikut baris pertama file
- Live search (jadi pas ngetik langsung kefilter)
- Responsive:
  - mobile: bentuk card
  - desktop: tabel
- Klik row buat edit isi
- Delete row
- Delete 1 import sekalian
- Export ulang jadi `.xlsx`
- SweetAlert buat confirm delete

## Dipakai apa aja

- Laravel 13
- PHP 8.3+
- PhpSpreadsheet
- DB: bisa SQLite / MySQL / MariaDB
- SweetAlert2

## Cara jalanin di lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Lalu buka:

`http://127.0.0.1:8000`

## Cara pakai singkat

1. Upload file Excel (pastikan row pertama itu header/judul kolom)
2. Masuk ke halaman data import
3. Search data kalau perlu
4. Klik row buat edit
5. Kalau udah beres, export lagi

## Endpoint penting

- `GET /` -> list import
- `POST /spreadsheets` -> upload file
- `GET /spreadsheets/{spreadsheet}` -> lihat isi + search
- `PATCH /spreadsheets/{spreadsheet}/rows/{row}` -> update row
- `DELETE /spreadsheets/{spreadsheet}/rows/{row}` -> hapus row
- `GET /spreadsheets/{spreadsheet}/export` -> export xlsx
- `DELETE /spreadsheets/{spreadsheet}` -> hapus 1 import

## Catatan

- Project ini butuh PHP `^8.3`, jadi hosting PHP lawas (5.x - 7.x) jelas nggak masuk.
- Kalau upload/export Excel error, cek extension PHP (minimal `zip` + extension standar Laravel).

