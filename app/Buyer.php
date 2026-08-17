<?php

namespace App;

use App\ModelTraits\InnKppTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static find($buyerId)
 */
class Buyer extends Model
{
    use InnKppTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'POKUPATCODE';

    protected $table = 'POKUPAT';

    public function advancedBuyer()
    {
        return $this->hasOne('App\AdvancedBuyer', 'buyer_id', 'POKUPATCODE')
            ->withDefault();
    }

    /**
     * Идентификатор стороны в ЭДО: свой, если заведён, иначе ИНН.
     */
    public function edoId(): string
    {
        return (string)($this->advancedBuyer->edo_id ?? $this->Inn);
    }

    /**
     * Участник оборота ЧЗ: коды маркировки передаём ему в УПД.
     * Иначе коды в УПД не попадают — из оборота их выводят не здесь.
     */
    public function transfersMarkCodes(): bool
    {
        return (int)$this->CHZ_MEMBER === 1;
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function retailOrders()
    {
        return $this->hasMany('App\RetailOrder', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'POKUPATCODE', 'POKUPATCODE');
    }
}
