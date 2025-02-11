<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Файл конфигурации модуля.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    'translator' => [
        'locale'   => 'auto',
        'patterns' => [
            'text' => [
                'basePath' => __DIR__ . '/../lang',
                'pattern'  => 'text-%s.php'
            ]
        ],
        'autoload' => ['text'],
        'external' => [BACKEND]
    ],

    'accessRules' => [
        // для авторизованных пользователей Панели управления
        [ // разрешение "Полный доступ" (any: view, read, add, edit, delete, clear)
            'allow',
            'permission'  => 'any',
            'controllers' => [
                'RoleGrid'       => ['data', 'view', 'update', 'delete', 'clear', 'expand', 'filter'],
                'RoleForm'       => ['data', 'view', 'add', 'update', 'delete'],
                'ModulesGrid'    => ['data', 'view', 'update', 'clear'],
                'ExtensionsGrid' => ['data', 'view', 'update', 'clear'],
                'Trigger'        => ['combo'],
                'Search'         => ['data', 'view']
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Просмотр" (view)
            'allow',
            'permission'  => 'view',
            'controllers' => [
                'RoleGrid'       => ['data', 'view', 'expand', 'filter'],
                'RoleForm'       => ['data', 'view'],
                'ModulesGrid'    => ['data', 'view'],
                'ExtensionsGrid' => ['data', 'view'],
                'Trigger'        => ['combo'],
                'Search'         => ['data', 'view']
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Чтение" (read)
            'allow',
            'permission'  => 'read',
            'controllers' => [
                'RoleGrid'       => ['data'],
                'RoleForm'       => ['data'],
                'ModulesGrid'    => ['data'],
                'ExtensionsGrid' => ['data'],
                'Trigger'        => ['combo'],
                'Search'         => ['data']
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Добавление" (add)
            'allow',
            'permission'  => 'add',
            'controllers' => [
                'RoleForm' => ['add']
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Изменение" (edit)
            'allow',
            'permission'  => 'edit',
            'controllers' => [
                'RoleGrid'       => ['update'],
                'RoleForm'       => ['update'],
                'ModulesGrid'    => ['update'],
                'ExtensionsGrid' => ['update']
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Удаление" (delete)
            'allow',
            'permission'  => 'delete',
            'controllers' => [
                'RoleGrid' => ['delete'],
                'RoleForm' => ['delete'],
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Очистка" (clear)
            'allow',
            'permission'  => 'clear',
            'controllers' => [
                'RoleGrid'       => ['clear'],
                'PermissionGrid' => ['clear']
            ],
            'users' => ['@backend']
        ],
        [ // разрешение "Информация о модуле" (info)
            'allow',
            'permission'  => 'info',
            'controllers' => ['Info'],
            'users'       => ['@backend']
        ],
        [ // для всех остальных, доступа нет
            'deny',
            'users' => ['*']
        ]
    ],

    'dataManager' => [
        'settings' => [
            'permissions' => [
                'grid.owner_id'    => 'Доступ на уровне записей',
                'grid.log_columns' => '#'
            ]
        ],
    ],

    'viewManager' => [
        'id'          => 'gm-userroles-{name}',
        'useTheme'    => true,
        'useLocalize' => true,
        'viewMap'     => [
            // информации о модуле
            'info' => [
                'viewFile'      => '//backend/module-info.phtml', 
                'forceLocalize' => true
            ],
            'roleForm' => '/role-form.json',
            'rowInfo'  => '/role-row-info.phtml'
        ]
    ]
];
