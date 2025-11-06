<?php
namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Contracts\ProductRepositoryContract;
use App\Helpers\TextNormalizer;
use App\Models\Product;

class ProductRepository implements ProductRepositoryContract
{
    public function __construct(private Product $model)
    {
    }

    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {

        return $this->model->newQuery()
            ->when($search, function ($q, $search) {
                $decoded = TextNormalizer::decode($search);
                $patterns = TextNormalizer::patterns($decoded);

                $q->where(function ($query) use ($patterns) {
                    collect($patterns)->each(function ($pattern) use ($query) {
                        $query->orWhere('title', 'like', "%{$pattern}%")
                          ->orWhere('color_name', 'like', "%{$pattern}%")
                          ->orWhere('unique_key', 'like', "%{$pattern}%")
                          ->orWhere('size', 'like', "%{$pattern}%");
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function upsert(array $data): void
    {
        $this->model->updateOrCreate(
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
