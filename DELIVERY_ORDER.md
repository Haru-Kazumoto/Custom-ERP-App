
# BUSINESS LOGIC CUSTOMER ORDER

Gunakan dokumen ini sebagai **source of truth business logic** ketika melakukan migrasi Customer Order dari sistem legacy ke sistem baru.

## ATURAN UTAMA

Migrasi **WAJIB mempertahankan behavior bisnis dari sistem legacy**.

Jangan mengubah rumus, urutan proses, cara menentukan harga, cara menentukan promo, maupun cara menghitung diskon hanya karena ada cara implementasi yang dianggap lebih sederhana.

Pisahkan antara:

1. **Legacy Business Behavior** → wajib dipertahankan.
2. **Refactoring Architecture** → boleh diubah.
3. **Bug / behavior yang terlihat janggal** → jangan diperbaiki secara otomatis. Pertahankan dahulu untuk parity dan dokumentasikan sebagai potential issue.

---

# 1. ALUR BESAR TRANSAKSI

Urutan proses Customer Order adalah:

```text
Customer dipilih
    ↓
Customer metadata ditentukan
    ↓
Delivery / Shipping ditentukan
    ↓
Product & Price data diambil
    ↓
Product dipilih
    ↓
Price dipilih
    ↓
Promo tersedia?
    ↓
Promo dipilih
    ↓
Validasi quantity + harga + promo
    ↓
Hitung diskon promo
    ↓
Hitung harga akhir per unit
    ↓
Hitung total harga item
    ↓
Item dimasukkan ke transaction_items
    ↓
Semua item dihitung ulang
    ↓
Subtotal / PPN / Total Discount / Grand Total
    ↓
Submit Customer Order
    ↓
Build transaction_details
    ↓
POST Customer Order
```

---

# 2. CUSTOMER MENENTUKAN SEGMENT HARGA

Customer memiliki beberapa metadata yang digunakan dalam transaksi.

Yang paling penting untuk pricing adalah:

```text
segment_customer
```

Segment customer digunakan untuk menentukan field harga yang digunakan.

Format dynamic price field:

```text
{segment_customer}_price
```

Field segment harus diubah menjadi lowercase terlebih dahulu.

Contoh:

```text
segment_customer = "RETAIL"

pricing field = "retail_price"
```

Contoh lainnya:

```text
segment_customer = "WHOLESALE"

pricing field = "wholesale_price"
```

Jadi sistem **tidak boleh hard-code hanya satu jenis harga**.

Harga yang digunakan harus mengikuti segment customer.

---

# 3. SUMBER PRODUCT BERDASARKAN DELIVERY

Product source tidak selalu sama.

Jika delivery adalah salah satu dari:

```text
DIRECT
DIRECT_DEPO
DO
```

maka product berasal dari:

```text
productMasters
```

Jika delivery bukan salah satu dari tiga tipe tersebut, product berasal dari:

```text
productOptions
```

Rule ini WAJIB dipertahankan.

```pseudo
if delivery in ["DIRECT", "DIRECT_DEPO", "DO"]:
    products = productMasters
else:
    products = productOptions
```

---

# 4. PEMILIHAN PRODUCT

Ketika product dipilih, sistem mengambil informasi product:

```text
product_id
product_code
product_name
unit
stock
promo_programs
```

Delivery tertentu juga mempengaruhi apakah stock ditampilkan.

Untuk:

```text
DO
DIRECT
DIRECT_DEPO
```

stock product tidak ditampilkan.

Untuk delivery lainnya, stock digunakan dalam validasi.

Jika product bukan berasal dari master product dan stock:

```text
stock < 10
```

maka product dianggap memiliki status stock yang bermasalah.

Jika:

```text
stock >= 10
```

maka status stock dianggap aman.

---

# 5. PEMILIHAN HARGA

Setelah product dipilih, sistem menyediakan price options berdasarkan:

```text
customer segment
```

Field harga diambil secara dynamic:

