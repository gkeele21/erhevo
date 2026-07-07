<script setup>
import { ref, computed, inject, watch } from 'vue'
import axios from 'axios'
import StoryEditor from '@/Components/Story/StoryEditor.vue'
import TalkPicker from '@/Components/Lesson/TalkPicker.vue'
import QuotePicker from '@/Components/Lesson/QuotePicker.vue'
import ScripturePicker from '@/Components/Lesson/ScripturePicker.vue'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    typeMeta: {
        type: Object,
        default: () => ({})
    },
    scriptureBooks: {
        type: Array,
        default: () => []
    },
    highlight: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['remove'])

// Ensure config is always an object for the config-backed block types.
if (!props.item.config) {
    props.item.config = {}
}

// Effective max upload size (MB) per media type, provided by the builder.
const uploadLimits = inject('lessonUploadLimits', { video_mb: 20, image_mb: 10 })

// --- Media source (link vs uploaded file) for video/image blocks ---
const mediaSource = ref(props.item.config.source || (props.item.config.file_url ? 'upload' : 'url'))
const uploading = ref(false)
const uploadError = ref('')

// Per-type upload/delete endpoints and the form field each expects.
const mediaEndpoints = {
    video: { upload: 'lessons.video-upload', delete: 'lessons.video-delete', field: 'video' },
    image: { upload: 'lessons.image-upload', delete: 'lessons.image-delete', field: 'image' },
}

const setMediaSource = (source) => {
    mediaSource.value = source
    props.item.config.source = source
}

// Delete an uploaded file from storage (best effort — ignore failures).
const deleteMediaFile = async (type, path) => {
    if (!path || !mediaEndpoints[type]) return
    try {
        await axios.delete(route(mediaEndpoints[type].delete), { data: { path } })
    } catch (e) {
        // The orphaned file is harmless; don't block the UI on cleanup.
    }
}

const clearMediaFile = async () => {
    const path = props.item.config.file_path
    props.item.config.file_url = null
    props.item.config.file_path = null
    props.item.config.filename = null
    await deleteMediaFile(props.item.type, path)
}

const uploadMedia = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    const endpoint = mediaEndpoints[props.item.type]
    uploading.value = true
    uploadError.value = ''

    const formData = new FormData()
    formData.append(endpoint.field, file)
    const previousPath = props.item.config.file_path

    try {
        const { data } = await axios.post(route(endpoint.upload), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        props.item.config.source = 'upload'
        props.item.config.file_url = data.url
        props.item.config.file_path = data.path
        props.item.config.filename = data.filename

        // A replacement just superseded the old file — clean it up.
        if (previousPath && previousPath !== data.path) {
            await deleteMediaFile(props.item.type, previousPath)
        }
    } catch (e) {
        uploadError.value = e.response?.data?.message
            || 'Upload failed. The file may be too large or an unsupported format.'
    } finally {
        uploading.value = false
        event.target.value = ''
    }
}

// Removing the whole block should also clean up any uploaded file it owns.
const handleRemove = async () => {
    if (['video', 'image'].includes(props.item.type) && props.item.config.file_path) {
        await deleteMediaFile(props.item.type, props.item.config.file_path)
    }
    emit('remove')
}

// --- Collapse / expand to keep long blocks compact while reordering ---
// Stored on the item itself (transient, like _uid) so the state survives the
// item being dragged between lists/groups, where the card is re-created.
const collapsed = computed({
    get: () => props.item._collapsed ?? false,
    set: (value) => { props.item._collapsed = value },
})

// Respond to the builder's "Collapse all / Expand all" controls.
const collapseBus = inject('lessonCollapseBus', null)
if (collapseBus) {
    watch(() => collapseBus.tick, () => {
        collapsed.value = collapseBus.collapsed
    })
}

