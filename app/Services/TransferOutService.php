<?php


namespace App\Services;


use App\TransferOut;
use App\TransferOutLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
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
        $director = $request->get('director');
        $output = View::make('transfer-out-xml')
            ->with(compact('fileId', 'transferOut', 'transferOutLines', 'director'))
            ->render();
        return "<?xml version=\"1.0\" encoding=\"windows-1251\" ?> \n" . iconv("utf-8", "cp1251", $output);
    }
}
