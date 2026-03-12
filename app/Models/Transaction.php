<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    const TYPE_STOCK_IN   = 'stock_in';
    const TYPE_STOCK_OUT  = 'stock_out';
    const TYPE_TRANSFER   = 'transfer';
    const TYPE_DEPLOYMENT = 'deployment';
    const TYPE_RETRIEVAL  = 'retrieval';
    const TYPE_RMA_OUT    = 'rma_out';
    const TYPE_RMA_IN     = 'rma_in';

    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transaction_number', 'type', 'contract_type',
        'contract_start_date', 'contract_end_date',
        'from_site_id', 'to_site_id', 'customer_id',
        'tridatu_user_id', 'tridatu_user_name',
        'created_by', 'status',
        'transaction_date', 'notes',
    ];

    protected $casts = [
        'transaction_date'    => 'date',
        'contract_start_date' => 'date',
        'contract_end_date'   => 'date',
    ];

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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /** Compatibility for old UI **/
    public function employee() { return $this->belongsTo(User::class, 'created_by'); }
    public function division() { return $this->belongsTo(Site::class, 'to_site_id'); }
    public function detail() { return $this->hasMany(TransactionDetail::class); }

    public function assetUnitLogs()
    {
        return $this->hasMany(AssetUnitLog::class);
    }

    // Auto-generate transaction number
    public static function generateNumber(string $type): string
    {
        $prefix = [
            self::TYPE_STOCK_IN   => 'IN',
            self::TYPE_STOCK_OUT  => 'OUT',
            self::TYPE_TRANSFER   => 'TRF',
            self::TYPE_DEPLOYMENT => 'DEP',
            self::TYPE_RETRIEVAL  => 'RTV',
            self::TYPE_RMA_OUT    => 'RMA',
            self::TYPE_RMA_IN     => 'RMI',
        ][$type] ?? 'TRX';

        $date  = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
