
# PRODUCT PRICING DEPO — LEGACY BUSINESS LOGIC

Gunakan bagian ini sebagai **source of truth** untuk migrasi modul pricing produk dengan delivery type `DEPO`.

Tujuan migration adalah mempertahankan **hasil kalkulasi legacy secara identik**. Struktur Vue/JavaScript boleh berubah, tetapi rumus, urutan kalkulasi, rounding, dan behavior khusus harus dipertahankan.

---

# 1. DATA PRICING

Pricing memiliki beberapa komponen utama:

```text
redemp_price
transportation_cost
oh_depo
bad_debt
budget_marketing
saving
margin_all_segment
margin_grosir
margin_retail
margin_end_user
all_segment_price
grosir_price
retail_price
end_user_price
```

Selain itu terdapat:

```text
percentage
rounded_all_segment_price
```

Pricing selalu menggunakan:

```text
delivery_type = "DEPO"
```

---

# 2. HARGA TEBUS / REDEMPT PRICE

`redemp_price` adalah **harga tebus**.

Pada mode Perhitungan Maju, harga tebus merupakan salah satu input utama.

Sistem terlebih dahulu memastikan harga tebus berupa bilangan bulat.

Behavior legacy:

```pseudo
if redemp_price:
    redemp_price = Math.round(Number(redemp_price))

if redemp_price:
    redemp_price = Math.trunc(redemp_price)
```

Jangan melakukan pembagian `/ 1.11` pada harga tebus.

Legacy memiliki kode pembagian `/ 1.11`, tetapi kode tersebut saat ini **disabled/commented**.

---

# 3. PERHITUNGAN MAJU

Mode:

```text
PERHITUNGAN MAJU
```

digunakan ketika sistem mulai dari:

```text
Harga Tebus
+
Biaya-biaya
+
Margin Normal
```

untuk mendapatkan:

```text
Harga All Segment
```

---

# 4. KOMPONEN BIAYA

Komponen biaya yang ditambahkan ke harga tebus:

```text
transportation_cost
oh_depo
bad_debt
budget_marketing
saving
margin_all_segment
```

Semua nilai yang kosong dianggap:

```text
0
```

karena legacy menggunakan:

```pseudo
Number(value) || 0
```

---

# 5. RUMUS HARGA ALL SEGMENT

Rumus legacy:

```text
all_segment_price =
    redemp_price
    + transportation_cost
    + oh_depo
    + bad_debt
    + budget_marketing
    + saving
    + margin_all_segment
```

Dengan notasi:

```text
R  = redemp_price
T  = transportation_cost
O  = oh_depo
B  = bad_debt
M  = budget_marketing
S  = saving
N  = margin_all_segment
```

maka:

```text
ALL_SEGMENT_PRICE = R + T + O + B + M + S + N
```

**Catatan penting:**

Legacy menghitung `ppn` tetapi hasil PPN tersebut **tidak ditambahkan** ke `all_segment_price`.

Kode yang aktif adalah:

```text
all_segment_price = basePrice
```

bukan:

```text
all_segment_price = basePrice + PPN
```

Jangan mengaktifkan kembali rumus PPN yang sudah di-comment tanpa requirement baru.

---

# 6. PERHITUNGAN PPN PADA FORWARD CALCULATION

Legacy menghitung:

```text
ppn =
    transportation_cost
    + oh_depo
    + bad_debt
    + budget_marketing
    + saving
    + margin_all_segment
```

Kemudian:

```text
resultPpn = Math.ceil(ppn * 0.11)
```

Tetapi:

```text
resultPpn
```

tidak digunakan dalam hasil akhir.

Jadi untuk parity:

```text
all_segment_price = basePrice
```

bukan:

```text
all_segment_price = basePrice + resultPpn
```

---

# 7. HARGA TRUCKING

Harga trucking berasal dari:

```text
region_delivery
```

Setiap region mempunyai:

```text
region_name
region_price
```

