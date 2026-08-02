<script setup>
import { ref, computed, inject, watch } from 'vue'
import axios from 'axios'
import StoryEditor from '@/Components/Story/StoryEditor.vue'
import TagInput from '@/Components/Story/TagInput.vue'
import TalkPicker from '@/Components/Lesson/TalkPicker.vue'
import QuotePicker from '@/Components/Lesson/QuotePicker.vue'
import PostPicker from '@/Components/Lesson/PostPicker.vue'
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

// Emphasis flag: 'key' (make sure to cover) | 'optional' (if time) | none.
// Lives in config so it persists through the normal item save path.
const toggleEmphasis = (value) => {
    props.item.config = {
        ...(props.item.config || {}),
        emphasis: props.item.config?.emphasis === value ? null : value
    }
}

// --- Save a "My Words" block as a standalone Post ---
const showSaveAsPost = ref(false)
const savingPost = ref(false)
const savePostError = ref('')
const postForm = ref({ title: '', post_type: 'thought', visibility: 'private', tags: [] })

const hasTextContent = computed(() =>
    (props.item.content || '').replace(/<[^>]*>/g, ' ').trim().length > 0
)

// --- Image block ↔ image posts ---
const savingImagePost = ref(false)
const imagePostError = ref('')
const imageSearchOpen = ref(false)
const imageQuery = ref('')
const imageResults = ref([])
const imageSearching = ref(false)
const imageSearched = ref(false)
let imageDebounce = null

const blockHasImage = computed(() =>
    mediaSource.value === 'url' ? !!props.item.config.url : !!props.item.config.file_url
)

const saveImageAsPost = async () => {
    savingImagePost.value = true
    imagePostError.value = ''
    try {
        const { data } = await axios.post(route('lessons.save-post'), {
            post_type: 'image',
            title: props.item.config.caption || props.item.config.filename || null,
            content: props.item.config.caption || null,
            cover_image: mediaSource.value === 'url' ? props.item.config.url : null,
            image_path: mediaSource.value === 'upload' ? props.item.config.file_path : null,
            visibility: 'private'
        })
        props.item.post_id = data.id
        props.item.config = { ...(props.item.config || {}), saved_post_url: data.url }
    } catch (e) {
        imagePostError.value = e.response?.data?.message || 'Could not save the post — please try again.'
    } finally {
        savingImagePost.value = false
    }
}

const runImageSearch = () => {
    clearTimeout(imageDebounce)
    imageDebounce = setTimeout(async () => {
        imageSearching.value = true
        try {
            const { data } = await axios.get(route('lessons.post-search'), {
                params: { q: imageQuery.value.trim(), type: 'image' }
            })
            imageResults.value = data
            imageSearched.value = true
        } catch (e) {
            imageResults.value = []
        } finally {
            imageSearching.value = false
        }
    }, 300)
}

const attachImagePost = (post) => {
    props.item.post_id = post.post_id
    props.item.config = {
        ...(props.item.config || {}),
        url: post.cover_image,
        caption: props.item.config.caption || post.title,
        saved_post_url: route('posts.show', post.slug)
    }
    imageSearchOpen.value = false
    imageQuery.value = ''
    imageResults.value = []
}

// --- Video block ↔ video posts ---
const savingVideoPost = ref(false)
const videoPostError = ref('')
const videoSearchOpen = ref(false)
const videoQuery = ref('')
const videoResults = ref([])
const videoSearching = ref(false)
const videoSearched = ref(false)
let videoDebounce = null

const saveVideoAsPost = async () => {
    savingVideoPost.value = true
    videoPostError.value = ''
    try {
        const { data } = await axios.post(route('lessons.save-post'), {
            post_type: 'video',
            title: props.item.config.title || props.item.config.url,
            content: props.item.config.note || null,
            source_url: props.item.config.url,
            visibility: 'private'
        })
        props.item.post_id = data.id
        props.item.config = { ...(props.item.config || {}), saved_post_url: data.url }
    } catch (e) {
        videoPostError.value = e.response?.data?.message || 'Could not save the post — please try again.'
    } finally {
        savingVideoPost.value = false
    }
}

