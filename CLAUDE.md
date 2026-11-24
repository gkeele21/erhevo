# Erhevo

## Ticket workflow

"Work on ER-123" runs the `work-on-ticket` skill from the shared `team-workflow` plugin
(github.com/gkeele21/claude-team, enabled in `.claude/settings.json` along with `dev-workflow`).
Settings for this repo:

- **Jira:** site `grantkeele-topdawgsports.atlassian.net`, project key `ER`. Use your own
  Atlassian connection for that site; name it in `CLAUDE.local.md` if you have more than one.
- **Main branch:** `main`
- **Pull requests:** GitHub (`gkeele21/erhevo`). After the developer approves the push, open one
  with `gh pr create --base main`, titled `<KEY>: <what changed>`.
- **Checks before done:** `php artisan test` and `npm run build`
