# PRD: Sistem Booking Hotel dan Kos

| | |
|---|---|
| **Versi** | 1.2 |
| **Tanggal** | 31 Oktober 2026 |
| **Stack** | Laravel + Vue (Inertia starter kit) |
| **Timezone** | Asia/Jakarta (WIB), dipakai untuk penyimpanan dan perhitungan |
| **Status** | Siap development |

## Riwayat Perubahan

| Versi | Perubahan |
|---|---|
| 1.0 | Versi awal |
| 1.1 | Harga berpindah ke level tipe kamar (`room_types`, `room_type_prices`) |
| 1.2 | Audit konsistensi: perbaikan unique index soft delete, timezone tunggal WIB, aturan batas upload, expired vs verifikasi, denda hanya pada booking confirmed, `overdue` menjadi status turunan, refund customer tanpa denda, penyederhanaan status refund, penyelesaian booking, perpanjangan lanjutan, hak admin atas status kamar, syarat dan ketentuan, audit trail (`status_histories`), format kode, notifikasi tambahan |

---

## 1. Ringkasan

Aplikasi web untuk memesan hotel dan kos. Satu pengelola mengoperasikan banyak properti di banyak kota (contoh: "Kos Melati Singkawang", "Hotel Mawar Singkawang"). Setiap properti memiliki satu atau lebih **tipe kamar** (contoh: Standard, Deluxe, AC, Non-AC) dengan tarif masing-masing. Pembayaran dicatat melalui upload bukti transfer, dan arsitekturnya siap diganti payment gateway tanpa merombak alur.

## 2. Tujuan

1. Pengunjung dapat melihat properti, tipe kamar, dan memesan secara online.
2. Seluruh alur booking, tagihan, pembayaran, dan refund tercatat dan dapat diaudit.
3. Pengelola mengontrol data master, verifikasi pembayaran, dan refund.
4. Kode mengikuti SOLID, design pattern, dan clean code, dengan keamanan dan SEO dasar.
5. Siap migrasi ke payment gateway lewat konfigurasi.

## 3. Di Luar Cakupan (Fase Ini)

- Payment gateway (struktur disiapkan, implementasi menyusul)
- Multi-tenant / banyak pemilik usaha
- Laporan keuangan, multi-currency
- Diskon dan promo
- Pembayaran DP selain skema cicilan tahunan
- Harga musiman / akhir pekan
- Galeri gambar per tipe kamar (galeri di level properti)
- Check-in/check-out terkelola di sistem (tidak ada status check-in; lihat 6.10)

## 4. Role dan Hak Akses

| Role | Hak akses |
|---|---|
| **Superadmin** | Kelola kota, fasilitas, properti (thumbnail dan galeri), tipe kamar, tarif, kamar, rekening bank, user, penugasan admin, moderasi review, halaman syarat dan ketentuan. Dapat melakukan semua aksi admin pada semua properti |
| **Admin** | Pada properti yang ditugaskan saja: melihat booking, verifikasi pembayaran, menandai booking selesai, membatalkan booking (wajib alasan), memproses refund, **mengubah status kamar** (`available`/`maintenance`), membuka kembali batas upload bukti |
| **Customer** | Registrasi, verifikasi email, booking, upload bukti bayar, melihat tagihan, perpanjang, batalkan, mengisi rekening refund, menulis review |
| **Publik** | Melihat landing page, list, detail properti, halaman per kota, syarat dan ketentuan, kebijakan privasi |

Hanya superadmin yang boleh membuat atau mengubah data properti, tipe kamar, tarif, dan membuat atau menghapus kamar. Admin hanya boleh mengubah kolom `status` kamar.

## 5. Halaman

**Publik**
- Landing page
- List properti: filter kota, tipe properti, periode, rentang harga; pagination
- Detail properti: galeri, fasilitas, daftar tipe kamar beserta tarif dan kapasitas, kamar tersedia pada tanggal terpilih, review, form booking, tombol "Tanya admin" (WhatsApp, memakai `whatsapp_number`)
- Halaman per kota (`/kota/{slug}`)
- Syarat dan ketentuan, kebijakan privasi

**Auth:** login, register, lupa password, verifikasi email

**Customer:** profil (nama, telepon, password), riwayat booking, detail booking dan tagihan, upload bukti, perpanjang, batalkan (menampilkan hasil kelayakan refund sebelum konfirmasi), form rekening refund, form review

**Admin:** daftar booking, detail booking (verifikasi pembayaran, tombol **Tandai selesai**, tombol **Batalkan**), tagihan telat, refund, daftar kamar (ubah status)

**Superadmin:** semua menu admin, ditambah kota, fasilitas, properti, tipe kamar dan tarif, kamar, rekening bank, user dan penugasan admin, moderasi review, pengelolaan konten syarat dan ketentuan

## 6. Aturan Bisnis

