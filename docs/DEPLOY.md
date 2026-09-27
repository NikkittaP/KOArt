# Деплой на Hetzner Webhosting S — katiaoskina.com

Приложение: Yii2 basic, PHP 8.1+, MySQL, обработка картинок через Imagine (GD/Imagick c WebP).
Хостинг: Hetzner Webhosting S (Apache + mod_rewrite, MariaDB, phpMyAdmin, без SSH).

---

## 1. Настройка хостинга в konsoleH

1. **Привязать домен.** В konsoleH добавь/подключи `katiaoskina.com` к пакету Webhosting S.
   Раз домен зарегистрирован у Hetzner — DNS и A-запись на сервер настроятся автоматически.
   Добавь и `www.katiaoskina.com`.

2. **Document root → папка `web`.** В настройках домена укажи каталог сайта на подпапку
   `web` твоего проекта (например `/katiaoskina.com/web`). Это правильный и самый
   безопасный вариант: `.env` и `config/` остаются выше docroot и недоступны из браузера.
   Маршрутизацию внутри `web` делает `web/.htaccess` (он уже есть в проекте).
   _Если в konsoleH нельзя задать подпапку — залей проект в docroot как есть, корневой
   `.htaccess` сам перенаправит всё в `web/`._

3. **PHP версия:** выбери 8.2 или 8.3 для домена.

4. **PHP лимиты** (konsoleH → домен → PHP Configuration; на FPM это надёжнее, чем .htaccess):
   - `upload_max_filesize = 20M`  (этого хватит на твои файлы до 15 МБ; post_max_size Hetzner поднимет автоматически следом)
   - `max_execution_time = 120` (потолок тарифа S, хватает на обработку одного фото)
   - `memory_limit = 192M` — на S это фиксированный максимум, выше не поднять.
     Если на самых больших по разрешению картинках поймаешь «Allowed memory size exhausted» —
     это сигнал перейти на тариф M (256 МБ). Смена тарифа в konsoleH в пару кликов, без переноса данных.

5. **Расширение для картинок:** убедись, что GD собран с WebP (проверишь через phpinfo).
   Для подстраховки включи расширение **ImageMagick** в PHP Configuration.
   GD с WebP нужен не только для миниатюр, но и для карточек превью соцсетей
   (`app\helpers\OgImage`).

5a. **Модули Apache.** `web/.htaccess` включает сжатие, кеширование и
   security-заголовки. Нужны `mod_deflate` (или `mod_brotli`), `mod_expires`
   и `mod_headers` — на shared-хостинге Hetzner они стандартно включены.
   Все блоки обёрнуты в `<IfModule>`, поэтому отсутствие модуля не уронит
   сайт в 500 — просто заголовок не появится. Как проверить — см. раздел 6.

6. **База данных:** создай MySQL-базу и пользователя (konsoleH → Databases).
   Запиши: имя базы, имя пользователя, пароль, хост (обычно `localhost`).

7. **SSL:** включи бесплатный сертификат **Let's Encrypt** для домена и www.
   ВАЖНО: сделай это ДО переключения сайта в режим prod — в prod код принудительно
   редиректит на https, без сертификата будет цикл редиректов.

---

## 2. Подготовка файлов локально

1. **Собрать vendor без dev-зависимостей:**
   ```
   composer install --no-dev --optimize-autoloader
   ```
   (debug/gii не нужны в проде — они и так грузятся только при YII_ENV=dev.)

2. **Создать продакшен `.env`** в корне проекта (заменит локальный перед загрузкой):
   ```
   YII_DEBUG=false
   YII_ENV=prod

   DB_DSN=mysql:host=localhost;dbname=ИМЯ_БАЗЫ_HETZNER
   DB_USERNAME=ПОЛЬЗОВАТЕЛЬ_HETZNER
   DB_PASSWORD=ПАРОЛЬ_HETZNER

   COOKIE_VALIDATION_KEY=СГЕНЕРИРУЙ_НОВЫЙ
   ```
   Новый ключ: `php -r "echo bin2hex(random_bytes(16));"`

3. **Выгрузить локальную базу дампом** (в ней лежат записи о картинах/сериях — без них
   загруженные файлы не свяжутся):
   ```
   mysqldump -u root oskina_art > oskina_art.sql
   ```

