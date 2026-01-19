# Erhevo Vue Components

All Vue components are located in `resources/js/`.

## Directory Structure

```
resources/js/
├── Components/
│   ├── Story/           # Story-related components
│   │   ├── StoryCard.vue
│   │   ├── StoryEditor.vue
│   │   ├── VisibilitySelector.vue
│   │   ├── CategorySelector.vue
│   │   ├── TagInput.vue
│   │   ├── AuthorInput.vue
│   │   └── PrivacyOptions.vue
│   ├── Friends/         # Friendship components (if created)
│   │   ├── FriendsList.vue
│   │   ├── FriendRequests.vue
│   │   └── UserSearch.vue
│   └── [Jetstream Components]
├── Layouts/
│   └── AppLayout.vue    # Main application layout
└── Pages/
    ├── Dashboard.vue
    ├── Welcome.vue
    ├── About.vue
    ├── Stories/
    │   ├── Index.vue
    │   ├── Create.vue
    │   ├── Show.vue
    │   └── Edit.vue
    ├── Categories/
    │   └── Index.vue
    ├── Friends/
    │   └── Index.vue
    ├── Auth/             # Jetstream auth pages
    └── Profile/          # Jetstream profile pages
```

## Custom Components

### Story/StoryEditor.vue

WYSIWYG rich text editor using Tiptap.

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | String | `''` | HTML content (v-model) |
| `placeholder` | String | `'Start writing your story...'` | Placeholder text |

**Emits:**
- `update:modelValue` - Emits HTML content on change

**Features:**
- Bold, italic, headings (H2, H3)
- Bullet lists, numbered lists
- Blockquotes
- Links (with prompt dialog)
- Image upload (uploads to `/upload-image`)

**Usage:**
```vue
<StoryEditor v-model="form.content" placeholder="Share your story..." />
```

---

### Story/StoryCard.vue

Displays a story preview card in listings.

**Props:**
| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `story` | Object | Yes | Story object with title, slug, content, category, tags, etc. |

**Expected Story Object:**
```javascript
{
    id: 1,
    title: 'Story Title',
    slug: 'story-title',
    content: '<p>Story content...</p>',
    excerpt: 'Optional excerpt...',
    cover_image: '/storage/images/cover.jpg',
    published_at: '2024-01-15',
    creator_name: 'John Doe',  // or null if hidden/anonymous
    category: { name: 'Category', slug: 'category' },
    tags: [{ id: 1, name: 'tag-name' }]
}
```

**Usage:**
```vue
<StoryCard v-for="story in stories" :key="story.id" :story="story" />
```

---

### Story/VisibilitySelector.vue

Radio button group for selecting story visibility.

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | String | `'public'` | Selected visibility value (v-model) |
| `options` | Array | Required | Array of visibility options |

**Options Array Format:**
```javascript
[
    { value: 'public', label: 'Public', description: 'Visible to everyone' },
    { value: 'friends', label: 'Friends Only', description: 'Only your friends can see' },
    { value: 'private', label: 'Private', description: 'Only you can see' }
]
```

**Usage:**
```vue
<VisibilitySelector v-model="form.visibility" :options="visibilityOptions" />
```

---

### Story/TagInput.vue

Tag input with autocomplete suggestions.

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | Array | `[]` | Array of tag name strings (v-model) |

**Features:**
- Type to search existing tags
- Press Enter to add new tag
- Press Backspace to remove last tag
- Click X to remove specific tag
- Debounced API search (300ms)

**Usage:**
```vue
<TagInput v-model="form.tags" />
```

---

### Story/AuthorInput.vue

Select author type and value for a story.

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `authorType` | String | `'self'` | Author type: 'self', 'text', or 'user' |
| `authorText` | String | `''` | Free-text author name |
| `authorUserId` | Number/null | `null` | Selected user ID |

**Emits:**
- `update:authorType`
- `update:authorText`
- `update:authorUserId`