### 6.1 Properti
- Nama berformat brand + kota, contoh "Kos Melati Singkawang". Nama unik per kota.
- Slug dibuat otomatis dari nama, unik, dan tidak berubah saat nama diedit.
- Status: `draft` (tidak tampil publik) atau `published`.
- Dapat dipublikasikan hanya jika memiliki minimal satu tipe kamar dengan tarif aktif dan minimal satu kamar `available`.
- Satu thumbnail wajib. Galeri maksimal 10 gambar, dapat diurutkan dan dihapus.

### 6.2 Tipe Kamar dan Tarif
- Properti memiliki satu atau lebih tipe kamar. Properti berkamar seragam cukup satu tipe, misalnya "Standard".
- Tipe kamar memiliki nama, deskripsi, dan kapasitas maksimal tamu. Nama unik per properti.
- Setiap kamar fisik wajib memiliki tepat satu tipe, dan tipe itu harus milik properti yang sama.
- **Tarif ditetapkan per tipe kamar** per periode (harian, bulanan, tahunan): harga per 1 periode, durasi minimal, durasi maksimal, status aktif.
- Tipe tanpa tarif aktif tidak dapat dipesan. Tipe yang masih memiliki kamar tidak dapat dihapus.
- Perubahan tarif tidak memengaruhi booking yang sudah ada (harga disimpan sebagai snapshot).

### 6.3 Periode Sewa dan Cara Bayar

| Periode | Cara bayar yang diizinkan |
|---|---|
| Harian | Lunas sekaligus, atau per hari |
| Bulanan | Lunas sekaligus (1 atau N bulan), atau per bulan |
| Tahunan | Lunas sekaligus, atau cicilan bebas |

- Total = harga satuan tipe kamar × durasi (tanpa diskon), dihitung server.
- `check_out` dihitung server dari `check_in` + durasi (bulan dan tahun tanpa overflow akhir bulan).
- Durasi dibatasi min/max pada tarif (default: harian maks 30, bulanan maks 24, tahunan maks 5).
- Jumlah tamu tidak boleh melebihi kapasitas tipe kamar.
- Syarat booking: email sudah terverifikasi, nomor telepon terisi, dan customer mencentang persetujuan syarat dan ketentuan. Versi dan waktu persetujuan disimpan di booking.

### 6.4 Cicilan (Tahunan)
- Satu invoice dengan pembayaran parsial diizinkan.
- Pembayaran pertama minimal **50%** dari total. Berikutnya bebas, maksimal sebesar sisa tagihan. Jika sisa lebih kecil dari minimum, customer boleh membayar sisanya.
- Wajib **lunas sebelum check-out** (`due_date` invoice = `check_out` dikurangi 1 hari).
- Hanya satu payment berstatus `waiting_verification` per invoice pada satu waktu.

### 6.5 Konfirmasi dan Kedaluwarsa
- Booking baru berstatus `pending_payment` dengan batas bayar pertama (`expires_at`, default 24 jam).
- Booking menjadi `confirmed` saat pembayaran pertama disetujui (cicilan: minimal 50% terverifikasi). `confirmed_at` dicatat.
- Jika belum ada pembayaran terverifikasi sampai `expires_at`, booking menjadi `expired` dan kamar kembali tersedia.
- **Booking yang memiliki payment `waiting_verification` tidak boleh di-expire** oleh scheduler.
- Jika bukti ditolak dan booking masih `pending_payment`, `expires_at` diperpanjang menjadi `now() + payment_expiry_hours` (jika lebih lama dari nilai sebelumnya) agar customer sempat mengunggah ulang.
- Booking `pending_payment` yang belum kedaluwarsa tetap memblokir kamar.
- Payment `pending` pada booking yang kedaluwarsa menjadi `expired`.

### 6.6 Keterlambatan dan Denda
- Aturan keterlambatan **hanya berlaku untuk invoice pada booking berstatus `confirmed`**. Invoice pertama sebelum konfirmasi diatur oleh `expires_at`, bukan denda.
- `overdue` adalah **penanda turunan** (bukan status tersimpan): `due_date` sudah lewat dan invoice masih `unpaid` atau `partial`. Customer dan admin diberi notifikasi.
- Toleransi **7 hari** setelah `due_date` (`grace_until`).
- Lewat toleransi: denda **Rp 50.000, sekali per invoice** (dijaga `late_fee_applied_at`).
- Total tagihan = `amount + late_fee`. Invoice `paid` bila `paid_amount ≥ amount + late_fee`.
- Invoice yang sudah `paid` tidak terkena denda.
- Booking tidak otomatis dibatalkan. Admin menindaklanjuti manual.
- Catatan: denda Rp 50.000 dan toleransi 7 hari dapat lebih besar atau lebih panjang dari periode sewa harian. Ini disengaja sesuai kebijakan pengelola.

