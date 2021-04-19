<?php


namespace App\Services;


use App\TransferOutLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TransferOutLineService extends ModelService
{
    protected $countries = [
        'АВСТРАЛИЯ',
        'АВСТРИЯ',
        'БЕЛЬГИЯ',
        'БОЛГАРИЯ',
        'ВЕЛИКОБРИТАНИЯ',
        'СОЕДИНЕННОЕ КОРОЛЕВСТВО',
        'ВЕНГРИЯ',
        'ГЕРМАНИЯ',
        'ГРЕЦИЯ',
        'ДАНИЯ',
        'ИРЛАНДИЯ',
        'ИСПАНИЯ',
        'ИТАЛИЯ',
        'КАНАДА',
        'КИПР',
        'ЛАТВИЯ',
        'ЛИТВА',
        'ЛЮКСЕМБУРГ',
        'МАЛЬТА',
        'НИДЕРЛАНДЫ',
        'ПОЛЬША',
        'ПОРТУГАЛИЯ',
        'РУМЫНИЯ',
        'СЛОВАКИЯ',
        'СЛОВЕНИЯ',
        'США',
        'СОЕДИЕННЫЕ ШТАТЫ',
        'СОЕДИНЕННЫЕ ШТАТЫ',
        'ФИНЛЯНДИЯ',
        'ФРАНЦИЯ',
        'ХОРВАТИЯ',
        'ЧЕХИЯ',
        'ШВЕЦИЯ',
        'ЭСТОНИЯ',
        'ЯПОНИЯ'
    ];

    public function __construct()
    {
        parent::__construct(TransferOutLine::class);

        $this->query->join('GOODS as good', 'good.GOODSCODE', '=', 'REALPRICEF.GOODSCODE');

        $this->aliases['category.CATEGORY'] = function (Builder $query) {
            $query
                ->join(
                    'CATEGORY as category', 'category.CATEGORYCODE', '=', 'good.CATEGORYCODE'
                );
        };
        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };
        $this->aliases['transferOut.POKUPATCODE'] = function (Builder $query) {
            $query
                ->join('SF as transferOut', 'transferOut.SFCODE', '=', 'REALPRICEF.SFCODE');
        };
    }

    public function clearGtd($request)
    {
        $lines = $this->index(collect([
            'with' => ['transferOut'],
            'filterAttributes' => ['transferOut.POKUPATCODE', 'transferOut.DATA'],
            'filterOperators' => ['=', 'BETWEENDATE'],
            'filterValues' => [$request->buyerId, [$request->date, Carbon::create($request->date)->addDay()]],
        ]))->get();
        $chinaLine = $lines->first(function ($value) {
                return Str::upper($value->STRANA) === 'КИТАЙ';
            }) ?? TransferOutLine::query()
                ->where('STRANA', '=', 'КИТАЙ')
                ->orderBy('REALPRICEFCODE', 'desc')
                ->first();
        foreach ($lines as $line) {
            if (Str::upper($line->STRANA) === 'КИТАЙ') {
                $chinaLine = $line;
            } else if (array_search(Str::upper($line->STRANA), $this->countries) !== FALSE) {
                $line->fill(['STRANA' => 'КИТАЙ', 'GTD' => $chinaLine->GTD])->save();
            }
        }
    }

    public function index($request)
    {
        $this->addUserBuyers($request, 'transferOut');
        $this->addUserFirms($request, 'transferOut');
        return parent::index($request);
    }
}
