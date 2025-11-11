Media manager for MoonShine 4
============================

Media manager в MoonShine.

### Поддержка версий MoonShine

| MoonShine | Пакет |
|-----------|-------|
| 2.0+      | 1.0+  |
| 3.0+      | 2.0+  |
| 4.0+      | 3.0+  |

## Скриншот

![screenshot](https://github.com/yurizoom/moonshine-media-manager/blob/main/blob/screenshot.png?raw=true)

## Установка

```
$ composer require yurizoom/moonshine-media-manager
```

## Настройка

Если необходимо изменить настройки, добавьте в файле config/moonshine.php:

```php
[
    'media-manager' => [
        // Автоматическое добавление в меню
        'auto_menu' => true,
        // Корневая директория
        'disk' => config('filesystem.default', 'public'),
        // Разрешенные для загрузки расширения файлов
        'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
        // Вид менеджера по-умолчанию
        'default_view' => 'table',
    ]
]
```

### Добавление в меню

Для того чтобы добавить меню в другое место, вставьте следующий код в app/MoonShine/Layouts/MoonShineLayout.php:
```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
    {
        return [
            ...
            
            MenuItem::make(new MediaManagerPage()),
            
            ...
        ];
    }
```

Лицензия
------------
[The MIT License (MIT)](LICENSE).