Option ditampilkan sebagai:

```text
{region_name} - {formatRupiah(region_price)}
```

Value option:

```text
{region_name}-{region_price}
```

Ketika user memilih trucking:

```pseudo
price = selectedPrice.split("-")[1]

transportation_cost = Number(price)
```

Jadi `transportation_cost` berasal dari `region_price`.

---

# 8. OH DEPO

OH Depo berasal dari:

```text
dimensions
```

Setiap dimension memiliki:

```text
dimention_name
price_dimention
```

Option:

```text
label = dimention_name + " - " + price_dimention
value = price_dimention
```

Sehingga:

```text
oh_depo = selected dimension price
```

---

# 9. BUDGET MARKETING DAN BAD DEBT

Nilai awal:

```text
budget_marketing
bad_debt
```

diambil dari global configuration:

```text
BUDGET MARKETING
BAD DEBT
```

Jika configuration tersebut tersedia, nilainya menjadi default form.

---

# 10. SAVING

`Saving` merupakan salah satu komponen biaya dalam forward calculation.

Rumusnya tidak memiliki formula tambahan.

Nilainya langsung ditambahkan:

```text
saving
```

ke dalam `basePrice`.

---

# 11. MARGIN NORMAL / MARGIN ALL SEGMENT

Pada forward calculation:

```text
margin_all_segment
```

merupakan komponen yang langsung ditambahkan ke harga.

Sehingga:

```text
ALL_SEGMENT_PRICE =
    REDEMPT_PRICE
    + TRANSPORTATION
    + OH_DEPO
    + BAD_DEBT
    + BUDGET_MARKETING
    + SAVING
    + MARGIN_ALL_SEGMENT
```

---

# 12. PEMBULATAN HARGA

Terdapat fitur optional:

```text
rounded_all_segment_price
```

Jika user memasukkan nilai pembulatan:

```pseudo
all_segment_price =
    all_segment_price
    + rounded_all_segment_price
```

Dan:

```pseudo
margin_all_segment =
    margin_all_segment
    + rounded_all_segment_price
```

Jadi nilai pembulatan **tidak hanya mengubah harga**, tetapi juga menambah margin all segment dengan nominal yang sama.

Contoh:

```text
all_segment_price = 100.000
margin_all_segment = 10.000
rounded = 500
```

hasil:

```text
all_segment_price = 100.500
margin_all_segment = 10.500
```

Behavior ini harus dipertahankan.

---

# 13. PERHITUNGAN MUNDUR

Mode:

```text
PERHITUNGAN MUNDUR
```

berfungsi kebalikan dari forward calculation.

Input utama:

```text
redemp_price
percentage
all_segment_price
grosir_price
retail_price
end_user_price
```

Sistem kemudian menentukan cara menghitung berdasarkan pilihan user:

```text
HARGA TEBUS
```

atau:

```text
HARGA JUAL
```

---

# 14. PILIHAN "HARGA TEBUS"

Jika user memilih:

```text
HARGA TEBUS
```

sistem menggunakan:

```text
redemp_price
percentage
```

untuk menghitung harga jual.

Namun terdapat behavior khusus jika:

```text
percentage = 0.075
```

---

# 15. SPECIAL CASE 0.075

Jika:

```text
percentage === 0.075
```

sistem meminta user memilih:

```text
Tambah
```

atau:

```text
Kurang
```

Ini adalah behavior khusus yang **WAJIB dipertahankan**.

---

# 16. 0.075 + METODE KURANG

Jika:

```text
percentage = 0.075
isAdd = false
```

maka:

```text
redemp_price =
    Math.round(
        entry_price - (entry_price * 0.075)
    )
```

Sedangkan:

```text
all_segment_price = entry_price
```

Margin:

```text
marginAmount =
    entry_price - redemp_price
```

Contoh:

```text
entry_price = 100.000
percentage = 7.5%
```

Maka:

