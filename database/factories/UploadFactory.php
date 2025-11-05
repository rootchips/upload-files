<?php
namespace Database\Factories;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'file_name' => $this->faker->lexify('file-????.csv'),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'processed_at' => now(),
        ];
    }
}