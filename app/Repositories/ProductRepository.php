<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Contracts\ProductRepositoryContract;
use App\Helpers\TextNormalizer;
use App\Models\Product;
use Throwable;
use Log;

class ProductRepository implements ProductRepositoryContract
{
    public function __construct(private Product $model) {}
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

    public function upsert(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        try {
            collect($rows)
                ->chunk(500)
                ->each(
                    fn ($chunk) =>
                    $this->model->upsert(
                        $chunk->map(fn ($item) => [
                            'unique_key' => $item['unique_key'] ?? $item['UNIQUE_KEY'] ?? null,
                            'product_title' => $item['product_title'] ?? $item['PRODUCT_TITLE'] ?? null,
                            'product_description' => $item['product_description'] ?? $item['PRODUCT_DESCRIPTION'] ?? null,
                            'style_no' => $item['style_no'] ?? $item['STYLE#'] ?? null,
                            'sanmar_mainframe_color' => $item['sanmar_mainframe_color'] ?? $item['SANMAR_MAINFRAME_COLOR'] ?? null,
                            'size' => $item['size'] ?? $item['SIZE'] ?? null,
                            'color_name' => $item['color_name'] ?? $item['COLOR_NAME'] ?? null,
                            'piece_price' => (float)preg_replace('/[^\d.]/', '', $item['piece_price'] ?? $item['PIECE_PRICE'] ?? 0),
                        ])->toArray(),
                        uniqueBy: ['unique_key'],
                        update: [
                            'product_title',
                            'product_description',
                            'style_no',
                            'sanmar_mainframe_color',
                            'size',
                            'color_name',
                            'piece_price',
                        ]
                    )
                );
        } catch (Throwable $e) {
            Log::error('Upsert failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
