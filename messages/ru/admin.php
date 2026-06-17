<?php

/**
 * Russian translations for the admin / archive panel (Phase 4b).
 * Source strings are authored in English in code (see config/web.php i18n
 * 'admin' category, sourceLanguage 'en'). Add new keys here as admin copy grows.
 */
return [
    // Chrome / navigation
    'Admin' => 'Админка',
    'Archive' => 'Архив',
    'Admin panel' => 'Панель управления',
    'Admin · Archive' => 'Архив · Админка',
    'Archive · Admin' => 'Архив · Админка',
    'Dashboard' => 'Обзор',
    'Works' => 'Картины',
    'Series' => 'Серии',
    'Sections' => 'Разделы',
    'Genres' => 'Жанры',
    'Grounds' => 'Основы',
    'View site' => 'Открыть сайт',
    'View site ↗' => 'Открыть сайт ↗',
    'Log out' => 'Выйти',
    'Manage' => 'Управление',

    // Dashboard
    '+ Add work' => '+ Добавить картину',
    '+ Add series' => '+ Добавить серию',
    '{v} shown · {h} hidden' => 'показано {v} · скрыто {h}',
    '{v} shown' => 'показано {v}',
    'navigation' => 'навигация',
    'published (archive hidden)' => 'опубликовано (архив скрыт)',
    'published' => 'опубликовано',

    // Archive toggle
    'Hide archive' => 'Скрыть архив',
    'When on, hidden (archived) works are excluded from lists and counts.'
        => 'Когда включено, скрытые (архивные) работы не показываются в списках и счётчиках.',

    // Login
    'Sign in' => 'Войти',
    'Username' => 'Логин',
    'Password' => 'Пароль',
    'Remember me' => 'Запомнить меня',

    // Sections manager
    'Navigation' => 'Навигация',
    '+ Add section' => '+ Добавить раздел',
    'Add section' => 'Добавить раздел',
    'Edit section' => 'Изменить раздел',
    'Sections are the top-level navigation of the public site. Order controls where each appears in the menu (lower = higher).'
        => 'Разделы — это верхний уровень навигации сайта. Порядок определяет место в меню (меньше — выше).',
    'Order' => 'Порядок',
    'Title' => 'Название',
    'Slug' => 'Slug',
    'Actions' => 'Действия',
    'Edit' => 'Изменить',
    'Delete' => 'Удалить',
    'Works in section' => 'Картины раздела',
    'Delete section "{name}"? Only possible if it has no works or series.'
        => 'Удалить раздел «{name}»? Возможно только если в нём нет картин и серий.',
    'Used in the section URL, e.g. "picturebooks". Latin letters, digits, hyphen.'
        => 'Используется в URL раздела, например «picturebooks». Латиница, цифры, дефис.',
    'Position in the navigation (lower = higher).' => 'Порядок раздела в навигации (меньше — выше).',
    'Intro text' => 'Вступительный текст',
    'Short intro shown under the section title on the site.'
        => 'Короткое вступление, показывается под заголовком раздела на сайте.',
    'Save' => 'Сохранить',
    'Cancel' => 'Отмена',
    'Section created.' => 'Раздел создан.',
    'Section saved.' => 'Раздел сохранён.',
    'Section deleted.' => 'Раздел удалён.',
    'Cannot delete section: it still has works or series. Move them to another section first.'
        => 'Нельзя удалить раздел: в нём есть картины или серии. Сначала перенесите их в другой раздел.',

    // Taxonomy (Genres / Grounds)
    'Taxonomy' => 'Справочники',
    '+ Add genre' => '+ Добавить жанр',
    '+ Add ground' => '+ Добавить основу',
    'Name (RU)' => 'Название (RU)',
    'Name (EN)' => 'Название (EN)',
    'Optional. Shown when the admin is in English; falls back to the Russian name.'
        => 'Необязательно. Показывается в английском режиме админки; иначе используется русское название.',
    'Delete this item?' => 'Удалить эту запись?',

    // Works list
    'Section' => 'Раздел',
    'Visibility' => 'Видимость',
    'Name' => 'Название',
    'Size' => 'Размер',
    'All' => 'Все',
    'Published' => 'Опубликованные',
    'Archived' => 'В архиве',
    'On site' => 'На сайте',
    'All sections' => 'Все разделы',
    'All series' => 'Все серии',
    'Search by name' => 'Поиск по названию',
    'Type and press Enter' => 'Введите и нажмите Enter',
    'Reset' => 'Сбросить',
    'Showing {n} of {t}' => 'Показано {n} из {t}',
    'Sorted by section order — use ↑/↓ to reorder (this is the order shown on the site).'
        => 'Отсортировано по порядку раздела — стрелками ↑/↓ можно менять порядок (именно так работы показаны на сайте).',
    'Pick a section above to reorder works with ↑/↓.' => 'Выберите раздел выше, чтобы менять порядок работ стрелками ↑/↓.',
    'Pick a section above to reorder series with ↑/↓.' => 'Выберите раздел выше, чтобы менять порядок серий стрелками ↑/↓.',
    'Details' => 'Детали',
    'Shown on the (English) site; falls back to Russian if empty.'
        => 'Показывается на (английском) сайте; если пусто — используется русский.',
    'With selected:' => 'С отмеченными:',
    'Show on site' => 'Показать на сайте',
    'Hide (archive)' => 'Скрыть (в архив)',
    'Move to section:' => 'Перенести в раздел:',
    '— choose —' => '— выбрать —',
    'Move' => 'Перенести',
    'Up' => 'Выше',
    'Down' => 'Ниже',
    'Photos' => 'Фото',
    'Cover' => 'Обложка',
    'Nothing here yet.' => 'Здесь пока пусто.',
    'Load more' => 'Загрузить ещё',
    '{n} work(s) updated.' => 'Обновлено работ: {n}.',
    '{n} work(s) moved to another section.' => 'Перенесено работ: {n}.',
    'Open ↗' => 'Открыть ↗',

    // Series editor
    'Add series' => 'Добавить серию',
    'Edit series' => 'Изменить серию',
    'Description' => 'Описание',
    'Shown on the series page. Allowed: bold/italic, paragraphs, lists, links.'
        => 'Показывается на странице серии. Допустимы: жирный/курсив, абзацы, списки, ссылки.',
    'Leave empty to keep the current cover.' => 'Оставьте пустым, чтобы сохранить текущую обложку.',
    'Order within the section (lower = higher). Can also be changed with ↑/↓ in the list.'
        => 'Порядок внутри раздела (меньше — выше). Можно менять стрелками ↑/↓ в списке.',
    'Visible on the site' => 'Показывать на сайте',

    // Photos
    'Add photos' => 'Добавить фото',
    'Choose cover' => 'Выбрать обложку',
    'Choose cover →' => 'Выбрать обложку →',
    'Upload photos, then choose which one is the cover.' => 'Загрузите фото, затем выберите обложку.',
    'Delete photos' => 'Удалить фото',
    'No photos yet — add some first.' => 'Фото пока нет — сначала добавьте.',
    'No photos yet.' => 'Фото пока нет.',
    'Back' => 'Назад',
    'Tick the photos you want to delete.' => 'Отметьте фото, которые хотите удалить.',
    'Delete selected' => 'Удалить выбранные',
    'Delete the selected photos?' => 'Удалить выбранные фото?',

    // Work editor form
    'Add work' => 'Добавить картину',
    'Edit work' => 'Картина',
    'Basic info' => 'Основное',
    'Shown on the series page, under this work. Allowed: bold/italic, paragraphs, lists, links.'
        => 'Показывается на странице серии, под этой работой. Допустимы: жирный/курсив, абзацы, списки, ссылки.',
    'Series…' => 'Серия…',
    'Dimensions' => 'Размеры',
    'Existing landscape size' => 'Готовый альбомный размер',
    '— pick a landscape size —' => '— выбрать альбомный размер —',
    'Existing portrait size' => 'Готовый портретный размер',
    '— pick a portrait size —' => '— выбрать портретный размер —',
    'OR' => 'ИЛИ',
    'OR enter exact size' => 'ИЛИ введите точный размер',
    'Width (cm)' => 'Ширина (см)',
    'Height (cm)' => 'Высота (см)',
    '1–1000' => 'от 1 до 1000',
    'Artistic details' => 'Художественная информация',
    'Genre…' => 'Жанр…',
    'Style…' => 'Стиль…',
    'Styles' => 'Стили',
    'Ground…' => 'Основа…',
    'Ground' => 'Основа',
    'Materials…' => 'Материалы…',
    'Materials' => 'Материалы',
    'Sale' => 'Продажа',
    'Status' => 'Статус',
    'Available' => 'В наличии',
    'Sold' => 'Продано',
    'Not available' => 'Нет в наличии',
    'All statuses' => 'Все статусы',
    'Set status:' => 'Изменить статус:',
    'Apply' => 'Применить',
    '{n} work(s) status updated.' => 'Обновлён статус работ: {n}.',
    'Availability of the original. Admin-only; not shown on the public site.'
        => 'Наличие оригинала. Только в админке; на сайте не показывается.',
    'Price' => 'Цена',
    'Shop link…' => 'Ссылка на магазин…',
    'Shop link' => 'Ссылка на магазин',
    'Date & place' => 'Дата и место',
    'Date created…' => 'Дата создания…',
    'Date created' => 'Дата создания',
    'Location' => 'Место',
    'Private notes (not shown on the site)' => 'Скрытые заметки (не видны на сайте)',
    'Notes' => 'Комментарии',
    'Material costs' => 'Затраты на материалы',
    'Time spent' => 'Затраты времени',
];
