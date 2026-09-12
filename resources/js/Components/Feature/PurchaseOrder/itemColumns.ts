import { h } from "vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { Trash2 } from "lucide-vue-next";
import { NButton, NIcon } from "naive-ui";
import { formatRupiah } from "@/utils/format";
import type { TransactionItem } from "@/types/purchase-order";

export function buildItemColumns(onRemove: (index: number) => void): ColumnDef<TransactionItem>[] {
    return [
        {
            id: "index",
            header: () =>
                h("span", { class: "text-xs font-medium text-slate-400" }, "#"),
            cell: ({ row }) =>
                h("span", { class: "text-slate-400" }, row.index + 1),
        },
        {
            accessorFn: (r) => r.product.code,
            id: "code",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Kode Barang",
                ),
            cell: ({ row }) =>
                h(
                    "span",
                    { class: "text-slate-500" },
                    row.original.product.code,
                ),
        },
        {
            accessorFn: (r) => r.product.name,
            id: "name",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Nama Barang",
                ),
            cell: ({ row }) =>
                h(
                    "span",
                    { class: "font-medium text-slate-700" },
                    row.original.product.name,
                ),
        },
        {
            accessorKey: "quantity",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Jumlah",
                ),
            cell: ({ row }) =>
                h("span", { class: "text-slate-600" }, row.original.quantity),
        },
        {
            accessorKey: "unit",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Kemasan",
                ),
            cell: ({ row }) =>
                h("span", { class: "text-slate-600" }, row.original.unit),
        },
        {
            accessorKey: "amount",
            header: () =>
                h(
                    "span",
                    { class: "text-xs font-medium text-slate-400" },
                    "Harga Barang",
                ),
            cell: ({ row }) =>
                h(
                    "span",
                    { class: "text-slate-600" },
                    formatRupiah(row.original.amount),
                ),
        },
        {
            accessorKey: "total_price",
            header: () =>
                h(
                    "span",
                    {
                        class: "text-right block text-xs font-medium text-slate-400",
                    },
                    "Total Harga",
                ),
            cell: ({ row }) =>
                h(
                    "span",
                    { class: "block text-right font-semibold text-slate-800" },
                    formatRupiah(row.original.total_price),
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
                        onClick: () => onRemove(row.index),
                    },
                    {
                        icon: () =>
                            h(
                                NIcon,
                                { size: 18 },
                                {
                                    default: () => h(Trash2),
                                },
                            ),
                    },
                ),
        },
    ];
}