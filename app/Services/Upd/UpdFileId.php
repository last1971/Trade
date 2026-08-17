<?php

namespace App\Services\Upd;

use App\Buyer;
use App\Firm;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Идентификатор файла УПД (он же имя файла) в формате ФНС:
 *   ON_NSCHFDOPPR_<получатель>_<отправитель>_<ГГГГММДД>-<GUID>
 * и хвост признаков 5.03, если он нужен:
 *   _<прослеживаемость>_<маркировка>_<алкоголь>_<табак>_<нефтепродукты>_<резерв>
 *
 * Единственное место в проекте, которое знает этот формат.
 */
class UpdFileId
{
    private const PREFIX = 'ON_NSCHFDOPPR_';

    /** Хвост признаков 5.03; подставляется признак маркированных товаров (2-я группа). */
    private const FLAGS_FORMAT = '_0_%d_0_0_0_00';

    /** Тот же хвост в чужом имени файла — меняем только 2-ю группу. */
    private const FLAGS_PATTERN = '/_(\d)_\d(_\d_\d_\d_\d{2})$/u';

    /**
     * @param bool|null $hasMarkCodes NULL — хвост признаков не добавляем.
     */
    public static function build(Buyer $buyer, Firm $firm, ?bool $hasMarkCodes = null): string
    {
        return self::PREFIX
            . $buyer->edoId()
            . '_' . $firm->EDOID
            . '_' . Carbon::now()->format('Ymd')
            . '-' . Str::uuid()
            . ($hasMarkCodes === null ? '' : sprintf(self::FLAGS_FORMAT, (int)$hasMarkCodes));
    }

    /**
     * Признак маркировки в готовом имени файла (УПД маркетплейса).
     * NULL — хвоста признаков в имени нет, выставлять нечего.
     */
    public static function withMarkFlag(string $fileId, bool $hasMarkCodes): ?string
    {
        $patched = preg_replace(
            self::FLAGS_PATTERN,
            '_$1_' . (int)$hasMarkCodes . '$2',
            $fileId,
            1,
            $count
        );

        return $count === 1 ? $patched : null;
    }
}