```pseudo
priceKey = lowercase(segment_customer) + "_price"
```

Contoh:

```text
segment = RETAIL
priceKey = retail_price
```

Value harga kemudian ditampilkan dalam format Rupiah.

Selain harga dari master, sistem selalu menyediakan opsi:

```text
HARGA MANUAL
```

---

# 6. HARGA MANUAL

Jika user memilih:

```text
HARGA MANUAL
```

maka sistem masuk ke mode manual price.

State:

```text
use_manual_price = true
```

Harga transaksi kemudian menggunakan:

```text
manual_amount
```

bukan harga dari price master.

Jika user memilih harga normal:

```text
use_manual_price = false
```

dan:

```text
amount = selected_price
```

Jangan mencampurkan kedua mode ini.

Secara konsep:

```pseudo
if selectedPrice == "MANUAL":
    use_manual_price = true
    amount = manual_amount
else:
    use_manual_price = false
    amount = selected_price
```

---

# 7. VALIDASI SEBELUM PRODUCT DITAMBAHKAN

Sebelum product masuk ke transaction items, lakukan validasi berikut secara berurutan.

## 7.1 Product wajib dipilih

```text
product_id wajib ada
```

Jika tidak ada, product tidak boleh ditambahkan.

---

## 7.2 Quantity

Quantity dikonversi menjadi Number.

```text
quantity = Number(input_quantity)
```

Quantity tidak boleh kosong atau bernilai zero/truthy failure.

---

## 7.3 Quantity tidak boleh melebihi stock

Jika stock digunakan:

```text
quantity <= last_stock
```

Jika:

```text
quantity > last_stock
```

maka transaksi item ditolak.

---

## 7.4 Quantity vs Promo Base Quota

Jika promo memiliki:

```text
base_quota
```

maka quantity juga tidak boleh melebihi base quota.

Rule:

```text
quantity <= base_quota
```

Jika:

```text
quantity > base_quota
```

maka item tidak boleh ditambahkan.

---

## 7.5 Harga wajib valid

Harga harus tersedia dan numeric.

---

# 8. PROMO — BAGIAN PALING PENTING

Promo bukan sekadar:

```text
harga - diskon
```

Promo menggunakan beberapa tahap discount.

Satu promo dapat memiliki:

```text
min
max
base_quota
promo_type
percentage_1
percentage_2
percentage_3
manual_type
manual_percentage
manual_value
```

Jangan menganggap seluruh promo hanya memiliki satu percentage.

---

# 9. VALIDASI QUANTITY TERHADAP PROMO

Normalnya promo memiliki range quantity:

```text
min
max
```

Quantity customer harus berada dalam range:

```text
min <= quantity <= max
```

Jika quantity berada di luar range, promo tidak eligible.

### Pengecualian penting

Jika:

```text
promo_type === "FLUSH_OUT"
```

maka validasi:

```text
min <= quantity <= max
```

DI-BYPASS.

Artinya promo `FLUSH_OUT` tidak mengikuti validasi min/max quantity seperti promo biasa.

Rule ini WAJIB dipertahankan.

---

# 10. PROMO DAPAT MEMILIKI 1, 2, ATAU 3 TAHAP DISKON

Diskon promo bersifat **sequential / cascading**.

JANGAN menghitung:

```text
percentage_1 + percentage_2 + percentage_3
```

sebagai satu discount percentage.

Contoh salah:

```text
10% + 5% + 2% = 17%
```

Kemudian:

```text
100.000 - 17% = 83.000
```

Cara tersebut SALAH untuk business logic ini.

Diskon harus diterapkan satu per satu terhadap harga setelah diskon sebelumnya.

---

# 11. DISKON TAHAP PERTAMA

Misalkan harga awal per unit:

```text
A
```

dan promo memiliki:

```text
percentage_1 = P1
```

Maka:

```text
Discount1 = A × (P1 / 100)
```

Harga setelah discount pertama:

```text
A1 = A - Discount1
```

