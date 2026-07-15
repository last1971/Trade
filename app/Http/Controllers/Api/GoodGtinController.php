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
    /** Русские сообщения валидации (переводов validation.* в проекте нет). */
    private const MESSAGES = [
        'GTIN.required' => 'Укажите GTIN',
        'GTIN.digits_between' => 'GTIN — от 8 до 14 цифр',
        'TNVED.max' => 'ТНВЭД — не более 10 символов',
        'OKPD2.max' => 'ОКПД2 — не более 12 символов',
        'SUPPLIER_INN.digits_between' => 'ИНН поставщика — от 10 до 12 цифр',
        'MARK_REQUIRED.required' => 'Не указан вердикт маркировки',
        'MARK_REQUIRED.in' => 'Неверное значение вердикта',
        'PRIM.max' => 'Примечание — не более 250 символов',
    ];

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
            'PRIM' => 'nullable|string|max:250',
        ], self::MESSAGES);
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
        if ($request->filled('TNVED') || $request->filled('OKPD2') || $request->filled('PRIM')) {
            $row->update($request->only(['TNVED', 'OKPD2', 'PRIM']));
        }
        return $row;
    }

    /**
     * Вердикт классификации товара: подлежит/не подлежит маркировке + ТНВЭД.
     * Вердикт живёт на строке IS_PRIMARY=1 (одна на товар, GTIN может быть NULL);
     * «не подлежит» гасит MARK_REQUIRED на всех строках товара, потому что
     * «подлежит?» проверяется как EXISTS(строка с MARK_REQUIRED=1).
     *
     * @param Request $request
     * @param int $goodscode
     * @return \Illuminate\Support\Collection
     */
    public function classify(Request $request, $goodscode)
    {
        $request->validate([
            'MARK_REQUIRED' => 'required|in:0,1',
            'TNVED' => 'nullable|string|max:10',
            'OKPD2' => 'nullable|string|max:12',
            'PRIM' => 'nullable|string|max:250',
        ], self::MESSAGES);
        $goodscode = intval($goodscode);
        $markRequired = intval($request->MARK_REQUIRED);

        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();
        try {
            $primary = GoodClassif::query()
                ->where('GOODSCODE', $goodscode)
                ->where('IS_PRIMARY', 1)
                ->first();
            if ($primary) {
                $primary->update([
                    'MARK_REQUIRED' => $markRequired,
                    'TNVED' => $request->TNVED ?: $primary->TNVED,
                    'OKPD2' => $request->OKPD2 ?: $primary->OKPD2,
                    'PRIM' => $request->PRIM,
                    'UPDATED_AT' => now(),
                ]);
            } else {
                $primary = GoodClassif::query()->create([
                    'GOODSCODE' => $goodscode,
                    'IS_PRIMARY' => 1,
                    'MARK_REQUIRED' => $markRequired,
                    'TNVED' => $request->TNVED,
                    'OKPD2' => $request->OKPD2,
                    'PRIM' => $request->PRIM,
                    'UPDATED_AT' => now(),
                ]);
            }
            GoodClassif::query()
                ->where('GOODSCODE', $goodscode)
                ->where('ID', '<>', $primary->ID)
                ->where('IS_PRIMARY', 1)
                ->update(['IS_PRIMARY' => 0, 'UPDATED_AT' => now()]);
            if ($markRequired === 0) {
                GoodClassif::query()
                    ->where('GOODSCODE', $goodscode)
                    ->where('MARK_REQUIRED', 1)
                    ->update(['MARK_REQUIRED' => 0, 'UPDATED_AT' => now()]);
            }
            // Читаем до commit: при AUTOCOMMIT=0 после него нет открытой транзакции.
            $rows = $this->forGood($goodscode);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            abort(422, $e->getMessage());
        } finally {
            $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        }
        return $rows;
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
        // Строка-вердикт живёт без GTIN — для неё он не обязателен;
        // у обычной строки затирать GTIN пустым нельзя.
        $request->validate([
            'GTIN' => [$row->GTIN ? 'required' : 'nullable', 'digits_between:8,14'],
            'TNVED' => 'nullable|string|max:10',
            'OKPD2' => 'nullable|string|max:12',
            'SUPPLIER_INN' => 'nullable|digits_between:10,12',
            'PRIM' => 'nullable|string|max:250',
        ], self::MESSAGES);
        $duplicate = $request->filled('GTIN') && GoodClassif::query()
            ->where('GTIN', $request->GTIN)
            ->where('ID', '<>', $row->ID)
            ->exists();
        abort_if($duplicate, 422, 'Этот GTIN уже привязан к другому товару.');
        $row->update([
            'GTIN' => $request->GTIN ?: null,
            'TNVED' => $request->TNVED,
            'OKPD2' => $request->OKPD2,
            'SUPPLIER_INN' => $request->SUPPLIER_INN,
            'PRIM' => $request->PRIM,
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
