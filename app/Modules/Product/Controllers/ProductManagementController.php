<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Product\Actions\DeleteProductAction;
use App\Modules\Product\Actions\StoreProductAction;
use App\Modules\Product\Actions\UpdateProductAction;
use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Repositories\ProductRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman manajemen produk (Inertia).
 *
 * Sengaja dipisah dari `ProductController`, yang `index`-nya melayani endpoint
 * JSON `api/product` untuk form pemesanan — endpoint itu tidak boleh ikut
 * berubah bentuk responsnya.
 */
class ProductManagementController extends Controller
{
    /**
     * Aturan validasi yang dipakai bersama oleh `store` dan `update`.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request, ?Product $product = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                // `ignore` dipakai agar kode produk tidak bentrok dengan dirinya
                // sendiri saat diedit.
                Rule::unique('products', 'code')->ignore($product?->id),
            ],
            'unit' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'product_type_id' => ['nullable', 'integer', Rule::exists('product_type', 'id')],
            'product_sub_type_id' => ['nullable', 'integer', Rule::exists('product_sub_type', 'id')],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendor', 'id')],
        ];
    }

    public function index(Request $request, ProductRepository $repository): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Product/Index', [
            'products' => $repository->getPaginated(
                search: $search !== '' ? $search : null,
                perPage: 10,
                sortBy: $request->query('sort_by', 'id'),
                sortDir: $request->query('sort_dir', 'desc'),
            ),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(ProductRepository $repository): Response
    {
        return Inertia::render('Product/Create', [
            'productTypes' => $repository->getProductTypeOptions(),
            'productSubTypes' => $repository->getProductSubTypeOptions(),
            'vendors' => $repository->getVendorOptions(),
        ]);
    }

    public function store(
        Request $request,
        StoreProductAction $store_product,
    ): RedirectResponse {
        $request->validate($this->rules($request));

        $product = $store_product->execute(ProductDTO::fromRequest($request));

        return redirect()
            ->route('products.index')
            ->with('success', sprintf('Produk "%s" berhasil ditambahkan!', $product->name));
    }

    public function show(Product $product, ProductRepository $repository): Response
    {
        return Inertia::render('Product/Show', [
            'product' => $repository->find((int) $product->id),
        ]);
    }

    public function edit(Product $product, ProductRepository $repository): Response
    {
        return Inertia::render('Product/Edit', [
            'product' => $repository->find((int) $product->id),
            'productTypes' => $repository->getProductTypeOptions(),
            'productSubTypes' => $repository->getProductSubTypeOptions(),
            'vendors' => $repository->getVendorOptions(),
        ]);
    }

    public function update(
        Request $request,
        Product $product,
        UpdateProductAction $update_product,
    ): RedirectResponse {
        $request->validate($this->rules($request, $product));

        $product = $update_product->execute($product, ProductDTO::fromRequest($request));

        return redirect()
            ->route('products.index')
            ->with('success', sprintf('Produk "%s" berhasil diperbarui!', $product->name));
    }

    public function destroy(
        Product $product,
        DeleteProductAction $delete_product,
    ): RedirectResponse {
        $name = $product->name;

        $delete_product->execute($product);

        return redirect()
            ->route('products.index')
            ->with('success', sprintf('Produk "%s" berhasil dihapus!', $name));
    }
}