### 6.7 Pembatalan dan Refund

| Dibatalkan oleh | Aturan |
|---|---|
| **Customer** pada `pending_payment` | Diizinkan, tanpa refund (belum ada pembayaran terverifikasi). Tidak dapat dibatalkan selama ada payment `waiting_verification` |
| **Customer** pada `confirmed` | Refund penuh dari **pokok sewa** jika ≤ 3 hari sejak `confirmed_at` **dan** belum check-in. Selain itu tidak ada refund. **Denda yang sudah dibayar tidak dikembalikan** |
| **Admin/Superadmin** (misal kamar rusak) | Refund penuh dari seluruh pembayaran terverifikasi, **termasuk denda yang sudah dibayar**. Alasan wajib diisi |

- Nominal refund customer = jumlah `min(paid_amount, amount)` seluruh invoice booking tersebut (bagian denda dikecualikan). Nominal refund admin = jumlah `paid_amount` seluruh invoice. Selalu dihitung server.
- Sebelum konfirmasi, sistem menampilkan kelayakan dan nominal refund kepada customer.
- Customer mengisi rekening tujuan refund pada form pembatalan. Pada pembatalan oleh admin, refund dibuat berstatus `awaiting_account` dan customer diminta mengisi rekening.
- Jika nominal refund = 0, tidak dibuat record refund. Keputusan tercatat di riwayat status.
- Pembatalan membuat invoice yang belum lunas berstatus `cancelled`, termasuk denda yang belum dibayar.
- Satu booking hanya boleh memiliki satu refund aktif.
- Alur status refund: `awaiting_account` → `requested` → `completed`. `rejected` hanya bila data rekening tidak valid, dan customer dapat memperbaiki sehingga kembali `requested`.
- Admin memproses refund secara manual (transfer sendiri), lalu mengunggah bukti transfer refund.
- Pembatalan oleh admin pada booking `confirmed` memberi notifikasi ke superadmin.

### 6.8 Perpanjangan
- Dibuat sebagai booking baru yang terhubung ke booking asal (`extended_from_id`).
- Kamar sama, `check_in` = `check_out` booking asal, memakai **harga terbaru tipe kamar tersebut**, dengan periode dan cara bayar dipilih ulang.
- Hanya booking `confirmed` yang dapat diperpanjang, mulai H-14 sebelum akhir masa sewa.
- **Satu booking induk hanya boleh memiliki satu perpanjangan aktif** (`pending_payment` atau `confirmed`). Perpanjangan lanjutan dibuat dari booking anak yang `confirmed`.
- Ditolak otomatis jika kamar sudah dipesan pihak lain setelah tanggal tersebut, jika kamar berstatus `maintenance`, atau jika tipe kamar tidak lagi memiliki tarif aktif untuk periode yang dipilih.
- Hitungan refund 3 hari berlaku per booking. Membatalkan perpanjangan hanya memengaruhi booking perpanjangan itu.
- **Jika booking induk dibatalkan** (oleh siapa pun), perpanjangan aktifnya ikut dibatalkan. Refund dihitung per booking sesuai aturan pembatalnya (admin: penuh, customer: aturan 3 hari masing-masing booking).

### 6.9 Review
- Hanya customer dengan booking `completed`, satu review per booking.
- Rating 1 sampai 5 dan teks opsional.
- Untuk rantai perpanjangan, review dikaitkan ke booking terakhir yang `completed`.
- Review berlaku di level properti. Moderasi (sembunyikan) hanya oleh superadmin.
- Rata-rata rating dan jumlah review properti diperbarui otomatis.

### 6.10 Penyelesaian Booking dan Definisi "Belum Check-in"
- **Tandai selesai** dilakukan oleh admin (atau superadmin), hanya jika: status `confirmed`, tanggal hari ini ≥ `check_out`, dan semua invoice `paid` atau `cancelled`.
- Booking dengan tunggakan setelah `check_out` tidak dapat diselesaikan admin. Superadmin dapat menutupnya dengan catatan wajib (invoice tetap tercatat belum lunas).
- Tidak ada status check-in. **"Belum check-in" = tanggal hari ini < `check_in`**.

### 6.11 Status Kamar
- Admin properti dapat mengubah status kamar antara `available` dan `maintenance`.
- Kamar `maintenance` tidak ditawarkan untuk booking atau perpanjangan baru.
- Mengubah kamar ke `maintenance` saat ada booking aktif di kamar itu menampilkan peringatan dan memberi notifikasi ke superadmin. Booking tidak dibatalkan otomatis. Pembatalan tetap lewat 6.7.

### 6.12 Kode dan Nomor
- Kode booking: `BK-YYYYMMDD-NNNN` (contoh `BK-20261031-0001`).
- Nomor invoice: `INV-YYYYMMDD-NNNN`.
- Nomor urut harian dibuat atomik di dalam transaksi memakai tabel `number_sequences`.

