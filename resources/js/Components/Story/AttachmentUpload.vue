<script setup>
import { ref } from 'vue'
import axios from 'axios'

// Writes straight into the Inertia form object (attachment_url / _path / _name
// / _size), the same way LessonItemCard edits item.config in place. The four
// fields always move together, so there is nothing for the page to wire up.
const props = defineProps({
    form: { type: Object, required: true },
})

const uploading = ref(false)
const error = ref('')

const formatSize = (bytes) => {
    if (!bytes) return ''
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

// Drop a file from storage. Best effort: an orphaned PDF is harmless, and the
// user shouldn't be blocked on cleanup.
const deleteStored = async (path) => {
    if (!path) return
    try {
        await axios.delete('/upload-attachment', { data: { path } })
    } catch (e) {
        // Ignore — the post is what matters here.
    }
}

const apply = ({ url = null, path = null, name = null, size = null }) => {
    props.form.attachment_url = url
    props.form.attachment_path = path
    props.form.attachment_name = name
    props.form.attachment_size = size
}

const upload = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    uploading.value = true
    error.value = ''
    const previousPath = props.form.attachment_path

    try {
        const data = new FormData()
        data.append('file', file)
        const res = await axios.post('/upload-attachment', data)
        apply(res.data)

        // A replacement just superseded the old file.
        if (previousPath && previousPath !== res.data.path) {
            await deleteStored(previousPath)
        }
    } catch (err) {
        error.value = err.response?.data?.message
            || 'Upload failed — the file may not be a PDF, or may be larger than 20MB.'
    } finally {
        uploading.value = false
        event.target.value = ''
    }
}

const remove = async () => {
    const path = props.form.attachment_path
    apply({})
    error.value = ''
    await deleteStored(path)
}
</script>

<template>
    <div class="bg-white rounded-lg shadow p-6 border border-stone-100 space-y-3">
        <label class="block text-sm font-medium text-stone-700">
            PDF attachment (optional)
        </label>
        <p class="text-sm text-stone-500">
            Attach a document — a handout, study guide, or marked-up chapter. It shows
            up on the post for anyone who can see it.
        </p>

        <div
            v-if="form.attachment_url"
            class="flex flex-wrap items-center gap-3 rounded-lg border border-stone-200 bg-stone-50 px-4 py-3"
        >
            <svg class="h-8 w-8 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-stone-800">
                    {{ form.attachment_name || 'Attached PDF' }}
                </p>
                <p class="text-xs text-stone-500">
                    PDF<span v-if="form.attachment_size"> · {{ formatSize(form.attachment_size) }}</span>
                </p>
            </div>
            <a
                :href="form.attachment_url"
                target="_blank"
                rel="noopener"
                class="text-sm font-medium text-amber-600 hover:text-amber-800"
            >
                Open
            </a>
            <button type="button" class="text-sm font-medium text-stone-500 hover:text-red-600" @click="remove">
                Remove
            </button>
        </div>

        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-50">
            <span>
                {{ uploading ? 'Uploading…' : (form.attachment_url ? 'Replace PDF' : 'Upload a PDF') }}
            </span>
            <input
                type="file"
                accept="application/pdf,.pdf"
                class="hidden"
                :disabled="uploading"
                @change="upload"
            >
        </label>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="form.errors?.attachment_url" class="text-sm text-red-600">{{ form.errors.attachment_url }}</p>
    </div>
</template>
