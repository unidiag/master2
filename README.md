# Master Mobile

Переписанная версия старого проекта учёта заявок. Работает с существующими таблицами без миграций:

- `master_zayavki`
- `master_podkluchki`
- `master_database`

## Требования

- PHP 7.4
- расширения PDO и pdo_mysql
- MariaDB/MySQL
- Apache или Nginx

## Установка

1. Скопировать `config.example.php` в `config.local.php`.
2. Указать параметры базы.
3. Создать хеш пароля:

```bash
php -r "echo password_hash('НовыйПароль', PASSWORD_DEFAULT), PHP_EOL;"
```

4. Вставить хеш в `auth.password_hash`.
5. Направить DocumentRoot на каталог проекта.

Проект не запускает SQL-миграции и не меняет структуру базы.

## Совместимые страницы

- `?module=zayavki`
- `?module=podkluchki`
- `?module=database`
- `?module=stat`
# master2