### 6.13 Pengaturan Bisnis
Disimpan di `config/booking.php`, diakses lewat `BookingSettings`.

| Kunci | Nilai |
|---|---|
| min_down_payment_percent | 50 |
| late_grace_days | 7 |
| late_fee | 50000 |
| refund_window_days | 3 |
| payment_expiry_hours | 24 |
| max_rejected_uploads | 3 |
| extension_open_days_before_end | 14 |
| max_gallery_images | 10 |
| terms_version | 1 |

### 6.14 Batas Upload Bukti
- Batas **3 kali penolakan** per invoice (yang dihitung hanya payment berstatus `rejected`, pembayaran yang disetujui tidak dihitung, sehingga cicilan tidak terhambat).
- Setelah 3 penolakan, upload dikunci dan customer diarahkan ke tombol "Tanya admin".
- Admin dapat **membuka kembali** upload. Aksi ini mengisi `invoices.upload_reset_at`, dan penghitungan hanya memakai penolakan setelah waktu tersebut.

## 7. Alur Utama

**Persiapan (superadmin):** buat kota dan fasilitas, buat properti beserta gambar, tipe kamar dan tarif, kamar dan tipenya, rekening bank, akun admin dan penugasannya, lalu publikasikan properti.

**Booking (customer):** pilih properti, tipe kamar, periode, durasi, tanggal mulai, dan cara bayar. Sistem menampilkan kamar tersedia, customer memilih kamar, membaca dan menyetujui syarat dan ketentuan. Server memvalidasi (email terverifikasi, telepon, kapasitas), mengecek bentrok, menghitung total, lalu membuat booking dan invoice. Customer transfer dan mengunggah bukti.

**Verifikasi (admin):** admin menerima notifikasi, memeriksa bukti, approve atau reject dengan alasan. Penolakan dapat diunggah ulang (lihat 6.14).

**Penagihan:** skema per periode membuat invoice per hari/bulan dengan pengingat H-3. Scheduler mengirim notifikasi `overdue` dan mengenakan denda (booking `confirmed` saja).

**Pembatalan dan refund:** lihat 6.7. **Perpanjangan:** lihat 6.8.

**Selesai:** admin menandai `completed` (6.10), customer dapat memberi review.

## 8. Model Tiga Lapisan Pembayaran

| Lapisan | Arti | Contoh |
|---|---|---|
| Booking | Kontrak sewa | Kamar 203 (Deluxe), 1 Jan sampai 1 Jul |
| Invoice | Tagihan | Tagihan Januari Rp 2.000.000 |
| Payment | Satu kali upload bukti | Transfer Rp 2.000.000, menunggu verifikasi |

Satu booking memiliki banyak invoice, satu invoice memiliki banyak payment.

## 9. Status

| Entitas | Status tersimpan |
|---|---|
| Booking | `pending_payment` → `confirmed` → `completed`; `cancelled`; `expired` |
| Invoice | `unpaid` → `partial` → `paid`; `cancelled`. (`overdue` = penanda turunan, lihat 6.6) |
| Payment | `pending` → `waiting_verification` → `paid`; `rejected`; `expired`; `failed` |
| Refund | `awaiting_account` → `requested` → `completed`; `rejected` (kembali ke `requested` setelah data diperbaiki) |

Aturan transisi ditempatkan di Enum (`canTransitionTo()`). Setiap perubahan status dicatat di `status_histories`.

## 10. Skema Database

Nominal uang memakai `unsignedBigInteger` (rupiah). Semua tabel memiliki `id`, `created_at`, `updated_at` (kecuali pivot). **SD** = soft delete. Seluruh timestamp dan tanggal disimpan dalam WIB (`app.timezone = Asia/Jakarta`, sesi database `+07:00`).

### Relasi
```
users ──< property_user >── properties ──< property_images
                               │  ├──< room_types ──< room_type_prices
cities ──< properties          │  │        └──< rooms
                               │  └──< bank_accounts (nullable)
facilities ──< facility_property >── properties

users ──< bookings >── rooms (→ room_types), properties
          │  └── extended_from_id → bookings
          ├──< invoices ──< payments >── bank_accounts
          ├──< refunds
          └──── reviews (1 booking = 1 review)

status_histories (polimorfik: booking, invoice, payment, refund)
number_sequences (penomoran harian)
```

### Strategi Unique Index dengan Soft Delete
Unique yang melibatkan `deleted_at` tidak efektif karena `NULL` dianggap berbeda. Solusinya kolom generated `active_key`:

```php
// MySQL 8
$table->unsignedTinyInteger('active_key')->nullable()
      ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');
$table->unique(['slug', 'active_key']);
```

