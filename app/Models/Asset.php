<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Compatibility model for the old UI.
 * Points to the 'asset_types' table but allows the old controllers to function.
 */
class Asset extends Model
{
    use HasFactory;

    protected $table = 'asset_types';

    protected $fillable = [
        'name', 'brand', 'category_id', 'supplier_id', 'uid', 'specification',
        'production_year', 'purchase_date', 'purchase_price', 'condition', 'status', 'uom'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function image()
    {
        return $this->hasMany(AssetImages::class, 'asset_id');
    }

    public function units()
    {
        return $this->hasMany(AssetUnit::class, 'asset_type_id');
    }

    public function transaction_detail()
    {
        return $this->hasMany(TransactionDetail::class, 'asset_unit_id');
    }
}
