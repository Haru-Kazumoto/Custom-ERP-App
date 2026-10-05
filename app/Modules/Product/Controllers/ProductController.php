<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Queries\GetProductQuery;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct() {}

    /**
     * Katalog produk untuk form pemesanan.
     *
     * Query string yang dibaca:
     * - `search`    : cocokkan nama, kode, kategori, atau nama principal
     * - `limit`     : jumlah hasil (di-clamp di `GetProductQuery`)
     * - `category`  : batasi satu kategori
     * - `vendor_id` : batasi barang milik satu principal
     *
     * Tanpa `search` endpoint tetap mengembalikan halaman pertama katalog supaya
     * dropdown tidak kosong saat form Purchase Order baru dibuka.
     */
    public function index(Request $request, GetProductQuery $product)
    {
        $search = $request->query('search');
        $search = is_string($search) ? trim($search) : null;

        return response()->json($product->execute(
            $search !== '' ? $search : null,
            (int) $request->query('limit', 20),
            [
                'category' => $request->query('category'),
                'vendorId' => $request->integer('vendor_id') ?: null,
            ],
        ));
    }
}