Nilai 1 untuk data aktif, `NULL` untuk data terhapus, sehingga data terhapus tidak memblokir data baru. Jika memakai PostgreSQL, gunakan partial unique index `WHERE deleted_at IS NULL`. Berlaku untuk `properties`, `room_types`, dan `rooms`.

### users
name, email (unique), email_verified_at, password, phone (null, wajib saat booking), `role` (index), remember_token, SD
Catatan: email tetap unik meski akun terhapus. Pemulihan akun dilakukan superadmin.

### cities
name, slug (unique), province

### facilities
name (unique), icon

### properties
city_id, created_by, `type` (hotel/kos), name, slug, description, address, latitude, longitude, whatsapp_number, `status`, thumbnail_path, thumbnail_alt, rating_avg, reviews_count, active_key (generated), SD
Index: unique (slug, active_key), unique (name, city_id, active_key), status, type, city_id

### property_images
property_id (cascade), path, alt, sort_order

### facility_property (pivot)
property_id, facility_id. PK gabungan.

### property_user (penugasan admin)
property_id, user_id. PK gabungan. User harus berrole admin (divalidasi di `AssignAdminAction`).

### room_types
property_id, name, description (null), capacity (unsignedSmallInt), active_key (generated), SD
Unique (property_id, name, active_key)

### room_type_prices
room_type_id (cascade), `period`, price, min_duration (default 1), max_duration, is_active (default true)
Unique (room_type_id, period)

### rooms
property_id, room_type_id (restrict), name, `status`, active_key (generated), SD
Unique (property_id, name, active_key). Index: room_type_id.
Aturan aplikasi: `room_type_id` harus milik `property_id` yang sama.

### bank_accounts
property_id (null = global), bank_name, account_number, account_holder, is_active

### bookings
code (unique), property_id, room_type_id (snapshot), room_id, user_id, extended_from_id (null), `period`, duration, `payment_plan`, unit_price (snapshot), total_price, guests, check_in, check_out, `status`, expires_at, confirmed_at, completed_at (null), cancelled_at, cancelled_by (null = sistem), cancel_reason, terms_accepted_at, terms_version, note, SD
Index: (room_id, check_in, check_out), status, user_id, property_id, room_type_id, expires_at, extended_from_id

### invoices
booking_id, number (unique), amount, late_fee (default 0), paid_amount (default 0), due_date, grace_until, late_fee_applied_at (null), upload_reset_at (null), allow_partial, `status`, SD
Index: status, due_date, grace_until, booking_id

### payments
invoice_id, `method`, `status`, amount, bank_account_id (null), proof_path (null, privat), external_id (null, unique), external_reference (null), payload (json, null), paid_at, verified_by, verified_at, rejection_reason, SD
Index: invoice_id, status

### refunds
booking_id, requested_by, `initiated_by` (customer/admin), amount, `status`, bank_name (null), account_number (null), account_holder (null), proof_path (null, privat), processed_by, processed_at, rejection_reason, SD
Index: booking_id, status
Catatan: kolom rekening null saat `awaiting_account`.

### reviews
property_id, user_id, booking_id (unique), rating (1-5), comment, is_visible (default true), SD
Index: (property_id, is_visible)

### status_histories (BARU, audit trail)
historable_type, historable_id, from_status (null), to_status, actor_id (FK users, null = sistem), note (null), created_at
Index: (historable_type, historable_id), created_at
Tanpa soft delete dan tanpa update (append-only).

### number_sequences (BARU)
name (unique, contoh `BK-20261031`), last_number (unsignedInt)
Diperbarui di dalam transaksi dengan `lockForUpdate()`.

### Bawaan Laravel
password_reset_tokens, sessions, cache, jobs, failed_jobs, notifications

### Kebijakan Integritas
- Soft delete: users, properties, room_types, rooms, bookings, invoices, payments, refunds, reviews.
- Tanpa soft delete: cities, facilities, property_images, room_type_prices, bank_accounts, status_histories, number_sequences, pivot.
- Foreign key `restrictOnDelete`, kecuali `property_images` dan `room_type_prices` (`cascadeOnDelete`).
- Tipe kamar tidak dapat dihapus selama masih memiliki kamar aktif.

## 11. Enum

| Enum | Nilai |
|---|---|
| UserRole | superadmin, admin, customer |
| PropertyType | hotel, kos |
| PropertyStatus | draft, published |
| RoomStatus | available, maintenance |
| BookingPeriod | daily, monthly, yearly |
| PaymentPlan | full, per_period, installment |
| BookingStatus | pending_payment, confirmed, completed, cancelled, expired |
| InvoiceStatus | unpaid, partial, paid, cancelled |
| PaymentStatus | pending, waiting_verification, paid, rejected, expired, failed |
| PaymentMethod | manual_transfer (midtrans nanti) |
| RefundStatus | awaiting_account, requested, completed, rejected |
| RefundInitiator | customer, admin |

