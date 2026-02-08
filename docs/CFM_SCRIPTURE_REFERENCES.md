# Scripture References

## Overview

Posts can be linked to specific scripture passages using the `post_scripture_references` table. This system supports:

- Single verses: "1 Nephi 3:7"
- Verse ranges: "1 Nephi 3:7-12"
- Whole chapters: "1 Nephi 3"
- Multi-chapter ranges: "1 Nephi 3-4"
- Cross-chapter verse ranges: "1 Nephi 3:25-4:5"

## Storage Format

The `post_scripture_references` table uses four columns to represent any reference:

| Column | Purpose |
|--------|---------|
| `start_chapter_id` | The starting chapter (always required) |
| `start_verse` | The starting verse (null for whole chapters) |
| `end_chapter_id` | The ending chapter (null if same as start) |
| `end_verse` | The ending verse (null for whole chapters) |

### Examples

| Reference | start_chapter | start_verse | end_chapter | end_verse |
|-----------|--------------|-------------|-------------|-----------|
| "1 Nephi 3:7" | 1 Nephi 3 | 7 | NULL | NULL |
| "1 Nephi 3:7-12" | 1 Nephi 3 | 7 | NULL | 12 |
| "1 Nephi 3" | 1 Nephi 3 | NULL | NULL | NULL |
| "1 Nephi 3-4" | 1 Nephi 3 | NULL | 1 Nephi 4 | NULL |
| "1 Nephi 3:25-4:5" | 1 Nephi 3 | 25 | 1 Nephi 4 | 5 |

## Parsing

The `ScriptureReferenceParser` service handles parsing user input into the database format:

```php
$parser = app(ScriptureReferenceParser::class);

$parsed = $parser->parse("1 Nephi 3:7-12");
// Returns:
// [
//     'start_chapter_id' => 123,
//     'start_verse' => 7,
//     'end_chapter_id' => null,
//     'end_verse' => 12,
// ]
```

## Display

The `PostScriptureReference` model provides a `display_reference` attribute:

```php
$reference->display_reference; // "1 Nephi 3:7-12"
```

## Querying

To find posts referencing a specific scripture:

```php
// Find posts about 1 Nephi 3:7
$chapterId = ScriptureChapter::whereHas('book', function ($q) {
    $q->where('slug', '1-nephi');
})->where('chapter_number', 3)->first()->id;

$posts = Post::whereHas('scriptureReferences', function ($q) use ($chapterId) {
    $q->where('start_chapter_id', $chapterId)
      ->where(function ($q2) {
          // Single verse = 7
          $q2->where('start_verse', 7)
             ->whereNull('end_verse');
      })
      ->orWhere(function ($q2) {
          // Range includes verse 7
          $q2->where('start_verse', '<=', 7)
             ->where('end_verse', '>=', 7);
      });
})->get();
```

## Integration with CFM Weeks

When viewing a CFM week, the resurfacing mechanism queries for posts whose scripture references overlap with the week's assigned chapters. See [CFM_RESURFACING.md](CFM_RESURFACING.md) for details.
