# Scriptures

Browsing a passage and seeing what has been written about it.

## The idea

A thought about a verse is a **Post**, not a field on something else. The verse
link is a `scripture_references` row, and a lesson block that uses the passage
*references* the post rather than carrying its own copy. Write once, and it
shows up in the lesson, in your posts, and on the passage page.

## `scripture_references`

Polymorphic — anything that can point at a passage uses it:

| referenceable_type | Written by |
|--------------------|------------|
| `App\Models\Post` | The scripture picker on the post form, or `posts:backfill-scripture-references` |
| `App\Models\LessonItem` | `Lesson::syncItems()`, derived from a scripture block's `config` |

Four columns describe any reference — see
[CFM_SCRIPTURE_REFERENCES.md](CFM_SCRIPTURE_REFERENCES.md) for the full table
and examples.

Models opt in with `App\Models\Concerns\HasScriptureReferences`, which provides
`scriptureReferences()` and `syncScriptureReferences()`. A soft delete keeps its
references; a force delete takes them with it.

> `Lesson::syncItems()` mass-deletes its items, which skips model events — so it
> clears those items' references by hand before the rows go. Any new code that
> mass-deletes referencing models must do the same.

## Finding what covers a passage

`ScriptureReference::coveringChapter($chapter)` matches references that start
in, end in, **or run straight through** a chapter. That last case matters:
"1 Nephi 3:25-5:2" covers chapter 4 without naming it, and a plain
`whereIn('start_chapter_id', ...)` misses it.

`$reference->coversVerse($chapter, $verseNumber)` narrows that to the verses
actually named, which is what drives the per-verse counts on the page.

## Pages

| Route | Name | What it shows |
|-------|------|---------------|
| `/scriptures` | `scriptures.index` | Volumes → books, plus recently written-about chapters |
| `/scriptures/{book:slug}/{chapter}` | `scriptures.show` | The chapter's verses, and the posts/lessons/talks referencing it |

On the chapter page, verses carrying references show a count; clicking one
filters the list to that verse. Entries are grouped per post or per lesson, so a
lesson quoting a passage three times appears once with all three passages listed.

### Visibility

Scoped per viewer, and resolved separately for each side because visibility
lives on different models:

- Posts — `Post::visibleTo($user)->published()`
- Lesson blocks — the owning `Lesson` must be `visibleTo($user)->published()`

This is the friends-graph scope that already exists elsewhere. A public,
cross-user verse commons would be a separate decision with its own moderation
questions; `public` posts already feed this page and could feed that later.

## Backfills

Both are additive and safe to re-run.

```bash
# Derive references from lesson scripture blocks' config.
php artisan lessons:backfill-scripture-references [--dry-run]

# Scan post title/content for references. Skips posts that already have some
# unless --overwrite.
php artisan posts:backfill-scripture-references [--dry-run] [--overwrite]
```

The post scan is a heuristic, and three things about it are deliberate:

- **Book names come from the database**, not a regex guess. Guessing is too
  loose — "See 1 Nephi 4" matches "See" + chapter 1 and swallows the numeral
  belonging to the book.
- **HTML tags become spaces**, not nothing. Stripping them glues a reference to
  the next block (`Moses 4:4</p><p>Satan`), which costs the verse number.
- **A bare "Moses 4" is dropped when the post also names verses in Moses 4.**
  Prose routinely does both, and keeping the chapter-wide row would mark every
  verse in the chapter as discussed.
