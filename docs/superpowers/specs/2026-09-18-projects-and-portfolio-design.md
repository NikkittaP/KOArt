# Projects, multi-photo works and section PDF portfolios — design

Date: 2026-09-18 · Status: approved in chat, awaiting spec review

## Problem

Three requests from the owner, which turn out to share one root cause:

1. A work with several photos but **no description** shows only its cover in
   the lightbox; the "Read more" link (the only way to its `/work/N` page)
   appears only when a description exists, so the other photos are unreachable.
2. Each section needs a **downloadable PDF portfolio** containing every work on
   that page; for works with several photos the author picks which photos go in.
3. **Projects** (e.g. a board game: box, cards, rules) should open straight to
   their own page on click, not to the cover in the lightbox. Single paintings
   (sometimes photographed from several angles) keep the lightbox.

State of production (katiaoskina.com, checked 2026-09-18): there are **no
series** in use. The author models projects as one `paintings` row with
several `photos` plus a description (e.g. `/work/202` has 8 photos, `/work/195`
has 6). The local DB is empty; all real content lives on production.

Root cause: the site has no notion of "what kind of thing is this work". Adding
that one attribute, plus a per-photo portfolio flag, covers all three items.

## Decisions (agreed)

- Project vs artwork is an **explicit admin toggle** per work — not inferred
  from photo count (a painting shot from 3 angles is still an artwork) and not
  per section.
- The PDF is **generated on the server on first request and cached**; the cache
  invalidates itself when the section's content changes.
- PDF content: **cover page + one photo per page with captions**, no long
  descriptions. English only.
- Series are out of scope: their code stays untouched and series works are not
  included in the PDF.

## 1. Data model

One Yii migration (`m260918_120000_add_display_type_and_in_portfolio`), with
`safeDown()`:

| Column | Type | Default | Back-fill |
|---|---|---|---|
| `paintings.display_type` | `varchar(16)` not null | `'artwork'` | all rows stay `artwork`; the author flips the ~6 existing projects herself |
| `photos.in_portfolio` | `tinyint(1)` not null | `0` | `1` where `isMain = 1`; for paintings with no `isMain` photo, the photo with the lowest `sort_order` |

Model changes:

- `Paintings`: constants `TYPE_ARTWORK = 'artwork'`, `TYPE_PROJECT = 'project'`,
  `displayTypes()` label map, `isProject(): bool`, validation `in` range.
  Guard with `hasAttribute()` the same way `status` is guarded, so code
  deployed before the migration runs does not fatal.
- `Photos`: `in_portfolio` in rules (boolean) and labels.
- `Paintings::portfolioPhotos(): Photos[]` — photos with `in_portfolio = 1`
  ordered by `sort_order, id`; **if none are flagged, returns `[cover]`**
  (main photo, else first photo). If the work has no photos at all, returns
  `[]` and the work is skipped. This rule is what guarantees "every work on the
  page is in the PDF".

## 2. Admin

All new admin strings go through `Yii::t('admin', …)` with English source
keys and Russian translations added to the existing admin message file, like
the rest of the admin UI.

- **Work form** (`views/paintings/_form.php`): a two-option radio
  "Artwork / Project" (ru: «Картина / Проект») bound to `display_type`, next
  to the visibility/status fields, with a one-line hint "A project opens
  straight to its own page" (ru: «Проект открывается сразу своей страницей»).
- **Works grid** (`paintings/index`): a small "Project" badge in the name
  column for `display_type = project`. No new filter.
- **Manage photos** (`views/photos/manage.php`): each tile's
  `.photo-sort-controls` gets a third control, a checkbox
  `portfolio_photo_ids[]` "In portfolio" (ru: «В портфолио»), next to Cover
  and Delete. It is saved by the existing form POST in
  `PhotosController::actionManage`, as a fourth step after deletions, order
  and cover: set `in_portfolio = 1` for the posted ids of this painting and
  `0` for the rest. Help text: "If nothing is ticked, the cover goes into the
  PDF" (ru: «Если ничего не отмечено, в PDF попадёт обложка»).
- New uploads: `in_portfolio = 0` unless the photo becomes the cover as the
  work's first photo, in which case `1` (mirrors the back-fill).

## 3. Public site

### 3a. Artwork (item 1)

In `views/site/section.php`, the lightbox link `data-url` is set when the work
has a description **or** more than one photo (today: description only).

- With a description: link text stays "Read more →".
- Without a description: "All photos (N) →".

The label is passed as a new `data-more` attribute; `web/js/public.js` uses it
instead of the hard-coded "Read more →" (falls back to "Read more →" if
absent).

### 3b. Project (item 3)

For `display_type = project` the mosaic tile is rendered as
`<a class="proj" href="/work/N"><figure>…</figure></a>`, the figure carries **no
`data-full`**, so `public.js` does not bind the lightbox to it (the same
mechanism the work page already relies on). Hover caption adds a second line
"Project · N images" (just "Project" when N = 1). Keyboard: it is a real link,
so focus/Enter work without JS.

The project page is the existing `/work/N` page, unchanged: with several
photos it already renders "description on top, photos stacked full-width".

### 3c. Query efficiency

`renderSection()` eager-loads photos (`->with('photos')`) so the photo counts
and cover lookups in the mosaic do not issue one query per work.

## 4. Section PDF portfolio (item 2)

### Entry point

