/**
 * Parse a date-only value (e.g. "2026-12-31" or "2026-12-31T00:00:00.000000Z"
 * where only the date part is meaningful) as a LOCAL date. `new Date(...)` on
 * these strings gives UTC midnight, which renders as the previous day in
 * timezones behind UTC.
 */
export function parseLocalDate(value) {
    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number)
    return new Date(year, month - 1, day)
}

export function formatLocalDate(value, options = undefined) {
    return parseLocalDate(value).toLocaleDateString(undefined, options)
}
