# Image storage & WebP pipeline

## How images are stored

When a painting photo or a series cover is uploaded through the admin:

1. **The upload is validated.** Files larger than **15 MB** or with a longer side
   over **8000 px**, and non-image / unsupported formats, are rejected with a
   message to the author — nothing is saved.
2. **The master original is always JPG.** A JPG upload is kept byte-for-byte.
   A PNG (or any other accepted format) is converted to JPG (quality 92). The
   upload form warns about this.
3. **All derivatives are WebP** (quality 82), built from the master:

   | Folder | Size | Where it's used |
   |---|---|---|
   | `paintings_photo/original/` | full-res **JPG master** | "Download original" only, not shown on the site |
   | `paintings_photo/original_site/` | 2000 px WebP | lightbox / full view |
   | `paintings_photo/preview/` | 900 px WebP | gallery & mosaic |
   | `paintings_photo/thumb_squared/` | 700×700 WebP (padded) | admin grid, series cards |
   | `paintings_photo/thumb_tiny/` | 100×100 WebP (cropped) | mini icons |
   | `series_cover/original/` | JPG master | — |
   | `series_cover/thumb/` | 700×700 WebP | series cards |
   | `series_cover/<name>.webp` | 2000 px WebP | series hero |

The filename stored in the DB (`photos.filename`, `series.cover_filename`) is the
**JPG master** name, e.g. `Ab3kZ9x.jpg`. Derivative names are the same base with a
`.webp` extension — see `app\helpers\Img::webp()`. Tunable limits and quality live
in `app\helpers\Img`.

## "Download original" button

- **Works list** (`/paintings/index`): each row has an *Original* button that
  downloads the cover photo's master.
- **Choose-cover page** (`/photos/selectmain`) and **work page** (`/paintings/show`):
  a download link per photo.

Served by `PhotosController::actionDownloadOriginal()`, named after the work.

## One-time migration of existing images

Existing photos/covers are still JPG. Rebuild everything as WebP (and convert PNG
covers to JPG masters) with the console command — run it once from the project
root using the **same PHP that runs the site** (OSPanel locally, or over SSH on
the host):

```bash
php yii image/regenerate --dry=1   # preview: shows what would change, writes nothing
php yii image/regenerate           # do it
```

The command is idempotent (safe to re-run) and removes the old non-WebP
derivatives unless you pass `--keepOld=1`. Originals (masters) are never deleted.

## Hosting / environment requirement

WebP generation needs the active image library to support WebP:

- **GD**: PHP built with WebP support — check with
  `php -r 'var_dump(function_exists("imagewebp"));'` (expect `true`).
- **Imagick**: a `webp` delegate — `php -r '$i=new Imagick(); var_dump(in_array("WEBP", $i->queryFormats()));'`.

Both are standard on PHP 7.1+ and on the target shared host, but verify before
running the migration. If WebP is unavailable, uploads fail gracefully with an
error message and the regen command reports per-item errors instead of corrupting
anything.
