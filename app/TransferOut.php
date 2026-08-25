<?php

namespace App;

use App\Interfaces\IMarkCodeDocument;
use Illuminate\Database\Eloquent\Model;

class TransferOut extends Model implements IMarkCodeDocument
{
    //
    public $timestamps = false;

    protected $fillable = ['PRIM'];

    protected $connection = 'firebird';

    protected $primaryKey = 'SFCODE';

    protected $table = 'SF';

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'POKUPATCODE', 'POKUPATCODE');
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

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'SFCODE', 'SFCODE');
    }

    /**
     * Все коды маркировки документа, включая уже переданные и выведенные из оборота
     * (в отличие от TransferOutLine::markCodes, где только непереданные).
     */
    public function markCodes()
    {
        return $this->hasManyThrough(
            'App\MarkCode',
            'App\TransferOutLine',
            'SFCODE',
            'REALPRICEFCODE',
            'SFCODE',
            'REALPRICEFCODE'
        );
    }

    public function markCodeDocumentTitle(): string
    {
        return "УПД № {$this->NSF}";
    }

    /** С УПД коды уходят юрлицу. */
    public function markCodeTransferType(): int
    {
        return 1;
    }

    /** Передача B2B: в ГИС МТ вывода нет, владение переходит по самой УПД. */
    public function markCodeRetireReason(): int
    {
        return 3;
    }

    public function markCodeTransferBlockReason(): ?string
    {
        return null;
    }
}
