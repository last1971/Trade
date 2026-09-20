// Единственная копия правил MPN для справочника mpn.cc: нормализация ключа кэша
// и проверка годности строки прайса. Сервис (pricing-nest, модуль mpn) нормализует
// точно так же — toUpperCase + только латиница и цифры.

/** Минимум букв/цифр, иначе mpn.cc отвечает мусором (MIN_MPN_ALNUM в сервисе). */
export const MIN_MPN_ALNUM = 4;

/** Ключ карточки: то же, что normalized_mpn у mpn.cc. */
export function normalizeMpn(q) {
    return (q || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
}

/**
 * Годится ли строка прайса как MPN: латиница с разделителями и не меньше
 * MIN_MPN_ALNUM букв/цифр. Кириллицу и описания mpn.cc не понимает —
 * для таких строк кнопку справки не показываем и запрос не шлём.
 */
export function isMpnQueryable(q) {
    const value = (q || '').trim();
    if (!/^[A-Za-z0-9][A-Za-z0-9\-._/#+]*$/.test(value)) return false;
    return normalizeMpn(value).length >= MIN_MPN_ALNUM;
}

/** Ключ карточки в сторе: MPN плюс подсказка производителя, если она была. */
export function mpnCardKey(q, manufacturer) {
    return normalizeMpn(q) + (manufacturer ? ' : ' + manufacturer : '');
}
