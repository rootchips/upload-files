<?php
namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryContract
{
    public function upsert(Collection $data): void;
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator;
    public function clearAll(): void;
}