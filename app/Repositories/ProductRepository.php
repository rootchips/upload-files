<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Contracts\ProductRepositoryContract;
use App\Models\Product;

class ProductRepository implements ProductRepositoryContract
{
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        $q = Product::query();

        if ($search) {
            $q->where(function ($s) use ($search) {
                $s->where('unique_key', 'like', "%{$search}%")
                  ->orWhere('product_title', 'like', "%{$search}%")
                  ->orWhere('color_name', 'like', "%{$search}%");
            });
        }

        return $q->orderByDesc('id')->paginate($perPage);
    }

    public function upsert(array $data): void
    {
        Product::updateOrCreate(
            ['unique_key' => $data['UNIQUE_KEY']],
            [
                'product_title' => $data['PRODUCT_TITLE'],
                'product_description' => $data['PRODUCT_DESCRIPTION'],
                'style_no' => $data['STYLE#'],
                'sanmar_mainframe_color' => $data['SANMAR_MAINFRAME_COLOR'],
                'size' => $data['SIZE'],
                'color_name' => $data['COLOR_NAME'],
                'piece_price' => (float) preg_replace('/[^\d.]/', '', $data['PIECE_PRICE']),
            ]
        );
    }
}
