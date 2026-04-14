# Struktur Folder Dilan Maskapai

## Folder Structure (Clean & Organized)

```
dilanapi/
|
|-- config/                 # Konfigurasi sistem
|   |-- database.php        # Koneksi database
|   |-- config.php          # Konfigurasi aplikasi
|   `-- .env.example       # Template konfigurasi
|
|-- auth/                   # Autentikasi
|   |-- login.php          # Halaman login dengan role selection
|   |-- register.php       # Halaman registrasi (admin/customer)
|   `-- logout.php         # Proses logout
|
|-- admin/                  # Halaman admin
|   |-- dashboard.php       # Dashboard admin
|   |-- flights.php         # Kelola penerbangan
|   |-- flight_form.php     # Form tambah/edit penerbangan
|   `-- bookings.php        # Kelola pesanan
|
|-- user/                   # Halaman user/customer
|   |-- flights.php         # Lihat jadwal penerbangan
|   |-- booking.php         # Form pemesanan
|   |-- payment.php         # Upload bukti pembayaran
|   |-- bookings.php        # Riwayat pesanan
|   |-- ticket.php          # E-ticket
|   `-- profile.php         # Profil user
|
|-- includes/               # Template components
|   |-- header.php          # Header HTML dengan navigation
|   `-- footer.php          # Footer HTML
|
|-- assets/                 # Static assets
|   |-- css/
|   |   `-- style.css       # Stylesheet utama (elegant design)
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

## Fitur Sistem

### **Authentication System**
- **Role-based login**: Admin & Customer
- **Registration with role selection**: Pilih role saat daftar
- **Secure password hashing**: password_hash() & password_verify()

### **Flight Management**
- **Admin**: Create, edit, delete flights
- **Customer**: View available flights, book tickets
- **Real-time availability**: Available seats tracking

### **Booking System**
- **Online booking**: Form pemesanan
- **Payment upload**: Upload bukti pembayaran
- **E-ticket generation**: PDF ticket generation
- **Booking history**: Riwayat pesanan

### **User Management**
- **Profile management**: Edit profil user
- **Role-based access**: Admin vs Customer access
- **Session management**: Secure session handling

## Design Features

### **UI/UX**
- **Elegant modern design**: Glass morphism effects
- **Responsive layout**: Mobile-friendly
- **Gradient backgrounds**: Visual appeal
- **Smooth animations**: Better user experience

### **Database Structure**
- **Normalized tables**: flights, airlines, airports, users, roles
- **Foreign key constraints**: Data integrity
- **Secure relationships**: Proper table relationships

## File yang Dihapus (Cleanup)

### **Debug Files Removed:**
- `make_admin.php` - Debug admin creation
- `create_admin_simple.php` - Simple admin creation
- `debug_database.php` - Database debugging
- `recreate_roles_admin.php` - Role recreation
- `create_admin_now.php` - Quick admin creation
- `admin_fix.php` - Admin fix script
- `debug_flights.php` - Flight debugging
- `fix_flights.php` - Flight fix script
- `debug_specific_flights.php` - Specific debugging
- `fix_existing_flights.php` - Flight data fix
- `user_list.php` - User listing
- `fix_admin_final.php` - Final admin fix

### **Benefits:**
1. **Clean Structure** - Hanya file penting yang tersisa
2. **Production Ready** - Tidak ada file debugging
3. **Easy Maintenance** - Struktur yang jelas
4. **Professional Look** - Folder yang terorganisir

## Installation Guide

1. **Setup Database**: `setup/setup_database.php`
2. **Add Flights**: `setup/add_flights.php`
3. **Register Admin**: `auth/register.php` (pilih role admin)
4. **Login**: `auth/login.php`

## Access Points

- **Landing Page**: `index.php`
- **Login**: `auth/login.php`
- **Register**: `auth/register.php`
- **Admin Dashboard**: `admin/dashboard.php`
- **Customer Flights**: `user/flights.php`
