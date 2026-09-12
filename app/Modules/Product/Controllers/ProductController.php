<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Queries\GetProductQuery;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {}

    public function index(Request $request, GetProductQuery $product)
    {
        return response()->json($product->execute($request->search));
    }
}