Atau:

```text
A1 = A × (1 - P1 / 100)
```

Simpan nilai discount pertama sebagai:

```text
result_discount1
```

Penting:

```text
result_discount1
```

adalah **nominal discount**, bukan percentage.

---

# 12. DISKON TAHAP KEDUA

Jika terdapat:

```text
percentage_2
```

maka percentage kedua dihitung dari harga yang SUDAH dikurangi discount pertama.

Bukan dari harga awal.

Rumus:

```text
Discount2 = A1 × (P2 / 100)
```

Kemudian:

```text
A2 = A1 - Discount2
```

Simpan nominal discount sebagai:

```text
result_discount2
```

Contoh:

```text
Harga awal = 100.000

Discount 1 = 10%
= 10.000

Harga setelah discount 1
= 90.000

Discount 2 = 5%
= 4.500

Harga setelah discount 2
= 85.500
```

Jadi total discount:

```text
10.000 + 4.500
= 14.500
```

Bukan:

```text
15% × 100.000
= 15.000
```

---

# 13. DISKON TAHAP KETIGA

Tahap ketiga memiliki dua kemungkinan.

## CASE A — percentage_3 tersedia

Jika:

```text
percentage_3
```

tersedia, maka:

```text
Discount3 = A2 × (P3 / 100)
```

Kemudian:

```text
A3 = A2 - Discount3
```

Simpan:

```text
result_discount3 = Discount3
```

---

# 14. CASE B — percentage_3 TIDAK ADA

Jika `percentage_3` tidak tersedia, sistem melihat:

```text
manual_type
```

Ada dua kemungkinan utama:

```text
PERCENTAGE
VALUE
```

---

# 15. MANUAL TYPE = PERCENTAGE

Jika:

```text
percentage_3 tidak tersedia
```

dan:

```text
manual_type === "PERCENTAGE"
```

maka gunakan:

```text
manual_percentage
```

untuk discount tahap ketiga.

Rumus:

```text
Discount3 = A2 × (manual_percentage / 100)
```

Kemudian:

```text
A3 = A2 - Discount3
```

---

# 16. MANUAL TYPE = VALUE

Jika:

```text
percentage_3 tidak tersedia
```

dan:

```text
manual_type === "VALUE"
```

maka discount ketiga merupakan nominal value.

Gunakan:

```text
manual_value
```

Tetapi discount tidak boleh melebihi harga setelah discount tahap kedua.

Rumus:

```text
Discount3 = MIN(manual_value, A2)
```

Kemudian:

```text
A3 = A2 - Discount3
```

Contoh:

```text
Harga setelah discount 1 dan 2 = 80.000

manual_value = 15.000

Discount3 = 15.000

Harga akhir = 65.000
```

Jika:

```text
manual_value = 100.000
```

maka:

```text
Discount3 = MIN(100.000, 80.000)
           = 80.000

Harga akhir = 0
```

Jangan sampai harga menjadi negatif.

---

# 17. RANGKUMAN FORMULA PROMO

Secara keseluruhan:

```text
A = original unit price
```

### Discount 1

```text
D1 = A × (P1 / 100)

A1 = A - D1
```

### Discount 2

```text
D2 = A1 × (P2 / 100)

A2 = A1 - D2
```

### Discount 3 jika percentage_3 tersedia

```text
D3 = A2 × (P3 / 100)

A3 = A2 - D3
```

### Discount 3 jika manual percentage

```text
D3 = A2 × (manual_percentage / 100)

A3 = A2 - D3
```

### Discount 3 jika manual value

```text
D3 = MIN(manual_value, A2)

A3 = A2 - D3
```

Harga final per unit:

```text
final_unit_price = A3
```

---

# 18. FIELD HASIL DISKON

Perhatikan bahwa terdapat perbedaan antara:

```text
result_discount1
result_discount2
result_discount3
```

dan:

```text
total_price_discount_1
total_price_discount_2
total_price_discount_3
```

