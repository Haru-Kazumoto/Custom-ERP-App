<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class DashboardController extends Controller
{
    public function renderSalesDashboard(): Response
    {
        return Inertia::render('Dashboard/Sales');
    }

    public function renderMarketingDashboard(): Response
    {
        return Inertia::render('Dashboard/Marketing');
    }

    public function renderProcurementDashboard(): Response
    {
        return Inertia::render('Dashboard/Procurement');
    }

    public function renderBusinessDevelopmentDashboard(): Response
    {
        return Inertia::render('Dashboard/BusinessDevelopment');
    }

    public function renderInvoicingDashboard(): Response
    {
        return Inertia::render('Dashboard/Invoicing');
    }

    public function renderFinanceDashboard(): Response
    {
        return Inertia::render('Dashboard/Finance');
    }

    public function renderWarehouseDashboard(): Response
    {
        return Inertia::render('Dashboard/Warehouse');
    }

    public function renderArControllerDashboard(): Response
    {
        return Inertia::render('Dashboard/ArController');
    }

    public function renderDocumentControlDashboard(): Response
    {
        return Inertia::render('Dashboard/DocumentControl');
    }

    public function renderAdminDashboard(): Response
    {
        return Inertia::render('Dashboard/Admin');
    }

    public function renderDefaultDashboard(): Response
    {
        // abort with 403 Forbidden or render a generic dashboard
        abort(403, 'Unauthorized access to dashboard');
    }

    public function index(Request $request)
    {
        $role = $request->user()->role ? $request->user()->role->code : null;
        // dd($role);
        return match ($role) {
            'admin'                 => $this->renderAdminDashboard(),
            'sales'                 => $this->renderSalesDashboard(),
            'marketing'             => $this->renderMarketingDashboard(),
            'procurement'           => $this->renderProcurementDashboard(),
            'business_development'  => $this->renderBusinessDevelopmentDashboard(),
            'invoicing'             => $this->renderInvoicingDashboard(),
            'finance'               => $this->renderFinanceDashboard(),
            'warehouse'             => $this->renderWarehouseDashboard(),
            'ar_controller'         => $this->renderArControllerDashboard(),
            'document_control'      => $this->renderDocumentControlDashboard(),
            default                 => $this->renderDefaultDashboard(),
        };
    }
}
