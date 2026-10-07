import { h } from "vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { Trash2, BadgePercent, TriangleAlert, Pencil } from "lucide-vue-next";
import { NButton, NIcon } from "naive-ui";
import { formatRupiah } from "@/utils/format";
import { finalUnitPrice } from "@/composables/useDeliveryOrder";
import type { DeliveryOrderItem, DeliveryPromo } from "@/types/delivery-order";

/**
 * Chip persentase/nominal untuk setiap tahap diskon cascading — urutan &
 * nilai yang sama dengan `DeliveryOrderPromoCalculator::apply()`.
 */
function discountChips(promo: DeliveryPromo | null): string[] {
    if (!promo) return [];

    const chips: string[] = [];

    for (const percentage of [promo.percentage_1, promo.percentage_2]) {
        if (percentage !== null && percentage > 0) {
            chips.push(`${percentage}%`);
        }
    }

    if (promo.percentage_3 !== null && promo.percentage_3 > 0) {
        chips.push(`${promo.percentage_3}%`);
    } else {
        const manualType = String(promo.manual_type ?? "").toUpperCase();

        if (manualType === "PERCENTAGE" && promo.manual_percentage !== null) {
            chips.push(`${promo.manual_percentage}%`);
        } else if (manualType === "VALUE" && promo.manual_value !== null) {
            chips.push(formatRupiah(promo.manual_value));
        }
    }

    return chips;
}

/**
 * Kolom tabel barang form DO — compact: gabung kode ke kolom nama, stok ke
 * kolom jumlah, dan persentase + nominal diskon jadi satu kolom.
 *
 * `showStock` hanya true untuk jenis pengiriman DEPO — jenis lain tidak
 * mengekspos stok di form (server tetap memvalidasi FEFO saat submit).
 */
export function buildItemColumns(options: {
    onRemove: (index: number) => void;
    showStock: boolean;
}): ColumnDef<DeliveryOrderItem>[] {
    const columns: ColumnDef<DeliveryOrderItem>[] = [
        {
            id: "product",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Barang",
                ),
            cell: ({ row }) => {
                const item = row.original;
                const tags = [];

                if (item.promo) {
                    tags.push(
                        h(
                            "span",
                            {
                                class:
                                    "inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600",
                            },
                            [
                                h(BadgePercent, { class: "h-3 w-3" }),
                                item.promo.name,
                            ],
                        ),
                    );
                }

                if (item.use_manual_price) {
                    tags.push(
                        h(
                            "span",
                            {
                                class:
                                    "inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-600",
                            },
                            [h(Pencil, { class: "h-3 w-3" }), "Manual"],
                        ),
                    );
                }

                return h("div", { class: "flex flex-col gap-1" }, [
                    h(
                        "span",
                        { class: "text-xs text-slate-400" },
                        item.product.code,
                    ),
                    h(
                        "span",
                        { class: "font-medium text-slate-700" },
                        item.product.name,
                    ),
                    tags.length
                        ? h(
                              "div",
                              { class: "flex flex-wrap gap-1" },
                              tags,
                          )
                        : null,
                ]);
            },
        },
        {
            accessorKey: "quantity",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Jumlah",
                ),
            cell: ({ row }) => {
                const item = row.original;

                const parts = [
                    h(
                        "span",
                        { class: "text-slate-600" },
                        `${item.quantity} ${item.product.unit || ""}`.trim(),
                    ),
                ];

                // Stok DEPO diselipkan di bawah jumlah — satu kolom untuk
                // qty + ketersediaan.
                if (options.showStock) {
                    const stock = item.stock ?? 0;
                    const over = item.quantity > stock;

                    parts.push(
                        h(
                            "span",
                            {
                                class: over
                                    ? "inline-flex items-center gap-1 text-xs font-medium text-rose-600"
                                    : stock < 10
                                      ? "inline-flex items-center gap-1 text-xs font-medium text-amber-600"
                                      : "text-xs text-slate-400",
                            },
                            [
                                over || stock < 10
                                    ? h(TriangleAlert, { class: "h-3 w-3" })
                                    : null,
                                `stok ${stock}`,
                            ],
                        ),
                    );
                }

                return h("div", { class: "flex flex-col gap-0.5" }, parts);
            },
        },
        {
            id: "unit_price",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Harga Satuan",
                ),
            cell: ({ row }) => {
                const item = row.original;
                const final = finalUnitPrice(item);

                return h("div", { class: "flex flex-col" }, [
                    h(
                        "span",
                        { class: "font-medium text-slate-700" },
                        formatRupiah(final),
                    ),
                    item.promo
                        ? h(
                              "span",
                              { class: "text-xs text-amber-600" },
                              `normal: ${formatRupiah(item.unit_price)}`,
                          )
                        : null,
                ]);
            },
        },
        {
            id: "discount",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Diskon",
                ),
            cell: ({ row }) => {
                const item = row.original;

                if (!item.promo) {
                    return h(
                        "span",
                        { class: "text-slate-300" },
                        "—",
                    );
                }

                const chips = discountChips(item.promo).map((chip) =>
                    h(
                        "span",
                        {
                            class:
                                "rounded bg-rose-50 px-1.5 py-0.5 text-xs font-medium text-rose-600",
                        },
                        chip,
                    ),
                );

                const nominal = (finalUnitPrice(item) - item.unit_price) * item.quantity;

                return h("div", { class: "flex flex-col gap-1" }, [
                    h("div", { class: "flex flex-wrap gap-1" }, chips),
                    h(
                        "span",
                        { class: "text-xs font-medium text-rose-600" },
                        `− ${formatRupiah(Math.abs(Math.round(nominal * 100) / 100))}`,
                    ),
                ]);
            },
        },
        {
            id: "total_price",
            header: () =>
                h(
                    "span",
                    {
                        class:
                            "block text-right text-xs font-medium text-slate-400",
                    },
                    "Total",
                ),
            cell: ({ row }) =>
                h(
                    "span",
                    {
                        class: "block text-right font-semibold text-slate-800",
                    },
                    formatRupiah(
                        finalUnitPrice(row.original) * row.original.quantity,
                    ),
                ),
        },
        {
            id: "actions",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Aksi",
                ),
            cell: ({ row }) =>
                h(
                    NButton,
                    {
                        quaternary: true,
                        circle: true,
                        type: "error",
                        size: "small",
                        onClick: () => options.onRemove(row.index),
                    },
                    {
                        icon: () =>
                            h(
                                NIcon,
                                { size: 18 },
                                { default: () => h(Trash2) },
                            ),
                    },
                ),
        },
    ];

    return columns;
}
