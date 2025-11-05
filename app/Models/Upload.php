<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Upload extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
      'id',
      'file_name',
      'status',
      'processed_at'
];
}
