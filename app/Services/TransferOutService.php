<?php


namespace App\Services;


use App\Exceptions\ApiException;
use App\Services\Upd\Sources\TransferOutUpdSource;
use App\Services\Upd\UpdXmlBuilder;
use App\TransferOut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $source = new TransferOutUpdSource(
            $transferOut,
            $request->get('basis'),
            $request->get('basisNumber'),
            $request->get('basisDate'),
            json_decode($request->get('advanceInvoices'), true) ?? []
        );

        return app(UpdXmlBuilder::class)->build($source);
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
