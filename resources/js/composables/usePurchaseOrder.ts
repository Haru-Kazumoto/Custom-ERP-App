import { ref, computed, watch, watchEffect } from "vue";
import { useForm } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { addDays } from "@/utils/format";
import { searchProducts, type ApiProduct } from "@/services/productApi";
import type { TransactionItem } from "@/types/purchase-order";

interface Options {
    poNumber: string;
    fullname: string;
}

const PPN_DIVISOR = 1.11;

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

export function usePurchaseOrder({ poNumber, fullname }: Options) {
    const form = useForm({
        document_code: poNumber,
        term_of_payment: 45,
        due_date: null as string | null,
        description: "",
        sub_total: 0,
        total: 0,
        tax_amount: 0,
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

    // ---- remote product search state ----
    const productOptions = ref<ProductOption[]>([]);
    const productLoading = ref(false);
    const selectedProductId = ref<number | null>(null);
    /** option yang sedang dipilih (disimpan agar label tetap tampil walau list berganti). */
    const selectedProduct = ref<ProductOption | null>(null);

    let abort: AbortController | null = null;

    const runSearch = debounce(async (q: string) => {
        abort?.abort();
        abort = new AbortController();
        productLoading.value = true;
        try {
            const res = await searchProducts(q, abort.signal);
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

    function onProductSearch(q: string) {
        // hanya query kalau user mulai ketik; kosong = bersihkan (kecuali yg terpilih)
        if (!q) {
            productOptions.value = selectedProduct.value
                ? [selectedProduct.value]
                : [];
            return;
        }
        runSearch(q);
    }

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
        const amt =
            Number(
                usePromo.value
                    ? draft.value.amount_discount
                    : draft.value.amount,
            ) || 0;
        const qty = Number(draft.value.quantity) || 0;
        if (!amt || !qty) return 0;
        return Math.round(amt / PPN_DIVISOR) * qty;
    });

    // ---- ADD / REMOVE ITEM ----
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

        const amountExclTax = Math.round(parsed / PPN_DIVISOR);
        const totalPrice = parseFloat(
            (amountExclTax * draft.value.quantity).toFixed(2),
        );

        form.transaction_items = [
            ...form.transaction_items,
            {
                unit: draft.value.unit,
                quantity: draft.value.quantity,
                product_id: opt.value,
                amount: amountExclTax,
                tax_id: null,
                use_tax: form.transaction_detail.use_tax,
                trade_promo_id: draft.value.trade_promo_id ?? null,
                total_price: totalPrice,
                product: { code: opt.code, unit: opt.unit, name: opt.name },
            },
        ];

        resetDraft();
        return null;
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

    // ---- DERIVED TOTALS (logika lama dipertahankan) ----
    const getSubtotal = () =>
        form.transaction_items.reduce(
            (t, i) => t + Number(i.total_price ?? 0),
            0,
        );

    const subTotal = computed(() =>
        form.transaction_detail.use_tax
            ? getSubtotal() / PPN_DIVISOR
            : getSubtotal(),
    );
    const taxAmount = computed(() =>
        form.transaction_detail.use_tax ? getSubtotal() - subTotal.value : 0,
    );
    const total = computed(() => getSubtotal());

    watchEffect(() => {
        form.sub_total = Math.round(subTotal.value);
        form.tax_amount = Math.round(taxAmount.value);
        form.total = Math.round(total.value);
    });

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

    return {
        form,
        productOptions,
        productLoading,
        selectedProductId,
        selectedProduct,
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
