// Mirrors App\Enums\Ordinance.
export const ORDINANCES = [
    { value: 'baptism_confirmation', label: 'Baptism & Confirmation' },
    { value: 'initiatory', label: 'Initiatory' },
    { value: 'endowment', label: 'Endowment' },
    { value: 'sealing', label: 'Sealing' },
]

export function ordinanceLabel(value) {
    return ORDINANCES.find((o) => o.value === value)?.label ?? value
}
