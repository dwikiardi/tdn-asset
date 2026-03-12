<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetUnit extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_NEW       = 'new';
    const STATUS_IN_STOCK  = 'in_stock';
    const STATUS_DEPLOYED  = 'deployed';
    const STATUS_FAULTY    = 'faulty';
    const STATUS_RMA       = 'rma';
    const STATUS_PULLED    = 'pulled';

    const ALL_STATUSES = [
        self::STATUS_NEW, self::STATUS_IN_STOCK, self::STATUS_DEPLOYED,
        self::STATUS_FAULTY, self::STATUS_RMA, self::STATUS_PULLED,
    ];

    protected $fillable = [
        'asset_type_id', 'serial_number', 'mac_address', 'mac_address_2',
        'status', 'ownership_status', 'site_id', 'customer_id',
        'purchase_date', 'purchase_price', 'warranty_expires_at',
        'condition_notes', 'last_seen_at', 'quantity',
    ];

    protected $casts = [
        'purchase_date'       => 'date',
        'warranty_expires_at' => 'date',
        'last_seen_at'        => 'datetime',
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function logs()
    {
        return $this->hasMany(AssetUnitLog::class)->orderBy('created_at', 'desc');
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function isDeployed(): bool  { return $this->status === self::STATUS_DEPLOYED; }
    public function isInStock(): bool   { return $this->status === self::STATUS_IN_STOCK; }
    public function isFaulty(): bool    { return $this->status === self::STATUS_FAULTY; }
}
