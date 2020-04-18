<?php


namespace App\Services;


use App\Invoice;
use Illuminate\Database\Eloquent\Builder;

class InvoiceService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Invoice::class);
        $this->aggregateAttributes = [
            'cashFlowsSum' => ['cashFlows' => function (Builder $query) {
                $query->cashFlowsSum();
            }],
            'invoiceLinesCount' => ['invoiceLines' => function (Builder $query) {
                $query->invoiceLinesCount();
            }],
            'invoiceLinesSum' => ['invoiceLines' => function (Builder $query) {
                $query->invoiceLinesSum();
            }],
            'transferOutLinesSum' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesSum();
            }],
        ];
        $this->dateAttributes = ['DATA'];
        $this->aliases['buyer.SHORTNAME'] = function (Builder $query) {
            $query->join('POKUPAT as buyer', 'buyer.POKUPATCODE', '=', 'S.POKUPATCODE');
        };
        $this->aliases['employee.FULLNAME'] = function (Builder $query) {
            $query->join('STAFF as employee', 'employee.ID', '=', 'S.STAFF_ID');
        };
        $this->aliases['firm.FIRMNAME'] = function (Builder $query) {
            $query->join('FIRMS as firm', 'firm.FIRM_ID', '=', 'S.FIRM_ID');
        };
    }

    public function index($request)
    {
        $query = parent::index($request);
        $this->checkUserBuyers($query);
        $this->checkUserFirms($query);
        return $query;
    }
}