`result_discountX` adalah **nominal discount per unit**.

Sedangkan:

```text
total_price_discount_1
total_price_discount_2
total_price_discount_3
```

secara behavior legacy berisi **harga setelah discount pada tahap tersebut**, walaupun nama field-nya menggunakan kata `total_price`.

Contoh:

```text
Original = 100.000

Discount 1 = 10%
result_discount1 = 10.000
total_price_discount_1 = 90.000

Discount 2 = 5%
result_discount2 = 4.500
total_price_discount_2 = 85.500
```

Jangan mengubah arti field hanya berdasarkan nama field.

---

# 19. FIELD DISCOUNT_1 / DISCOUNT_2 / DISCOUNT_3

Behavior legacy:

```text
discount_1 = percentage_1
discount_2 = percentage_2
```

Untuk:

```text
discount_3
```

behavior tergantung sumber discount ketiga.

Jika menggunakan:

```text
percentage_3
```

maka:

```text
discount_3 = percentage_3
```

Jika menggunakan:

```text
manual_type = PERCENTAGE
```

maka:

```text
discount_3 = manual_percentage
```

Jika menggunakan:

```text
manual_type = VALUE
```

maka:

```text
discount_3 = manual_value
```

Jadi `discount_3` tidak selalu berarti percentage.

Untuk `manual_type = VALUE`, field tersebut berisi nominal value.

---

# 20. TOTAL HARGA PRODUCT

Setelah mendapatkan harga akhir per unit:

```text
amount = final_unit_price
```

dan quantity:

```text
quantity
```

maka:

```text
total_price = amount × quantity
```

Contoh:

```text
Harga akhir per unit = 85.500
Quantity = 10

total_price = 85.500 × 10
            = 855.000
```

---

# 21. PRODUCT JOURNAL

Setiap product yang ditambahkan juga memiliki journal:

```text
{
    quantity,
    amount,
    action: "OUT",
    batch_code,
    expiry_date,
    product_id
}
```

Action untuk customer order adalah:

```text
OUT
```

Journal harus menggunakan product yang sama dengan transaction item.

---

# 22. TOTAL DISCOUNT TRANSAKSI

Total discount bukan sekadar:

```text
original_price - final_price
```

Legacy menghitung total discount berdasarkan nominal discount setiap tahap.

Per item:

```text
item_discount =
    result_discount1
    + result_discount2
    + result_discount3
```

Kemudian dikalikan quantity:

```text
item_total_discount =
    (
        result_discount1
        + result_discount2
        + result_discount3
    )
    × quantity
```

Untuk seluruh transaction:

```text
total_discount =
    SUM(item_total_discount)
```

Kemudian:

```text
form.total_discount = total_discount
```

---

# 23. GRAND TOTAL SEBELUM DISKON

Grand total dihitung dari seluruh transaction items.

Untuk setiap item:

```text
item_total = amount × quantity
```

Kemudian:

```text
grand_total =
    SUM(item.amount × item.quantity)
```

Legacy melakukan rounding dalam proses perhitungan total.

---

# 24. PAJAK / PPN

Jika:

```text
use_tax = true
```

maka grand total dianggap sudah termasuk PPN 11%.

Subtotal dihitung:

```text
subtotal = ROUND(grand_total / 1.11)
```

PPN:

```text
PPN = grand_total - subtotal
```

Jika:

```text
use_tax = false
```

maka:

```text
subtotal = grand_total
PPN = 0
```

---

# 25. GRAND TOTAL SETELAH DISKON

Setelah mendapatkan:

```text
grand_total
total_discount
```

maka jika discount lebih besar dari zero:

```text
grand_total_after_discount =
    MAX(
        0,
        ROUND(grand_total - total_discount)
    )
```

Harga akhir tidak boleh negatif.

Namun terdapat behavior legacy yang harus diperhatikan:

Jika:

```text
total_discount <= 0
```

fungsi legacy menghasilkan:

```text
0
```

