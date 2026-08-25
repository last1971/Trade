<?php

namespace App;

use App\Interfaces\IMarkCodeDocument;
use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model implements IMarkCodeDocument
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = [
        'NS',
        'NZ',
        'FIRM_ID',
        'FIRMS_HISTORY_ID',
        'POKUPATCODE',
        'PRIM',
        'STATUS',
        'IGK',
        'DATA',
        'STAFF_ID'
    ];

    protected $primaryKey = 'SCODE';

    protected $sequenceName = 'SCODE_GEN';

    protected $table = 'S';

    protected $casts = [
        'SCODE' => 'integer',
        'NS' => 'integer',
    ];

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function cashFlows()
    {
        return $this->hasMany('App\CashFlow', 'SCODE', 'SCODE');
    }

    public function deposits()
    {
        return $this->hasMany('App\Deposit', 'SCODE', 'SCODE');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'STAFF_ID', 'ID');
    }

    public function firm()
    {
        return $this->belongsTo('App\Firm', 'FIRM_ID', 'FIRM_ID');
    }

    public function firmHistory()
    {
        return $this->belongsTo('App\FirmHistory', 'FIRMS_HISTORY_ID', 'ID');
    }

    public function invoiceLines()
    {
        return $this->hasMany('App\InvoiceLine', 'SCODE', 'SCODE');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'SCODE', 'SCODE');
    }

    public function transferOutLines()
    {
        return $this->hasManyThrough(
            'App\TransferOutLine',
            'App\InvoiceLine',
            'SCODE',
            'REALPRICECODE',
            'SCODE',
            'REALPRICECODE'
        );
    }

    public function pickUps()
    {
        return $this->hasMany('App\PickUp', 'SCODE', 'SCODE');
    }

    /**
     * Все коды маркировки счёта, включая уже переданные и выведенные из оборота
     * (в отличие от InvoiceLine::markCodes, где только непереданные).
     */
    public function markCodes()
    {
        return $this->hasManyThrough(
            'App\MarkCode',
            'App\InvoiceLine',
            'SCODE',
            'REALPRICECODE',
            'SCODE',
            'REALPRICECODE'
        );
    }

    public function markCodeDocumentTitle(): string
    {
        return "счёт № {$this->NS}";
    }

    /** Со счёта коды уходят маркетплейсу по УПД-2 (FBO). */
    public function markCodeTransferType(): int
    {
        return 2;
    }

    /** Передача маркетплейсу: та же причина, что ставит авто-отправка УПД-2 в ЭДО. */
    public function markCodeRetireReason(): int
    {
        return 3;
    }
}