- Route `portfolio/<slug:[\w-]+>.pdf` → `PortfolioController::actionSection($slug)`.
- Link in the section header (`views/site/section.php`, under the intro):
  "Download portfolio (PDF)". Shown only if the section has at least one
  work with a photo. Same link on `/ru/` pages (PDF is English-only).
- Not in `sitemap.xml`. `robots.txt` gets `Disallow: /portfolio/` (the PDFs
  duplicate page content; keeping crawlers off also keeps generation load down).
- Response: `Content-Type: application/pdf`,
  `Content-Disposition: inline; filename="Katia-Oskina-<Section-Title>.pdf"`,
  `Cache-Control: public, max-age=3600`.

### Content and order

Exactly the works shown in the section mosaic: same query as `renderSection()`
(visible, in section, not in a series, `sort_order, id`). Extract that query
into one shared method (`Paintings::findForSectionMosaic($sectionId)`) so the
page and the PDF can never disagree.

Per work: `portfolioPhotos()` in order.

### Layout (A4 landscape, 297 × 210 mm)

- **Cover page:** artist name (large), section title, `katiaoskina.com`,
  contact email (`params['contactEmail']`), location
  (`params['contactLocation']`). Plain text, Jost, generous whitespace.
- **One photo per page**, scaled to fit the page box minus margins and a
  caption band, centred, aspect ratio preserved.
- **Caption** under the **first** photo of each work: title, then
  "materials · ground · year · size" (reuse `PaintingPresenter` labels).
  Subsequent photos of the same work: no caption.
- Footer on every page except the cover: "Katia Oskina · katiaoskina.com · p. N".

### Generation

- Library: **`setasign/tfpdf`** (FPDF with UTF-8 + TTF). Chosen for its small
  footprint and low memory use on Hetzner Webhosting S (192 MB, 120 s).
  Font: Jost TTF (Regular + Medium) added to `assets/fonts/` (outside
  `web/`); tFPDF font cache dir set to `runtime/tfpdf/`.
- Images: FPDF cannot read WebP. For each photo, load
  `web/paintings_photo/original_site/<name>.webp` (~1500 px) with GD
  `imagecreatefromwebp`, write a temporary JPEG (quality 85) to
  `runtime/portfolio/tmp/`, add it, then `imagedestroy` immediately. One
  image in memory at a time.
- Service class `app\helpers\PortfolioPdf` with
  `build(Sections $s, Paintings[] $works): string` (returns the file path),
  kept free of HTTP concerns so it can be called from a console command later.

### Cache

- File: `runtime/portfolio/<slug>-<hash>.pdf`.
- `<hash>` = sha1 of a canonical JSON of everything that affects the output:
  per work `id, name_en, date, width, height, ground_id, material ids,
  display order`; per chosen photo `id, filename, sort_order`; plus section
  title and a `PDF_LAYOUT_VERSION` constant (bump on layout changes).
- Request flow: compute hash (DB only, cheap) → if file exists, stream it →
  else build, then delete older `<slug>-*.pdf` files, then stream.
- Concurrency: build into a temp name and `rename()` into place, so two
  simultaneous first requests cannot serve a half-written file.
- Failure (missing image file, GD error): skip that photo, log a warning via
  `Yii::warning`, continue. If the whole build throws, respond 503 with a
  short message; nothing is cached.

## 5. Deploy impact

- New Composer dependency → `vendor/` must be re-uploaded via SFTP (no SSH on
  tariff S). Add a note to `docs/DEPLOY.md`.
- Run the migration: there is no console on hosting and DEPLOY.md has no
  migration procedure (production was seeded from a full dump). Ship an
  equivalent `docs/sql/2026-09-18-display-type-and-portfolio.sql` (the
  `ALTER TABLE`s, the back-fill `UPDATE`s, and the `INSERT INTO migration`
  row so a later `yii migrate` does not re-run it) to be pasted into
  phpMyAdmin, and add a short "Schema updates" section to DEPLOY.md. Deploy
  order: run SQL first, then upload code (the `hasAttribute()` guards make
  the reverse order safe too).
- `runtime/portfolio/` must be writable (it is under `runtime/`, already 775).
- Verify GD has WebP read support on the host (already required for thumbnails).

## 6. Order of work

1. Migration + model changes (shared by everything).
2. Item 1: `data-url` rule + `data-more` label.
3. Item 3: project tiles + admin toggle + grid badge.
4. Item 2: admin portfolio checkbox → `PortfolioPdf` → controller/route/link →
   cache → robots/DEPLOY notes.

Items 1 and 3 can ship before item 2.

## 7. Testing

No automated test suite exists in the repo and no headless browser is
available, so verification is:

- Local: run the migration up/down/up on a copy of the production dump.
- `PortfolioPdf` smoke script (console): build a PDF for each section of the
  local copy, assert non-empty, page count = 1 + total portfolio photos, peak
  memory well under 192 MB (`memory_get_peak_usage`), time under 60 s.
- Cache: build twice → second call does not rebuild; change a work's title or
  a portfolio checkbox → hash changes, rebuild, old file removed.
- Manual QA by the owner on production after deploy: an artwork with several
  photos and no description shows "All photos (N)"; a project tile goes
  straight to its page; hover shows "Project · N images"; each section's PDF
  downloads and matches the mosaic order.

## Out of scope

- Series in PDFs or series-level project behaviour.
- Russian/Swedish PDFs.
- Per-work PDFs, or choosing which *works* are in the PDF (all mosaic works
  always are).
- Lightbox paging through a work's extra photos.
