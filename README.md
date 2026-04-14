# Dilan Maskapai - Sistem Pemesanan Tiket Pesawat

Sistem pemesanan tiket pesawat online yang dibangun dengan PHP native, MySQL, dan CSS murni. Aplikasi ini menyediakan fitur lengkap untuk memesan tiket pesawat dengan alur transaksi yang aman dan terverifikasi.

## Fitur Utama

### Autentikasi & Keamanan
- Sistem registrasi dan login pengguna
- Role-based access control (Admin & User)
- Session management yang aman
- Password hashing dengan `password_hash()`
- Validasi input yang komprehensif

### Fitur User
- Pencarian penerbangan berdasarkan rute dan tanggal
- Detail penerbangan dengan informasi lengkap
- Pemesanan tiket untuk multiple penumpang
- Upload bukti pembayaran
- Riwayat transaksi
- E-ticket digital yang dapat dicetak
- Profil pengguna

### Fitur Admin 
- Dashboard dengan statistik lengkap
- CRUD Bandara, Maskapai, dan Penerbangan
- Manajemen jadwal, harga, dan kapasitas kursi
- Verifikasi pembayaran (approve/reject)
- Manajemen pengguna
- Monitoring semua transaksi

### Alur Transaksi
1. User login/register
2. Cari dan pilih penerbangan
3. Isi data penumpang
4. Sistem generate booking dan invoice
5. User upload bukti pembayaran
6. Status menunggu verifikasi admin
7. Admin approve/reject pembayaran
8. Jika approve, tiket aktif dan dapat dicetak

## Struktur Database

### Tabel Utama
- `users` - Data pengguna
- `roles` - Role pengguna (admin/user)
- `airports` - Data bandara
- `airlines` - Data maskapai
- `flights` - Data penerbangan
- `bookings` - Data pesanan
- `booking_passengers` - Data penumpang per pesanan
- `payments` - Data pembayaran
- `payment_proofs` - Bukti pembayaran
- `tickets` - Data e-ticket

## Struktur Folder (Sederhana & Rapih)

```
dilanapi/
|
|-- config/                 # Konfigurasi sistem
|   |-- database.php        # Koneksi database
|   |-- config.php          # Konfigurasi aplikasi
|   `-- .env.example       # Template konfigurasi
|
|-- auth/                   # Autentikasi
|   |-- login.php          # Halaman login
|   |-- register.php       # Halaman registrasi
|   `-- logout.php         # Proses logout
|
|-- admin/                  # Halaman admin
|   |-- dashboard.php       # Dashboard admin
|   |-- flights.php         # Kelola penerbangan
|   |-- flight_form.php     # Form tambah/edit penerbangan
|   `-- bookings.php        # Kelola pesanan
|
|-- user/                   # Halaman user
|   |-- flights.php         # Lihat jadwal penerbangan
|   |-- booking.php         # Form pemesanan
|   |-- payment.php         # Upload bukti pembayaran
|   |-- bookings.php        # Riwayat pesanan
|   |-- ticket.php          # E-ticket
|   `-- profile.php         # Profil user
|
|-- includes/               # Template components
|   |-- header.php          # Header HTML
|   `-- footer.php          # Footer HTML
|
|-- assets/                 # Static assets
|   |-- css/
|   |   `-- style.css       # Stylesheet utama
|   `-- js/
|       `-- script.js       # JavaScript utama
|
|-- setup/                  # Setup & maintenance
|   |-- setup_database.php  # Setup database awal
|   `-- add_flights.php     # Tambah jadwal penerbangan
|
|-- uploads/                # Upload bukti pembayaran
|-- tickets/                # File e-ticket
|
|-- index.php               # Landing page
|-- database.sql            # Schema database
|-- STRUCTURE.md            # Dokumentasi struktur
`-- README.md               # Dokumentasi utama
```

## Persyaratan Sistem

- **PHP** 7.4 atau lebih tinggi
- **MySQL** 5.7 atau lebih tinggi
- **Web Server** (Apache/Nginx)
- **Ekstensi PHP**: PDO, PDO_MySQL, GD (untuk upload gambar)

## Instalasi

### 1. Setup Database (Cara Mudah)
Buka browser dan akses: `http://localhost/dilanapi/setup/setup_database.php`

Script ini akan:
- Membuat database `dilan_airlines` otomatis
- Import semua tabel dan data dummy
- Memberikan konfirmasi setup selesai

