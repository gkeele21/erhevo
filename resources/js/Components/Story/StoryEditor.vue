<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Start writing your story...'
    }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Image.configure({
            HTMLAttributes: {
                class: 'max-w-full rounded-lg'
            }
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-amber-600 hover:text-amber-800 underline'
            }
        }),
        Placeholder.configure({
            placeholder: props.placeholder
        })
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-lg prose-stone max-w-none min-h-[300px] p-4 focus:outline-none'
        }
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    }
})

watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value, false)
    }
})

const isUploading = ref(false)

const uploadImage = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    isUploading.value = true

    const formData = new FormData()
    formData.append('image', file)

    try {
        const response = await fetch('/upload-image', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })

        const data = await response.json()

        if (data.url) {
            editor.value.chain().focus().setImage({ src: data.url }).run()
        }
    } catch (error) {
        console.error('Image upload failed:', error)
    } finally {
        isUploading.value = false
        event.target.value = ''
    }
}

const addLink = () => {
    const url = prompt('Enter URL:')
    if (url) {
        editor.value.chain().focus().setLink({ href: url }).run()
    }
}
</script>

<template>
    <div class="border border-stone-300 rounded-lg overflow-hidden">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap items-center gap-1 p-2 border-b border-stone-300 bg-stone-50">
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-stone-200': editor.isActive('bold') }"
                class="p-2 rounded hover:bg-stone-200"
                title="Bold"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h8a4 4 0 100-8H6v8zm0 0h9a4 4 0 110 8H6v-8z"/>
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-stone-200': editor.isActive('italic') }"
                class="p-2 rounded hover:bg-stone-200"
                title="Italic"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l-4 4m-4 4l4-4"/>
                </svg>
            </button>

            <div class="w-px h-6 bg-stone-300 mx-1"></div>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'bg-stone-200': editor.isActive('heading', { level: 2 }) }"
                class="p-2 rounded hover:bg-stone-200"
                title="Heading"
            >
                <span class="font-bold text-sm">H2</span>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="{ 'bg-stone-200': editor.isActive('heading', { level: 3 }) }"
                class="p-2 rounded hover:bg-stone-200"
                title="Subheading"
            >
                <span class="font-bold text-sm">H3</span>
            </button>

            <div class="w-px h-6 bg-stone-300 mx-1"></div>

            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-stone-200': editor.isActive('bulletList') }"
                class="p-2 rounded hover:bg-stone-200"
                title="Bullet List"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'bg-stone-200': editor.isActive('orderedList') }"
                class="p-2 rounded hover:bg-stone-200"
                title="Numbered List"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h10M7 16h10M3 8h.01M3 12h.01M3 16h.01"/>
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{ 'bg-stone-200': editor.isActive('blockquote') }"
                class="p-2 rounded hover:bg-stone-200"
                title="Quote"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </button>

            <div class="w-px h-6 bg-stone-300 mx-1"></div>

            <button
                type="button"
                @click="addLink"
                :class="{ 'bg-stone-200': editor.isActive('link') }"
                class="p-2 rounded hover:bg-stone-200"
                title="Add Link"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </button>

            <label class="p-2 rounded hover:bg-stone-200 cursor-pointer" title="Add Image">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <input type="file" accept="image/*" @change="uploadImage" class="hidden" :disabled="isUploading">
            </label>

            <span v-if="isUploading" class="text-sm text-stone-500 ml-2">Uploading...</span>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" class="bg-white" />
    </div>
</template>

<style>
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>
