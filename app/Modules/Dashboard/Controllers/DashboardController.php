<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Queries\GetSalesDashboardDataQuery;
use App\Modules\Dashboard\Queries\GetWarehouseDashboardDataQuery;
use App\Modules\Finance\Controllers\FinanceDashboardController;
use App\Modules\PurchaseOrder\Queries\GetTenLatestPurchaseOrderQueries;
use App\Modules\Roles\Queries\GetOneRoleFromUserQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class DashboardController extends Controller
{
    public function __construct(
        private GetTenLatestPurchaseOrderQueries $get_latest_purchase_orders,
        private GetSalesDashboardDataQuery $sales_dashboard_data,
        private GetWarehouseDashboardDataQuery $warehouse_dashboard_data
    ) {}

    private function renderSalesDashboard(int $userId): Response
    {
        return Inertia::render('Dashboard/Sales', $this->sales_dashboard_data->execute($userId));
    }

    private function renderMarketingDashboard(): Response
    {
        return Inertia::render('Dashboard/Marketing');
    }

    private function renderProcurementDashboard(): Response
    {
        return Inertia::render('Dashboard/Procurement', [
            'purchase_orders' => $this->get_latest_purchase_orders->execute()
        ]);
    }

    private function renderBusinessDevelopmentDashboard(): Response
    {
        return Inertia::render('Dashboard/BusinessDevelopment');
    }

    private function renderInvoicingDashboard(): Response
    {
        return Inertia::render('Dashboard/Invoicing');
    }

    /**
     * Dashboard finance punya modul sendiri karena isinya bukan satu kartu
     * statis: antrean approval lintas tipe dokumen, faktur terbit, dan klaim
     * promo. Query-nya tetap milik modul Finance, di sini cukup meneruskan.
     */
    private function renderFinanceDashboard(FinanceDashboardController $finance): Response
    {
        return $finance->index();
    }

    private function renderWarehouseDashboard(): Response
    {
        return Inertia::render('Dashboard/Warehouse', $this->warehouse_dashboard_data->execute());
    }

    private function renderArControllerDashboard(): Response
    {
        return Inertia::render('Dashboard/ArController');
    }

    private function renderDocumentControlDashboard(): Response
    {
        return Inertia::render('Dashboard/DocumentControl');
    }

    private function renderAdminDashboard(): Response
    {
        return Inertia::render('Dashboard/Admin');
    }

    private function renderDefaultDashboard(): Response
    {
        // abort with 403 Forbidden or render a generic dashboard
        abort(403, 'Unauthorized access to dashboard');
    }

    public function index(Request $request, GetOneRoleFromUserQuery $get_one_role_from_user, FinanceDashboardController $finance_dashboard)
    {
        $role = $get_one_role_from_user->execute($request->user()?->id);

        return match ($role->code) {
            'admin' => $this->renderAdminDashboard(),
            'sales' => $this->renderSalesDashboard((int) $request->user()->id),
            'marketing' => $this->renderMarketingDashboard(),
            'procurement' => $this->renderProcurementDashboard(),
            'business_development' => $this->renderBusinessDevelopmentDashboard(),
            'invoicing' => $this->renderInvoicingDashboard(),
            'finance' => $this->renderFinanceDashboard($finance_dashboard),
            'warehouse' => $this->renderWarehouseDashboard(),
            'ar_controller' => $this->renderArControllerDashboard(),
            'document_control' => $this->renderDocumentControlDashboard(),
            default => $this->renderDefaultDashboard(),
        };
    }
}