## 12. Arsitektur Kode

```
app/
├── Enums/
├── Contracts/      PaymentGatewayInterface, RefundGatewayInterface, PeriodCalculatorInterface,
│                   PaymentPlanInterface, RefundPolicyInterface, ImageStorageInterface
├── DTOs/           BookingData, PropertyData, RoomTypeData, PaymentInstruction, Quote, RefundDecision
├── Models/
├── Http/
│   ├── Controllers/   (tipis: hanya HTTP)
│   ├── Requests/      (validasi + authorize)
│   └── Resources/
├── Policies/       Property, RoomType, Room, Booking, Invoice, Payment, Refund, Review
├── Services/
│   ├── Pricing/    PricingService, Daily/Monthly/YearlyCalculator, PeriodCalculatorFactory
│   ├── Payment/    ManualTransferGateway, PaymentGatewayManager (MidtransGateway nanti)
│   ├── PaymentPlan/ FullPaymentPlan, PerPeriodPlan, InstallmentPlan
│   ├── Refund/     CustomerCancellationRefundPolicy, AdminCancellationRefundPolicy
│   └── PropertyService, RoomTypeService, AvailabilityService, BookingService,
│       ReviewService, ImageStorageService, NumberGenerator,
│       StatusHistoryRecorder, BookingSettings
├── Actions/        CreateBooking, ExtendBooking, GenerateInvoices, SubmitPaymentProof,
│                   ApprovePayment, RejectPayment, CancelBooking, CompleteBooking,
│                   ProcessRefund, RejectRefund, SubmitRefundAccount, ReopenUpload,
│                   ChangeRoomStatus, AssignAdmin
├── Repositories/   PropertyRepository, BookingRepository
├── Events/ + Listeners/   InvoicePaid → ConfirmBookingOnFirstInvoicePaid, notifikasi,
│                          pencatatan status_histories
├── Observers/      ReviewObserver
└── Console/Commands/ ExpireUnpaidBookings, NotifyOverdueInvoices, ApplyLateFees,
                      SendInvoiceReminders, SendExtensionReminders
```

### Harga
`PricingService::quote()` membaca tarif dari `room_type_prices` (aktif, sesuai periode), memvalidasi min/max durasi, menghitung total dan `check_out`. `CreateBookingAction` memvalidasi kapasitas dan kepemilikan kamar, lalu menyimpan `room_type_id` dan `unit_price` sebagai snapshot.

### Tampilan "Mulai dari"
- Dengan filter periode: tampilkan tarif aktif termurah untuk periode itu.
- Tanpa filter: tampilkan periode terpendek yang tersedia pada properti (harian, lalu bulanan, lalu tahunan), dengan satuan jelas ("mulai dari Rp X / hari").
- JSON-LD `priceRange` memakai tarif harian termurah sampai termahal jika ada, selain itu periode terpendek yang tersedia. Periode tidak dicampur.

### Penerapan SOLID dan Pattern

| Prinsip/Pattern | Penerapan |
|---|---|
| S | Controller hanya HTTP, logika bisnis di Service/Action, query kompleks di Repository |
| O | Periode, cara bayar, gateway, dan kebijakan refund baru berarti class baru |
| L/I | Interface kecil dan fokus |
| D | Service bergantung pada interface, di-bind di ServiceProvider |
| Strategy + Factory | Period calculator, payment plan, payment gateway, refund policy |
| Event/Observer | `InvoicePaid` mengonfirmasi booking, `ReviewObserver` menghitung rating |
| DTO | Membawa data antar layer |

Repository hanya untuk Property dan Booking, tidak untuk semua model.

## 13. Kesiapan Payment Gateway

- `PAYMENT_DRIVER=manual` sekarang, `midtrans` nanti, dipilih `PaymentGatewayManager`.
- Kolom `external_id`, `external_reference`, `payload` sudah ada dan nullable.
- Route webhook `POST /webhooks/payment` disiapkan (kecualikan CSRF, wajib verifikasi signature).
- Webhook hanya mengubah Payment menjadi `paid` dan memicu `InvoicePaid` yang sama. Aksi idempoten.
- Satu invoice atau satu cicilan setara satu transaksi gateway.
- `RefundGatewayInterface` disiapkan (manual sekarang, API gateway nanti).

## 14. Upload Gambar

| | Gambar properti | Bukti pembayaran / refund |
|---|---|---|
| Format | png, jpg, jpeg, webp | sama |
| Ukuran | maks 2 MB | maks 2 MB |
| Disk | `public` | `local` (privat) |
| Akses | URL langsung | Route terproteksi + Policy |
| Nama file | UUID | UUID |

Validasi memeriksa ekstensi dan MIME type. Semua logika di `ImageStorageService`. File dihapus saat entitas di-force delete. Opsional: kompres dan resize.

## 15. Keamanan

