/**
 * Get VAT rate based on date
 * @param {string|Date} date - The date to check VAT rate for
 * @returns {number} VAT rate (18, 20, or 22)
 */
export default function getVAT(date = null) {
    const checkDate = date ? new Date(date) : new Date();

    if (checkDate < new Date('2019-01-01')) return 18;
    if (checkDate < new Date('2026-01-01')) return 20;
    return 22;
}
