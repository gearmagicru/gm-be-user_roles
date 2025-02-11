<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Пакет английской (британской) локализации.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    '{name}'        => 'Roles and user permissions',
    '{description}' => 'Accessibility to a group of users to manage system modules',
    '{permissions}' => [
        'any'    => ['Full access', 'Viewing and editing user roles and permissions'],
        'view'   => ['View', 'Viewing roles and user permissions'],
        'read'   => ['Reading', 'Reading roles and user permissions'],
        'add'    => ['Adding', 'Adding roles and user permissions'],
        'edit'   => ['Editing', 'Editing the permissions'],
        'delete' => ['Deleting', 'Deleting roles and user permissions'],
        'clear'  => ['Clear', 'Deleting all roles and user permissions']
    ],

    'Permissions' => 'Permissions',

    // RoleForm
    '{form.title}' => 'Add user role',
    '{form.titleTpl}' => 'Edit user role "{name}"',
    // RoleForm: поля
    'Name' => 'Name',
    'Shortname' => 'Shortname',
    'Description' => 'Description',
    'Parents' => 'Parents',
    // RoleForm: сообщения
    'Cannot inherit roles from itself' => 'Cannot inherit roles from itself',

    // RoleGrid: контекстное меню
    'Edit record' => 'Edit record',
    // RoleGrid: столбцы
    'Modules permissions' => 'Modules permissions',
    'Extensions permissions' => 'Extensions permissions',
    // RoleGrid: шаблон
    'Record ID' => 'Record ID',
    'Record Information' => 'Record Information',
    'No information to display' => 'No information to display',

    // ModulesGrid, ExtensionsGrid: панель инструментов
    'Are you sure you want to remove all permissions (access rights) for the current role?' 
        => 'Are you sure you want to remove all permissions (access rights) for the current role?',
    // ModulesGrid: столбцы
    'Resultant resolution' => 'Resultant resolution',
    'Module name / Permission' => 'Module name / Permission',
    // ModulesGrid: журнал аудита
    '{modules.title}' => 'Access rights of role "{0}" to modules',

    // ExtensionsGrid
    '{extensions.title}' => 'Access rights of the role "{0}" to extensions',
    // ExtensionsGrid: столбцы
    'Module name' => 'Module name',
    'Extension name / Permissions' => 'Extension name / Permissions',
    'Access' => 'Access',
    // ExtensionsGrid: журнал аудита
    '{view permissions action}' => 'view list of records «<b>{0}</b>»',
    'unknow' => 'неизвестно',
    
    // ModulesGridRow, ExtensionsGridRow: сообщения
    'Permission "{0}" for role "{1}"' => 'Permission &laquo;<b>{0}</b>&raquo; for role &laquo;<b>{1}</b>&raquo;<br>- {2}',
    'Permission to access' => 'Permission to access',
    '{accessible}' => ['<b>disabled</b>', '<b>enabled</b>'],

    // ModulesGrid, ExtensionsGrid: сообщения
    'Role ID missing' => 'Role ID missing!',
    'accessible permission {0} for role {1}' => 'accessible permission «<b>{0}</b>» for role «<b>{1}</b>»',
    'inaccessible permission {0} for role {1}' => 'inaccessible permission «<b>{0}</b>» for role «<b>{1}</b>»'
];
