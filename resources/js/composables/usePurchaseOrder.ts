import { ref, computed, watch, onMounted } from "vue";
import { useForm, type Form } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { addDays } from "@/utils/format";
import { searchProducts, type ApiProduct } from "@/services/productApi";
import type { PoItem, TransactionItem } from "@/types/purchase-order";

/**
 * Dokumen yang akan diedit ulang saat mode `revise`.
 *
 * Bentuknya mengikuti `FindPurchaseOrderQuery`: `details` sudah berupa map
 * dengan kunci `name` yang sudah lowercase (`pemasok`, `tanggal_po`, ...).
 */
export interface ReviseSource {
    term_of_payment: number | null;
    due_date: string | null;
    description: string | null;
    details: Record<string, string | null>;
    items: PoItem[];
}

/**
 * Bentuk form yang dikirim ke server.
 *
 * `transaction_detail` adalah bentuk singleton yang enak dipakai template, lalu
 * `buildTransactionDetails()` di `Create.vue` mengubahnya jadi array
 * `transaction_details` sesuai skema tabel. Dua bentuk itu sengaja dipisah:
 * server hanya mengenal array-nya.
 */
export interface PurchaseOrderFormData {
    /** Hanya dikirim di mode create; di mode revise dibuang lewat `transform`. */
    document_code: string;
    term_of_payment: number;
    due_date: string | null;
    description: string;
    transaction_detail: {
        supplier: string;
        storehouse: string;
        located: string;
        purchase_order_date: string | null;
        send_date: string | null;
        transportation: string;
        sender: string;
        delivery_type: string;
        employee_name: string;
        transportation_cost: string | null;
        use_tax: boolean;
    };
    transaction_details: Record<string, unknown>[];
    transaction_items: TransactionItem[];
}

interface Options {
    poNumber: string;
    /** Nama PIC. `auth.user` hanya punya `name` — tidak ada `fullname`. */
    fullname: string;
    /**
     * `revise` mengisi form dari dokumen yang sudah ada lalu mengirim ke
     * `purchase-order.revise.update`; `create` mengisi form kosong dan POST ke
     * `purchase-order.store`.
     */
    mode?: "create" | "revise";
    /** Wajib diisi saat `mode === "revise"`. */
    initial?: ReviseSource | null;
}

/** Pembagi PPN 11%. */
const PPN_DIVISOR = 1.11;

/** Jumlah produk yang diminta per fetch. */
const PRODUCT_PAGE_SIZE = 20;

/** Bentuk option untuk NSelect produk. */
export interface ProductOption {
    label: string;
    value: number;
    code: string;
    unit: string;
    name: string;
    price: number;
    vendor: string;
    category: string;
    tradePromos: TradePromoOption[]; // ← ikut dibawa
}

export interface TradePromoOption {
    label: string; // = name (pelanggan/akun grosir)
    value: number;
    price: number; // = discount price
    quota: number;
    is_active: boolean;
}

function mapProduct(p: ApiProduct): ProductOption {
    return {
        label: `${p.code} — ${p.name}`,
        value: p.product_id,
        code: p.code,
        unit: p.unit,
        name: p.name,
        price: Number(p.price) || 0,
        vendor: p.vendor,
        category: p.category,
        tradePromos: (p.trade_promos ?? [])
            .filter((tp) => Number(tp.is_active) === 1 || tp.is_active === true)
            .map((tp) => ({
                label: tp.name,
                value: tp.id,
                price: Number(tp.price) || 0,
                quota: Number(tp.quota) || 0,
                is_active: Boolean(tp.is_active),
            })),
    };
}

/**
 * Isi form dari dokumen yang sudah tersimpan, supaya revisi=koreksi, bukan
 * ketik ulang.
 *
 * Pemetaan di bawah adalah kebalikan dari `buildTransactionDetails()` di
 * `Create.vue`. Kalau salah satu sisi berubah, sisi lain harus ikut — dan
 * karena keduanya berada di file berbeda, konsekuensinya detail yang lupa
 * dipetakan akan hilang begitu PO disimpan ulang. Itu sebabnya mapping-nya
 * ditulis eksplisit per kunci, bukan loop generik.
 */
