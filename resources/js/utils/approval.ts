export type ApprovalStatusDb = "APPROVED" | "PENDING" | "NEED_REVISION";

export type ApprovalTone = "emerald" | "amber" | "red" | "violet" | "slate";

export interface ApprovalMeta {
  key: ApprovalStatusDb | "UNKNOWN";
  label: string; // Indonesia
  tone: ApprovalTone;
}

const MAP: Record<ApprovalStatusDb, ApprovalMeta> = {
  APPROVED: { key: "APPROVED", label: "Disetujui", tone: "emerald" },
  PENDING: { key: "PENDING", label: "Menunggu", tone: "amber" },
  NEED_REVISION: { key: "NEED_REVISION", label: "Perlu Revisi", tone: "violet" },
};

export function normalizeApproval(status?: string | null): ApprovalMeta {
  if (!status) return { key: "UNKNOWN", label: "Belum diproses", tone: "slate" };
  return (
    MAP[status.toUpperCase() as ApprovalStatusDb] ?? {
      key: "UNKNOWN",
      label: status,
      tone: "slate",
    }
  );
}

/** Ringkasan status keseluruhan chain dari daftar approval. */
export function overallApproval(
  approvals: { status: string; proceed_by?: string | null }[],
): ApprovalMeta {
  if (!approvals.length)
    return { key: "UNKNOWN", label: "Belum ada persetujuan", tone: "slate" };

  // Terminal diperiksa lebih dulu: `generateApprovals()` membuat SEMUA langkah
  // sekaligus sebagai PENDING, jadi PO yang berhenti di Finance masih punya
  // langkah Marketing yang PENDING. Kalau PENDING dicek lebih dulu, dokumen
  // yang butuh revisi akan dilaporkan sebagai "Menunggu".
  const revision = approvals.find(
    (a) => a.status?.toUpperCase() === "NEED_REVISION",
  );
  if (revision) return normalizeApproval("NEED_REVISION");

  const allApproved = approvals.every(
    (a) => a.status?.toUpperCase() === "APPROVED",
  );
  if (allApproved) return normalizeApproval("APPROVED");

  return normalizeApproval("PENDING");
}