4. **Почта — настраивать НЕ нужно.** Сайт использует только ссылки `mailto:`
   (подвал в `views/layouts/public.php` и кнопка на странице серии) — они открывают
   почтовую программу посетителя, сервер писем не отправляет. SMTP / App Password
   не требуются. Просто проверь, что адрес верный: `contactEmail` в `config/params.php`.
   На сервере оставь `MAIL_USE_FILE_TRANSPORT=true` (или не задавай эти переменные).

---

## 3. Загрузка на хостинг (SFTP)

На тарифе S нет SSH, поэтому распаковать архив на сервере нельзя — загружаем файлы как есть.
Используй SFTP-клиент (WinSCP / FileZilla) с данными доступа из konsoleH.

1. Залей **весь проект** в каталог сайта: код приложения, `vendor/` (~70 МБ+),
   `config/`, `.env` (продакшен-версию) и `web/` со всеми картинками
   `web/paintings_photo/...` и `web/series_cover/...` (~1.6 ГБ, займёт время —
   включи параллельные передачи в клиенте).

   **При добавлении Composer-зависимости** (например, `setasign/tfpdf` для
   PDF-портфолио, сентябрь 2026) обычно перезаливают `vendor/` целиком: собрать
   локально `composer install --no-dev --optimize-autoloader` и загрузить
   папку поверх. Для именно этого изменения достаточно залить `vendor/setasign/`
   и `vendor/composer/` (там автозагрузчик хранит карты классов) — полный
   перезалив `vendor/` тоже подойдёт. Также заливается `assets/fonts/`
   (шрифты для PDF).
2. Проверь, что `.env` лежит в **корне проекта** (на уровень выше `web`).
3. **Права на запись** (chmod через SFTP) для папок, куда пишет приложение:
   - `runtime/` → 775
   - `web/assets/` → 775
   - `web/uploads/` → 775
   - `web/paintings_photo/` и все подпапки (`original`, `preview`, `thumb_squared`,
     `thumb_squared_small`, `thumb_tiny`, `original_site`) → 775
   - `web/series_cover/` и подпапки → 775
   - `web/og_cache/` → 775 — сюда генерируются карточки превью для соцсетей.
     Папки может не быть: создай её вручную. Если прав на запись нет, сайт
     продолжит работать, но при шеринге ссылки на работу будет показываться
     общая карточка с логотипом вместо самой картины.

---

## 4. Импорт базы данных

1. konsoleH → phpMyAdmin → выбери созданную базу.
2. Вкладка **Import** → загрузи `oskina_art.sql` → Go.
3. Если дамп больше лимита загрузки phpMyAdmin — пожми его в .gz или импортируй по частям.

---

## 4a. Обновления схемы БД (после первого деплоя)

Консоли на хостинге нет, поэтому каждая миграция из `migrations/` имеет
SQL-двойник в `docs/sql/`. Порядок при выкладке фичи с миграцией:

1. phpMyAdmin → production-база → вкладка **SQL** → вставить содержимое
   файла из `docs/sql/` → Go. Файл сам записывает миграцию в таблицу
   `migration`, так что повторно её никто не применит.
2. Сразу после этого — очисти кеш схемы: удали по SFTP содержимое
   `runtime/cache/` (или просто подожди час). Причина: `config/db.php`
   держит `enableSchemaCache` включённым с `schemaCacheDuration => 3600`,
   и пока старый кеш схемы жив, `hasAttribute()` в коде до часа продолжает
   видеть старую структуру таблицы — новые поля выглядят отсутствующими,
   а, например, отметка «В портфолио» тихо не сохраняется.
3. Только после этого — заливать код. (Код защищён проверками
   `hasAttribute()`, так что обратный порядок сайт не уронит — но новые
   функции заработают только после того, как применён SQL И сброшен
   кеш схемы; без сброса кеша они молча не заработают до часа.)

| Дата | Файл | Что делает |
|---|---|---|
| 2026-09-18 | `docs/sql/2026-09-18-display-type-and-portfolio.sql` | «Проект / Картина» у работ, отметка «В портфолио» у фото |

---

## 5. Чек-лист «готово к публикации»

