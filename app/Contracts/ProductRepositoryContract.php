<?php
namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryContract
{
    public function upsert(array $data): void;
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator;
}