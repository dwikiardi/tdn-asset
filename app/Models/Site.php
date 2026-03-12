<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    // type constants
    const TYPE_WAREHOUSE     = 'warehouse';
    const TYPE_POP           = 'pop';
    const TYPE_HUB           = 'hub';
    const TYPE_CUSTOMER_SITE = 'customer_site';
    const TYPE_OFFICE        = 'office';

    protected $fillable = [
        'name', 'code', 'type', 'region_id',
        'address', 'pic_name', 'pic_phone', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function assetUnits()
    {
        return $this->hasMany(AssetUnit::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function transactionsFrom()
    {
        return $this->hasMany(Transaction::class, 'from_site_id');
    }

    public function transactionsTo()
    {
        return $this->hasMany(Transaction::class, 'to_site_id');
    }
}