- [ ] Домен привязан, DNS указывает на Hetzner, открывается https://katiaoskina.com
- [ ] Document root указывает на `web` (или корневой .htaccess перенаправляет в web/)
- [ ] SSL Let's Encrypt активен, замок зелёный, http→https работает без цикла
- [ ] `.env`: YII_ENV=prod, YII_DEBUG=false, верные данные базы, новый COOKIE_VALIDATION_KEY
- [ ] База импортирована, в phpMyAdmin видны таблицы и записи
- [ ] Главная и галерея открываются, **картинки (webp) отображаются** → GD/Imagick с WebP ок
- [ ] Внутренние ссылки работают без `index.php` в URL (pretty URLs / mod_rewrite)
- [ ] Вход в админку, **пробная загрузка фото ~15 МБ проходит** → лимиты upload и память ок
- [ ] Папки runtime/assets/paintings_photo/og_cache доступны на запись
- [ ] В исходниках нет следов dev: debug-панель и gii недоступны (YII_ENV=prod)
- [ ] Прошёл раздел 6 «Проверка после деплоя» ниже
- [ ] После SQL-миграции из раздела 4a — очищен `runtime/cache/` (или прошёл час), иначе новые поля/функции до часа не видны из-за schema cache

---

## 6. Проверка после деплоя (SEO, скорость, соцсети)

Всё проверяется из командной строки, ничего ставить не надо.

**Сжатие и кеш.** Должны быть `Content-Encoding` и годичный `Cache-Control`:

```
curl -sI -H "Accept-Encoding: gzip,br" https://katiaoskina.com/css/public.css
```

Если `Content-Encoding` нет — на домене не включён `mod_deflate`/`mod_brotli`
(konsoleH → PHP/Apache). Если нет `Cache-Control: public, max-age=31536000` —
нет `mod_headers`.

**HTML не должен кешироваться.** Тут, наоборот, ждём `max-age=0`:

```
curl -sI https://katiaoskina.com/ | grep -i cache-control
```

**Канонический хост.** `www` обязан отдавать 301 на домен без `www`:

```
curl -sI https://www.katiaoskina.com/ | head -1
```

**Sitemap и robots:**

```
curl -s https://katiaoskina.com/sitemap.xml | head -5
curl -s https://katiaoskina.com/robots.txt
```

**PDF-портфолио раздела.** Первый запрос собирает файл (до ~минуты), следующие
отдаются из кеша `runtime/portfolio/`:

```
curl -sI https://katiaoskina.com/portfolio/picturebooks.pdf | grep -iE "^(HTTP|content-type)"
```

Ждём `200` и `application/pdf`. `503` — сборка упала: смотри
`runtime/logs/app.log` (обычно память — тогда тариф M, или нет прав на
запись в `runtime/`).

Браузер владельца может показывать старый PDF ещё до часа после правки
работы — контроллер отдаёт `Cache-Control: public, max-age=3600`. Чтобы
увидеть свежую версию сразу, сделай hard refresh или открой ссылку в
окне инкогнито.

**Карточки соцсетей.** Открой любую работу, найди `og:image` и скачай его —
должен быть JPEG 1200×630, а не 404:

```
curl -s https://katiaoskina.com/work/156 | grep 'og:image"'
```

Если 404 — у `web/og_cache/` нет прав на запись (см. раздел 3).
Живую проверку превью удобно делать в отладчиках Facebook и LinkedIn:
https://developers.facebook.com/tools/debug/ и
https://www.linkedin.com/post-inspector/

**Полные оригиналы закрыты.** Должно быть 404:

```
curl -so /dev/null -w "%{http_code}
" https://katiaoskina.com/paintings_photo/original/ЛЮБОЕ_ИМЯ.jpg
```

---

## Частые проблемы

- **Бесконечный редирект** — SSL ещё не активен, а YII_ENV уже prod. Включи сертификат.
- **500 / белый экран** — проверь права на `runtime/`, корректность `.env`, что `vendor/` залит полностью.
- **Картинка не грузится / ошибка памяти** — большое разрешение упёрлось в 192 МБ. Перейти на тариф M.
- **«broken» миниатюры** — у GD нет WebP. Включи ImageMagick в PHP Configuration.
- **Ошибка при загрузке >8–10 МБ** — не применился upload_max_filesize. Задай его в konsoleH PHP Configuration (не в .htaccess — на FPM php_value игнорируется).
