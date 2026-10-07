import { ref, computed, watch } from "vue";
import { useForm, type Form } from "@inertiajs/vue3";
import { debounce } from "lodash";
import axios from "axios";
import { addDays, formatRupiah } from "@/utils/format";
import type {
    CustomerOption,
    CompanyOption,
    DeliveryOrderDetailRow,
    DeliveryOrderFormOptions,
    DeliveryOrderItem,
    DeliveryProduct,
    DeliveryPromo,
    ShippingOption,
    SubShippingOption,
} from "@/types/delivery-order";

/**
 * Bentuk form yang dikirim ke `delivery-order.store`.
 *
 * `transaction_details` dibangun oleh `buildTransactionDetails()` (array sesuai
 * skema tabel), `transaction_items` berisi baris tampilan lalu dipetakan oleh
 * `transform()` di bawah ke payload minimal yang divalidasi server.
 */
export interface DeliveryOrderFormData {
    document_code: string;
    delivery_date: string | null;
    description: string;
    // Opsi select di-build dengan `String(id)` — nilai form berupa string;
    // server meng-cast ke integer lewat `DeliveryOrderController::normalizeIds()`.
    company_id: string | null;
    shipping_id: string | null;
    sub_shipping_id: string | null;
    customer_id: string | null;
    /**
     * Segmen harga dipilih manual (default mengikuti pelanggan) — menentukan
     * kolom `product_prices` yang dipakai server untuk pricing.
     */
    segment: string;
    /** Opsional — disimpan ke `transaction_details` hanya bila terisi. */
    customer_po_number: string;
    cashback_pph_4: number | null;
    biaya_bongkar: number | null;
    /** Label opsi `PAYMENT_TERMS_OPTIONS` yang dicentang. */
    syarat_pembayaran: string[];
    transaction_details: DeliveryOrderDetailRow[];
    transaction_items: DeliveryOrderItem[];
}

/** Segmen yang dikenal server (`DeliveryOrderController::SEGMENTS`). */
export const SEGMENT_OPTIONS = [
    "RETAIL",
    "WHOLESALE",
    "GROSIR",
    "END_USER",
    "ALL_SEGMENT",
] as const;

/** Opsi centang "Syarat Pembayaran" (informasi cetak, tidak mempengaruhi total). */
export const PAYMENT_TERMS_OPTIONS = [
    "Invoice",
    "Surat Jalan Asli PO/CO",
    "Faktur Pajak",
    "Kwitansi",
    "Surat Penerimaan Barang Asli",
] as const;

/** Satu tahap diskon cascading (preview; server menyimpan bentuk serupa). */
export interface PromoStage {
    sequence: number;
    type: "PERCENTAGE" | "VALUE";
    value: number;
    before: number;
    after: number;
}

/** Pembagi PPN 11% — sama dengan `DeliveryOrderCalculator`. */
const PPN_DIVISOR = 1.11;

/** Pembulatan 2 desimal mengikuti `round($value, 2)` di server. */
function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/**
 * Cek kelayakan quantity untuk satu promo — pesan identik dengan
 * `DeliveryOrderPromoCalculator::assertQuantityEligible()` supaya error form
 * sebelum submit sama dengan yang akan dilempar server.
 *
 * FLUSH_OUT melewati range min/max; `base_quota` berlaku untuk semua promo.
 */
export function validatePromoQuantity(
    promo: DeliveryPromo,
    quantity: number,
): string | null {
    const name = promo.name || "promo";

    if (promo.base_quota !== null && quantity > promo.base_quota) {
        return `Jumlah ${quantity} melebihi kuota promo "${name}" (maksimal ${promo.base_quota}).`;
    }

    if (String(promo.type).toUpperCase() === "FLUSH_OUT") {
        return null;
    }

    if (promo.min_qty !== null && quantity < promo.min_qty) {
        return `Jumlah ${quantity} kurang dari minimum promo "${name}" (minimal ${promo.min_qty}).`;
    }

    if (promo.max_qty !== null && quantity > promo.max_qty) {
        return `Jumlah ${quantity} melebihi maksimum promo "${name}" (maksimal ${promo.max_qty}).`;
    }

    return null;
}

