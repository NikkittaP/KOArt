# Projects, Multi-photo Works and Section PDF Portfolios — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a work be marked as a *project* (opens straight to its page), make extra photos of ordinary works reachable from the lightbox, and let visitors download a per-section PDF portfolio whose photos the author picks in the admin.

**Architecture:** One migration adds `paintings.display_type` and `photos.in_portfolio`. The section mosaic query moves into `Paintings::findForSectionMosaic()` so the page and the PDF always list the same works. A new `app\helpers\PortfolioPdf` (tFPDF) builds and caches the PDF under `runtime/portfolio/`, keyed by a hash of its content; a public `PortfolioController` streams it, and a console command of the same name is the smoke test.

**Tech Stack:** PHP 8.2, Yii 2.0.55 (basic template), MySQL 8, vanilla JS, `setasign/tfpdf` 1.33, GD with WebP.

**Spec:** `docs/superpowers/specs/2026-09-18-projects-and-portfolio-design.md`

## Global Constraints

- Repo root: `G:\OSPanel\home\katiaoskina\public_html` (all paths below are relative to it). Shell: Git Bash. PHP: `/g/OSPanel/modules/PHP-8.2/php.exe` (abbreviated `$PHP` below — run `PHP=/g/OSPanel/modules/PHP-8.2/php.exe` first in each shell). MySQL client: `/g/OSPanel/modules/MySQL-8.0/bin/mysql.exe` (`$MYSQL`). Local site: `https://katiaoskina.local`.
- The production DB and images are the only real data; the local DB starts empty (Task 0 fixes that).
- Code must keep working **before** the migration has run on production: every access to `display_type` / `in_portfolio` is guarded with `hasAttribute()` or a schema check, the same way `status` is.
- Admin strings: `Yii::t('admin', '<English source>')` + a Russian entry in `messages/ru/admin.php`. Public strings: plain English in the view (the public site has no i18n catalogue).
- Public copy, verbatim: `Read more →`, `All photos (N) →`, `Project · N images` / `Project`, `Download portfolio (PDF)`.
- PDF: English only, A4 landscape, cover page + one photo per page, caption (title, then "materials · ground · year · size") under each work's first photo only, footer `Katia Oskina · katiaoskina.com · p. N` on every page but the cover.
- Hosting limits the PDF build must respect: 192 MB memory, 120 s per request (Hetzner Webhosting S). Target: peak < 96 MB, < 60 s per section.
- No automated test suite exists. Verification is by console commands, `curl` against the local site, and SQL — each task states the exact command and expected output.
- Bump `params['buildVersion']` once per public-frontend change (it is the cache-buster in the footer).
- Commit after each task. Do not push.

---

## File Structure

| File | Status | Responsibility |
|---|---|---|
| `commands/*.php` | move from `config/commands/` | Console commands (currently unreachable: `controllerNamespace` is `app\commands` but the files live in `config/commands/`) |
| `migrations/m260918_120000_add_display_type_and_in_portfolio.php` | create | Schema change |
| `docs/sql/2026-09-18-display-type-and-portfolio.sql` | create | Same change for phpMyAdmin on production |
| `models/Paintings.php` | modify | Type constants, `isProject()`, `portfolioPhotos()`, `findForSectionMosaic()` |
| `models/Photos.php` | modify | Label for `in_portfolio` |
| `helpers/PaintingPresenter.php` | modify | `metaLine()`, `photoCount()`, `hasOwnPage()`, `moreLabel()`, `projectLabel()` |
| `controllers/SiteController.php` | modify | Section page uses the shared query |
| `views/site/section.php` | modify | Project tiles, "All photos" link, PDF link |
| `views/paintings/work.php` | modify | Use `PaintingPresenter::metaLine()` |
| `web/js/public.js` | modify | Lightbox link label from `data-more` |
| `web/css/public.css` | modify | `.mosaic a.proj`, `.shead .pdf-dl` |
| `views/paintings/_form.php`, `views/paintings/index.php`, `web/css/admin.css` | modify | Type radio, "Project" badge |
| `views/photos/manage.php`, `controllers/PhotosController.php` | modify | "In portfolio" checkbox and its save step |
| `messages/ru/admin.php` | modify | Russian admin strings |
| `assets/fonts/Jost-Regular.ttf`, `Jost-Medium.ttf`, `OFL.txt` | create | PDF font |
| `helpers/PortfolioDocument.php` | create | tFPDF subclass: page footer |
| `helpers/PortfolioPdf.php` | create | Manifest, cache, build |
| `commands/PortfolioController.php` | create | `php yii portfolio/build [slug]` smoke test |
| `controllers/PortfolioController.php` | create | Public `/portfolio/<slug>.pdf` |
| `config/web.php` | modify | URL rule |
| `web/robots.txt` | modify | `Disallow: /portfolio/` |
| `docs/DEPLOY.md` | modify | Schema updates, vendor re-upload, checks |
| `config/params.php` | modify | `buildVersion` bump |

---

### Task 0: Local copy of production data (prerequisite, partly manual)

Everything below needs real works and photos locally.

**Files:** none committed.