function hydrateFromPurchaseOrder(
    form: Form<PurchaseOrderFormData>,
    source: ReviseSource,
) {
    const d = source.details ?? {};

    // Default form memakai `transportation: "-"` sebagai placeholder; dokumen
    // lama bisa menyimpan apa saja, jadi nilai kosong tidak boleh mengganti
    // placeholder dengan string kosong yang membuat dropdown tidak menampilkan
    // apa pun.
    const orPlaceholder = (value: string | null | undefined, fallback: string) =>
        value === null || value === undefined || value === "" ? fallback : value;

    form.term_of_payment = Number(source.term_of_payment ?? 0) || 45;
    // `due_date` ikut di-hidrasi supaya field tanggal langsung tampil, bukan
    // kosong selama 300ms sampai watcher di bawah menghitungnya. Watcher itu
    // tetap berlaku: `due_date` adalah turunan `tanggal_po + term_of_payment`,
    // jadi kalau user mengubah salah satunya, nilainya dihitung ulang. Nilai
    // hasil hitung ulang sama dengan nilai yang di-hidrasi selama PO tidak
    // diubah, karena PO aslinya juga disusun dengan aturan yang sama.
    form.due_date = source.due_date ?? null;
    form.description = source.description ?? "";

    form.transaction_detail.supplier = d.pemasok ?? "";
    form.transaction_detail.located = d.alokasi ?? "";
    form.transaction_detail.purchase_order_date = d.tanggal_po ?? null;
    form.transaction_detail.send_date = d.tanggal_kirim ?? null;
    form.transaction_detail.sender = d.transportasi ?? "";
    form.transaction_detail.delivery_type = d.jenis_pengiriman ?? "";
    form.transaction_detail.employee_name = d.nama_petugas ?? "";
    form.transaction_detail.transportation_cost =
        d.harga_angkutan ?? null;
    form.transaction_detail.transportation = orPlaceholder(
        d.nomor_polosi,
        "-",
    );

    // PPN disimpan sebagai string "true"/"false" di `transaction_details.value`
    // (kolomnya bertipe string). Sumber kebenaran yang sama dipakai
    // `CreatePurchaseOrderDTO::extractUseTax()` di server, jadi form dan server
    // tidak mungkin berbeda paham soal dokumen ini pakai PPN atau bukan.
    const useTax = d.ppn === undefined || d.ppn === null ? true : d.ppn === "true";
    form.transaction_detail.use_tax = useTax;

    form.transaction_items = (source.items ?? []).map((item) => {
        const quantity = Number(item.quantity) || 0;
        // `unit_price` dikirim ulang apa adanya supaya server menghitung
        // `total_price` dengan angka yang sama seperti sebelumnya. Kalau tidak,
        // pembulatan `round(unit_price / 1.11)` bisa menggeser total beberapa
        // rupiah setiap kali PO direvisi tanpa ada yang diedit.
        const unitPrice = Number(item.unit_price) || 0;

        return {
            unit: item.product_unit ?? "",
            quantity,
            product_id: Number(item.product_id),
            amount: Number(item.base_price) || 0,
            unit_price: unitPrice,
            original_price: Number(item.product_price) || unitPrice,
            tax_id: null,
            use_tax: useTax,
            trade_promo_id: item.trade_promo_id ?? null,
            total_price: Number(item.total_price) || unitPrice * quantity,
            product: {
                code: item.product_code ?? "",
                unit: item.product_unit ?? "",
                name: item.product_name ?? "",
            },
        } satisfies TransactionItem;
    });
}

