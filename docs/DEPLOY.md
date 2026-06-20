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

1. Залей **весь проект** в каталог сайта: код приложения, `vendor/` (68 МБ),
   `config/`, `.env` (продакшен-версию) и `web/` со всеми картинками
   `web/paintings_photo/...` и `web/series_cover/...` (~1.6 ГБ, займёт время —
   включи параллельные передачи в клиенте).
2. Проверь, что `.env` лежит в **корне проекта** (на уровень выше `web`).
3. **Права на запись** (chmod через SFTP) для папок, куда пишет приложение:
   - `runtime/` → 775
   - `web/assets/` → 775
   - `web/uploads/` → 775
   - `web/paintings_photo/` и все подпапки (`original`, `preview`, `thumb_squared`,
     `thumb_squared_small`, `thumb_tiny`, `original_site`) → 775
   - `web/series_cover/` и подпапки → 775

---

## 4. Импорт базы данных

1. konsoleH → phpMyAdmin → выбери созданную базу.
2. Вкладка **Import** → загрузи `oskina_art.sql` → Go.
3. Если дамп больше лимита загрузки phpMyAdmin — пожми его в .gz или импортируй по частям.

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
- [ ] Папки runtime/assets/paintings_photo доступны на запись (нет ошибок при загрузке)
- [ ] Форма обратной связи реально отправляет письмо (mailer переведён на SMTP)
- [ ] В исходниках нет следов dev: debug-панель и gii недоступны (YII_ENV=prod)

---

## Частые проблемы

- **Бесконечный редирект** — SSL ещё не активен, а YII_ENV уже prod. Включи сертификат.
- **500 / белый экран** — проверь права на `runtime/`, корректность `.env`, что `vendor/` залит полностью.
- **Картинка не грузится / ошибка памяти** — большое разрешение упёрлось в 192 МБ. Перейти на тариф M.
- **«broken» миниатюры** — у GD нет WebP. Включи ImageMagick в PHP Configuration.
- **Ошибка при загрузке >8–10 МБ** — не применился upload_max_filesize. Задай его в konsoleH PHP Configuration (не в .htaccess — на FPM php_value игнорируется).