untuk `grandTotalAfterDiscounts`.

**Jangan otomatis mengubah behavior ini menjadi `grand_total` pada migration parity.**

Jika ingin memperbaiki behavior tersebut, tandai sebagai **separate bug fix**, bukan bagian dari migration parity.

---

# 26. CONTOH PERHITUNGAN LENGKAP

Misalkan:

```text
Harga awal       = 100.000
Quantity         = 10

percentage_1     = 10%
percentage_2     = 5%
percentage_3     = 2%
```

### Step 1

```text
D1 = 100.000 × 10%
   = 10.000

A1 = 100.000 - 10.000
   = 90.000
```

### Step 2

```text
D2 = 90.000 × 5%
   = 4.500

A2 = 90.000 - 4.500
   = 85.500
```

### Step 3

```text
D3 = 85.500 × 2%
   = 1.710

A3 = 85.500 - 1.710
   = 83.790
```

Harga akhir:

```text
83.790 / unit
```

Total item:

```text
83.790 × 10
= 837.900
```

Total discount per unit:

```text
10.000 + 4.500 + 1.710
= 16.210
```

Total discount item:

```text
16.210 × 10
= 162.100
```

---

# 27. CONTOH PROMO DENGAN MANUAL VALUE

Misalkan:

```text
Harga awal       = 100.000
percentage_1     = 10%
percentage_2     = 5%
percentage_3     = null

manual_type      = VALUE
manual_value     = 20.000
```

Step 1:

```text
D1 = 10.000
A1 = 90.000
```

Step 2:

```text
D2 = 4.500
A2 = 85.500
```

Step 3:

```text
D3 = MIN(20.000, 85.500)
   = 20.000

A3 = 85.500 - 20.000
   = 65.500
```

Harga final:

```text
65.500
```

Total discount:

```text
10.000 + 4.500 + 20.000
= 34.500
```

---

# 28. CONTOH PROMO DENGAN MANUAL PERCENTAGE

Misalkan:

```text
Harga awal       = 100.000
percentage_1     = 10%
percentage_2     = 5%
percentage_3     = null

manual_type      = PERCENTAGE
manual_percentage = 10%
```

Step 1:

```text
A1 = 90.000
D1 = 10.000
```

Step 2:

```text
A2 = 85.500
D2 = 4.500
```

Step 3:

```text
D3 = 85.500 × 10%
   = 8.550

A3 = 85.500 - 8.550
   = 76.950
```

Harga final:

```text
76.950
```

Total discount:

```text
10.000 + 4.500 + 8.550
= 23.050
```

---

# 29. URUTAN YANG TIDAK BOLEH DIUBAH

AI yang melakukan migration HARUS mempertahankan urutan berikut:

```text
Original Price
      ↓
Discount 1
      ↓
Price After Discount 1
      ↓
Discount 2
      ↓
Price After Discount 2
      ↓
Discount 3
      ↓
Final Unit Price
      ↓
Final Unit Price × Quantity
      ↓
Transaction Item
      ↓
Aggregate All Items
      ↓
Grand Total
      ↓
Tax Calculation
      ↓
Total Discount
      ↓
Grand Total After Discount
```

Jangan menghitung discount secara paralel dari harga awal.

---

# 30. CONTOH KESALAHAN YANG TIDAK BOLEH TERJADI

Jangan melakukan:

```text
10% + 5% + 2% = 17%
```

kemudian:

```text
100.000 × 83%
```

Karena legacy menggunakan cascading discount.

Yang benar:

```text
100.000
→ -10%
→ 90.000
→ -5%
→ 85.500
→ -2%
→ 83.790
```

---

# 31. EDGE CASE YANG WAJIB DIPERHATIKAN

Migration harus melakukan pengujian terhadap:

### Case 1 — Tidak ada promo

```text
percentage_1 = null
percentage_2 = null
percentage_3 = null
```

### Case 2 — Hanya discount pertama

```text
percentage_1 = 10%
```

