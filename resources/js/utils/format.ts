export const formatRupiah = (n: number | string | null | undefined): string => {
    const num = typeof n === "string" ? parseFloat(n) : (n ?? 0);
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number.isFinite(num) ? num : 0);
};

export const formatDate = (value: string | null, withTime = true): string => {
    if (!value) return "";
    const d = new Date(value);
    if (isNaN(d.getTime())) return "";
    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        ...(withTime ? { hour: "2-digit", minute: "2-digit" } : {}),
    }).format(d);
};

export const capitalize = (v: string): string => (v ? v.toUpperCase() : v);

/** Tambah `days` hari ke tanggal, kembalikan 'YYYY-MM-DD HH:mm:ss'. */
export const addDays = (start: Date | string, days: number): string => {
    const base = new Date(start);
    if (isNaN(base.getTime())) return "";
    base.setDate(base.getDate() + days);
    return base.toISOString().slice(0, 19).replace("T", " ");
};