### 2. Setup Database (Manual)
Jika ingin setup manual:
```bash
# Import database schema
mysql -u root -p < database.sql
```

### 3. Konfigurasi Database
Edit file `config/database.php` sesuai dengan konfigurasi database Anda:
```php
private $host = "localhost";           // Host database
private $db_name = "dilan_airlines";  // Nama database
private $username = "root";            // Username MySQL
private $password = "";                // Password MySQL
```

### 4. Tambah Jadwal Penerbangan
Untuk menambah jadwal penerbangan demo:
Buka browser: `http://localhost/dilanapi/setup/add_flights.php`

### 5. Permission Folder
Pastikan folder berikut memiliki permission write:
```bash
chmod 755 uploads/
chmod 755 tickets/
```

### 6. Akses Aplikasi
Buka browser dan akses: `http://localhost/dilanapi/`

## Akun Default

### Admin
- **Username**: duvadilan
- **Password**: Davin1702
- **Email**: davinrheyadi1702@gmail.com

### User Demo
- **Username**: gunawan
- **Password**: Gunawan123
- **Email**: user@example.com

## Fitur Keamanan

- **Prepared Statements** untuk mencegah SQL Injection
- **Input Validation** dan sanitasi
- **File Upload Validation** dengan batasan ukuran dan tipe
- **Session Protection** dengan secure cookies
- **Role-based Access Control**
- **CSRF Protection** (dapat ditambahkan)

## API Endpoints

### Autentikasi
- `POST /auth/login.php` - Login pengguna
- `POST /auth/register.php` - Registrasi pengguna
- `GET /auth/logout.php` - Logout

### User
- `GET /user/search.php` - Cari penerbangan
- `POST /user/booking.php` - Buat pesanan
- `POST /user/payment.php` - Upload pembayaran
- `GET /user/bookings.php` - Riwayat pesanan
- `GET /user/ticket.php` - Lihat e-ticket

### Admin
- `GET /admin/dashboard.php` - Dashboard
- `GET /admin/flights.php` - Kelola penerbangan
- `GET /admin/bookings.php` - Kelola pesanan
- `POST /admin/flight_form.php` - Tambah/edit penerbangan

## Customization

### Menambah Bandara Baru
```sql
INSERT INTO airports (code, name, city, country) 
VALUES ('CGK', 'Soekarno-Hatta', 'Jakarta', 'Indonesia');
```

### Menambah Maskapai Baru
```sql
INSERT INTO airlines (name, code, logo) 
VALUES ('Garuda Indonesia', 'GA', 'garuda.png');
```

### Menambah Penerbangan Baru
```sql
INSERT INTO flights (flight_number, airline_id, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, price, available_seats, total_seats) 
VALUES ('GA123', 1, 1, 2, '2024-12-25 08:00:00', '2024-12-25 10:30:00', 1500000, 180, 180);
```

## Troubleshooting

### Error Koneksi Database
- Pastikan MySQL service berjalan
- Cek kredensial database di `config/database.php`
- Pastikan database `dilan_airlines` sudah dibuat

### Upload File Gagal
- Cek permission folder `uploads/`
- Pastikan ukuran file tidak melebihi 5MB
- Verifikasi tipe file (JPG/PNG)

### Session Error
- Pastikan session path dapat ditulis
- Cek konfigurasi `session.save_path` di php.ini

## Development Notes

### Best Practices yang Diterapkan
- **Separation of Concerns**: Logic, presentation, dan data terpisah
- **DRY Principle**: Menghindari duplikasi kode
- **Security First**: Validasi dan sanitasi input
- **Responsive Design**: Mobile-first approach
- **Clean Code**: Nama variabel dan fungsi yang deskriptif

### Potential Improvements
- Implementasi RESTful API
- Integrasi payment gateway (Midtrans, Xendit)
- Email notifications
- SMS notifications
- Real-time flight status
- Multi-language support
- Advanced search filters

## License

Proyek ini dibuat untuk tujuan edukasi dan demo. Feel free to modify dan distribute sesuai kebutuhan.

## Support

Untuk pertanyaan atau bantuan, silakan hubungi:
- Email: info@dilanmaskapai.com
- Telepon: +62 21 1234 5678

---

**Dilan Maskapai** - Terbang dengan Nyaman dan Aman