- [ ] **Step 1: Owner exports the production DB.** konsoleH → phpMyAdmin → production DB → Export → Quick, SQL → save as `oskina_art_prod.sql` somewhere outside the repo (e.g. `G:\ClaudeCowork\oskina_art\`). *(Owner action — ask for the file path.)*

- [ ] **Step 2: Start OSPanel** (tray icon → Start; icon turns green). Check MySQL answers within a few seconds:

```bash
MYSQL=/g/OSPanel/modules/MySQL-8.0/bin/mysql.exe
P=$(grep DB_PASSWORD .env | cut -d= -f2-)
timeout 10 $MYSQL -h MySQL-8.0 -u root -p"$P" -e "select 1"
```
Expected: a one-row table with `1`. If it hangs/times out, the MySQL module is not running — start it in OSPanel before continuing.

- [ ] **Step 3: Import the dump into the local `oskina_art` DB** (replaces the empty local DB):

```bash
$MYSQL -h MySQL-8.0 -u root -p"$P" -e "DROP DATABASE IF EXISTS oskina_art; CREATE DATABASE oskina_art CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art < /g/ClaudeCowork/oskina_art/oskina_art_prod.sql
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "select count(*) works from paintings; select count(*) photos from photos"
```
Expected: non-zero counts.

- [ ] **Step 4: Fetch the public image derivatives from production** (the masters in `/original` are blocked and not needed):

```bash
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -N -e "select filename from photos" | tr -d '\r' | while read f; do
  w="${f%.*}.webp"
  for d in original_site preview thumb_squared; do
    mkdir -p "web/paintings_photo/$d"
    [ -f "web/paintings_photo/$d/$w" ] || curl -sf -o "web/paintings_photo/$d/$w" "https://katiaoskina.com/paintings_photo/$d/$w" || echo "missing: $d/$w"
  done
done
ls web/paintings_photo/original_site | wc -l
```
Expected: the count is about equal to the photo count from Step 3; a few `missing:` lines are acceptable (they will show up as skipped photos later). `Img::webp()` maps `x.jpg` → `x.webp`; confirm with `grep -n "function webp" -A6 helpers/Img.php` and adjust the `w=` line if it differs.

- [ ] **Step 5: Open `https://katiaoskina.local/commercial-illustrations`** and confirm the mosaic shows images. No commit.

---

### Task 1: Make console commands reachable

`config/console.php` sets `controllerNamespace => 'app\commands'`, which Yii resolves to `@app/commands`, but the three command files live in `config/commands/`, so `php yii user/create` (documented in `docs/00-START-HERE.md`) and the new smoke command cannot run.

**Files:**
- Move: `config/commands/{HelloController,ImageController,UserController}.php` → `commands/`

**Interfaces:**
- Produces: working `php yii <command>` for classes in `app\commands` (Task 6 adds `portfolio/build`).

- [ ] **Step 1: Confirm the failure**

Run: `$PHP yii help | grep -E "^- (user|image|hello)"`
Expected: no output.

- [ ] **Step 2: Move the files**

```bash
git mv config/commands commands
```

- [ ] **Step 3: Verify**

Run: `$PHP yii help | grep -E "^- (user|image|hello)"`
Expected: three lines, `- hello`, `- image`, `- user`.

- [ ] **Step 4: Commit**

```bash
git commit -m "fix(console): move commands to commands/ so app\\commands resolves"
```

---

### Task 2: Schema and model support

**Files:**
- Create: `migrations/m260918_120000_add_display_type_and_in_portfolio.php`
- Create: `docs/sql/2026-09-18-display-type-and-portfolio.sql`
- Modify: `models/Paintings.php`, `models/Photos.php`

**Interfaces:**
- Produces:
  - `Paintings::TYPE_ARTWORK = 'artwork'`, `Paintings::TYPE_PROJECT = 'project'`
  - `Paintings::displayTypes(): array` (type ⇒ admin label)
  - `Paintings::isProject(): bool` (false when the column is missing)
  - `Paintings::portfolioPhotos(): Photos[]` (ticked photos by `sort_order, id`; none ticked ⇒ `[cover]`; no photos ⇒ `[]`)
  - `Paintings::findForSectionMosaic(int $sectionId): \yii\db\ActiveQuery` (visible, in section, not in any series, `sort_order, id`, eager-loads `photos`, `mainPhoto`, `materialsToPaintings.material`, `ground`)

- [ ] **Step 1: Write the migration**

`migrations/m260918_120000_add_display_type_and_in_portfolio.php`:

```php
<?php

use yii\db\Migration;

/**
 * Projects and PDF portfolios
 * (docs/superpowers/specs/2026-09-18-projects-and-portfolio-design.md).
 *
 * paintings.display_type — 'artwork' opens in the lightbox, 'project' (a board
 * game, a picture book) opens straight to its own page. All existing works
 * stay artworks; the author switches the projects herself.
 *
 * photos.in_portfolio — the photos of a work that go into the section PDF.
 * Deliberately NOT back-filled: with nothing ticked the PDF uses the work's
 * cover (Paintings::portfolioPhotos()), and that keeps following the cover if
 * the author changes it later. A back-fill would freeze today's cover.
 *
 * Production has no console: docs/sql/2026-09-18-display-type-and-portfolio.sql
 * is the same change for phpMyAdmin. Keep the two in sync.
 */
class m260918_120000_add_display_type_and_in_portfolio extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%paintings}}',
            'display_type',
            $this->string(16)->notNull()->defaultValue('artwork')->comment('artwork | project')
        );
        $this->addColumn(
            '{{%photos}}',
            'in_portfolio',
            $this->boolean()->notNull()->defaultValue(0)->comment('Goes into the section PDF portfolio')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%photos}}', 'in_portfolio');
        $this->dropColumn('{{%paintings}}', 'display_type');
    }
}
```

- [ ] **Step 2: Run it up, down, up**

```bash
$PHP yii migrate --interactive=0
$PHP yii migrate/down 1 --interactive=0
$PHP yii migrate --interactive=0
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "show columns from paintings like 'display_type'; show columns from photos like 'in_portfolio'; select display_type, count(*) from paintings group by display_type"
```
Expected: each command ends with `Migrated up/down successfully.`; the columns exist (`varchar(16)` default `artwork`, `tinyint(1)` default `0`); every painting is `artwork`.

- [ ] **Step 3: Write the phpMyAdmin SQL twin**

`docs/sql/2026-09-18-display-type-and-portfolio.sql`:

```sql
-- Same change as migrations/m260918_120000_add_display_type_and_in_portfolio.php,
-- for production (no console on Hetzner Webhosting S). Paste into
-- phpMyAdmin → SQL on the production database. Run ONCE.
-- The last statement records the migration so a future `yii migrate`
-- does not try to apply it again.

ALTER TABLE `paintings`
  ADD COLUMN `display_type` VARCHAR(16) NOT NULL DEFAULT 'artwork' COMMENT 'artwork | project';

ALTER TABLE `photos`
  ADD COLUMN `in_portfolio` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Goes into the section PDF portfolio';

INSERT INTO `migration` (`version`, `apply_time`)
  VALUES ('m260918_120000_add_display_type_and_in_portfolio', UNIX_TIMESTAMP());
```

- [ ] **Step 4: Verify the SQL twin against a scratch copy**

```bash
$MYSQL -h MySQL-8.0 -u root -p"$P" -e "DROP DATABASE IF EXISTS oskina_sqlcheck; CREATE DATABASE oskina_sqlcheck"
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_sqlcheck < /g/ClaudeCowork/oskina_art/oskina_art_prod.sql
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_sqlcheck < docs/sql/2026-09-18-display-type-and-portfolio.sql
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_sqlcheck -e "show columns from paintings like 'display_type'; show columns from photos like 'in_portfolio'; select version from migration order by apply_time desc limit 1"
$MYSQL -h MySQL-8.0 -u root -p"$P" -e "DROP DATABASE oskina_sqlcheck"
```
Expected: both columns shown identically to Step 2; last migration is `m260918_120000_add_display_type_and_in_portfolio`.

- [ ] **Step 5: Extend `models/Paintings.php`**

Add `use yii\db\ActiveQuery;` to the imports. Below the `STATUS_*` constants add:

```php
    // How a work opens from the section mosaic (column `display_type`).
    const TYPE_ARTWORK = 'artwork'; // lightbox; "Read more" / "All photos" link to its page
    const TYPE_PROJECT = 'project'; // straight to its own page (board game, picture book…)
```

In `rules()`, after the `status` block:

```php
        if ($this->hasAttribute('display_type')) {
            $rules[] = [['display_type'], 'default', 'value' => self::TYPE_ARTWORK];
            $rules[] = [['display_type'], 'in', 'range' => array_keys(self::displayTypes())];
        }
```

In `attributeLabels()` add `'display_type' => 'Тип',`.

After `statuses()` add:

```php
    /**
     * Display types → admin labels (radio in the work form).
     *
     * @return array type const => label
     */
    public static function displayTypes()
    {
        return [
            self::TYPE_ARTWORK => Yii::t('admin', 'Artwork'),
            self::TYPE_PROJECT => Yii::t('admin', 'Project'),
        ];
    }

    /** True when the work opens straight to its own page (false pre-migration). */
    public function isProject(): bool
    {
        return $this->hasAttribute('display_type') && $this->display_type === self::TYPE_PROJECT;
    }

    /**
     * Photos that go into the section PDF portfolio, in page order.
     *
     * The ticked ones (photos.in_portfolio) if any; otherwise just the cover
     * (main photo, else the first). This fallback is what guarantees every
     * work in a section appears in its PDF, and it is why the migration does
     * not back-fill the flag. Empty only when the work has no photos at all.
     *
     * @return Photos[]
     */
    public function portfolioPhotos(): array
    {
        $photos = $this->photos;
        if (!$photos) {
            return [];
        }
        usort($photos, function ($a, $b) {
            return [(int) $a->sort_order, (int) $a->id] <=> [(int) $b->sort_order, (int) $b->id];
        });

        $ticked = array_values(array_filter($photos, function ($p) {
            return $p->hasAttribute('in_portfolio') && (int) $p->in_portfolio === 1;
        }));
        if ($ticked) {
            return $ticked;
        }
        foreach ($photos as $p) {
            if ((int) $p->isMain === 1) {
                return [$p];
            }
        }
        return [$photos[0]];
    }

    /**
     * The works shown in a section's mosaic: visible, in the section, not part
     * of any series, in manual order. Shared by the section page and its PDF
     * portfolio so the two can never list different works.
     */
    public static function findForSectionMosaic(int $sectionId): ActiveQuery
    {
        return static::find()
            ->where(['section_id' => $sectionId, 'isVisible' => 1])
            ->andWhere(['not in', 'id', PaintingsToSeries::find()->select('painting_id')])
            ->with(['photos', 'mainPhoto', 'materialsToPaintings.material', 'ground'])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }
```

- [ ] **Step 6: Extend `models/Photos.php`**

In `attributeLabels()` add `'in_portfolio' => 'В портфолио',`. (No validation rule: the flag is only written with `save(false, ['in_portfolio'])`, and a rule on a missing column would break uploads before the migration runs.)

- [ ] **Step 7: Smoke-check the model code**

A throwaway script in `runtime/` (git-ignored via `runtime/.gitignore`), so nothing is committed:

```bash
cat > runtime/check_models.php <<'EOF'
<?php
require 'vendor/autoload.php';
(\Dotenv\Dotenv::createImmutable(getcwd()))->safeLoad();
define('YII_DEBUG', true); define('YII_ENV', 'dev');
require 'vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require 'config/console.php');
use app\models\{Paintings, Sections};
foreach (Sections::find()->orderBy('sort')->all() as $s) {
    $works = Paintings::findForSectionMosaic($s->id)->all();
    $pf = 0; foreach ($works as $w) { $pf += count($w->portfolioPhotos()); }
    printf("%-26s works=%-3d portfolioPhotos=%-3d projects=%d\n", $s->slug, count($works), $pf,
        count(array_filter($works, fn($w) => $w->isProject())));
}
EOF
$PHP runtime/check_models.php
```
Expected: one line per section; `portfolioPhotos` equals `works` minus works without any photo (nothing is ticked yet, so each work contributes its cover); `projects=0`.

- [ ] **Step 8: Commit**

```bash
git add migrations/m260918_120000_add_display_type_and_in_portfolio.php docs/sql/2026-09-18-display-type-and-portfolio.sql models/Paintings.php models/Photos.php
git commit -m "feat(model): display_type and in_portfolio, shared section mosaic query"
```

---

### Task 3: Artworks — reach every photo from the lightbox (item 1)

**Files:**
- Modify: `helpers/PaintingPresenter.php`, `controllers/SiteController.php:91-122`, `views/site/section.php` (mosaic loop), `views/paintings/work.php` (meta line), `web/js/public.js` (`caption()`), `config/params.php`

**Interfaces:**
- Consumes: `Paintings::findForSectionMosaic()` (Task 2).
- Produces (all `public static` on `PaintingPresenter`):
  - `metaLine(Paintings $p): string` — `"materials · ground · year · size"`, empty parts dropped
  - `photoCount(Paintings $p): int`
  - `hasOwnPage(Paintings $p): bool` — description or more than one photo
  - `moreLabel(Paintings $p): string` — `'Read more →'` or `'All photos (N) →'`
  - `projectLabel(Paintings $p): string` — `'Project · N images'` or `'Project'` (used in Task 4)

- [ ] **Step 1: Record the current behaviour** (pick a work with several photos and no description, if any):

```bash
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "
select p.id, s.slug, count(ph.id) n, (coalesce(p.description_en,'')='' and coalesce(p.description,'')='') no_desc
from paintings p join sections s on s.id=p.section_id join photos ph on ph.painting_id=p.id
where p.isVisible=1 group by p.id having n>1 order by no_desc desc, n desc limit 5"
```
Note an `id`/`slug` with `no_desc=1` (call it `$ID` / `$SLUG`). If none has `no_desc=1`, blank one locally: `update paintings set description=NULL, description_en=NULL where id=<a multi-photo id>` (local DB only).

Run: `curl -sk https://katiaoskina.local/$SLUG | grep -o "data-url=\"/work/$ID\""`
Expected now: no output (the bug).

- [ ] **Step 2: Add presenter helpers** to `helpers/PaintingPresenter.php`, after `sizeLabel()`:

```php
    /**
     * "Materials · ground · year · size", skipping empty parts. Used by the
     * work page header and the PDF portfolio captions.
     */
    public static function metaLine(Paintings $painting): string
    {
        return implode(' · ', array_filter([
            self::materialsLabel($painting),
            self::groundLabel($painting),
            self::yearLabel($painting),
            self::sizeLabel($painting),
        ]));
    }

    /** Number of photos of a work (uses the eager-loaded relation when present). */
    public static function photoCount(Paintings $painting): int
    {
        return count($painting->photos);
    }

    /**
     * Whether an artwork's lightbox should link to its own page: there is more
     * to see there than the viewer shows — a description, or extra photos.
     */
    public static function hasOwnPage(Paintings $painting): bool
    {
        return self::descPlain($painting) !== '' || self::photoCount($painting) > 1;
    }

    /** Lightbox link text for an artwork that hasOwnPage(). */
    public static function moreLabel(Paintings $painting): string
    {
        if (self::descPlain($painting) !== '') {
            return 'Read more →';
        }
        return 'All photos (' . self::photoCount($painting) . ') →';
    }

    /** Second hover line on a project tile, e.g. "Project · 8 images". */
    public static function projectLabel(Paintings $painting): string
    {
        $n = self::photoCount($painting);
        return $n > 1 ? "Project · {$n} images" : 'Project';
    }
```

- [ ] **Step 3: Use the shared query in `SiteController::renderSection()`**

Replace

```php
        $loosePaintingIds = PaintingsToSeries::find()->select('painting_id');
        $paintings = Paintings::find()
            ->where(['section_id' => $section->id, 'isVisible' => 1])
            ->andWhere(['not in', 'id', $loosePaintingIds])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
```

with

```php
        // Same query as the section PDF (PortfolioController), so the page and
        // the download always list the same works. Eager-loads photos too.
        $paintings = Paintings::findForSectionMosaic($section->id)->all();
```

Then `grep -n "PaintingsToSeries" controllers/SiteController.php`; if the only remaining hit is the `use` line, delete it.

- [ ] **Step 4: Update the mosaic `<figure>` in `views/site/section.php`**

Replace the block from `$hasDesc = PaintingPresenter::descPlain($p) !== '';` through the opening `<figure ...>` tag with:

```php
            // The lightbox links to the work's own page when there is more to
            // see there than the viewer shows: a description or extra photos.
            // data-more carries the link text ("Read more" / "All photos (N)").
            $workUrl = PaintingPresenter::hasOwnPage($p) ? Url::to(['/paintings/work', 'id' => $p->id]) : '';
            if (!$sm) {
                continue;
            }
            ?>
            <?php $name = $p->tr('name', true); ?>
            <figure data-full="<?= Html::encode($lg) ?>" data-title="<?= Html::encode($name) ?>" data-mat="<?= Html::encode($mat) ?>" data-ground="<?= Html::encode($ground) ?>" data-year="<?= Html::encode($year) ?>" data-size="<?= Html::encode($size) ?>"<?= $workUrl ? ' data-url="' . Html::encode($workUrl) . '" data-more="' . Html::encode(PaintingPresenter::moreLabel($p)) . '"' : '' ?>>
```

(The old comment about "Read more" above `$hasDesc` goes too.)

- [ ] **Step 5: Use the label in `web/js/public.js`** — in `caption(f)` replace

```js
    if(u) h+="<a class='cl' href='"+esc(u)+"'>Read more →</a>";
```

with

```js
    // Link text comes from the page: "Read more →" or "All photos (N) →".
    if(u) h+="<a class='cl' href='"+esc(u)+"'>"+esc(f.getAttribute('data-more')||'Read more →')+"</a>";
```

and update the comment above it to say the link appears "when the work has a description or extra photos".

- [ ] **Step 6: Use `metaLine()` in `views/paintings/work.php`** — replace

```php
$mat = PaintingPresenter::materialsLabel($painting);
$ground = PaintingPresenter::groundLabel($painting);
$year = PaintingPresenter::yearLabel($painting);
$size = PaintingPresenter::sizeLabel($painting);
$metaLine = implode(' · ', array_filter([$mat, $ground, $year, $size]));
```

with `$metaLine = PaintingPresenter::metaLine($painting);`. Then `grep -n '\$mat\b\|\$ground\b\|\$year\b\|\$size\b' views/paintings/work.php` must return nothing.

- [ ] **Step 7: Bump `buildVersion`** in `config/params.php` by 1.

- [ ] **Step 8: Verify**

```bash
curl -sk https://katiaoskina.local/$SLUG | grep -oE "data-url=\"/work/$ID\" data-more=\"[^\"]+\""
curl -sk https://katiaoskina.local/work/$ID | grep -c '<h1>'
curl -sk https://katiaoskina.local/ | grep -c 'data-more="Read more'
```
Expected: `data-url="/work/$ID" data-more="All photos (N) →"` with the real N; `1`; a count ≥ 1 (works with descriptions still say Read more).
Manual (browser): open `$SLUG`, click that tile, the lightbox shows "All photos (N) →", clicking it opens the work page with all N photos.

- [ ] **Step 9: Commit**

```bash
git add helpers/PaintingPresenter.php controllers/SiteController.php views/site/section.php views/paintings/work.php web/js/public.js config/params.php
git commit -m "feat(public): link multi-photo works to their page from the lightbox"
```

---

### Task 4: Projects open straight to their page (item 3)

**Files:**
- Modify: `views/paintings/_form.php` (after the `isVisible` field, line ~172), `views/paintings/index.php` (name_en cell, line ~155), `web/css/admin.css` (after `.pill.status-*`), `messages/ru/admin.php`, `views/site/section.php` (mosaic loop), `web/css/public.css` (mosaic rules + the two mobile media blocks), `config/params.php`

**Interfaces:**
- Consumes: `Paintings::isProject()`, `Paintings::displayTypes()` (Task 2), `PaintingPresenter::projectLabel()`, `PaintingPresenter::photoUrl()` (Task 3 / existing).

- [ ] **Step 1: Check the Russian keys are new**

Run: `grep -n "'Artwork'\|'Project'\|'Type'\|A project opens" messages/ru/admin.php`
Expected: no output. (If `'Type'` exists, reuse it and skip adding it below.)

- [ ] **Step 2: Add Russian strings** to `messages/ru/admin.php` before the closing `];`:

```php
    // Projects & PDF portfolio
    'Type' => 'Тип',
    'Artwork' => 'Картина',
    'Project' => 'Проект',
    'A project opens straight to its own page instead of the lightbox.' => 'Проект открывается сразу своей страницей, а не в просмотрщике.',
```

- [ ] **Step 3: Type radio in `views/paintings/_form.php`** — directly after the `isVisible` field line add:

```php
        <?php if ($model->hasAttribute('display_type')): ?>
            <?= $form->field($model, 'display_type')->radioList(Paintings::displayTypes())
                ->label(Yii::t('admin', 'Type'))
                ->hint(Yii::t('admin', 'A project opens straight to its own page instead of the lightbox.')) ?>
        <?php endif; ?>
```

- [ ] **Step 4: "Project" badge in `views/paintings/index.php`** — replace the name_en cell

```php
            <td><?= $nameEn !== '' ? Html::encode($nameEn) : '<span style="color:var(--faint)">—</span>' ?></td>
```

with

```php
            <td><?= $nameEn !== '' ? Html::encode($nameEn) : '<span style="color:var(--faint)">—</span>' ?><?php if ($m->isProject()): ?> <span class="pill proj"><?= Yii::t('admin', 'Project') ?></span><?php endif; ?></td>
```

and add to `web/css/admin.css` after the `.pill.status-*` rules:

```css
.pill.proj{background:#f1e9e2;color:var(--accent)}
```

- [ ] **Step 5: Verify the admin side.** First record a baseline for Step 9 — pick the section of the board-game work you are about to switch (`$PSLUG`) and run `curl -sk https://katiaoskina.local/$PSLUG | grep -c '<figure data-full'` (note the number). Then in the browser, logged in: open a multi-photo board-game work → Edit → Type = Project → Save. The works list shows a "Проект" pill next to it (admin language RU) / "Project" (EN). Then:

```bash
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "select id, display_type from paintings where display_type='project'"
```
Expected: that work's id. Note it as `$PID`, its section as `$PSLUG`.

- [ ] **Step 6: Project tile in `views/site/section.php`** — inside `foreach ($paintings as $p)`, right after the existing `if (!$sm) { continue; }` / `$name = ...` lines and before the `<figure data-full=...>`, wrap the existing figure in an `if/else`:

```php
            <?php if ($p->isProject()): ?>
                <?php // A project (board game, picture book) is read as a whole: the
                      // tile is a plain link to its page. The figure has no
                      // data-full, so public.js leaves it out of the lightbox. ?>
                <a class="proj" href="<?= Url::to(['/paintings/work', 'id' => $p->id]) ?>">
                    <figure>
                        <img src="<?= Html::encode($sm) ?>" alt="<?= Html::encode($name) ?>" loading="lazy">
                        <figcaption class="hov">
                            <?php if ($name !== ''): ?><span class="t"><?= Html::encode($name) ?></span><?php endif; ?>
                            <span class="m"><?= Html::encode(PaintingPresenter::projectLabel($p)) ?></span>
                        </figcaption>
                    </figure>
                </a>
            <?php else: ?>
                … the existing <figure data-full=…> … </figure> block, unchanged …
            <?php endif; ?>
```

Move the `$lg / $mat / $ground / $year / $size / $workUrl` computations inside the `else` branch (they are not needed for projects), keeping `$sm`, the `continue`, and `$name` above the `if`.

- [ ] **Step 7: Styles in `web/css/public.css`** — after `.mosaic figure:hover .hov{opacity:1}` add:

```css
/* Project tiles: the figure sits inside a link, so the link takes over the
   column-break and spacing duties the bare figure has for artworks. */
.mosaic a.proj{display:block;break-inside:avoid;margin:0 0 20px;color:inherit;text-decoration:none}
.mosaic a.proj figure{margin:0;cursor:pointer}
.mosaic a.proj:focus-visible{outline:2px solid currentColor;outline-offset:3px}
.mosaic a.proj:focus-visible .hov{opacity:1}
```

In the phone-portrait media block (where `.mosaic figure{margin-bottom:14px}`) add `.mosaic a.proj{margin-bottom:14px}`; in the phone-landscape block (where `.mosaic figure{margin-bottom:12px}`) add `.mosaic a.proj{margin-bottom:12px}`.

- [ ] **Step 8: Bump `buildVersion`** in `config/params.php` by 1.

- [ ] **Step 9: Verify**

```bash
curl -sk https://katiaoskina.local/$PSLUG | grep -oE "<a class=\"proj\" href=\"/work/$PID\">"
curl -sk https://katiaoskina.local/$PSLUG | grep -oE "Project · [0-9]+ images" | head -1
curl -sk https://katiaoskina.local/$PSLUG | grep -c '<figure data-full'
```
Expected: the `<a class="proj" …>` tag; `Project · N images`; the `<figure data-full` count is exactly one less than the Step 5 baseline.
Manual (browser, desktop + phone width): hovering the project tile shows the title and "Project · N images"; clicking opens `/work/$PID` directly; the lightbox's prev/next on the same page skips the project; Tab reaches the tile and shows the outline + caption; on phone width tile spacing matches its neighbours.

- [ ] **Step 10: Commit**

```bash
git add views/paintings/_form.php views/paintings/index.php web/css/admin.css messages/ru/admin.php views/site/section.php web/css/public.css config/params.php
git commit -m "feat: projects open straight to their own page"
```

---

### Task 5: Admin — choose the portfolio photos

**Files:**
- Modify: `views/photos/manage.php` (tile controls, hint, CSS), `controllers/PhotosController.php::actionManage` (new step 4), `messages/ru/admin.php`

**Interfaces:**
- Consumes: `photos.in_portfolio` (Task 2). Produces: the POST field `portfolio_photo_ids[]`.

- [ ] **Step 1: Russian strings** — append to the "Projects & PDF portfolio" group in `messages/ru/admin.php`:

```php
    'In portfolio' => 'В портфолио',
    'Tick the photos that go into the section PDF portfolio. If nothing is ticked, the cover is used.' => 'Отметьте фото для PDF-портфолио раздела. Если ничего не отмечено, в PDF попадёт обложка.',
```

- [ ] **Step 2: Checkbox on each tile** in `views/photos/manage.php` — as the first child of `<div class="photo-sort-controls">`:

```php
                        <?php if ($photo->hasAttribute('in_portfolio')): ?>
                        <label class="pc-pf" title="<?= Yii::t('admin', 'In portfolio') ?>">
                            <?= Html::checkbox('portfolio_photo_ids[]', (int) $photo->in_portfolio === 1, ['value' => $photo->id]) ?>
                            <?= Yii::t('admin', 'In portfolio') ?>
                        </label>
                        <?php endif; ?>
```

After the existing hint `<p>` ("Drag the cards…") add:

```php
        <p style="color:var(--faint);font-size:12.5px;margin:-6px 0 12px">
            <?= Yii::t('admin', 'Tick the photos that go into the section PDF portfolio. If nothing is ticked, the cover is used.') ?>
        </p>
```

In the `$css` heredoc change `.photo-sort-controls{display:flex}` to `.photo-sort-controls{display:flex;flex-wrap:wrap}` and add:

```css
.photo-sort-controls .pc-pf{flex-basis:100%;background:rgba(46,84,62,.85)}
```

- [ ] **Step 3: Save step in `PhotosController::actionManage`** — after the "3) Cover choice" block and before the success flash:

```php
            // 4) Portfolio selection. Ticked photos go into the section PDF;
            // none ticked is valid and means "use the cover"
            // (Paintings::portfolioPhotos()). Skipped before the migration.
            if (Photos::getTableSchema()->getColumn('in_portfolio') !== null) {
                $portfolioIds = array_map('intval', (array) $req->post('portfolio_photo_ids', []));
                foreach (Photos::find()->where(['painting_id' => $painting_id])->all() as $p) {
                    $want = in_array((int) $p->id, $portfolioIds, true) ? 1 : 0;
                    if ((int) $p->in_portfolio !== $want) {
                        $p->in_portfolio = $want;
                        $p->save(false, ['in_portfolio']);
                    }
                }
            }
```

Also update the docblock line `POST → apply deletions, then the new order, then the cover choice.` to end with `…then the cover choice, then the portfolio ticks.`

- [ ] **Step 4: Verify** (browser, logged in): open `$PID` → Manage photos. Each tile shows a green "В портфолио" strip above Cover/Delete; none ticked. Tick photos 2 and 3, Save → flash "Photos updated." and they stay ticked after reload. Then:

```bash
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "select id, sort_order, isMain, in_portfolio from photos where painting_id=$PID order by sort_order"
```
Expected: exactly the two ticked rows have `in_portfolio=1`. Untick both, Save → all `0`. Also confirm drag-reorder and cover change still save in the same submit.

- [ ] **Step 5: Commit**

```bash
git add views/photos/manage.php controllers/PhotosController.php messages/ru/admin.php
git commit -m "feat(admin): pick which photos of a work go into the PDF portfolio"
```

---

### Task 6: PDF builder and its smoke command (item 2, core)

**Files:**
- Modify: `composer.json`, `composer.lock` (via composer)
- Create: `assets/fonts/Jost-Regular.ttf`, `assets/fonts/Jost-Medium.ttf`, `assets/fonts/OFL.txt`
- Create: `helpers/PortfolioDocument.php`, `helpers/PortfolioPdf.php`, `commands/PortfolioController.php`

**Interfaces:**
- Consumes: `Paintings::findForSectionMosaic()`, `Paintings::portfolioPhotos()` (Task 2); `PaintingPresenter::metaLine()` (Task 3); `Img::webp()` (existing).
- Produces (all `public static` on `app\helpers\PortfolioPdf`):
  - `get(Sections $section, Paintings[] $works): string` — absolute path to the cached PDF, building it when needed; always renders in English; throws on failure (nothing cached)
  - `hasContent(Paintings[] $works): bool`
  - `photoCount(Paintings[] $works): int` — total portfolio photos
  - `countPages(string $pdfPath): int`
  - `downloadName(Sections $section): string` — e.g. `Katia-Oskina-Commercial-illustrations.pdf` (call with the app language set to `en`)

- [ ] **Step 1: Add the dependency**

```bash
$PHP /g/OSPanel/modules/PHP-8.2/composer.phar require setasign/tfpdf:^1.33
grep -n "setasign/tfpdf" composer.json
```
Expected: `"setasign/tfpdf": "^1.33"` in `require`.

- [ ] **Step 2: Fetch the fonts** (Jost 400/500 with latin, latin-ext and cyrillic — material/ground names fall back to Russian when untranslated):

```bash
mkdir -p assets/fonts
for pair in 400:Regular 500:Medium; do
  url=$(curl -s "https://fonts.googleapis.com/css?family=Jost:${pair%%:*}&subset=latin,latin-ext,cyrillic" | grep -oE 'https://[^)]+\.ttf' | head -1)
  curl -sf -o "assets/fonts/Jost-${pair##*:}.ttf" "$url"
done
cp web/fonts/OFL.txt assets/fonts/OFL.txt
ls -la assets/fonts
```
Expected: two `.ttf` files of roughly 55–60 KB each (a ~25 KB file means the cyrillic subset was not included — re-check the URL) plus `OFL.txt`.

- [ ] **Step 3: Write `helpers/PortfolioDocument.php`**

```php
<?php

namespace app\helpers;

/**
 * tFPDF document for section portfolios: adds the page footer.
 * All layout lives in PortfolioPdf; this class only exists because FPDF
 * draws footers through an overridable Footer() hook.
 */
class PortfolioDocument extends \tFPDF
{
    /** Text before " · p. N"; set by PortfolioPdf. */
    public $footerText = '';

    public function Footer()
    {
        if ($this->PageNo() === 1) {
            return; // cover page stays clean
        }
        $this->SetY(-12);
        $this->SetFont('Jost', '', 8);
        $this->SetTextColor(150, 146, 142);
        $this->Cell(0, 6, $this->footerText . ' · p. ' . $this->PageNo(), 0, 0, 'C');
    }
}
```

- [ ] **Step 4: Write `helpers/PortfolioPdf.php`**

```php
<?php

namespace app\helpers;

use app\models\Paintings;
use app\models\Photos;
use app\models\Sections;
use Yii;
use yii\helpers\FileHelper;

/**
 * Section PDF portfolios: a cover page, then every work of the section's
 * mosaic, one photo per A4 landscape page, captioned under each work's first
 * photo. Spec: docs/superpowers/specs/2026-09-18-projects-and-portfolio-design.md
 *
 * Built on first request and cached under runtime/portfolio/. The filename
 * carries a hash of everything that affects the output (manifest()), so any
 * edit to the section's works yields a new file; older files for the section
 * are deleted after a successful build. Nothing has to be invalidated by hand.
 *
 * Sized for Hetzner Webhosting S (192 MB, 120 s): images come from the
 * ~1500 px original_site WebP derivatives, are converted to JPEG one at a
 * time (FPDF cannot read WebP) and freed immediately.
 */
class PortfolioPdf
{
    /** Bump whenever the layout changes, so cached PDFs are rebuilt. */
    const LAYOUT_VERSION = 1;

    const SITE_LABEL = 'katiaoskina.com';
    const JPEG_QUALITY = 85;

    // A4 landscape, millimetres.
    const PAGE_W = 297;
    const PAGE_H = 210;
    const MARGIN = 15;
    const FOOTER_H = 12;  // kept free at the bottom for the page footer
    const CAPTION_H = 16; // extra space under the first photo of each work

    /**
     * Path to the section's PDF, building it first if the cached copy is
     * missing or stale. Always rendered in English.
     *
     * @param Paintings[] $works from Paintings::findForSectionMosaic()
     * @throws \Throwable when the build fails (nothing is cached then)
     */
    public static function get(Sections $section, array $works): string
    {
        $prev = Yii::$app->language;
        Yii::$app->language = 'en';
        try {
            $key = sha1(json_encode(self::manifest($section, $works), JSON_UNESCAPED_UNICODE));
            $dir = Yii::getAlias('@runtime/portfolio');
            $path = $dir . '/' . $section->slug . '-' . $key . '.pdf';
            if (is_file($path)) {
                return $path;
            }

            FileHelper::createDirectory($dir);
            $tmp = $path . '.' . getmypid() . '.tmp';
            try {
                self::build($section, $works, $tmp);
            } catch (\Throwable $e) {
                @unlink($tmp);
                throw $e;
            }
            // A concurrent request may have finished the same build first;
            // the files are identical, so losing that race is harmless.
            if (!@rename($tmp, $path)) {
                @unlink($tmp);
                if (!is_file($path)) {
                    throw new \RuntimeException("Could not move the portfolio PDF into place: {$path}");
                }
            }
            self::pruneOld($dir, $section->slug, $path);
            return $path;
        } finally {
            Yii::$app->language = $prev;
        }
    }

    /** @param Paintings[] $works */
    public static function hasContent(array $works): bool
    {
        return self::photoCount($works) > 0;
    }

    /** @param Paintings[] $works */
    public static function photoCount(array $works): int
    {
        $n = 0;
        foreach ($works as $w) {
            $n += count($w->portfolioPhotos());
        }
        return $n;
    }

    public static function countPages(string $pdfPath): int
    {
        return (int) preg_match_all('#/Type\s*/Page(?!s)#', (string) file_get_contents($pdfPath));
    }

    /** e.g. "Katia-Oskina-Commercial-illustrations.pdf" (call with language 'en'). */
    public static function downloadName(Sections $section): string
    {
        $base = Yii::$app->params['siteName'] . ' ' . $section->tr('title');
        return trim(preg_replace('/[^A-Za-z0-9]+/', '-', $base), '-') . '.pdf';
    }

    /**
     * Everything that affects the PDF, in a canonical order. Its hash is the
     * cache key, so anything rendered must be derived from here or be a
     * constant covered by LAYOUT_VERSION.
     *
     * @param Paintings[] $works
     */
    private static function manifest(Sections $section, array $works): array
    {
        $items = [];
        foreach ($works as $w) {
            $photos = [];
            foreach ($w->portfolioPhotos() as $ph) {
                $photos[] = [(int) $ph->id, (string) $ph->filename];
            }
            if ($photos) {
                $items[] = [(int) $w->id, self::title($w), PaintingPresenter::metaLine($w), $photos];
            }
        }
        $p = Yii::$app->params;
        return [
            'layout' => self::LAYOUT_VERSION,
            'section' => (string) $section->tr('title'),
            'contact' => [$p['siteName'], $p['contactEmail'], $p['contactLocation']],
            'works' => $items,
        ];
    }

    /** @param Paintings[] $works */
    private static function build(Sections $section, array $works, string $dest): void
    {
        if (!defined('_SYSTEM_TTFONTS')) {
            // tFPDF looks for TTFs here. It also tries to write a metrics cache
            // next to its own font dir and silently skips that when it is not
            // writable (vendor/ on the host), so no extra writable dir is needed.
            define('_SYSTEM_TTFONTS', Yii::getAlias('@app/assets/fonts') . '/');
        }
        $params = Yii::$app->params;

        $pdf = new PortfolioDocument('L', 'mm', 'A4');
        $pdf->footerText = $params['siteName'] . ' · ' . self::SITE_LABEL;
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $pdf->AddFont('Jost', '', 'Jost-Regular.ttf', true);
        $pdf->AddFont('Jost', 'B', 'Jost-Medium.ttf', true);
        $pdf->SetTitle($params['siteName'] . ' — ' . $section->tr('title'), true);
        $pdf->SetAuthor($params['siteName'], true);

        self::coverPage($pdf, $section);

        $tmpDir = Yii::getAlias('@runtime/portfolio/tmp');
        FileHelper::createDirectory($tmpDir);
        foreach ($works as $w) {
            $first = true;
            foreach ($w->portfolioPhotos() as $photo) {
                $jpg = self::toJpeg($photo, $tmpDir);
                if ($jpg === null) {
                    Yii::warning("Portfolio: skipped photo #{$photo->id} of work #{$w->id} (missing or unreadable)", __METHOD__);
                    continue;
                }
                $pdf->AddPage();
                self::placeImage($pdf, $jpg, $first);
                if ($first) {
                    self::caption($pdf, $w);
                }
                @unlink($jpg); // FPDF has already read it inside Image()
                $first = false;
            }
        }

        $pdf->Output('F', $dest);
    }

    private static function coverPage(PortfolioDocument $pdf, Sections $section): void
    {
        $p = Yii::$app->params;
        $pdf->AddPage();

        $pdf->SetXY(self::MARGIN + 10, 72);
        $pdf->SetFont('Jost', '', 36);
        $pdf->SetTextColor(20, 18, 16);
        $pdf->Cell(0, 16, $p['siteName'], 0, 2);

        $pdf->SetFont('Jost', '', 18);
        $pdf->SetTextColor(90, 86, 82);
        $pdf->Cell(0, 11, (string) $section->tr('title'), 0, 2);

        $pdf->SetXY(self::MARGIN + 10, 150);
        $pdf->SetFont('Jost', '', 10.5);
        $pdf->SetTextColor(120, 116, 112);
        foreach ([self::SITE_LABEL, $p['contactEmail'], $p['contactLocation']] as $line) {
            $pdf->Cell(0, 6, $line, 0, 2);
        }
    }

    /** Fit the image into the page box (above the caption band if any), centred. */
    private static function placeImage(PortfolioDocument $pdf, string $jpg, bool $withCaption): void
    {
        [$pxW, $pxH] = getimagesize($jpg);
        $boxW = self::PAGE_W - 2 * self::MARGIN;
        $boxH = self::PAGE_H - self::MARGIN - self::FOOTER_H - ($withCaption ? self::CAPTION_H : 0);
        $scale = min($boxW / $pxW, $boxH / $pxH);
        $w = $pxW * $scale;
        $h = $pxH * $scale;
        $pdf->Image($jpg, (self::PAGE_W - $w) / 2, self::MARGIN + ($boxH - $h) / 2, $w, $h, 'JPG');
    }

    private static function caption(PortfolioDocument $pdf, Paintings $w): void
    {
        $pdf->SetXY(self::MARGIN, self::PAGE_H - self::FOOTER_H - self::CAPTION_H + 3);
        $title = self::title($w);
        if ($title !== '') {
            $pdf->SetFont('Jost', 'B', 11);
            $pdf->SetTextColor(20, 18, 16);
            $pdf->Cell(0, 6, $title, 0, 2, 'C');
        }
        $meta = PaintingPresenter::metaLine($w);
        if ($meta !== '') {
            $pdf->SetFont('Jost', '', 8.5);
            $pdf->SetTextColor(120, 116, 112);
            $pdf->Cell(0, 5, $meta, 0, 2, 'C');
        }
    }

    private static function title(Paintings $w): string
    {
        return trim((string) $w->tr('name', true));
    }

    /** original_site WebP → temporary JPEG; null if the source is missing or unreadable. */
    private static function toJpeg(Photos $photo, string $tmpDir): ?string
    {
        $src = Yii::getAlias('@app/web/paintings_photo/original_site/') . Img::webp($photo->filename);
        if (!is_file($src) || !function_exists('imagecreatefromwebp')) {
            return null;
        }
        $img = @imagecreatefromwebp($src);
        if (!$img) {
            return null;
        }
        $dst = $tmpDir . '/' . (int) $photo->id . '-' . getmypid() . '.jpg';
        $ok = imagejpeg($img, $dst, self::JPEG_QUALITY);
        imagedestroy($img);
        return $ok ? $dst : null;
    }

    private static function pruneOld(string $dir, string $slug, string $keep): void
    {
        $pattern = '/^' . preg_quote($slug, '/') . '-[0-9a-f]{40}\.pdf$/';
        foreach (glob($dir . '/*.pdf') ?: [] as $file) {
            if ($file !== $keep && preg_match($pattern, basename($file))) {
                @unlink($file);
            }
        }
    }
}
```

- [ ] **Step 5: Write the smoke command `commands/PortfolioController.php`**

```php
<?php

namespace app\commands;

use app\helpers\PortfolioPdf;
use app\models\Paintings;
use app\models\Sections;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Builds section PDF portfolios from the command line. Doubles as the smoke
 * test for app\helpers\PortfolioPdf (the repo has no automated test suite):
 * it fails when a PDF's page count is not 1 cover + one page per photo.
 *
 *   php yii portfolio/build                 every section
 *   php yii portfolio/build picturebooks    one section
 */
class PortfolioController extends Controller
{
    public function actionBuild($slug = null)
    {
        $query = Sections::find()->orderBy(['sort' => SORT_ASC]);
        if ($slug !== null) {
            $query->andWhere(['slug' => $slug]);
        }

        $failed = false;
        foreach ($query->all() as $section) {
            $works = Paintings::findForSectionMosaic($section->id)->all();
            if (!PortfolioPdf::hasContent($works)) {
                $this->stdout("{$section->slug}: no works with photos, skipped\n");
                continue;
            }
            $expected = 1 + PortfolioPdf::photoCount($works);
            $t = microtime(true);
            $path = PortfolioPdf::get($section, $works);
            $secs = microtime(true) - $t;
            $pages = PortfolioPdf::countPages($path);
            $ok = $pages === $expected;
            $failed = $failed || !$ok;
            $this->stdout(sprintf(
                "%s: %d pages, %.1f MB, %.1f s, peak %.0f MB — %s\n  %s\n",
                $section->slug,
                $pages,
                filesize($path) / 1048576,
                $secs,
                memory_get_peak_usage(true) / 1048576,
                $ok ? 'OK' : "FAIL (expected {$expected} pages)",
                $path
            ));
        }
        return $failed ? ExitCode::SOFTWARE : ExitCode::OK;
    }
}
```

- [ ] **Step 6: Run it — first build**

```bash
rm -rf runtime/portfolio
$PHP yii portfolio/build; echo "exit=$?"
```
Expected: one line per section ending `— OK`, each well under 60 s and peak well under 96 MB; `exit=0`. A `FAIL` with fewer pages than expected means photos were skipped — check `runtime/logs/app.log` for `Portfolio: skipped photo` and fetch the missing files (Task 0 Step 4).

- [ ] **Step 7: Cache hit and invalidation**

```bash
$PHP yii portfolio/build picturebooks          # cached: same path, ~0.0 s
ls runtime/portfolio/picturebooks-*.pdf
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "update paintings set name_en=concat(coalesce(name_en,''),' [x]') where id=(select id from (select p.id from paintings p join sections s on s.id=p.section_id where s.slug='picturebooks' and p.isVisible=1 order by p.sort_order, p.id limit 1) t)"
$PHP yii portfolio/build picturebooks          # rebuilt: new path
ls runtime/portfolio/picturebooks-*.pdf        # exactly one file, the new hash
$MYSQL -h MySQL-8.0 -u root -p"$P" oskina_art -e "update paintings set name_en=nullif(replace(name_en,' [x]',''),'') where name_en like '% [x]'"
```
Expected: first run reports ~0.0 s with the same path as Step 6; after the title edit the path's hash changes, the time is non-zero again, and only one `picturebooks-*.pdf` exists.

- [ ] **Step 8: Look at a PDF** — open `runtime/portfolio/commercial-illustrations-*.pdf` (or send it to the owner). Check: cover page text; one image per page, never cropped or overlapping the caption/footer; caption only on each work's first page; footer `Katia Oskina · katiaoskina.com · p. N` from page 2; Swedish/Spanish letters and any Russian material names render (no empty boxes).

- [ ] **Step 9: Commit**

```bash
git add composer.json composer.lock assets/fonts helpers/PortfolioDocument.php helpers/PortfolioPdf.php commands/PortfolioController.php
git commit -m "feat(portfolio): cached per-section PDF builder with console smoke test"
```

(`vendor/` is not tracked — confirm with `git check-ignore vendor` → prints `vendor`.)

---

### Task 7: Serve the PDF and link it from each section (item 2, web)

**Files:**
- Create: `controllers/PortfolioController.php`
- Modify: `config/web.php` (rules), `views/site/section.php` (header), `web/css/public.css`, `web/robots.txt`, `config/params.php`

**Interfaces:**
- Consumes: `Paintings::findForSectionMosaic()`, `PortfolioPdf::get()/hasContent()/downloadName()` (Tasks 2, 6).
- Produces: route `portfolio/section` ⇄ URL `/portfolio/<slug>.pdf`.

- [ ] **Step 1: Confirm the URL does not exist yet**

Run: `curl -sk -o /dev/null -w "%{http_code}\n" https://katiaoskina.local/portfolio/picturebooks.pdf`
Expected: `404`.

- [ ] **Step 2: Write `controllers/PortfolioController.php`**

```php
<?php

namespace app\controllers;

use app\helpers\PortfolioPdf;
use app\models\Paintings;
use app\models\Sections;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ServiceUnavailableHttpException;

/**
 * Public section PDF portfolio: /portfolio/<slug>.pdf. English only, whatever
 * page linked here. The PDF is built and cached by app\helpers\PortfolioPdf.
 */
class PortfolioController extends Controller
{
    public function actionSection($slug)
    {
        Yii::$app->language = 'en';

        $section = Sections::find()->where(['slug' => $slug])->one();
        if (!$section) {
            throw new NotFoundHttpException('The requested section does not exist.');
        }
        $works = Paintings::findForSectionMosaic($section->id)->all();
        if (!PortfolioPdf::hasContent($works)) {
            throw new NotFoundHttpException('This section has no portfolio yet.');
        }

        try {
            $path = PortfolioPdf::get($section, $works);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            throw new ServiceUnavailableHttpException('The portfolio could not be prepared right now. Please try again in a minute.');
        }

        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        return $response->sendFile($path, PortfolioPdf::downloadName($section), [
            'mimeType' => 'application/pdf',
            'inline' => true,
        ]);
    }
}
```

- [ ] **Step 3: URL rule** in `config/web.php`, directly after the `'work/<id:\d+>' => 'paintings/work',` rule (so it stays before the admin rules and the generic `<slug>` rule):

```php
                // Section PDF portfolio (built and cached on first request by
                // app\helpers\PortfolioPdf). Before the generic <slug> rule.
                'portfolio/<slug:[\w-]+>.pdf' => 'portfolio/section',
```

- [ ] **Step 4: Link in `views/site/section.php`** — add `use app\helpers\PortfolioPdf;` to the imports, and inside `<header class="shead">` after the intro `endif`:

```php
    <?php if (PortfolioPdf::hasContent($paintings)): ?>
        <a class="pdf-dl" href="<?= Url::to(['/portfolio/section', 'slug' => $section->slug, 'language' => 'en']) ?>" target="_blank" rel="noopener">Download portfolio (PDF)</a>
    <?php endif; ?>
```

- [ ] **Step 5: Style** — in `web/css/public.css` after the `.shead .meta` rule:

```css
.shead .pdf-dl{display:inline-block;margin-top:16px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);text-decoration:none;border-bottom:1px solid currentColor;padding-bottom:2px}
.shead .pdf-dl:hover,.shead .pdf-dl:focus-visible{color:inherit}
```

- [ ] **Step 6: robots** — in `web/robots.txt`, after the `Disallow: /series_cover/original/` line:

```
# Section PDF portfolios duplicate the section pages; keep crawlers off them
# (and off the on-demand PDF build).
Disallow: /portfolio/
```

- [ ] **Step 7: Bump `buildVersion`** in `config/params.php` by 1.

- [ ] **Step 8: Verify**

```bash
B=https://katiaoskina.local
curl -sk $B/picturebooks | grep -o 'href="/portfolio/picturebooks.pdf"'
curl -sk $B/ru/picturebooks | grep -o 'href="/portfolio/picturebooks.pdf"'
curl -sk -D - -o /tmp/p.pdf $B/portfolio/picturebooks.pdf | grep -iE "^(HTTP|content-type|content-disposition|cache-control)"
head -c 5 /tmp/p.pdf; echo
curl -sk -o /dev/null -w "%{http_code}\n" $B/portfolio/no-such-section.pdf
curl -sk $B/robots.txt | grep portfolio
```
Expected: the link on both the EN and `/ru/` page points to the un-prefixed EN URL; `HTTP/… 200`, `Content-Type: application/pdf`, `Content-Disposition: inline; filename="Katia-Oskina-Picturebooks.pdf"` (plus a `filename*=` variant), `Cache-Control: public, max-age=3600`; body starts `%PDF-`; unknown slug → `404`; robots line present.
Manual (browser): the link sits under the section intro in the site's small-caps style, opens the PDF in a new tab, and is keyboard-focusable.

- [ ] **Step 9: Commit**

```bash
git add controllers/PortfolioController.php config/web.php views/site/section.php web/css/public.css web/robots.txt config/params.php
git commit -m "feat(portfolio): download a section's PDF portfolio from its page"
```

---

### Task 8: Deploy documentation and final regression pass

**Files:**
- Modify: `docs/DEPLOY.md`

- [ ] **Step 1: Add a "Schema updates" section** to `docs/DEPLOY.md`, after section 4 "Импорт базы данных":

```markdown
## 4a. Обновления схемы БД (после первого деплоя)

Консоли на хостинге нет, поэтому каждая миграция из `migrations/` имеет
SQL-двойник в `docs/sql/`. Порядок при выкладке фичи с миграцией:

1. phpMyAdmin → production-база → вкладка **SQL** → вставить содержимое
   файла из `docs/sql/` → Go. Файл сам записывает миграцию в таблицу
   `migration`, так что повторно её никто не применит.
2. Только после этого — заливать код. (Код защищён проверками
   `hasAttribute()`, так что обратный порядок сайт не уронит, но новые
   функции заработают только после SQL.)

| Дата | Файл | Что делает |
|---|---|---|
| 2026-09-18 | `docs/sql/2026-09-18-display-type-and-portfolio.sql` | «Проект / Картина» у работ, отметка «В портфолио» у фото |
```

- [ ] **Step 2: Note the vendor re-upload** — in section 3 "Загрузка на хостинг", append to step 1:

```markdown
   **При добавлении Composer-зависимости** (например, `setasign/tfpdf` для
   PDF-портфолио, сентябрь 2026) `vendor/` нужно перезалить целиком: собрать
   локально `composer install --no-dev --optimize-autoloader` и загрузить
   папку поверх. Также заливается `assets/fonts/` (шрифты для PDF).
```

- [ ] **Step 3: Add PDF checks** to section 6 "Проверка после деплоя":

````markdown
**PDF-портфолио раздела.** Первый запрос собирает файл (до ~минуты), следующие
отдаются из кеша `runtime/portfolio/`:

```
curl -sI https://katiaoskina.com/portfolio/picturebooks.pdf | grep -iE "^(HTTP|content-type)"
```

Ждём `200` и `application/pdf`. `503` — сборка упала: смотри
`runtime/logs/app.log` (обычно память — тогда тариф M, или нет прав на
запись в `runtime/`).
````

- [ ] **Step 4: Regression pass on the local site** — every item must hold:

```bash
B=https://katiaoskina.local
for u in / /commercial-illustrations /picturebooks /sketchbooks /about /privacy /sitemap.xml /work/$ID /work/$PID; do
  printf "%-28s %s\n" "$u" "$(curl -sk -o /dev/null -w '%{http_code}' $B$u)"; done
curl -sk $B/sitemap.xml | grep -c portfolio
$PHP yii portfolio/build; echo "exit=$?"
```
Expected: every URL `200`; sitemap has `0` portfolio entries; all sections `OK`, `exit=0`.
Manual (browser, desktop + phone portrait + phone landscape): artwork lightbox still opens, swipes and closes; "Read more" / "All photos (N)" links work; project tiles go straight to the page; admin works list, edit form and Manage photos still save.

- [ ] **Step 5: Commit**

```bash
git add docs/DEPLOY.md
git commit -m "docs(deploy): schema-update procedure, vendor re-upload and PDF checks"
```

- [ ] **Step 6: Hand-off to the owner** — list for the production rollout (owner actions, in order): run the SQL in phpMyAdmin; upload code + `vendor/` + `assets/fonts/`; make sure `runtime/` is writable; switch the ~6 existing projects to "Project" in the admin; tick portfolio photos where the cover alone is not enough; run the DEPLOY.md section 6 PDF check.
