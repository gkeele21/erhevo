<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
    // Each temple needs id, slug, name, latitude, longitude, visited.
    temples: { type: Array, default: () => [] },
    // Re-fit the viewport to the markers whenever the list changes.
    fitOnChange: { type: Boolean, default: true },
    // Radius-explorer mode: draw a circle and report map clicks.
    circleCenter: { type: Object, default: null }, // { lat, lng }
    radiusMiles: { type: Number, default: null },
    clickToSetCenter: { type: Boolean, default: false },
    // Label for the popup button on `nearby` temples; omit for no button.
    nearbyActionLabel: { type: String, default: '' },
    heightClass: { type: String, default: 'h-[32rem]' },
})

const emit = defineEmits(['map-click', 'temple-action'])

const container = ref(null)
let map = null
let markerLayer = null
let circle = null

// Brand-colored divIcons instead of Leaflet's PNG markers: no Vite asset
// path issues, and the fill doubles as the legend. Three states: visited /
// planned (the main pins) and `nearby` — smaller gold dots for suggestions
// that aren't part of the list yet.
const icon = (temple) => {
    if (temple.nearby) {
        return L.divIcon({
            className: '',
            html: '<div class="w-3 h-3 rounded-full border-2 bg-gold-400 border-gold-700 shadow"></div>',
            iconSize: [12, 12],
            iconAnchor: [6, 6],
        })
    }

    // Fill carries the visited state; a gold ring marks a favorite, so the
    // two read independently.
    const fill = temple.visited ? 'bg-teal-500' : 'bg-white'
    const border = temple.favorite
        ? 'border-gold-600'
        : (temple.visited ? 'border-teal-700' : 'border-navy-600')

    return L.divIcon({
        className: '',
        html: `<div class="w-4 h-4 rounded-full border-2 ${fill} ${border} shadow"></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8],
    })
}

const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])

// A DOM node rather than an HTML string so the action button can carry a
// real click handler back to the parent.
const popupContent = (temple) => {
    const location = [temple.city, temple.state, temple.country].filter(Boolean).join(', ')
    const el = document.createElement('div')

    el.innerHTML =
        `<a href="/temples/${escapeHtml(temple.slug)}" class="font-semibold">${escapeHtml(temple.name)}</a>` +
        `<br><span class="text-xs">${escapeHtml(location)}</span>` +
        (temple.visited ? '<br><span class="text-xs">✓ Visited</span>' : '') +
        (temple.favorite ? '<br><span class="text-xs">★ Favorite</span>' : '')

    if (temple.nearby && props.nearbyActionLabel) {
        const button = document.createElement('button')
        button.type = 'button'
        button.className = 'mt-2 block text-xs font-semibold text-teal-700 hover:text-teal-900'
        button.textContent = props.nearbyActionLabel
        button.addEventListener('click', () => {
            map.closePopup()
            emit('temple-action', temple)
        })
        el.appendChild(button)
    }

    return el
}

const renderMarkers = () => {
    markerLayer.clearLayers()
    const placeable = props.temples.filter((t) => t.latitude != null && t.longitude != null)

    for (const temple of placeable) {
        L.marker([temple.latitude, temple.longitude], { icon: icon(temple) })
            .bindPopup(() => popupContent(temple))
            .addTo(markerLayer)
    }

    // Nearby suggestions shouldn't pull the viewport away from the temples
    // the page is actually about.
    const framed = placeable.filter((t) => !t.nearby)
    const fitTo = framed.length ? framed : placeable

    if (props.fitOnChange && fitTo.length) {
        map.fitBounds(L.latLngBounds(fitTo.map((t) => [t.latitude, t.longitude])), {
            padding: [30, 30],
            maxZoom: 11,
        })
    }
}

const renderCircle = () => {
    if (circle) {
        circle.remove()
        circle = null
    }

    if (props.circleCenter && props.radiusMiles) {
        circle = L.circle([props.circleCenter.lat, props.circleCenter.lng], {
            radius: props.radiusMiles * 1609.344,
            color: '#136F74',
            fillColor: '#136F74',
            fillOpacity: 0.08,
            weight: 2,
        }).addTo(map)

        map.fitBounds(circle.getBounds(), { padding: [20, 20] })
    }
}

onMounted(() => {
    map = L.map(container.value, { scrollWheelZoom: true }).setView([20, 0], 2)

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map)

    markerLayer = L.layerGroup().addTo(map)

    if (props.clickToSetCenter) {
        map.on('click', (e) => emit('map-click', { lat: e.latlng.lat, lng: e.latlng.lng }))
    }

    renderMarkers()
    renderCircle()
})

onBeforeUnmount(() => {
    map?.remove()
    map = null
})

watch(() => props.temples, () => map && renderMarkers(), { deep: true })
watch([() => props.circleCenter, () => props.radiusMiles], () => map && renderCircle())
</script>

<template>
    <div ref="container" class="w-full rounded-lg border border-stone-200 z-0" :class="heightClass" />
</template>
