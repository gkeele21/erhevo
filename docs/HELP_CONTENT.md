# Help & Training Content

How user-facing feature documentation works, and how to add to it when a
feature ships. There are three layers, shallowest to deepest:

| Layer | Where | Purpose |
|---|---|---|
| **Guide** (`/guide`) | `resources/js/Pages/Guide.vue` | Marketing-style tour for new/prospective users. One card per major feature, benefit-focused, short. |
| **Help & Training** (`/help`) | `resources/js/Pages/Help.vue` | Task-oriented details per feature: how things actually behave (drafts, autosave, sharing rules), plus "Did you know?" callouts for discoverable gems. |
| **Contextual tips** | `resources/js/Components/HelpTip.vue` | A small "?" placed next to a UI section. Hover = one-line insight; click = the matching Help anchor in a new tab. |

> **Definition of done for a notable feature:** add or update a Help bullet
> (and a "Did you know?" if the feature is easy to miss), consider a
> HelpTip at the spot in the UI where users would wonder about it, and add
> a dated entry to `config/whats_new.php` so existing users see it on their
> dashboard and it joins the history on `/whats-new`. The Guide only changes
> for headline features.

---

## Adding content to the Help page

`Help.vue` is a single page: a `topics` array drives the sidebar, and each
topic is a `<section>` with a matching `id`. Anchors are the contract —
HelpTips deep-link to them (`/help#teach-mode`), so **don't rename an id**
without grepping for `anchor="..."` usages.

### Add a bullet to an existing topic

Find the section by id and add an `<li>`. Keep bullets task-oriented
("Editing a plan reflows the schedule but keeps your check-offs") rather
than descriptive ("Plans are editable").

### Add a new topic

1. Add to the `topics` array (drives the sidebar):

```js
{ id: 'my-feature', label: 'My Feature' },
```

2. Add a section following the house pattern:

```vue
<section id="my-feature" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
    <h3 class="text-xl font-semibold text-navy mb-3">🎯 My Feature</h3>
    <p class="text-teal mb-3">One-paragraph orientation: what it is, where it lives.</p>
    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
        <li>Task-oriented bullets — behaviors, not adjectives.</li>
        <li>Bold the <strong class="text-navy">feature nouns</strong> users will look for in the UI.</li>
    </ul>
    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
        💡 <strong>Did you know?</strong> Reserve this for the discoverable gem —
        the thing users love once they find it (e.g. the teach-mode TV icon).
        One per section, max.
    </div>
</section>
```

Conventions: `scroll-mt-6` on every section (anchor landing offset), one
emoji per heading, keep each section skimmable (a paragraph, 3–6 bullets,
at most one callout).

---

## Placing a contextual HelpTip

```vue
import HelpTip from '@/Components/HelpTip.vue'

<HelpTip
    anchor="teach-mode"
    tip="Tip: the TV icon on any block presents it full-screen in a new tab."
/>
```

- `anchor` must be a real section id in `Help.vue`.
- `tip` is the hover tooltip — write it as the **insight itself**, not "click
  for help". A user who never clicks should still learn something.
- The link opens in a new tab and stops click propagation, so it's safe
  inside collapsible headers and next to unsaved forms.

Current placements: lesson/talk builder (content heading, CFM card,
visibility card) and the teach-mode toolbar. Good future candidates: the
study plan form, the post form's visibility area, and the plan-sharing panel.

---

## Related

- `docs/CONFERENCE_UPDATES.md` — operational runbook for library content.
- The dashboard "Getting Started" checklist (`DashboardController::gettingStartedSteps`)
  and the friend-invitation email also describe features; update them when a
  flagship feature changes what a new user should try first.
