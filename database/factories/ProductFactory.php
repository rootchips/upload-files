<?php
namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'unique_key' => $this->faker->unique()->ean8(),
            'product_title' => $this->faker->words(3, true),
            'product_description' => $this->faker->sentence(),
            'style_no' => strtoupper($this->faker->bothify('ST###')),
            'sanmar_mainframe_color' => $this->faker->safeColorName(),
            'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
            'color_name' => ucfirst($this->faker->colorName()),
            'piece_price' => $this->faker->randomFloat(2, 5, 50),
        ];
    }
}