### Case 3 — Dua discount

```text
percentage_1 = 10%
percentage_2 = 5%
```

### Case 4 — Tiga discount

```text
percentage_1 = 10%
percentage_2 = 5%
percentage_3 = 2%
```

### Case 5 — Manual percentage

```text
percentage_3 = null
manual_type = PERCENTAGE
```

### Case 6 — Manual value

```text
percentage_3 = null
manual_type = VALUE
```

### Case 7 — Manual value lebih besar dari harga

Pastikan:

```text
discount3 <= price_after_discount2
```

dan harga akhir:

```text
>= 0
```

### Case 8 — FLUSH_OUT

Pastikan validasi:

```text
min/max quantity
```

tidak diterapkan.

### Case 9 — Quantity melebihi base quota

Item harus ditolak.

### Case 10 — Multiple products

Pastikan discount setiap item dihitung sendiri-sendiri sebelum di-aggregate.

---

# 32. MULTIPLE ITEMS

Jika terdapat:

```text
Item A
Item B
Item C
```

maka promo setiap item harus dihitung secara independen.

Contoh:

```text
Item A
price = 100.000
qty = 10
promo = 10% → 5%

Item B
price = 200.000
qty = 5
promo = 20%

Item C
price = 50.000
qty = 20
tanpa promo
```

Jangan menggunakan satu discount global untuk semua product.

Setiap item mempunyai:

```text
amount
quantity
result_discount1
result_discount2
result_discount3
total_price
```

sendiri.

Setelah masing-masing item selesai dihitung, baru lakukan aggregation.

---

# 33. SUBMISSION

Sebelum submit Customer Order:

1. Validasi form.
2. Jika valid, buka preview/confirmation modal.
3. Ketika user melakukan submit:

   * tutup preview modal
   * tampilkan loading
   * build `transaction_details`
   * POST Customer Order
4. Jika berhasil:

   * reset form
   * ambil document/CO number baru
   * tampilkan success notification.
5. Jika gagal:

   * hentikan loading
   * tampilkan error.

---

# 34. TRANSACTION DETAILS

Saat submit, metadata transaction detail harus dibangun.

Metadata legacy mencakup:

```text
CO_DATE
CUSTOMER
DELIVERY
SUB_DELIVERY
CASHBACK
UNLOADING_COST
SALESMAN
WAREHOUSE
COMPANY
NPWP
SEGMENT
GENERATING
PO_CUSTOMER
USE_TAX
CONDITION_INVOICE
CONDITION_TRAVEL_DOCUMENT
CONDITION_TAX_INVOICE
CONDITION_RECEIPT
CONDITION_ITEM_RECEIPT
USE_MANUAL_PRICE
```

Beberapa value legacy bersifat hard-coded.

Contoh:

```text
WAREHOUSE = "DKU"
COMPANY   = "DKU"
NPWP      = "npwp-sementara"
GENERATING = "false"
```

Jangan mengganti value tersebut tanpa requirement baru.

---

# 35. PAYMENT CONDITION

Condition payment disimpan sebagai string boolean:

```text
"true"
"false"
```

Untuk:

```text
CONDITION_INVOICE
CONDITION_TRAVEL_DOCUMENT
CONDITION_TAX_INVOICE
CONDITION_RECEIPT
CONDITION_ITEM_RECEIPT
```

Demikian juga:

```text
USE_TAX
```

di-serialize sebagai string.

---

# 36. MANUAL PRICE DI TRANSACTION DETAIL

`USE_MANUAL_PRICE` merepresentasikan apakah transaksi menggunakan harga manual.

Value legacy disimpan sebagai string:

```text
String(use_manual_price)
```

sedangkan metadata type-nya:

```text
boolean
```

Jangan mengubah behavior serialization saat migration parity.

---

# 37. INSTRUKSI UNTUK AI MIGRATION

Saat mengimplementasikan sistem baru:

### BOLEH DIUBAH

Boleh mengubah:

