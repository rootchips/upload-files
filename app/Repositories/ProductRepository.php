<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Contracts\ProductRepositoryContract;
use App\Models\Product;

class ProductRepository implements ProductRepositoryContract
{
    public function __construct(private Product $model) {}

    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->query()
            ->when($search, function ($q, $search) {
                $q->where('unique_key', 'like', "%{$search}%")
                  ->orWhere('product_title', 'like', "%{$search}%")
                  ->orWhere('color_name', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function upsert(array $data): void
    {
        $this->model
            ->query()
            ->updateOrCreate(
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