const stripHtml = (html) => (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()

const summary = computed(() => {
    const c = props.item.config || {}
    switch (props.item.type) {
        case 'scripture': return c.reference || 'Scripture reference'
        case 'talk': return c.title || 'Talk / quote'
        case 'quote': return c.source_title || c.author || stripHtml(props.item.content) || 'Quote'
        case 'video': return c.title || c.filename || c.url || 'Video / link'
        case 'image': return c.caption || c.filename || c.url || 'Image'
        case 'text': return stripHtml(props.item.content) || 'Empty'
        case 'question': return props.item.content || 'Question'
        default: return ''
    }
})
</script>

<template>
    <div
        class="rounded-lg border bg-white shadow-sm transition-all duration-700"
        :class="highlight ? 'border-amber-400 ring-2 ring-amber-400' : 'border-stone-200'"
    >
        <!-- Card header: drag handle, collapse toggle, type label, remove -->
        <div class="flex items-center justify-between border-b border-stone-100 bg-stone-50 px-3 py-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="lesson-drag-handle cursor-grab text-stone-400 hover:text-stone-600" title="Drag to reorder">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"/>
                    </svg>
                </span>
                <button
                    type="button"
                    @click="collapsed = !collapsed"
                    class="text-stone-400 hover:text-stone-600"
                    :title="collapsed ? 'Expand' : 'Collapse'"
                >
                    <svg class="h-4 w-4 transition-transform" :class="collapsed ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <span class="flex-shrink-0 text-sm font-medium text-stone-700">{{ typeMeta.label || item.type }}</span>
                <span v-if="collapsed" class="truncate text-sm text-stone-400">— {{ summary }}</span>
            </div>
            <button
                type="button"
                @click="handleRemove"
                class="ml-2 flex-shrink-0 text-stone-400 hover:text-red-600"
                title="Remove block"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div v-show="!collapsed" class="p-4">
            <!-- Scripture -->
            <div v-if="item.type === 'scripture'">
                <ScripturePicker
                    v-model="item.config"
                    :passage="item.content"
                    @update:passage="item.content = $event"
                    :scripture-books="scriptureBooks"
                />
            </div>

            <!-- Talk / Quote -->
            <div v-else-if="item.type === 'talk'" class="space-y-3">
                <TalkPicker v-model="item.config" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Quote or note (optional)</label>
                    <textarea
                        v-model="item.content"
                        rows="3"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Pull a quote from the talk, or add a note about how you'll use it..."
                    ></textarea>
                </div>
            </div>

            <!-- Quote (references a saved Quote post) -->
            <div v-else-if="item.type === 'quote'" class="space-y-3">
                <QuotePicker :item="item" />
                <div v-if="item.post_id">
                    <label class="mb-1 block text-sm font-medium text-stone-700">
                        Your copy of the quote — highlight or trim it for this lesson
                    </label>
                    <StoryEditor v-model="item.content" placeholder="The quote text..." />
                    <p class="mt-1 text-xs text-stone-400">
                        Edits and highlights here only affect this lesson; the saved quote stays unchanged.
                    </p>
                </div>
            </div>

            <!-- Video / Link -->
            <div v-else-if="item.type === 'video'" class="space-y-3">
                <!-- Source toggle: link vs uploaded file -->
                <div class="inline-flex rounded-lg border border-stone-200 p-0.5">
                    <button
                        type="button"
                        @click="setMediaSource('url')"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="mediaSource === 'url' ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                    >
                        Link
                    </button>
                    <button
                        type="button"
                        @click="setMediaSource('upload')"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="mediaSource === 'upload' ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                    >
                        Upload a file
                    </button>
                </div>

                <!-- Link mode -->
                <div v-if="mediaSource === 'url'">
                    <label class="mb-1 block text-sm font-medium text-stone-700">URL</label>
                    <input
                        v-model="item.config.url"
                        type="url"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="https://..."
                    >
                </div>

                <!-- Upload mode -->
                <div v-else>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Video file</label>
                    <div v-if="item.config.file_url" class="mb-2">
                        <video :src="item.config.file_url" controls class="max-h-48 w-full rounded-lg bg-black"></video>
                        <p class="mt-1 text-xs text-stone-500">
                            {{ item.config.filename }}
                            <button type="button" @click="clearMediaFile" class="ml-2 text-red-600 hover:underline">Remove</button>
                        </p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-50">
                        <span>{{ uploading ? 'Uploading...' : (item.config.file_url ? 'Replace file' : 'Choose a video file') }}</span>
                        <input type="file" accept="video/mp4,video/webm,video/ogg,video/quicktime" class="hidden" :disabled="uploading" @change="uploadMedia">
                    </label>
                    <p class="mt-1 text-xs text-stone-400">MP4, WebM, OGG or MOV, up to {{ uploadLimits.video_mb }}MB.</p>
                    <p v-if="uploadError" class="mt-1 text-xs text-red-600">{{ uploadError }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Title (optional)</label>
                    <input
                        v-model="item.config.title"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="What is this video/link?"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Duration (optional)</label>
                    <input
                        v-model="item.config.duration"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="e.g. 5:30"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Note (optional)</label>
                    <textarea
                        v-model="item.config.note"
                        rows="2"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="e.g. Show from 2:30 to 4:00"
                    ></textarea>
                </div>
            </div>

            <!-- Image -->
            <div v-else-if="item.type === 'image'" class="space-y-3">
                <!-- Source toggle: link vs uploaded file -->
                <div class="inline-flex rounded-lg border border-stone-200 p-0.5">
                    <button
                        type="button"
                        @click="setMediaSource('url')"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="mediaSource === 'url' ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                    >
                        Link
                    </button>
                    <button
                        type="button"
                        @click="setMediaSource('upload')"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="mediaSource === 'upload' ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                    >
                        Upload a file
                    </button>
                </div>

                <!-- Link mode -->
                <div v-if="mediaSource === 'url'">
                    <label class="mb-1 block text-sm font-medium text-stone-700">Image URL</label>
                    <input
                        v-model="item.config.url"
                        type="url"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="https://..."
                    >
                    <img v-if="item.config.url" :src="item.config.url" alt="" class="mt-2 max-h-48 rounded-lg">
                </div>

                <!-- Upload mode -->
                <div v-else>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Image file</label>
                    <div v-if="item.config.file_url" class="mb-2">
                        <img :src="item.config.file_url" :alt="item.config.caption || ''" class="max-h-48 rounded-lg">
                        <p class="mt-1 text-xs text-stone-500">
                            {{ item.config.filename }}
                            <button type="button" @click="clearMediaFile" class="ml-2 text-red-600 hover:underline">Remove</button>
                        </p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-50">
                        <span>{{ uploading ? 'Uploading...' : (item.config.file_url ? 'Replace image' : 'Choose an image') }}</span>
                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" :disabled="uploading" @change="uploadMedia">
                    </label>
                    <p class="mt-1 text-xs text-stone-400">JPEG, PNG, GIF or WebP, up to {{ uploadLimits.image_mb }}MB.</p>
                    <p v-if="uploadError" class="mt-1 text-xs text-red-600">{{ uploadError }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Caption (optional)</label>
                    <input
                        v-model="item.config.caption"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Describe the image..."
                    >
                </div>
            </div>

            <!-- My Words (rich text) -->
            <div v-else-if="item.type === 'text'">
                <StoryEditor v-model="item.content" placeholder="Write your own words..." />
            </div>

            <!-- Question -->
            <div v-else-if="item.type === 'question'">
                <label class="mb-1 block text-sm font-medium text-stone-700">Question to ask</label>
                <textarea
                    v-model="item.content"
                    rows="2"
                    class="w-full rounded-lg border-stone-300 bg-amber-50/40 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="e.g. How has this principle blessed your life?"
                ></textarea>
            </div>
        </div>
    </div>
</template>
