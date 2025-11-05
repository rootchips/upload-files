<?php

namespace Tests\Feature;

use App\Contracts\ProductRepositoryContract;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_paginated_products_with_defaults()
    {
        $mock = \Mockery::mock(ProductRepositoryContract::class);

        $items = collect([
            [
                'id' => 1,
                'unique_key' => 165313,
                'product_title' => 'Shirt',
                'product_description' => 'Desc',
                'style_no' => 'ST1',
                'sanmar_mainframe_color' => 'Blue',
                'size' => 'M',
                'color_name' => 'Blue',
                'piece_price' => 10.5,
            ],
            [
                'id' => 2,
                'unique_key' => 165314,
                'product_title' => 'Hat',
                'product_description' => 'Desc',
                'style_no' => 'ST2',
                'sanmar_mainframe_color' => 'Red',
                'size' => 'L',
                'color_name' => 'Red',
                'piece_price' => 5.0,
            ],
        ]);

        $perPage = 10;
        $currentPage = 1;
        $total = 25;

        $paginator = new Paginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => url('/api/products')]
        );

        $mock->shouldReceive('paginate')
            ->once()
            ->with(null, $perPage)
            ->andReturn($paginator);

        $this->app->instance(ProductRepositoryContract::class, $mock);

        $res = $this->getJson('/api/products');

        $res->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    ['id','unique_key','product_title','product_description','style_no','sanmar_mainframe_color','size','color_name','piece_price'],
                ],
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ])
            ->assertJsonFragment(['unique_key' => 165313])
            ->assertJsonPath('per_page', $perPage)
            ->assertJsonPath('current_page', $currentPage)
            ->assertJsonPath('total', $total);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_respects_search_and_custom_per_page()
    {
        $mock = \Mockery::mock(ProductRepositoryContract::class);

        $items = collect([
            [
                'id' => 10,
                'unique_key' => 165313,
                'product_title' => 'Hat Classic',
                'product_description' => 'Desc',
                'style_no' => 'H1',
                'sanmar_mainframe_color' => 'Black',
                'size' => 'M',
                'color_name' => 'Black',
                'piece_price' => 7.0,
            ],
        ]);

        $search = 'hat';
        $perPage = 5;
        $currentPage = 1;
        $total = 11;

        $paginator = new Paginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => url('/api/products')]
        );

        $mock->shouldReceive('paginate')
            ->once()
            ->with($search, $perPage)
            ->andReturn($paginator);

        $this->app->instance(ProductRepositoryContract::class, $mock);

        $res = $this->getJson("/api/products?per_page={$perPage}&search={$search}");

        $res->assertOk()
            ->assertJsonPath('per_page', $perPage)
            ->assertJsonPath('total', $total)
            ->assertJsonFragment(['unique_key' => 165313])
            ->assertJsonCount(1, 'data');
    }
}