- Policy pada setiap aksi. Query admin dibatasi ke properti yang ditugaskan (cegah IDOR). Customer hanya mengakses data miliknya.
- Validasi lewat FormRequest, `$fillable` eksplisit, tanpa `$guarded = []`.
- Rate limiting: login, booking, upload bukti, review, endpoint ketersediaan kamar.
- Nominal, total, check-out, denda, dan refund selalu dihitung server. Harga selalu dari `room_type_prices`.
- Kamar yang dipilih divalidasi milik properti dan tipe yang diminta.
- Cek bentrok kamar memakai `DB::transaction` dan `lockForUpdate()`.
- Nominal cicilan divalidasi terhadap sisa tagihan.
- Tanpa `v-html` untuk konten pengguna. Tanpa raw query dari input. Eager loading untuk mencegah N+1.
- Data rekening refund dan bukti transfer hanya dapat diakses pihak berwenang.
- Production: HTTPS, `APP_DEBUG=false`, security headers, `.env` tidak masuk git.

## 16. SEO

- Inertia SSR untuk halaman publik.
- `<Head>`: title, meta description, Open Graph. Title properti: "{Nama Properti} | {NamaBisnis}".
- URL slug, canonical, `sitemap.xml`, `robots.txt`.
- JSON-LD `LodgingBusiness` dengan `aggregateRating` dan `priceRange` (lihat bagian 12).
- Satu `<h1>` per halaman, HTML semantik, `alt` pada gambar, `loading="lazy"` pada galeri.
- Halaman per kota untuk SEO lokal.
- Area admin, dashboard, dan halaman customer `noindex`.

## 17. Notifikasi

| Kejadian | Penerima |
|---|---|
| Booking baru dibuat | Admin properti |
| Bukti pembayaran diunggah | Admin properti |
| Pembayaran disetujui / ditolak | Customer |
| Booking kedaluwarsa | Customer |
| Pengingat jatuh tempo (H-3) | Customer |
| Invoice telat (`overdue`) | Customer, admin properti |
| Denda dikenakan | Customer |
| Tombol perpanjang terbuka (H-14) | Customer |
| Booking dibatalkan | Customer, admin; superadmin jika dibatalkan admin |
| Refund menunggu rekening (`awaiting_account`) | Customer |
| Refund diminta | Admin properti |
| Refund selesai / ditolak (data rekening tidak valid) | Customer |
| Kamar diubah ke `maintenance` saat ada booking aktif | Superadmin |

Dikirim lewat email dengan Queue.

## 18. Scheduler

| Command | Fungsi | Frekuensi |
|---|---|---|
| ExpireUnpaidBookings | Booking `pending_payment` melewati `expires_at` **dan tanpa payment `waiting_verification`** menjadi `expired` | 10-15 menit |
| NotifyOverdueInvoices | Kirim notifikasi invoice lewat jatuh tempo (booking `confirmed`) | Harian |
| ApplyLateFees | Kenakan denda setelah toleransi, hanya booking `confirmed`, idempoten via `late_fee_applied_at` | Harian |
| SendInvoiceReminders | Pengingat H-3 jatuh tempo | Harian |
| SendExtensionReminders | Notifikasi tombol perpanjang terbuka (H-14) | Harian |

## 19. Persyaratan Non-Fungsional

- Responsif (mobile-first).
- Pagination pada semua list. Index database sesuai skema.
- Semua operasi uang dan status dalam `DB::transaction` dan idempoten.
- **Satu timezone: Asia/Jakarta** untuk penyimpanan, perhitungan, dan tampilan. Perhitungan batas hari memakai `now()` Laravel.
- Cakupan test untuk Service, Action, dan Policy.

## 20. Rencana Pengujian

