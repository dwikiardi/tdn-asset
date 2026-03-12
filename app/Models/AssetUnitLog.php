<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetUnitLog extends Model
{
    use HasFactory;

    // Action constants
    const ACTION_RECEIVED     = 'received';
    const ACTION_MOVED        = 'moved';
    const ACTION_DEPLOYED     = 'deployed';
    const ACTION_RETRIEVED    = 'retrieved';
    const ACTION_FAULTY_NOTED = 'faulty_noted';
    const ACTION_SENT_RMA     = 'sent_rma';
    const ACTION_RMA_RETURNED = 'rma_returned';
    const ACTION_PULLED       = 'pulled';
    const ACTION_CHECKED      = 'checked';

    protected $fillable = [
        'asset_unit_id', 'action',
        'from_status', 'to_status',
        'from_site_id', 'to_site_id',
        'customer_id',
        'tridatu_user_id', 'tridatu_user_name',
        'performed_by', 'transaction_id',
        'notes',
    ];

    public function assetUnit()
    {
        return $this->belongsTo(AssetUnit::class);
    }

    public function fromSite()
    {
        return $this->belongsTo(Site::class, 'from_site_id');
    }

    public function toSite()
    {
        return $this->belongsTo(Site::class, 'to_site_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
