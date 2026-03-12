<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetImages extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_type_id',
        'name',
        'path',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }
}