const runVideoSearch = () => {
    clearTimeout(videoDebounce)
    videoDebounce = setTimeout(async () => {
        videoSearching.value = true
        try {
            const { data } = await axios.get(route('lessons.post-search'), {
                params: { q: videoQuery.value.trim(), type: 'video' }
            })
            videoResults.value = data
            videoSearched.value = true
        } catch (e) {
            videoResults.value = []
        } finally {
            videoSearching.value = false
        }
    }, 300)
}

const attachVideoPost = (post) => {
    props.item.post_id = post.post_id
    props.item.config = {
        ...(props.item.config || {}),
        url: post.source_url,
        title: props.item.config.title || post.title,
        saved_post_url: route('posts.show', post.slug)
    }
    videoSearchOpen.value = false
    videoQuery.value = ''
    videoResults.value = []
}

const openSaveAsPost = () => {
    postForm.value.title = stripHtml(props.item.content).slice(0, 60)
    postForm.value.post_type = props.item.type === 'scripture_help' ? 'scripture_help' : 'thought'
    savePostError.value = ''
    showSaveAsPost.value = true
}

const saveAsPost = async () => {
    savingPost.value = true
    savePostError.value = ''
    try {
        const { data } = await axios.post(route('lessons.save-post'), {
            content: props.item.content,
            title: postForm.value.title || null,
            post_type: postForm.value.post_type,
            visibility: postForm.value.visibility,
            tags: postForm.value.tags
        })
        props.item.post_id = data.id
        props.item.config = { ...(props.item.config || {}), saved_post_url: data.url }
        showSaveAsPost.value = false
    } catch (e) {
        savePostError.value = e.response?.data?.message || 'Could not save the post — please try again.'
    } finally {
        savingPost.value = false
    }
}

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
        case 'post': return c.post_title || stripHtml(props.item.content) || 'My post'
        case 'text': return stripHtml(props.item.content) || 'Empty'
        case 'scripture_help': return stripHtml(props.item.content) || 'Scripture help'
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
            <!-- Emphasis: key (star) / optional (clock) -->
            <div class="ml-2 flex flex-shrink-0 items-center gap-0.5">
                <button
                    type="button"
                    class="rounded p-1 transition-colors"
                    :class="item.config.emphasis === 'key' ? 'text-amber-500 hover:text-amber-600' : 'text-stone-300 hover:text-stone-500'"
                    :title="item.config.emphasis === 'key' ? 'Key point — click to unmark' : 'Mark as key — make sure to cover this'"
                    @click="toggleEmphasis('key')"
                >
                    <svg class="h-4 w-4" :fill="item.config.emphasis === 'key' ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                </button>
                <button
                    type="button"
                    class="rounded p-1 transition-colors"
                    :class="item.config.emphasis === 'optional' ? 'text-teal-600 hover:text-teal-700' : 'text-stone-300 hover:text-stone-500'"
                    :title="item.config.emphasis === 'optional' ? 'Optional (if time) — click to unmark' : 'Mark as optional — cover if time allows'"
                    @click="toggleEmphasis('optional')"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
            </div>
            <button
                type="button"
                @click="handleRemove"
                class="ml-1 flex-shrink-0 text-stone-400 hover:text-red-600"
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
                        Your copy of the quote — highlight or trim it for this lesson or talk
                    </label>
                    <StoryEditor v-model="item.content" placeholder="The quote text..." />
                    <p class="mt-1 text-xs text-stone-400">
                        Edits and highlights here only affect this lesson or talk; the saved quote stays unchanged.
                    </p>
                </div>
            </div>

            <!-- My Post (references one of the user's own posts) -->
            <div v-else-if="item.type === 'post'" class="space-y-3">
                <PostPicker :item="item" />
                <div v-if="item.post_id">
                    <label class="mb-1 block text-sm font-medium text-stone-700">
                        Your copy for this lesson or talk — trim it to the part you'll share
                    </label>
                    <StoryEditor v-model="item.content" placeholder="The post text..." />
                    <p class="mt-1 text-xs text-stone-400">
                        Edits here only affect this lesson or talk; the post itself stays unchanged.
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

                    <!-- Post linkage: reuse a saved video post, or keep this one -->
                    <div class="mt-2">
                        <div v-if="item.post_id" class="flex items-center gap-2 text-sm text-green-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Saved as a post
                            <a
                                v-if="item.config?.saved_post_url"
                                :href="item.config.saved_post_url"
                                target="_blank"
                                class="text-amber-700 hover:text-amber-800 font-medium"
                            >View &rarr;</a>
                        </div>
                        <div v-else class="flex flex-wrap items-center gap-4">
                            <button
                                type="button"
                                class="text-sm text-amber-700 hover:text-amber-800 font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                                :disabled="!item.config.url || savingVideoPost"
                                :title="item.config.url ? 'Keep this video/link as a post so you can find and reuse it' : 'Enter a URL first'"
                                @click="saveVideoAsPost"
                            >
                                {{ savingVideoPost ? 'Saving…' : 'Save as a Post' }}
                            </button>
                            <button
                                type="button"
                                class="text-sm text-stone-500 hover:text-stone-700"
                                @click="videoSearchOpen = !videoSearchOpen"
                            >
                                {{ videoSearchOpen ? 'Hide saved videos' : 'Use a saved video' }}
                            </button>
                        </div>
                        <p v-if="videoPostError" class="mt-1 text-xs text-red-600">{{ videoPostError }}</p>

                        <div v-if="videoSearchOpen && !item.post_id" class="relative mt-2">
                            <input
                                v-model="videoQuery"
                                @input="runVideoSearch"
                                @focus="runVideoSearch"
                                type="text"
                                class="w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Search your saved videos and links..."
                            >
                            <ul
                                v-if="videoResults.length"
                                class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-stone-200 bg-white shadow-lg"
                            >
                                <li
                                    v-for="post in videoResults"
                                    :key="post.post_id"
                                    @click="attachVideoPost(post)"
                                    class="cursor-pointer border-b border-stone-100 px-3 py-2 last:border-0 hover:bg-amber-50"
                                >
                                    <p class="text-sm font-medium text-stone-800">{{ post.title }}</p>
                                    <p class="truncate text-xs text-stone-500">{{ post.source_url }}</p>
                                </li>
                            </ul>
                            <p v-else-if="videoSearched && !videoSearching" class="mt-1 text-xs text-stone-400">
                                No saved videos yet — save one with “Save as a Post”.
                            </p>
                        </div>
                    </div>
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

                <!-- Post linkage: reuse a saved image post, or keep this one -->
                <div>
                    <div v-if="item.post_id" class="flex items-center gap-2 text-sm text-green-700">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Saved as a post
                        <a
                            v-if="item.config?.saved_post_url"
                            :href="item.config.saved_post_url"
                            target="_blank"
                            class="text-amber-700 hover:text-amber-800 font-medium"
                        >View &rarr;</a>
                    </div>
                    <div v-else class="flex flex-wrap items-center gap-4">
                        <button
                            type="button"
                            class="text-sm text-amber-700 hover:text-amber-800 font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!blockHasImage || savingImagePost"
                            :title="blockHasImage ? 'Keep this image as a post so you can find and reuse it' : 'Add an image first'"
                            @click="saveImageAsPost"
                        >
                            {{ savingImagePost ? 'Saving…' : 'Save as a Post' }}
                        </button>
                        <button
                            v-if="mediaSource === 'url'"
                            type="button"
                            class="text-sm text-stone-500 hover:text-stone-700"
                            @click="imageSearchOpen = !imageSearchOpen"
                        >
                            {{ imageSearchOpen ? 'Hide saved images' : 'Use a saved image' }}
                        </button>
                    </div>
                    <p v-if="imagePostError" class="mt-1 text-xs text-red-600">{{ imagePostError }}</p>

                    <div v-if="imageSearchOpen && !item.post_id && mediaSource === 'url'" class="relative mt-2">
                        <input
                            v-model="imageQuery"
                            @input="runImageSearch"
                            @focus="runImageSearch"
                            type="text"
                            class="w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                            placeholder="Search your saved images..."
                        >
                        <ul
                            v-if="imageResults.length"
                            class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-stone-200 bg-white shadow-lg"
                        >
                            <li
                                v-for="post in imageResults"
                                :key="post.post_id"
                                @click="attachImagePost(post)"
                                class="flex cursor-pointer items-center gap-3 border-b border-stone-100 px-3 py-2 last:border-0 hover:bg-amber-50"
                            >
                                <img v-if="post.cover_image" :src="post.cover_image" alt="" class="h-10 w-10 rounded object-cover">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-stone-800">{{ post.title }}</p>
                                    <p class="truncate text-xs text-stone-500">{{ post.excerpt }}</p>
                                </div>
                            </li>
                        </ul>
                        <p v-else-if="imageSearched && !imageSearching" class="mt-1 text-xs text-stone-400">
                            No saved images yet — save one with “Save as a Post”.
                        </p>
                    </div>
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

            <!-- My Words / Scripture Help (rich text) -->
            <div v-else-if="item.type === 'text' || item.type === 'scripture_help'">
                <StoryEditor
                    v-model="item.content"
                    :placeholder="item.type === 'scripture_help'
                        ? 'Explain the passage — context, background, what it means...'
                        : 'Write your own words...'"
                />

                <!-- Save as Post -->
                <div class="mt-3">
                    <div v-if="item.post_id" class="flex items-center gap-2 text-sm text-green-700">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Saved as a post
                        <a
                            v-if="item.config?.saved_post_url"
                            :href="item.config.saved_post_url"
                            target="_blank"
                            class="text-amber-700 hover:text-amber-800 font-medium"
                        >
                            View &rarr;
                        </a>
                    </div>

                    <button
                        v-else-if="!showSaveAsPost"
                        type="button"
                        class="text-sm text-amber-700 hover:text-amber-800 font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="!hasTextContent"
                        :title="hasTextContent ? 'Keep this writing as a post of its own' : 'Write something first'"
                        @click="openSaveAsPost"
                    >
                        Save as a Post
                    </button>

                    <div v-else class="rounded-lg border border-stone-200 bg-stone-50 p-4 space-y-3">
                        <p class="text-sm text-stone-600">
                            This saves your words as a post of their own — they'll keep living in your
                            posts even if this lesson or talk changes.
                        </p>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-stone-700">Post title</label>
                            <input
                                v-model="postForm.title"
                                type="text"
                                maxlength="255"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-stone-700">Type</label>
                                <select v-model="postForm.post_type" class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500">
                                    <option value="thought">Thought</option>
                                    <option value="note">Note</option>
                                    <option value="story">Story</option>
                                    <option value="scripture_help">Scripture Help</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-stone-700">Visibility</label>
                                <select v-model="postForm.visibility" class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500">
                                    <option value="private">Private (just me)</option>
                                    <option value="friends">Friends</option>
                                    <option value="public">Public</option>
                                </select>
                            </div>
                        </div>
                        <TagInput v-model="postForm.tags" :content="item.content" />
                        <p v-if="savePostError" class="text-sm text-red-600">{{ savePostError }}</p>
                        <div class="flex justify-end gap-3">
                            <button type="button" class="text-sm text-stone-500 hover:text-stone-700" @click="showSaveAsPost = false">
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                                :disabled="savingPost"
                                @click="saveAsPost"
                            >
                                {{ savingPost ? 'Saving…' : 'Save Post' }}
                            </button>
                        </div>
                    </div>
                </div>
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
