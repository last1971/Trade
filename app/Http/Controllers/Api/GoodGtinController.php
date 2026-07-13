<?php

namespace App\Http\Controllers\Api;

use App\GoodClassif;
use App\Http\Controllers\Controller;
use App\MarkCode;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodGtinController extends Controller
{
    /**
     * GOODS_CLASSIF rows of the good with linked mark codes count.
     *
     * @param int $goodscode
     * @return \Illuminate\Support\Collection
     */
    public function forGood($goodscode)
    {
        $rows = GoodClassif::query()
            ->where('GOODSCODE', intval($goodscode))
            ->orderByDesc('IS_PRIMARY')
            ->orderBy('GTIN')
            ->get();
        $gtins = $rows->pluck('GTIN')->filter();
        $counts = $gtins->isEmpty()
            ? collect()
            : MarkCode::query()
                ->whereIn('GTIN', $gtins)
                ->groupBy('GTIN')
                ->selectRaw('GTIN, COUNT(*) AS CNT')
                ->get()
                ->mapWithKeys(fn($row) => [trim($row->GTIN) => intval($row->CNT)]);
        return $rows->map(function (GoodClassif $row) use ($counts) {
            $row->mark_codes_count = $row->GTIN ? ($counts[trim($row->GTIN)] ?? 0) : 0;
            return $row;
        });
    }

    /**
     * Add GTIN through the existing GOODS_CLASSIF_ADD_GTIN procedure
     * (it validates that the GTIN is not bound to another good).
     *
     * @param Request $request
     * @param int $goodscode
     * @return GoodClassif
     */
    public function store(Request $request, $goodscode)
    {
        $request->validate([
            'GTIN' => 'required|digits_between:8,14',
            'TNVED' => 'nullable|string|max:10',
            'OKPD2' => 'nullable|string|max:12',
            'SUPPLIER_INN' => 'nullable|digits_between:10,12',
        ]);
        try {
            DB::connection('firebird')->statement(
                'EXECUTE PROCEDURE GOODS_CLASSIF_ADD_GTIN(?, ?, ?)',
                [intval($goodscode), $request->GTIN, $request->SUPPLIER_INN]
            );
        } catch (QueryException $e) {
            abort(422, $this->procedureMessage($e));
        }
        $row = GoodClassif::query()
            ->where('GOODSCODE', intval($goodscode))
            ->where('GTIN', $request->GTIN)
            ->firstOrFail();
        if ($request->filled('TNVED') || $request->filled('OKPD2')) {
            $row->update($request->only(['TNVED', 'OKPD2']));
        }
        return $row;
    }

    /**
     * @param Request $request
     * @param int $id
     * @return GoodClassif
     */
    public function update(Request $request, $id)
    {
        $row = GoodClassif::query()->findOrFail(intval($id));
        abort_if(
            $row->markCodesCount() > 0,
            422,
            'К GTIN привязаны коды Честного знака — изменение запрещено'
        );
        $request->validate([
            'GTIN' => 'required|digits_between:8,14',
            'TNVED' => 'nullable|string|max:10',
            'OKPD2' => 'nullable|string|max:12',
            'SUPPLIER_INN' => 'nullable|digits_between:10,12',
        ]);
        $duplicate = GoodClassif::query()
            ->where('GTIN', $request->GTIN)
            ->where('ID', '<>', $row->ID)
            ->exists();
        abort_if($duplicate, 422, 'Этот GTIN уже привязан к другому товару.');
        $row->update([
            'GTIN' => $request->GTIN,
            'TNVED' => $request->TNVED,
            'OKPD2' => $request->OKPD2,
            'SUPPLIER_INN' => $request->SUPPLIER_INN,
            'UPDATED_AT' => now(),
        ]);
        return $row;
    }

    /**
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function destroy($id)
    {
        $row = GoodClassif::query()->findOrFail(intval($id));
        abort_if(
            $row->markCodesCount() > 0,
            422,
            'К GTIN привязаны коды Честного знака — удаление запрещено'
        );
        return $row->delete();
    }

    /**
     * Extract the human message raised by the Firebird procedure.
     *
     * @param QueryException $e
     * @return string
     */
    private function procedureMessage(QueryException $e): string
    {
        if (preg_match('/ANY_EXCEPTION\s*-?\s*(.+?)\s*(?:At procedure|\(SQL:|$)/su', $e->getMessage(), $m)) {
            return trim($m[1]);
        }
        return 'Не удалось добавить GTIN';
    }
}