```text
redemp_price =
100.000 - 7.500
= 92.500
```

dan:

```text
all_segment_price = 100.000
```

---

# 17. 0.075 + METODE TAMBAH

Jika:

```text
percentage = 0.075
isAdd = true
```

maka gunakan metode normal:

```text
redemp_price = entry_price
```

dan:

```text
all_segment_price =
    Math.round(
        entry_price + (entry_price * percentage)
    )
```

Contoh:

```text
entry_price = 100.000
percentage = 0.075
```

hasil:

```text
redemp_price = 100.000

all_segment_price =
100.000 + 7.500
= 107.500
```

Margin:

```text
marginAmount =
    107.500 - 100.000
    = 7.500
```

---

# 18. PERCENTAGE SELAIN 0.075

Jika:

```text
percentage !== 0.075
```

maka gunakan metode normal:

```text
redemp_price = entry_price
```

dan:

```text
all_segment_price =
    Math.round(
        entry_price + (entry_price * percentage)
    )
```

Margin:

```text
marginAmount =
    all_segment_price - entry_price
```

---

# 19. DEDUCTIONS PADA REVERSE CALCULATION

Setelah mendapatkan `marginAmount`, sistem mengurangi beberapa biaya:

```text
bad_debt
budget_marketing
saving
oh_depo
transportation_cost
```

Rumus:

```text
deductions =
    bad_debt
    + budget_marketing
    + saving
    + oh_depo
    + transportation_cost
```

Kemudian:

```text
normal_margin =
    Math.round(
        marginAmount - deductions
    )
```

Hasil disimpan sebagai:

```text
margin_all_segment
```

---

# 20. PILIHAN "HARGA JUAL"

Jika user memilih:

```text
HARGA JUAL
```

maka sistem menggunakan:

```text
redemp_price
all_segment_price
grosir_price
retail_price
end_user_price
```

untuk menghitung margin masing-masing segment.

Deductions:

```text
deductions =
    oh_depo
    + budget_marketing
    + saving
    + transportation_cost
    + bad_debt
```

---

# 21. MARGIN PER SEGMENT

Untuk setiap segment:

```text
margin =
    selling_price
    - redemp_price
    - deductions
```

Kemudian dibulatkan:

```text
Math.round(margin)
```

Formula:

### Grosir

```text
margin_grosir =
    Math.round(
        grosir_price
        - redemp_price
        - deductions
    )
```

### Retail

```text
margin_retail =
    Math.round(
        retail_price
        - redemp_price
        - deductions
    )
```

### End User

```text
margin_end_user =
    Math.round(
        end_user_price
        - redemp_price
        - deductions
    )
```

`margin_all_segment` tidak dihitung dari selling price pada fungsi ini karena bagian tersebut memang tidak aktif di legacy.

---

# 22. PERBEDAAN FORWARD VS REVERSE

## Forward

Starting point:

```text
Harga Tebus
```

Kemudian:

```text
+ Trucking
+ OH Depo
+ Bad Debt
+ Budget Marketing
+ Saving
+ Margin
```

Hasil:

```text
Harga All Segment
```

Formula:

```text
ALL_SEGMENT =
REDemption
+ Cost
+ Margin
```

---

## Reverse

Starting point dapat berupa:

```text
Harga Tebus
```

atau:

```text
Harga Jual
```

kemudian sistem mencari harga/margin yang sesuai.

Untuk metode harga tebus:

```text
Harga Tebus
      ↓
Percentage
      ↓
Harga All Segment
      ↓
Margin
      ↓
Kurangi Deductions
```

Untuk metode harga jual:

```text
Harga Jual
      ↓
Kurangi Harga Tebus
      ↓
Kurangi Deductions
      ↓
Margin Segment
```

---

# 23. PRODUCT PRICING OUTPUT

Pricing product akhirnya menghasilkan beberapa harga segment:

```text
all_segment_price
grosir_price
retail_price
end_user_price
```