**Usage:**
```vue
<AuthorInput
    v-model:author-type="form.author_type"
    v-model:author-text="form.author_text"
    v-model:author-user-id="form.author_user_id"
/>
```

---

### Story/PrivacyOptions.vue

Toggle switches for privacy settings.

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `hideCreator` | Boolean | `false` | Hide who created the story |
| `hideAuthor` | Boolean | `false` | Hide the author attribution |
| `anonymizeNames` | Boolean | `false` | Replace names in content |

**Emits:**
- `update:hideCreator`
- `update:hideAuthor`
- `update:anonymizeNames`

**Usage:**
```vue
<PrivacyOptions
    v-model:hide-creator="form.hide_creator"
    v-model:hide-author="form.hide_author"
    v-model:anonymize-names="form.anonymize_names"
/>
```

---

## Layouts

### Layouts/AppLayout.vue

Main application layout wrapper (extends Jetstream).

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | String | `''` | Page title for `<Head>` |

**Slots:**
- `default` - Main page content
- `header` - Optional header slot for page title

**Features:**
- Navigation bar with brand colors
- Links: Dashboard, Stories, Categories, Friends, About
- "New Story" CTA button
- User dropdown menu
- Mobile responsive navigation

**Usage:**
```vue
<AppLayout title="Dashboard">
    <template #header>
        <h2 class="text-xl font-semibold text-navy">Dashboard</h2>
    </template>

    <div class="py-12">
        <!-- Page content -->
    </div>
</AppLayout>
```

---

## Pages

### Pages/Welcome.vue
Landing page with hero section, features, and CTA.

### Pages/About.vue
About page explaining Erhevo's mission and meaning.

### Pages/Dashboard.vue
User dashboard with stats and quick actions.

### Pages/Stories/Index.vue
Browse public stories with filtering.

### Pages/Stories/Create.vue
Create new story form with editor, categories, tags, and privacy options.

### Pages/Stories/Show.vue
Display single story with full content.

### Pages/Stories/Edit.vue
Edit existing story.

### Pages/Friends/Index.vue
Manage friends - view friends list, pending requests, search users.

---

## Jetstream Components

These are provided by Laravel Jetstream and styled with brand colors:

| Component | Description |
|-----------|-------------|
| `NavLink.vue` | Navigation link with active state |
| `ResponsiveNavLink.vue` | Mobile navigation link |
| `Dropdown.vue` | Dropdown menu wrapper |
| `DropdownLink.vue` | Dropdown menu item |
| `PrimaryButton.vue` | Primary action button |
| `SecondaryButton.vue` | Secondary action button |
| `DangerButton.vue` | Destructive action button |
| `TextInput.vue` | Text input field |
| `InputLabel.vue` | Form label |
| `InputError.vue` | Validation error message |
| `Checkbox.vue` | Checkbox input |
| `Modal.vue` | Modal dialog |
| `DialogModal.vue` | Dialog-style modal |
| `ConfirmationModal.vue` | Confirmation modal |

---

## Inertia.js Helpers

Use these from `@inertiajs/vue3`:

```vue
<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'

// Access page props
const page = usePage()
const user = page.props.auth.user

// Create form
const form = useForm({
    title: '',
    content: ''
})

// Submit form
const submit = () => {
    form.post(route('stories.store'))
}
</script>

<template>
    <Head title="Page Title" />
    <Link :href="route('stories.index')">View Stories</Link>
</template>
```

---

## Adding New Components

1. Create component in appropriate directory
2. Use Vue 3 Composition API with `<script setup>`
3. Use brand colors from Tailwind config (navy, teal, aqua, amber, gold, ivory)
4. Export with `defineProps` and `defineEmits`
5. Import in pages as needed

Example:
```vue
<script setup>
defineProps({
    value: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['update:value'])
</script>

<template>
    <div class="text-navy">
        <!-- Component content -->
    </div>
</template>
```