- **Pricing:** harga dari tipe kamar yang benar, akhir bulan, tahun kabisat, min/max durasi, tipe tanpa tarif aktif ditolak, perubahan tarif tidak memengaruhi booking lama.
- **Tipe kamar dan kamar:** kamar dan tipe satu properti, tamu melebihi kapasitas ditolak, tipe yang masih punya kamar tidak bisa dihapus, properti tanpa tipe bertarif tidak bisa dipublikasikan, unique slug/nama tetap berfungsi setelah soft delete.
- **Syarat booking:** email belum terverifikasi, telepon kosong, atau belum menyetujui syarat ditolak.
- **Payment plan:** `allowedFor()` per periode, jumlah dan jatuh tempo invoice.
- **Cicilan:** 50% pertama (di bawah ditolak, tepat diterima), tidak melebihi sisa, hanya satu `waiting_verification`, approve dua kali tidak menggandakan efek, lebih dari 3 pembayaran disetujui tidak terkunci.
- **Batas upload:** 3 penolakan mengunci upload, `ReopenUpload` membukanya kembali.
- **Kedaluwarsa:** booking dengan `waiting_verification` tidak di-expire, penolakan memperpanjang `expires_at`.
- **Denda:** tidak pada hari ke-7, dikenakan hari ke-8, tidak dobel, tidak pada invoice `paid`, tidak pada booking yang belum `confirmed`.
- **Overdue:** penanda turunan muncul pada invoice `partial` yang telat.
- **Refund customer:** tepat di batas 3 hari (`Carbon::setTestNow`), setelah batas, setelah check-in, denda yang dibayar tidak ikut dikembalikan, pembatalan ganda tidak membuat dua refund, nominal 0 tidak membuat record refund.
- **Refund admin:** penuh termasuk denda, status awal `awaiting_account`, rekening dapat diisi customer.
- **Pembatalan `pending_payment`:** diizinkan, ditolak bila ada `waiting_verification`.
- **Perpanjangan:** harga terbaru, satu perpanjangan aktif per induk, ditolak bila kamar sudah dipesan atau `maintenance` atau tarif nonaktif, pembatalan induk ikut membatalkan anak dengan refund per booking.
- **Penyelesaian:** tidak bisa sebelum `check_out`, tidak bisa dengan invoice belum lunas, superadmin dapat menutup dengan catatan.
- **Kamar:** admin hanya boleh mengubah `status`, tidak boleh membuat atau menghapus kamar, peringatan saat `maintenance` pada kamar berbooking.
- **Audit:** setiap perubahan status menghasilkan baris `status_histories`.
- **Penomoran:** kode booking dan invoice unik pada akses bersamaan.
- **IDOR (403):** admin properti A tidak dapat mengakses booking, invoice, payment, refund, tipe kamar, atau kamar properti B. Customer X tidak dapat mengakses data customer Y, termasuk bukti bayar dan rekening refund.
- **Gambar:** ekstensi tidak valid dan ukuran di atas 2 MB ditolak, bukti bayar tidak dapat diakses tanpa otorisasi.

## 21. Tahapan Pengerjaan

| Fase | Isi |
|---|---|
| 1 | Setup project, migration (termasuk `active_key`, `status_histories`, `number_sequences`), Enum, `config/booking.php`, `BookingSettings`, seeder superadmin, middleware role |
| 2 | Model, relasi, Policy |
| 3 | Master data superadmin: kota, fasilitas, properti, gambar, tipe kamar dan tarif, kamar, rekening, penugasan admin |
| 4 | Halaman publik, syarat dan ketentuan, kebijakan privasi, SEO (SSR, sitemap, JSON-LD) |
| 5 | Booking, `PricingService`, `AvailabilityService`, cek bentrok, penomoran |
| 6 | Invoice, pembayaran, verifikasi, notifikasi, scheduler expired, overdue, dan denda |
| 7 | Pembatalan, refund, perpanjangan, penyelesaian booking, status kamar oleh admin |
| 8 | Review dan rating |
| 9 | Hardening keamanan, testing menyeluruh |
| 10 | Integrasi payment gateway |

## 22. Kriteria Penerimaan

- Customer dapat memilih tipe kamar, booking, membayar via upload bukti, dan melihat status serta tagihan.
- Harga booking selalu sesuai tarif tipe kamar dan tidak berubah setelah booking dibuat.
- Admin hanya dapat mengelola properti yang ditugaskan, terbukti lewat test IDOR.
- Semua aturan bisnis (50%, 7 hari, Rp 50.000, 3 hari, refund customer dan admin, perpanjangan, penyelesaian) berjalan sesuai bagian 6 dan lulus test.
- Tidak ada double booking pada kamar yang sama.
- Gambar sesuai batasan format dan ukuran, bukti bayar hanya dapat diakses pihak berwenang.
- Halaman publik dirender SSR dengan meta, sitemap, dan JSON-LD.
- Setiap perubahan status booking, invoice, payment, dan refund tercatat di `status_histories`.
- Mengganti `PAYMENT_DRIVER` tidak memerlukan perubahan pada Controller maupun Action utama.

## 23. Asumsi dan Pertanyaan Terbuka

- Harga per tipe kamar, bukan per kamar individual. Kamar berharga khusus dibuatkan tipe sendiri.
- Galeri tetap di level properti.
- Batas bayar pertama 24 jam, H-14 perpanjangan, dan seluruh angka bisnis dapat diubah di config.
- Isi syarat dan ketentuan harus ditulis pengelola sebelum rilis, dan perubahan isi menaikkan `terms_version`.
- Penutupan booking bertunggakan oleh superadmin (6.10) adalah asumsi, dapat diganti kebijakan penagihan lain.
- Persetujuan superadmin untuk refund di atas nominal tertentu belum ditetapkan (opsional).
- Jika tarif tipe kamar dinonaktifkan saat ada booking aktif, booking tetap berjalan dengan harga snapshot.