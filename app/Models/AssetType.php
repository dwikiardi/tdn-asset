<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'brand', 'model', 'uid',
        'category_id', 'supplier_id',
        'description', 'specifications',
        'production_year', 'purchase_price_default', 'warranty_months',
    ];

    protected $casts = [
        'specifications' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function units()
    {
        return $this->hasMany(AssetUnit::class);
    }

    public function images()
    {
        return $this->hasMany(AssetImages::class, 'asset_type_id');
    }

    // Helper: jumlah unit per status
    public function unitsByStatus(string $status)
    {
        return $this->units()->where('status', $status)->count();
    }
}
