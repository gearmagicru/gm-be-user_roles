<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Пакет русской локализации.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    '{name}'        => 'Роли и права доступа пользователей',
    '{description}' => 'Доступность пользователям управление модулями системы',
    '{permissions}' => [
        'any'    => ['Полный доступ', 'Просмотр и внесение изменений в роли и права доступа пользователей'],
        'view'   => ['Просмотр', 'Просмотр ролей и прав доступа пользователей'],
        'read'   => ['Чтение', 'Чтение ролей и прав доступа пользователей'],
        'add'    => ['Добавление', 'Добавление ролей и прав доступа пользователей'],
        'edit'   => ['Изменение', 'Изменение ролей и прав доступа пользователей'],
        'delete' => ['Удаление', 'Удаление ролей и прав доступа пользователей'],
        'clear'  => ['Очистка', 'Удаление всех ролей и прав доступа пользователей']
    ],

    'Permissions' => 'Разрешения',

    // RoleForm
    '{form.title}' => 'Создание роли',
    '{form.titleTpl}' => 'Изменение роли "{name}"',
    // RoleForm: поля
    'Name' => 'Название роли',
    'Shortname' => 'Сокращенное название',
    'Description' => 'Описание',
    'Parents' => 'Унаследован от ролей',
    // RoleForm: сообщения
    'Cannot inherit roles from itself' => 'Невозможно наследовать роли от самой себя',

    // RoleGrid: контекстное меню
    'Edit record' => 'Редактировать',
    // RoleGrid: столбцы
    'Modules permissions' => 'Права доступа к модулям',
    'Extensions permissions' => 'Права доступа к расширениям',
    // RoleGrid: шаблон
    'Record ID' => 'Идентификатор записи',
    'Record Information' => 'Информация о записи',
    'No information to display' => 'Нет информации для отображения',

    // ModulesGrid, ExtensionsGrid: панель инструментов
    'Are you sure you want to remove all permissions (access rights) for the current role?' 
        => 'Вы действительно хотите удалить все разрешения (права доступа) для текущей роли?',
    // ModulesGrid: столбцы
    'Resultant resolution' => 'Результирующие разрешение',
    'Module name / Permission' => 'Название модуля / Разрешение',
    // ModulesGrid: журнал аудита
    '{modules.title}' => 'Права доступа роли "{0}" к модулям',

    // ExtensionsGrid
    '{extensions.title}' => 'Права доступа роли "{0}" к расширениям',
    // ExtensionsGrid: столбцы
    'Module name' => 'Название модуля',
    'Extension name / Permissions' => 'Название расширения / Разрешение',
    'Access' => 'Доступ',
    // ExtensionsGrid: журнал аудита
    '{view permissions action}' => 'просмотр списка записей «<b>{0}</b>»',
    'unknow' => 'неизвестно',
    
    // ModulesGridRow, ExtensionsGridRow: сообщения
    'Permission "{0}" for role "{1}"' => 'Разрешение &laquo;<b>{0}</b>&raquo; для роли &laquo;<b>{1}</b>&raquo;<br>- {2}',
    'Permission to access' => 'Разрешение на доступ',
    '{accessible}' => ['<b>не доступно</b>', '<b>доступно</b>'],

    // ModulesGrid, ExtensionsGrid: сообщения
    'Role ID missing' => 'Не указан идентификатор роли!',
    'accessible permission {0} for role {1}' => 'открыл(а) доступ к разрешению «<b>{0}</b>» для роли «<b>{1}</b>»',
    'inaccessible permission {0} for role {1}' => 'закрыл(а) доступ к разрешению «<b>{0}</b>» для роли «<b>{1}</b>»'
];
