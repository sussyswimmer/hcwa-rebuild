# HCWA website rebuild plan

Plan files for rebuilding hcwa.org.vn, written to be handed to Claude Code. Drop this
folder into the project root; `CLAUDE.md` is the entry point Claude Code reads first.

Prepared 29 Oct 2026 from: HCWA's email, the "HCMC Child Welfare Association Website"
brief (2 pages), a review of the live site, and a look at peer child-welfare NGO sites.

## Files

| File | What it is | Who it's for |
|---|---|---|
| `CLAUDE.md` | Rules and context Claude Code must follow | Claude Code |
| `01-AUDIT.md` | What the current site has, what's broken, top 5 fixes | Maxwell, HCWA |
| `02-BENCHMARKS.md` | Peer NGO sites and the patterns worth borrowing | Maxwell, HCWA |
| `03-STRATEGY-AND-DECISIONS.md` | Approach, stack, phases, open questions for HCWA | Everyone |
| `04-SITEMAP-AND-CONTENT-MODEL.md` | Pages, menus (VI/EN), URLs, content types, redirects | Claude Code |
| `05-DESIGN.md` | Design system: tokens, type, color, components, motion | Claude Code |
| `06-PAGES.md` | Page-by-page specs, desktop and mobile | Claude Code |
| `07-BILINGUAL.md` | Language switching, translation coverage, glossary | Claude Code, HCWA |
| `08-BUILD-PLAN.md` | Phased tasks with acceptance criteria | Claude Code |
| `09-QA-CHECKLIST.md` | Gates every page must pass before launch | Claude Code |
| `10-ASSET-REQUEST.md` | Visuals list with sizes, ratios and placement for HCWA's design agency | HCWA design agency |

## How to run it with Claude Code

1. Get answers to the open questions in `03-STRATEGY-AND-DECISIONS.md` (at least Q1 to Q4).
2. Send `10-ASSET-REQUEST.md` to HCWA so their agency can start early.
3. Start Claude Code in the project folder and say: "Read CLAUDE.md, then do Phase 0 from
   08-BUILD-PLAN.md." Work one phase at a time. Review each phase's acceptance criteria
   before moving on.
4. Phase 1 ends with a shareable prototype link (desktop + mobile homepage, one center
   page, one project page). That is the "first homepage design" HCWA's brief asks for.

## What HCWA asked for, and where it's answered

| HCWA's ask (brief section 6 and 7) | Answered in |
|---|---|
| Top 3 to 5 things to improve | `01-AUDIT.md` → "Top 5" |
| A few ideas or inspiration examples | `02-BENCHMARKS.md` |
| First homepage design, desktop and mobile | Phase 1 prototype (`06-PAGES.md` spec) |
| Recommendations for other pages | `06-PAGES.md` |
| What can change within the existing site | `01-AUDIT.md` → "Quick wins", Phase 0 in `08-BUILD-PLAN.md` |
| List of visuals with sizes and placement (email) | `10-ASSET-REQUEST.md` |
