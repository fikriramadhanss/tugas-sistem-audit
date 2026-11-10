# Backend PHP Native & Database MySQL (phpMyAdmin)

Backend ini dibangun menggunakan **PHP Native (PDO)** dengan database **MySQL (phpMyAdmin)** untuk menggantikan ketergantungan pada backend cloud Supabase.

---

## 📁 Struktur Direktori

```
backend/
├── config/
│   ├── database.php        # Koneksi PDO ke database MySQL
│   └── cors.php            # Pengaturan CORS untuk frontend Next.js
├── helpers/
│   ├── response.php        # Helper standarisasi respon JSON
│   └── auth.php            # Verifikasi JWT stateless & RBAC (User / Admin)
├── api/
│   ├── auth/               # Endpoint autentikasi (login, register, me, update-profile)
│   ├── products/           # Endpoint CRUD produk parfum
│   ├── categories/         # Endpoint kategori parfum
│   ├── banners/            # Endpoint banner hero / slider
│   ├── cart/               # Endpoint keranjang belanja
│   ├── wishlist/           # Endpoint wishlist pelanggan
│   ├── checkout/           # Endpoint pemesanan server-side
│   ├── orders/             # Endpoint pesanan & nomor resi pengiriman
│   ├── reviews/            # Endpoint ulasan produk
│   ├── settings/           # Endpoint pengaturan toko
│   └── users/              # Endpoint kelola user & role admin
└── database.sql            # Script SQL skema tabel & seed data lengkap
```

---

## 🚀 Panduan Setup & Import ke phpMyAdmin

### Langkah 1: Nyalakan Apache & MySQL
1. Buka aplikasi **XAMPP Control Panel** (atau Laragon).
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**.

### Langkah 2: Import Database ke phpMyAdmin
1. Buka browser dan kunjungi: **`http://localhost/phpmyadmin`**
2. Klik menu **"New" / "Baru"** di panel sebelah kiri, beri nama database: `ecommerce_parfum`, lalu klik **Create**.
3. Pilih database `ecommerce_parfum` yang baru dibuat.
4. Klik tab **"Import"** di bagian atas.
5. Klik **"Choose File" / "Pilih File"**, lalu arahkan ke file:
   `D:\Ecommerce Parfum\backend\database.sql`
6. Gulir ke bawah dan klik tombol **"Import" / "Kirim"**.
7. Semua tabel (`users`, `categories`, `products`, `orders`, dll) beserta data awal akan otomatis terisi.

---

## 🔑 Akun Default untuk Pengujian

Database sudah dilengkapi akun default siap pakai:

| Role  | Email | Password |
|---|---|---|
| **Administrator** | `admin@parfum.com` | `admin123` |
| **Customer** | `user@parfum.com` | `user123` |

---

## ⚡ Cara Menjalankan Backend PHP

Anda dapat menjalankan backend dengan **salah satu dari 2 cara** berikut:

### Opsi 1: Menggunakan PHP Built-in Server (Sangat Praktis)
Jalankan perintah ini di terminal (dari folder project):
```powershell
cd "d:\Ecommerce Parfum\backend"
php -S localhost:8000
```
API akan aktif di: `http://localhost:8000/api/...`

### Opsi 2: Menggunakan Folder XAMPP (htdocs)
Buat symlink atau copy folder `backend` ke dalam `C:\xampp\htdocs\ecommerce-backend`.
API akan aktif di: `http://localhost/ecommerce-backend/api/...`

---

## 🔗 Daftar Endpoint Utama

| Endpoint | Method | Keterangan | Auth |
|---|---|---|---|
| `/api/auth/login.php` | `POST` | Login user / admin | Publik |
| `/api/auth/register.php` | `POST` | Pendaftaran akun baru | Publik |
| `/api/auth/me.php` | `GET` | Cek profil user yang sedang login | Bearer Token |
| `/api/products/index.php` | `GET` | Ambil katalog produk (search, category, sort) | Publik |
| `/api/products/detail.php?slug={slug}` | `GET` | Detail produk & review | Publik |
| `/api/products/index.php` | `POST` | Tambah produk baru | Admin |
| `/api/products/update.php` | `POST` / `PUT` | Update data produk | Admin |
| `/api/products/delete.php` | `POST` / `DELETE` | Hapus produk | Admin |
| `/api/cart/index.php` | `GET` | Ambil item keranjang user | Bearer Token |
| `/api/cart/add.php` | `POST` | Tambah produk ke keranjang | Bearer Token |
| `/api/checkout/place-order.php` | `POST` | Checkout & buat pesanan baru | Bearer Token |
| `/api/orders/index.php` | `GET` | Daftar pesanan pelanggan / admin | Bearer Token |
| `/api/orders/shipment.php` | `POST` | Input kurir & nomor resi | Admin |
| `/api/settings/index.php` | `GET` / `POST` | Pengaturan informasi toko | Publik / Admin |