/**
 * Cascading discount: tahap 1 & 2 persentase bertingkat, tahap 3
 * `percentage_3` → `manual_percentage` → `manual_value` (dibatasi harga A2).
 * Kalkulasi dan pembulatan per tahap identik dengan server.
 */
export function applyPromoCascade(
    promo: DeliveryPromo,
    unitPrice: number,
): number {
    let before = round2(unitPrice);

    for (const percentage of [promo.percentage_1, promo.percentage_2]) {
        if (percentage === null || percentage <= 0) continue;
        before = round2(before - (before * percentage) / 100);
    }

    if (promo.percentage_3 !== null && promo.percentage_3 > 0) {
        return round2(before - (before * promo.percentage_3) / 100);
    }

    const manualType = String(promo.manual_type ?? "").toUpperCase();

    if (manualType === "PERCENTAGE" && promo.manual_percentage !== null) {
        return round2(before - (before * promo.manual_percentage) / 100);
    }

    if (manualType === "VALUE" && promo.manual_value !== null) {
        return round2(before - Math.min(promo.manual_value, before));
    }

    return before;
}

/** Harga satuan akhir (setelah promo) untuk satu baris tabel. */
export function finalUnitPrice(item: DeliveryOrderItem): number {
    return item.promo
        ? applyPromoCascade(item.promo, item.unit_price)
        : round2(item.unit_price);
}

