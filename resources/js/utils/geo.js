/** Great-circle distance in miles between two lat/lng points. */
export function haversineMiles(lat1, lng1, lat2, lng2) {
    const toRad = (deg) => (deg * Math.PI) / 180
    const R = 3958.7613 // mean Earth radius, miles
    const dLat = toRad(lat2 - lat1)
    const dLng = toRad(lng2 - lng1)
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2
    return 2 * R * Math.asin(Math.sqrt(a))
}