Harga-harga tersebut nantinya digunakan sebagai pricing berdasarkan segment customer.

Pada Customer Order, price key mengikuti segment customer:

```text
{segment_customer}_price
```

Contoh:

```text
segment = retail
→ retail_price

segment = grosir
→ grosir_price

segment = end_user
→ end_user_price
```

---

# 24. ATURAN ROUNDING

Rounding **tidak boleh disamaratakan**.

Legacy menggunakan beberapa metode berbeda.

### Forward redemp price

```text
Math.round()
```

kemudian:

```text
Math.trunc()
```

### Reverse selling price

```text
Math.round()
```

### Reverse margin

```text
Math.round()
```

### Special 0.075

```text
Math.round()
```

### PPN calculation

```text
Math.ceil()
```

Jangan mengganti semuanya menjadi satu metode rounding global.

---

# 25. IMPORTANT — LEGACY BEHAVIOR

Jangan melakukan simplifikasi seperti:

```text
selling_price = redemp_price * 1.075
```

untuk semua kondisi.

Karena `0.075` memiliki dua behavior:

```text
Tambah
Kurang
```

Dan hasilnya berbeda.

Jangan pula memasukkan PPN ke harga All Segment karena legacy saat ini menghitung PPN tetapi **tidak memasukkannya ke hasil final**.

---

# 26. BUSINESS FLOW YANG HARUS DIPERTAHANKAN

```text
Pilih Product
    ↓
Pilih Trucking / Region
    ↓
Pilih OH Depo
    ↓
Masukkan / Ambil Bad Debt
    ↓
Masukkan / Ambil Budget Marketing
    ↓
Masukkan Saving
    ↓
Pilih Mode Calculation
    ↓
┌──────────────────────┐
│ PERHITUNGAN MAJU     │
└──────────────────────┘
    ↓
Harga Tebus
    +
Transportation
    +
OH Depo
    +
Bad Debt
    +
Budget Marketing
    +
Saving
    +
Margin
    ↓
All Segment Price
    ↓
Optional Rounding


ATAU


┌──────────────────────┐
│ PERHITUNGAN MUNDUR   │
└──────────────────────┘
    ↓
Pilih:
HARGA TEBUS / HARGA JUAL
    ↓
Jika HARGA TEBUS
    ↓
Check percentage
    ↓
Jika 0.075
    ↓
Tambah / Kurang
    ↓
Calculate Selling Price
    ↓
Calculate Margin

Jika HARGA JUAL
    ↓
Gunakan Selling Price
    ↓
Kurangi Redemp Price
    ↓
Kurangi Deductions
    ↓
Hitung Margin Segment
```

---

# 27. ACCEPTANCE TEST

Migration harus menghasilkan hasil yang sama dengan legacy untuk minimal:

```text
Forward Calculation
Reverse Calculation
Percentage 0.075 + Tambah
Percentage 0.075 + Kurang
Percentage selain 0.075
Harga Tebus
Harga Jual
Trucking
OH Depo
Bad Debt
Budget Marketing
Saving
Margin
Optional Rounding
```

Khusus `0.075`, test wajib mencakup:

```text
0.075 + Tambah
0.075 + Kurang
```

karena keduanya menggunakan formula berbeda.

---

# FINAL RULE

**Jangan menganggap pricing hanya sebagai `redemp_price + margin`.**

Pricing DEPO terdiri dari:

```text
REDEMPT PRICE
    +
TRANSPORTATION COST
    +
OH DEPO
    +
BAD DEBT
    +
BUDGET MARKETING
    +
SAVING
    +
MARGIN
    =
ALL SEGMENT PRICE
```

dan memiliki reverse calculation dengan behavior khusus berdasarkan:

```text
HARGA TEBUS
HARGA JUAL
percentage
percentage = 0.075
Tambah
Kurang
```

Semua formula, rounding, special case, dan urutan kalkulasi di atas harus dipertahankan pada migration.