export function useDeliveryOrder({
    doNumber,
    options,
}: {
    doNumber: string;
    options: DeliveryOrderFormOptions;
}) {
    const form = useForm<DeliveryOrderFormData>({
        document_code: doNumber,
        delivery_date: null as string | null,
        description: "",
        company_id: null,
        shipping_id: null,
        sub_shipping_id: null,
        customer_id: null,
        segment: "ALL_SEGMENT",
        customer_po_number: "",
        cashback_pph_4: null as number | null,
        biaya_bongkar: null as number | null,
        syarat_pembayaran: [] as string[],
        transaction_details: [] as DeliveryOrderDetailRow[],
        transaction_items: [] as DeliveryOrderItem[],
    });

    // Bukan field POST langsung: dikodekan ke `transaction_details`
    // (USE_TAX / USE_MANUAL_PRICE) — satu sumber kebenaran seperti DTO server.
    const useTax = ref(true);

    // ---- referensi terpilih ----
    // Nilai form berupa string (String(id)), sementara id opsi number —
    // konversi ke number dulu supaya perbandingan `===` tidak selalu false.
    const selectedShipping = computed(
        () =>
            options.shippings.find(
                (s) => s.id === Number(form.shipping_id),
            ) ?? null,
    );
    const selectedSub = computed<SubShippingOption | null>(
        () =>
            selectedShipping.value?.subs.find(
                (s) => s.id === Number(form.sub_shipping_id),
            ) ?? null,
    );
    const selectedCustomer = computed<CustomerOption | null>(
        () =>
            options.customers.find(
                (c) => c.id === Number(form.customer_id),
            ) ?? null,
    );
    const selectedCompany = computed<CompanyOption | null>(
        () =>
            options.companies.find(
                (c) => c.id === Number(form.company_id),
            ) ?? null,
    );

    const paymentTerm = computed(
        () => Number(selectedCustomer.value?.term_payment ?? 0) || 0,
    );

    // Pelanggan dipilih → segmen kembali ke default miliknya (user masih
    // bisa mengubahnya manual sebelum tabel barang terisi).
    watch(
        () => form.customer_id,
        () => {
            form.segment = selectedCustomer.value?.segment ?? "ALL_SEGMENT";
        },
    );
    /** Jatuh tempo = tanggal kirim + termin pelanggan (server menghitung sama). */
    const dueDate = computed(() =>
        form.delivery_date
            ? addDays(form.delivery_date, paymentTerm.value)
            : null,
    );
    /** Jenis pengiriman tanpa sub (DO) menyembunyikan select sub. */
    const hasSub = computed(
        () => (selectedShipping.value?.subs.length ?? 0) > 0,
    );
    /**
     * Hanya DEPO yang menampilkan stok di form. Jenis lain tetap divalidasi
     * server saat submit (FEFO tetap berlaku), tapi stoknya tidak diekspos.
     */
    const showStock = computed(
        () => selectedShipping.value?.code === "DEPO",
    );

    // ---- pencarian barang ----
    const products = ref<DeliveryProduct[]>([]);
    const productLoading = ref(false);
    const searchQuery = ref("");
    const selectedProductId = ref<number | null>(null);
    const selectedProduct = ref<DeliveryProduct | null>(null);

    let controller: AbortController | null = null;

    const fetchProducts = debounce(async () => {
        if (!form.company_id) {
            products.value = [];
            return;
        }

        controller?.abort();
        controller = new AbortController();
        productLoading.value = true;

        try {
            const { data } = await axios.get<DeliveryProduct[]>(
                route("delivery-order.products"),
                {
                    params: {
                        search: searchQuery.value,
                        company_id: form.company_id,
                        shipping_id: form.shipping_id,
                        sub_shipping_id: form.sub_shipping_id,
                        customer_id: form.customer_id,
                        segment: form.segment || null,
                    },
                    signal: controller.signal,
                },
            );
            products.value = data;
        } catch (e: any) {
            if (e?.name !== "CanceledError" && e?.code !== "ERR_CANCELED") {
                products.value = [];
            }
        } finally {
            productLoading.value = false;
        }
    }, 300);

    // ---- draft barang ----
    const draft = ref<{
        quantity: number | null;
        promo_product_id: number | null;
        unit_price: number | null;
        use_manual_price: boolean;
    }>({
        quantity: null as number | null,
        promo_product_id: null as number | null,
        unit_price: null as number | null,
        use_manual_price: false,
    });

    const draftPromo = computed<DeliveryPromo | null>(() => {
        const promos = selectedProduct.value?.promos ?? [];
        return (
            promos.find(
                (promo) => promo.promo_product_id === draft.value.promo_product_id,
            ) ?? null
        );
    });

    /** Harga satuan akhir draft (setelah promo) untuk preview. */
    const draftFinalUnit = computed(() => {
        const base = Number(draft.value.unit_price) || 0;
        return draftPromo.value
            ? applyPromoCascade(draftPromo.value, base)
            : round2(base);
    });

    const draftLineTotal = computed(
        () =>
            round2(
                draftFinalUnit.value * (Number(draft.value.quantity) || 0),
            ),
    );

    /**
     * Alasan draft tidak bisa ditambahkan — null berarti valid.
     * Pesan dibuat mirip dengan pesan server supaya user tidak kaget saat
     * submit ditolak.
     */
    const draftError = computed<string | null>(() => {
        const product = selectedProduct.value;
        if (!product) return null;

        const quantity = Number(draft.value.quantity || 0);
        if (quantity < 1) return "Jumlah harus diisi.";

        // Harga wajib ada di kedua mode: jual (otomatis) dan batas atas
        // (manual) — tanpa harga daftar tidak ada angka acuan sama sekali.
        if (!product.has_price) {
            return `Harga "${product.name}" tidak tersedia untuk pengiriman/segmen ini.`;
        }

        if (draft.value.use_manual_price) {
            if (!(Number(draft.value.unit_price) > 0)) {
                return `Harga manual untuk produk "${product.name}" belum diisi.`;
            }
            if (Number(draft.value.unit_price) > Number(product.price)) {
                return `Harga manual untuk produk "${product.name}" melebihi harga daftar (maksimal ${formatRupiah(Number(product.price))}).`;
            }
        }

        if (showStock.value && quantity > product.stock) {
            return `Stok "${product.name}" tidak mencukupi: tersedia ${product.stock}, diminta ${quantity}.`;
        }

        if (draftPromo.value) {
            return validatePromoQuantity(draftPromo.value, quantity);
        }

        return null;
    });

    function resetDraftSelection() {
        selectedProductId.value = null;
        selectedProduct.value = null;
        draft.value.quantity = null;
        draft.value.promo_product_id = null;
        draft.value.unit_price = null;
        draft.value.use_manual_price = false;
    }

    watch(selectedProductId, (id) => {
        const product =
            products.value.find((p) => p.id === id) ?? null;
        selectedProduct.value = product;
        draft.value.quantity = null;
        draft.value.promo_product_id = null;
        draft.value.use_manual_price = false;
        // Harga awal (A): harga katalog di mode otomatis, dan tetap jadi
        // default yang bisa diedit (turun saja) di mode manual.
        draft.value.unit_price = product ? product.price : null;
    });

    // Filter berubah → pilihan barang tidak relevan lagi (harga/promo ikut
    // berubah). Select filter sendiri dinonaktifkan saat tabel sudah terisi.
    // Segmen termasuk karena menentukan kolom `product_prices`.
    watch(
        [
            () => form.company_id,
            () => form.shipping_id,
            () => form.sub_shipping_id,
            () => form.customer_id,
            () => form.segment,
        ],
        () => {
            resetDraftSelection();
            fetchProducts();
        },
    );

    watch(searchQuery, () => fetchProducts());

    /** Ganti PPN: tidak mengubah harga bruto, hanya tampilan net/gross. */
    // (reaktivitas total di bawah membaca useTax langsung, jadi tidak ada
    // keharusan mutasi baris seperti PO.)

    // ---- tambah / hapus barang ----
    function addItem(): string | null {
        const product = selectedProduct.value;
        if (!product) return "Pilih barang terlebih dahulu.";
        if (draftError.value) return draftError.value;

        const quantity = Number(draft.value.quantity);
        const unitPrice = round2(Number(draft.value.unit_price) || 0);

        form.transaction_items = [
            ...form.transaction_items,
            {
                product_id: product.id,
                quantity,
                unit_price: unitPrice,
                use_manual_price: draft.value.use_manual_price,
                promo: draftPromo.value,
                product: {
                    code: product.code,
                    unit: product.unit,
                    name: product.name,
                },
                catalog_price: Number(product.price) || 0,
                stock: showStock.value ? product.stock : null,
            },
        ];

        resetDraftSelection();
        return null;
    }

    function removeItem(index: number) {
        form.transaction_items = form.transaction_items.filter(
            (_, i) => i !== index,
        );
    }

    // ---- total (meniru DeliveryOrderCalculator) ----
    function lineGross(item: DeliveryOrderItem): number {
        return round2(finalUnitPrice(item) * item.quantity);
    }

    function lineNet(item: DeliveryOrderItem): number {
        const gross = lineGross(item);
        return useTax.value ? round2(gross / PPN_DIVISOR) : gross;
    }

    function lineDiscount(item: DeliveryOrderItem): number {
        return round2(
            (round2(item.unit_price) - finalUnitPrice(item)) * item.quantity,
        );
    }

    const grandTotal = computed(() =>
        round2(
            form.transaction_items.reduce(
                (total, item) => total + lineGross(item),
                0,
            ),
        ),
    );

    const subTotal = computed(() =>
        round2(
            form.transaction_items.reduce(
                (total, item) => total + lineNet(item),
                0,
            ),
        ),
    );

    const taxAmount = computed(() =>
        Math.max(0, round2(grandTotal.value - subTotal.value)),
    );

    const totalDiscount = computed(() =>
        round2(
            form.transaction_items.reduce(
                (total, item) => total + lineDiscount(item),
                0,
            ),
        ),
    );

    /** Total bruto SEBELUM diskon promo (Σ harga awal × qty). */
    const grossBeforeDiscount = computed(() =>
        round2(
            form.transaction_items.reduce(
                (total, item) => total + round2(item.unit_price * item.quantity),
                0,
            ),
        ),
    );

    /** True bila minimal satu baris memakai harga manual. */
    const hasManualPrice = computed(() =>
        form.transaction_items.some((item) => item.use_manual_price),
    );

    // ---- payload ----
    function buildTransactionDetails() {
        const shipping = selectedShipping.value;
        const sub = selectedSub.value;
        const customer = selectedCustomer.value;
        const company = selectedCompany.value;

        const rows: DeliveryOrderDetailRow[] = [
            {
                name: "Delivery",
                type: "DELIVERY",
                value: shipping?.name ?? "",
                data_type: "string",
            },
        ];

        // Jenis pengiriman tanpa sub (DO) tidak mengirim baris ini — nilai
        // kosong akan ditolak aturan `required` di server.
        if (sub) {
            rows.push({
                name: "Sub Delivery",
                type: "SUB_DELIVERY",
                value: sub.name,
                data_type: "string",
            });
        }

        rows.push(
            {
                name: "Customer",
                type: "CUSTOMER",
                value: customer?.name ?? "",
                data_type: "string",
            },
            {
                name: "Segment",
                type: "SEGMENT",
                // Segmen pilihan user (bukan lagi kolom pelanggan).
                value: form.segment || "ALL_SEGMENT",
                data_type: "string",
            },
            {
                name: "Company",
                type: "COMPANY",
                value: company?.code ?? "",
                data_type: "string",
            },
            {
                name: "Warehouse",
                type: "WAREHOUSE",
                value: company?.code ?? "",
                data_type: "string",
            },
            {
                name: "Delivery Date",
                type: "DELIVERY_DATE",
                value: form.delivery_date ?? "",
                data_type: "datetime",
            },
            {
                name: "PPN",
                type: "USE_TAX",
                value: String(useTax.value),
                data_type: "boolean",
            },
            {
                name: "Manual Price",
                type: "USE_MANUAL_PRICE",
                value: String(hasManualPrice.value),
                data_type: "boolean",
            },
        );

        // ---- informasi opsional (hanya bila diisi user) ----
        // Disimpan sebagai data saja; tidak memengaruhi total pembayaran.
        const optional: DeliveryOrderDetailRow[] = [];
        const poNumber = form.customer_po_number.trim();
        if (poNumber) {
            optional.push({
                name: "Nomor PO Pelanggan",
                type: "CUSTOMER_PO_NUMBER",
                value: poNumber,
                data_type: "string",
            });
        }
        if (form.cashback_pph_4 !== null && form.cashback_pph_4 > 0) {
            optional.push({
                name: "Cashback PPh 4",
                type: "CASHBACK_PPH_4",
                value: String(form.cashback_pph_4),
                data_type: "float",
            });
        }
        if (form.biaya_bongkar !== null && form.biaya_bongkar > 0) {
            optional.push({
                name: "Biaya Bongkar",
                type: "BIAYA_BONGKAR",
                value: String(form.biaya_bongkar),
                data_type: "float",
            });
        }
        if (form.syarat_pembayaran.length > 0) {
            optional.push({
                name: "Syarat Pembayaran",
                type: "PAYMENT_TERMS",
                value: form.syarat_pembayaran.join(", "),
                data_type: "string",
            });
        }

        form.transaction_details = [...rows, ...optional];
    }

    // Baris tampilan dipetak ke payload minimal: server hanya mengenal
    // product_id, quantity, unit_price, use_manual_price, dan promo_product_id.
    form.transform((data) => {
        const payload = { ...data } as unknown as Record<string, unknown>;

        payload.transaction_items = data.transaction_items.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            use_manual_price: item.use_manual_price,
            promo_product_id: item.promo?.promo_product_id ?? null,
        }));

        return payload as unknown as DeliveryOrderFormData;
    });

    return {
        form,
        useTax,
        options,
        selectedShipping,
        selectedSub,
        selectedCustomer,
        selectedCompany,
        paymentTerm,
        dueDate,
        hasSub,
        showStock,
        products,
        productLoading,
        searchQuery,
        selectedProductId,
        selectedProduct,
        draft,
        draftPromo,
        draftFinalUnit,
        draftLineTotal,
        draftError,
        fetchProducts,
        resetDraftSelection,
        addItem,
        removeItem,
        subTotal,
        grandTotal,
        taxAmount,
        totalDiscount,
        grossBeforeDiscount,
        hasManualPrice,
        lineGross,
        buildTransactionDetails,
    };
}
