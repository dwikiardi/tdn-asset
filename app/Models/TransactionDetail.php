<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'asset_unit_id',
        'notes',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function assetUnit()
    {
        return $this->belongsTo(AssetUnit::class);
    }

    /** Compatibility for old UI **/
    public function asset() { return $this->belongsTo(Asset::class, 'asset_unit_id'); }
    public function getAssetIdAttribute() { return $this->asset_unit_id; }
    public function setAssetIdAttribute($value) { $this->attributes['asset_unit_id'] = $value; }
}
