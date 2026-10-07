<?php

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Modules\Stock\Actions\RecordGradualArrivalAction;
use App\Modules\Stock\DTOs\RecordGradualArrivalDTO;
use App\Modules\Stock\Queries\GetAllStocksQuery;
use App\Modules\Stock\Queries\GetBatchStockQuery;
use App\Modules\Stock\Queries\GetGradualStockQuery;
use App\Modules\Stock\Queries\GetStagnantStockQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request, GetAllStocksQuery $query)
    {
        return Inertia::render('Stocks/Index', [
            'stocks' => $query->execute($this->filters($request)),
            'filters' => $this->filterProps($request),
            'companies' => $this->companies(),
        ]);
    }

    public function batches(Request $request, GetBatchStockQuery $query)
    {
        return Inertia::render('Stocks/Batches', [
            'stocks' => $query->execute($this->filters($request)),
            'filters' => $this->filterProps($request),
            'companies' => $this->companies(),
        ]);
    }

    public function stagnations(Request $request, GetStagnantStockQuery $query)
    {
        return Inertia::render('Stocks/Stagnations', [
            'stocks' => $query->execute($this->filters($request)),
            'filters' => $this->filterProps($request),
            'companies' => $this->companies(),
        ]);
    }

    public function gradually(Request $request, GetGradualStockQuery $query)
    {
        return Inertia::render('Stocks/Gradually', [
            'stocks' => $query->execute($this->filters($request)),
            'filters' => $this->filterProps($request),
            'companies' => $this->companies(),
        ]);
    }

    public function arrive(Request $request, int $id, RecordGradualArrivalAction $action)
    {
        $validated = $request->validate([
            'batch_code' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'expiry_date' => ['nullable', 'date'],
            'stagnation_limit_date' => ['nullable', 'date'],
        ]);

        $action->execute(RecordGradualArrivalDTO::fromValidated($id, $validated));

        return redirect(route('stocks.index.gradually'))
            ->with('success', 'Kedatangan barang bertahap berhasil dicatat.');
    }

    /**
     * Filter bersama: `search` bebas, `company_id` opsional — default kosong
     * berarti SEMUA company tampil.
     *
     * @return array{search: string, company_id: int|null}
     */
    private function filters(Request $request): array
    {
        $companyId = $request->query('company_id');

        return [
            'search' => $request->string('search')->trim()->toString(),
            'company_id' => is_numeric($companyId) ? (int) $companyId : null,
        ];
    }

    private function filterProps(Request $request): array
    {
        $filters = $this->filters($request);

        return [
            'search' => $filters['search'],
            'company_id' => $filters['company_id'],
        ];
    }

    private function companies()
    {
        return Company::orderBy('name')->get(['id', 'name', 'code']);
    }
}
