<?php


namespace App\Services;


use App\Exceptions\ApiException;
use App\TransferOut;
use App\TransferOutLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Throwable;

class TransferOutService extends ModelService
{
    public function __construct()
    {
        parent::__construct(TransferOut::class);

        $this->aggregateAttributes = [
            'transferOutLinesSum' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesSum();
            }],
            'transferOutLinesCount' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesCount();
            }],
        ];

        $this->dateAttributes = ['DATA'];

        $this->aliases['buyer.SHORTNAME'] = function (Builder $query) {
            $query->join('POKUPAT as buyer', 'buyer.POKUPATCODE', '=', 'SF.POKUPATCODE');
        };
        $this->aliases['employee.FULLNAME'] = function (Builder $query) {
            $query->join('STAFF as employee', 'employee.ID', '=', 'SF.STAFF_ID');
        };
        $this->aliases['firm.FIRMNAME'] = function (Builder $query) {
            $query->join('FIRMS as firm', 'firm.FIRM_ID', '=', 'SF.FIRM_ID');
        };
    }

    public function index($request)
    {
        $this->addUserBuyers($request);
        $this->addUserFirms($request);
        return parent::index($request);
    }

    /**
     * @param Collection|FormRequest $request
     * @return string
     * @throws Throwable
     */
    public function xml($request)
    {
        $transferOut = is_object($request->get('transferOut'))
            ? $request->get('transferOut')
            : $this->query->with(['firm', 'buyer.advancedBuyer'])->find(intval($request->get('transferOut')));
        $fileId = 'ON_NSCHFDOPPR_' . ($transferOut->buyer->advancedBuyer->edo_id ?? $transferOut->buyer->Inn) .
            '_' . $transferOut->firm->EDOID . '_' . Carbon::now()->format('Ymd') . '-' . Str::uuid();
        $transferOutLines = TransferOutLine::with(['category', 'name', 'good'])
            ->where('SFCODE', '=', $transferOut->SFCODE)
            ->get();
        $cashFlows = $transferOut->invoice->cashFlows->filter(function ($v) {
            return !$v->SFCODE1;
        });
        $output = View::make('transfer-out-xml')
            ->with(compact('fileId', 'transferOut', 'transferOutLines', 'cashFlows'))
            ->render();
        return "<?xml version=\"1.0\" encoding=\"windows-1251\" ?> \n" . iconv("utf-8", "cp1251", $output);
    }

    public function create($request)
    {
        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();

        try {
            $res = $connection->select(
                'EXECUTE PROCEDURE CREATESF9 (?, ?, ?, ?, ?)',
                [null, $request['SCODE'], $request['STAFF_ID'], null, 0]
            );
            $ret = $this->index(collect(is_array($request) ? [] : $request->options))->find($res[0]->SFCODE);
            $connection->commit();
            return $ret;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new ApiException($e->getMessage(), 400);
        }
    }
}
