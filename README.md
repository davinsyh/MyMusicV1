# MyMusic (Versi Jamendo API)

MyMusic adalah aplikasi pemutar musik berbasis web yang dibangun menggunakan **Laravel** (dengan **Livewire** dan **Alpine JS** untuk interface interaktif). Aplikasi ini telah dimigrasikan sepenuhnya untuk berintegrasi langsung dengan **Jamendo API** untuk pencarian lagu, penayangan kategori musik terpopuler, penampilan detail album, penjelajahan profil artis, dan pemutaran audio secara real-time.

---

## ⚡ Fitur Utama

- **Beranda Musik Interaktif**: Menyuguhkan kategori lagu terpopuler secara dinamis (Popular Hits, Rock Anthems, Chill Acoustic, Electronic Beats).
- **Hot Albums**: Menampilkan album-album terpopuler pilihan dari Jamendo di halaman beranda.
- **Pencarian Global**: Mencari **Lagu**, **Album**, dan **Artis** dari database Jamendo secara instan melalui dropdown pencarian.
- **Profil Artis**: Menampilkan profil lengkap artis berserta daftar lagu terpopuler mereka.
- **Audio Player Premium**: Pemutar musik canggih di bagian bawah layar yang dilengkapi kontrol putar/jeda, progress bar interaktif, pengatur volume, mode shuffle (acak), dan repeat (ulang).
- **Riwayat Putar & Favorit**: Menyimpan lagu ke dalam daftar favorit serta mencatat riwayat pemutaran terakhir (memerlukan autentikasi user).
- **Pembersihan Arsitektur**: Microservice Python eksternal untuk YouTube/Piped API telah dinonaktifkan sepenuhnya, membuat aplikasi ini lebih ringan, lebih aman, dan bebas dari isu pemblokiran bot YouTube.

---

## 🛠️ Stack Teknologi

- **Backend**: Laravel 11+ (PHP 8.2+)
- **Frontend**: Livewire 3, Alpine JS, TailwindCSS/Vanilla CSS (Sketchy-border design aesthetic)
- **Database**: SQLite (untuk lokal) / AWS RDS MySQL (untuk produksi)
- **API Sumber Data**: Jamendo API v3.0

---

## 🚀 Panduan Instalasi Lokal

Ikuti langkah-langkah di bawah ini untuk menjalankan proyek ini di komputer lokal Anda:

### 1. Clone Repositori
```bash
git clone https://github.com/davinsyh/MyMusicV1.git
cd MyMusicV1
```

### 2. Install Dependensi PHP & Node.js
```bash
# Install package PHP
composer install

# Install package Node.js
npm install
```

### 3. Konfigurasi Environment File
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan masukkan **Jamendo Client ID** Anda:
```env
JAMENDO_CLIENT_ID=kredensial_client_id_jamendo_anda
```

### 4. Generate App Key & Migrasi Database
```bash
# Generate key enkripsi Laravel
php artisan key:generate

# Buat database sqlite kosong di database/database.sqlite (jika menggunakan sqlite)
touch database/database.sqlite

# Jalankan migrasi database beserta seeder data pengujian
php artisan migrate --seed
```
*Data login default pengujian:*
- Email: `user@example.com` atau `admin@example.com`
- Password: `password`

### 5. Jalankan Server Pengembangan
Jalankan kedua perintah ini di terminal terpisah:
```bash
# Jalankan server Laravel (Port 8000)
php artisan serve

# Jalankan Vite dev server untuk compile assets
npm run dev
```
Buka `http://localhost:8000` pada browser Anda.

---

## 🌐 Panduan Deployment (AWS EC2 & RDS)

Petunjuk lengkap mengenai langkah pembaruan server dan konfigurasi AWS RDS dapat dibaca pada berkas panduan deployment khusus:
👉 **[Panduan Deployment AWS](aws_deployment_guide.md)**