```text
Vue Options API
→ Vue Composition API

JavaScript
→ TypeScript

function besar
→ composables/services/use-cases

state lama
→ reactive/ref/computed

legacy controller
→ service/action/use-case

Axios/Inertia implementation
→ architecture baru
```

Selama behavior bisnis tetap sama.

### TIDAK BOLEH DIUBAH

Tidak boleh mengubah:

```text
urutan discount
rumus discount
sumber harga
segment pricing
promo eligibility
FLUSH_OUT behavior
base quota validation
manual percentage
manual value
quantity validation
stock validation
tax calculation
total discount calculation
grand total calculation
transaction detail values
serialization behavior
```

---

# 38. PRIORITAS IMPLEMENTASI

Prioritas migration adalah:

```text
1. Pricing
2. Promo eligibility
3. Sequential discount calculation
4. Manual discount
5. Item total
6. Total discount
7. Grand total
8. Tax
9. Transaction detail
10. Submission
```

Jangan mulai dari UI terlebih dahulu jika business logic belum jelas.

Business logic sebaiknya dibuat sebagai logic yang dapat diuji secara independen.

---

# 39. ACCEPTANCE CRITERIA

Migration dianggap berhasil apabila input legacy dan input sistem baru menghasilkan output bisnis yang sama.

Minimal harus tersedia test untuk:

```text
normal price
manual price
no promo
single discount
double discount
triple discount
manual percentage
manual value
manual value > remaining price
FLUSH_OUT
quantity > promo max
quantity < promo min
quantity > base quota
multiple products
use_tax = true
use_tax = false
```

Untuk setiap test, bandingkan minimal:

```text
amount
quantity
result_discount1
result_discount2
result_discount3
total_price_discount_1
total_price_discount_2
total_price_discount_3
total_price
total_discount
subtotal
tax_amount
grand_total
grand_total_after_discount
```

Jika hasil berbeda dari legacy, jangan langsung mengubah rumus.

Cari tahu terlebih dahulu apakah perbedaan tersebut:

```text
1. Migration bug
2. Data difference
3. Rounding difference
4. Legacy behavior
5. Suspected legacy bug
```

---

# 40. RULE TERPENTING

**Jangan pernah menganggap promo sebagai satu discount percentage.**

Promo pada sistem ini merupakan rangkaian discount yang dapat terdiri dari:

```text
percentage_1
    ↓
percentage_2
    ↓
percentage_3
```

atau:

```text
percentage_1
    ↓
percentage_2
    ↓
manual_percentage
```

atau:

```text
percentage_1
    ↓
percentage_2
    ↓
manual_value
```

Setiap tahap dihitung berdasarkan **harga hasil tahap sebelumnya**.

Dengan demikian:

```text
Discount 2 ≠ percentage_2 × original_price
```

melainkan:

```text
Discount 2 = percentage_2 × price_after_discount_1
```

dan:

```text
Discount 3 ≠ percentage_3 × original_price
```

melainkan:

```text
Discount 3 = percentage_3 × price_after_discount_2
```

Inilah behavior utama yang harus dipertahankan dalam migration.

---

# FINAL MIGRATION PRINCIPLE

Tujuan migration bukan membuat rumus baru.

Tujuannya adalah:

```text
LEGACY INPUT
     ↓
LEGACY BUSINESS RULE
     ↓
LEGACY CALCULATION
     ↓
LEGACY OUTPUT
```

menghasilkan output yang sama pada:

```text
NEW IMPLEMENTATION
```

Architecture boleh berbeda.

UI boleh berbeda.

Framework pattern boleh berbeda.

Nama function boleh berbeda.

Tetapi **hasil business calculation harus tetap sama**, terutama pada:

```text
PRICE
PROMO
DISCOUNT
QUANTITY
TOTAL DISCOUNT
TAX
GRAND TOTAL
```

**Promo calculation adalah bagian paling sensitif dan harus dianggap sebagai critical business logic.**
