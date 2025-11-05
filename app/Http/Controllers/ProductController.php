<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contracts\ProductRepositoryContract;

class ProductController extends Controller
{
    public function __construct(private ProductRepositoryContract $products) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search  = $request->input('search');

        $result = $this->products->paginate($search, $perPage);

        return response()->json($result);
    }
}