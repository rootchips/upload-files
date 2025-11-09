<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use App\Contracts\ProductRepositoryContract;

class ProductController extends Controller
{
    public function __construct(private ProductRepositoryContract $products) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search  = $request->input('search');

        $result = $this->products->paginate($search, $perPage);

        return ProductResource::collection($result);
    }

    public function clearAll(): JsonResponse
    {
        $this->products->clearAll();

        return response()->json([
            'message' => 'All products have been deleted.'
       ]);
    }
}
