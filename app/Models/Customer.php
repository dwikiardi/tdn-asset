<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'phone', 'email', 'address', 'site_id',
        'external_id', 'external_source', 'external_metadata',
        'is_active', 'notes', 'synced_at',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'external_metadata' => 'array',
        'synced_at'         => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function assetUnits()
    {
        return $this->hasMany(AssetUnit::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
