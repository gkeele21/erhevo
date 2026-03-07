<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['textExtracted'])

const isOpen = ref(false)
const isProcessing = ref(false)
const error = ref('')
const previewUrl = ref('')
const selectedFile = ref(null)

const openModal = () => {
    isOpen.value = true
    error.value = ''
    previewUrl.value = ''
    selectedFile.value = null
}

const closeModal = () => {
    isOpen.value = false
    error.value = ''
    previewUrl.value = ''
    selectedFile.value = null
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (!file) return

    if (!file.type.startsWith('image/')) {
        error.value = 'Please select an image file.'
        return
    }

    if (file.size > 10 * 1024 * 1024) {
        error.value = 'Image must be less than 10MB.'
        return
    }

    selectedFile.value = file
    previewUrl.value = URL.createObjectURL(file)
    error.value = ''
}

const extractText = async () => {
    if (!selectedFile.value) {
        error.value = 'Please select an image first.'
        return
    }

    isProcessing.value = true
    error.value = ''

    const formData = new FormData()
    formData.append('image', selectedFile.value)

    try {
        const response = await axios.post(route('ai.extract-text'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })

        if (response.data.success) {
            emit('textExtracted', response.data.text)
            closeModal()
        } else {
            error.value = response.data.error || 'Failed to extract text.'
        }
    } catch (err) {
        error.value = err.response?.data?.error || 'Failed to extract text. Please try again.'
    } finally {
        isProcessing.value = false
    }
}
</script>

<template>
    <div>
        <!-- Trigger Button -->
        <button
            type="button"
            @click="openModal"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-stone-300 text-stone-600 rounded-lg hover:bg-stone-50"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Import from Photo
        </button>

        <!-- Modal -->
        <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

                <div class="relative bg-white rounded-lg max-w-lg w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-stone-800">
                            Import Text from Photo
                        </h3>
                        <button @click="closeModal" class="text-stone-400 hover:text-stone-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-stone-600 text-sm mb-4">
                        Upload a photo of handwritten or printed text, and we'll convert it to editable text.
                    </p>

                    <!-- Error Message -->
                    <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        {{ error }}
                    </div>

                    <!-- File Input -->
                    <div v-if="!previewUrl" class="mb-4">
                        <label
                            class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-stone-300 rounded-lg cursor-pointer hover:border-amber-400 hover:bg-amber-50 transition-colors"
                        >
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-10 h-10 mb-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mb-2 text-sm text-stone-500">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-stone-400">PNG, JPG, HEIC up to 10MB</p>
                            </div>
                            <input
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleFileSelect"
                            >
                        </label>
                    </div>

                    <!-- Image Preview -->
                    <div v-else class="mb-4">
                        <div class="relative">
                            <img
                                :src="previewUrl"
                                alt="Preview"
                                class="w-full max-h-64 object-contain rounded-lg border border-stone-200"
                            >
                            <button
                                @click="previewUrl = ''; selectedFile = null"
                                class="absolute top-2 right-2 p-1 bg-white rounded-full shadow hover:bg-stone-100"
                            >
                                <svg class="w-4 h-4 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button
                            @click="closeModal"
                            class="px-4 py-2 text-stone-600 hover:text-stone-800"
                        >
                            Cancel
                        </button>
                        <button
                            @click="extractText"
                            :disabled="!selectedFile || isProcessing"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            {{ isProcessing ? 'Extracting...' : 'Extract Text' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