export function usePurchaseOrder({
    poNumber,
    fullname,
    mode = "create",
    initial = null,
}: Options) {
    const isRevise = mode === "revise";

    const form = useForm<PurchaseOrderFormData>({
        document_code: poNumber,
        term_of_payment: 45,
        due_date: null as string | null,
        description: "",
        // `sub_total`, `tax_amount`, dan `total` sengaja tidak ada di payload.
        // Nilainya dihitung ulang di server dari `transaction_items[].unit_price`
        // supaya angka di DB tidak bisa berbeda dari isi tabel.
        transaction_detail: {
            supplier: "",
            storehouse: "",
            located: "",
            purchase_order_date: null as string | null,
            send_date: null as string | null,
            transportation: "-",
            sender: "",
            delivery_type: "",
            employee_name: fullname,
            transportation_cost: null as string | null,
            use_tax: true as boolean,
        },
        transaction_details: [] as any[],
        transaction_items: [] as TransactionItem[],
    });

    // Nomor PO adalah identitas dokumen dan tidak boleh diganti saat revisi —
    // server juga tidak memvalidanya di endpoint revisi. Fieldnya tetap ada di
    // bentuk form supaya `Create.vue` tidak butuh dua bentuk berbeda, tapi
    // dibuang dari payload saat revise supaya tidak pernah terkirim.
    if (isRevise) {
        form.transform((data) => {
            const { document_code: _ignored, ...rest } = data as Record<
                string,
                unknown
            >;
            return rest as typeof data;
        });
    }

    if (isRevise && initial) {
        hydrateFromPurchaseOrder(form, initial);
    }

    // ---- remote product search state ----
    const productOptions = ref<ProductOption[]>([]);
    const productLoading = ref(false);
    const selectedProductId = ref<number | null>(null);
    /** option yang sedang dipilih (disimpan agar label tetap tampil walau list berganti). */
    const selectedProduct = ref<ProductOption | null>(null);
    /** Batasi katalog ke satu principal; null = semua principal. */
    const productVendorFilter = ref<number | null>(null);

    let abort: AbortController | null = null;

    const runSearch = debounce(async (q: string) => {
        abort?.abort();
        abort = new AbortController();
        productLoading.value = true;
        try {
            const res = await searchProducts(q, {
                signal: abort.signal,
                limit: PRODUCT_PAGE_SIZE,
                vendorId: productVendorFilter.value,
            });
            const opts = res.map(mapProduct);
            // pastikan option terpilih tetap ada di list
            if (
                selectedProduct.value &&
                !opts.some((o) => o.value === selectedProduct.value!.value)
            ) {
                opts.unshift(selectedProduct.value);
            }
            productOptions.value = opts;
        } catch (e: any) {
            if (e?.name !== "CanceledError" && e?.code !== "ERR_CANCELED") {
                productOptions.value = [];
            }
        } finally {
            productLoading.value = false;
        }
    }, 300);

    /**
     * Dipanggil juga saat query kosong — backend mengembalikan halaman pertama
     * katalog. Sebelumnya query kosong langsung dikosongkan, jadi dropdown
     * selalu kosong sampai user mengetik.
     */
    function onProductSearch(q: string) {
        runSearch(q);
    }

    /** Muat katalog awal saat halaman dibuka. */
    function loadProducts() {
        return runSearch("");
    }

    // Ganti principal → muat ulang katalog supaya barang yang ditampilkan
    // milik principal yang dipilih.
    watch(productVendorFilter, () => {
        // Produk terpilih bisa jadi tidak lagi termasuk hasil filter.
        if (selectedProduct.value && productVendorFilter.value !== null) {
            selectedProduct.value = null;
            selectedProductId.value = null;
            resetProductFields();
        }
        loadProducts();
    });

    // ---- draft input barang ----
    const showPrice = ref(true);
    const draft = ref({
        unit: "",
        quantity: null as unknown as number,
        amount: null as unknown as number,
        amount_discount: null as unknown as number, // ← harga promo
        product_id: null as unknown as number,
        trade_promo_id: null as unknown as number, // ← promo terpilih
    });

    const usePromo = computed(
        () =>
            draft.value.trade_promo_id != null &&
            draft.value.amount_discount != null,
    );

    // ---- trade promo state ----
    const tradePromoOptions = ref<TradePromoOption[]>([]);
    const quotaTradePromo = ref<number | null>(null);

    // hanya membatalkan pilihan promo; daftar opsi tetap
    function clearTradePromoSelection() {
        quotaTradePromo.value = null;
        draft.value.trade_promo_id = null as unknown as number;
        draft.value.amount_discount = null as unknown as number;
    }

    // reset total (saat ganti/clear produk)
    function clearTradePromo() {
        clearTradePromoSelection();
        tradePromoOptions.value = [];
    }

    function handleProductSelection(id: number | null) {
        const opt = productOptions.value.find((o) => o.value === id) ?? null;
        selectedProduct.value = opt;
        if (!opt) {
            resetProductFields();
            return;
        }
        draft.value.product_id = opt.value;
        draft.value.unit = opt.unit;
        draft.value.amount = opt.price; // harga asli selalu jadi default

        // promo hanya disiapkan sebagai OPSI, tidak otomatis dipakai
        tradePromoOptions.value = opt.tradePromos;
        clearTradePromoSelection(); // mulai tanpa promo terpilih
    }

    function resetProductFields() {
        draft.value.product_id = null as unknown as number;
        draft.value.unit = "";
        draft.value.amount = null as unknown as number;
        clearTradePromo();
    }

    // Hitung kuota tersisa (kurangi yang sudah dipakai item di tabel) — persis logika lama
    function updateTradePromoQuota() {
        const id = draft.value.trade_promo_id;
        const selected = tradePromoOptions.value.find((o) => o.value === id);
        if (!selected) {
            // promo dibatalkan → kembali ke harga asli
            quotaTradePromo.value = null;
            draft.value.amount_discount = null as unknown as number;
            return;
        }
        const usedQty = form.transaction_items
            .filter((i) => i.trade_promo_id === id)
            .reduce((sum, i) => sum + (Number(i.quantity) || 0), 0);

        const remaining = selected.quota - usedQty;
        quotaTradePromo.value = remaining;
        draft.value.amount_discount = selected.price; // override harga

        if ((draft.value.quantity || 0) > remaining) {
            draft.value.quantity = remaining;
        }
    }

    function validateQuota(quantity: number | null): string | null {
        if (!usePromo.value) return null; // tanpa promo, tak ada batas kuota
        if (
            quotaTradePromo.value !== null &&
            quantity !== null &&
            quantity > quotaTradePromo.value
        ) {
            return `Kuota hanya tersedia ${quotaTradePromo.value}`;
        }
        return null;
    }

    // ---- preview subtotal item (sebelum ditambah) ----
    const draftLineTotal = computed(() => {
        const gross =
            Number(
                usePromo.value
                    ? draft.value.amount_discount
                    : draft.value.amount,
            ) || 0;
        const qty = Number(draft.value.quantity) || 0;
        if (!gross || !qty) return 0;
        // Server membulatkan subtotal bersih per baris, bukan harga bersih
        // satuan lalu mengalikannya dengan jumlah.
        return toNetPrice(gross * qty, form.transaction_detail.use_tax);
    });

    // ---- ADD / REMOVE ITEM ----
    /**
     * Harga satuan bersih (belum termasuk PPN).
     *
     * Barang non-gula (`use_tax`) harga entered sudah termasuk PPN 11%, jadi
     * harus dibagi 1.11. Barang gula (`use_tax === false`) tidak dipotong sama
     * sekali — sebelumnya barang gula ikut dibagi, membuat nilainya terkurang 10%.
     */
    function toNetPrice(gross: number, useTax: boolean): number {
        return useTax
            ? Math.round(((gross / PPN_DIVISOR) + Number.EPSILON) * 100) / 100
            : gross;
    }

    function addProduct(): string | null {
        if (!selectedProductId.value || !draft.value.quantity) {
            return "Pilih barang dan isi jumlah";
        }
        const rawAmount = usePromo.value
            ? String(draft.value.amount_discount)
            : String(draft.value.amount).replace(",", ".");
        const parsed = parseFloat(rawAmount);
        if (isNaN(parsed) || parsed <= 0) return "Harga produk tidak valid";

        const opt = selectedProduct.value;
        if (!opt) return "Produk tidak ditemukan";

        const qty = Number(draft.value.quantity);
        if (!Number.isInteger(qty) || qty < 1) {
            return "Jumlah harus bilangan bulat lebih dari 0";
        }
        const useTax = form.transaction_detail.use_tax;

        form.transaction_items = [
            ...form.transaction_items,
            {
                unit: draft.value.unit,
                quantity: qty,
                product_id: opt.value,
                // `amount` = harga bersih (dikirim ke `base_price`).
                amount: toNetPrice(parsed, useTax),
                // `unit_price` = harga bruto termasuk PPN. Dikirim ke server
                // supaya subtotal/pajak dihitung ulang dari angka ini, bukan
                // dipercaya apa adanya dari browser.
                unit_price: parsed,
                original_price: opt.price,
                tax_id: null,
                use_tax: useTax,
                trade_promo_id: draft.value.trade_promo_id ?? null,
                // `total_price` = total bruto baris, sehingga penjumlahannya
                // sama dengan `grand_total`.
                total_price: parseFloat((parsed * qty).toFixed(2)),
                product: { code: opt.code, unit: opt.unit, name: opt.name },
            },
        ];

        resetDraft();
        return null;
    }

    /**
     * Hitung ulang semua baris ketika status PPN diklik.
     *
     * Tanpa ini, baris yang ditambahkan sebelum PPN diubah masih memakai nilai
     * lama sehingga `sub_total` tidak cocok dengan isi tabel.
     */
    function recalcItemsForTax(useTax: boolean) {
        form.transaction_items = form.transaction_items.map((item) => {
            const gross = Number(item.unit_price) || 0;
            return {
                ...item,
                use_tax: useTax,
                amount: toNetPrice(gross, useTax),
                total_price: parseFloat(
                    (gross * Number(item.quantity || 0)).toFixed(2),
                ),
            };
        });
    }

    function resetDraft() {
        showPrice.value = true;
        selectedProductId.value = null;
        selectedProduct.value = null;
        draft.value.quantity = null as unknown as number;
        draft.value.amount = null as unknown as number;
        draft.value.amount_discount = null as unknown as number;
        draft.value.product_id = null as unknown as number;
        draft.value.trade_promo_id = null as unknown as number;
        draft.value.unit = "";
        quotaTradePromo.value = null;
        tradePromoOptions.value = [];
    }

    function removeProduct(index: number) {
        form.transaction_items = form.transaction_items.filter(
            (_, i) => i !== index,
        );
    }

    // ---- DERIVED TOTALS ----
    // Total neto dibulatkan per baris, sama dengan kalkulator server.
    const grossTotal = computed(() =>
        Math.round(
            (form.transaction_items.reduce(
                (total, item) =>
                    total +
                    (Number(item.unit_price) || 0) *
                        (Number(item.quantity) || 0),
                0,
            ) +
                Number.EPSILON) *
                100,
        ) / 100,
    );

    /** Jumlah bersih per baris, dengan pembulatan yang sama seperti server. */
    const subTotal = computed(() =>
        Math.round(
            (form.transaction_items.reduce(
                (total, item) =>
                    total +
                    toNetPrice(
                        (Number(item.unit_price) || 0) *
                            (Number(item.quantity) || 0),
                        form.transaction_detail.use_tax,
                    ),
                0,
            ) +
                Number.EPSILON) *
                100,
        ) / 100,
    );

    const taxAmount = computed(() =>
        Math.max(0, parseFloat((grossTotal.value - subTotal.value).toFixed(2))),
    );

    const total = computed(() => grossTotal.value);

    watch(
        () => form.transaction_detail.use_tax,
        (useTax) => recalcItemsForTax(useTax),
    );

    // ---- DUE DATE ----
    function updateDueDate(poDate: string | null, term: number) {
        const termDays = Number(term) || 45;
        if (!poDate) return;
        const d = new Date(poDate);
        if (isNaN(d.getTime())) return;
        form.due_date = addDays(d, termDays);
    }

    // ---- WATCHERS ----
    watch(selectedProductId, (id) => handleProductSelection(id));
    watch(
        [
            () => form.transaction_detail.purchase_order_date,
            () => form.term_of_payment,
        ],
        debounce(
            ([date, term]) =>
                updateDueDate(date as string | null, term as number),
            300,
        ),
        { immediate: true },
    );

    watch(() => draft.value.trade_promo_id, updateTradePromoQuota);
    watch(
        () => draft.value.quantity,
        (qty) => {
            // auto-clamp seperti lama; pesan error ditampilkan di UI lewat validateQuota
            if (
                quotaTradePromo.value !== null &&
                qty != null &&
                qty > quotaTradePromo.value
            ) {
                draft.value.quantity = quotaTradePromo.value;
            }
        },
    );

    // Muat katalog awal supaya dropdown tidak kosong sebelum user mengetik.
    onMounted(loadProducts);

    return {
        form,
        productOptions,
        productLoading,
        selectedProductId,
        selectedProduct,
        productVendorFilter,
        loadProducts,
        onProductSearch,
        draft,
        draftLineTotal,
        usePromo, // ← usePromo (bukan showPrice)
        tradePromoOptions,
        quotaTradePromo,
        addProduct,
        removeProduct,
        validateQuota,
        subTotal,
        taxAmount,
        total,
    };
}
