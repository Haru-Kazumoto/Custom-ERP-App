export type ApprovalStatusDb =
  | "APPROVED"
  | "PENDING"
  | "REJECTED"
  | "NEED_REVISION";

export type ApprovalTone = "emerald" | "amber" | "red" | "violet" | "slate";

export interface ApprovalMeta {
  key: ApprovalStatusDb | "UNKNOWN";
  label: string; // Indonesia
  tone: ApprovalTone;
}

const MAP: Record<ApprovalStatusDb, ApprovalMeta> = {
  APPROVED: { key: "APPROVED", label: "Disetujui", tone: "emerald" },
  PENDING: { key: "PENDING", label: "Menunggu", tone: "amber" },
  REJECTED: { key: "REJECTED", label: "Ditolak", tone: "red" },
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

  const rejected = approvals.find((a) => a.status?.toUpperCase() === "REJECTED");
  if (rejected) return normalizeApproval("REJECTED");

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