<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Queries\GetApprovalHistoryQuery;
use App\Modules\Finance\Queries\GetDeliveryOrderPromoUsageQuery;
use App\Modules\Finance\Queries\GetInvoiceOverviewQuery;
use App\Modules\Finance\Queries\GetPendingApprovalsQuery;
use App\Modules\Finance\Queries\GetPromoClaimQuery;
use App\Modules\Finance\Repositories\FinanceDashboardRepository;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class FinanceDashboardController extends Controller
{
    public function __construct(
        private GetPendingApprovalsQuery $pending_approvals,
        private GetApprovalHistoryQuery $approval_history,
        private GetInvoiceOverviewQuery $invoices,
        private GetDeliveryOrderPromoUsageQuery $promo_usage,
        private GetPromoClaimQuery $promo_claims,
        private FinanceDashboardRepository $repository,
    ) {}

    /**
     * Dashboard finance.
     *
     * Halaman ini sepenuhnya baca: daftar dokumen yang menunggu persetujuan
     * finance, faktur yang sudah terbit, promo yang terpakai pada delivery order,
     * dan klaim promo yang tercatat. Tidak ada aksi approve maupun pembuatan
     * faktur dari sini.
     *
     * Antrean approval sengaja menyatukan Purchase Order, Delivery Order, dan
     * faktur dalam satu tabel karena ketiganya satu antrean persetujuan yang
     * sama. Baris DO dan faktur baru terisi setelah modulnya dibuat, tanpa
     * perubahan query.
     *
     * Dependensinya lewat constructor, bukan method injection: controller ini
     * dipanggil sebagai method biasa dari `DashboardController`, bukan sebagai
     * route action, jadi container tidak ikut menyelesaikannya.
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard/Finance', [
            'summary' => $this->repository->getSummary(),
            'pendingApprovals' => $this->pending_approvals->execute(),
            'approvalHistory' => $this->approval_history->execute(),
            'invoices' => $this->invoices->execute(),
            'promoUsage' => $this->promo_usage->execute(),
            'promoClaims' => $this->promo_claims->execute(),
        ]);
    }
}
