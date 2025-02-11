<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Файл конфигурации установки модуля.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    'use'         => BACKEND,
    'id'          => 'gm.be.user_roles',
    'name'        => 'Roles and user permissions',
    'description' => 'Accessibility to a group of users to manage system modules',
    'namespace'   => 'Gm\Backend\UserRoles',
    'path'        => '/gm/gm.be.user_roles',
    'route'       => 'user-roles',
    'routes'      => [
        [
            'type'    => 'crudSegments',
            'options' => [
                'module'      => 'gm.be.user_roles',
                'route'       => 'user-roles',
                'prefix'      => BACKEND,
                'constraints' => ['id'],
                'childRoutes' => [
                    'modules' => [
                        'route'       => 'modules',
                        'constraints' => ['roleId'],
                        'defaults'    => [
                            'action'     => 'view',
                            'controller' => ['grid' => 'mgrid', 'default' => 'mgrid']
                        ]
                    ],
                    'extensions' => [
                        'route'       => 'extensions',
                        'constraints' => ['roleId'],
                        'defaults'    => [
                            'action'     => 'view',
                            'controller' => ['grid' => 'egrid', 'default' => 'egrid']
                        ]
                    ]
                ]
            ]
        ]
    ],
    'locales'     => ['ru_RU', 'en_GB'],
    'permissions' => ['any', 'view', 'read', 'add', 'edit', 'delete', 'clear', 'recordRls', 'viewAudit',  'writeAudit', 'info'],
    'events'      => [],
    'required'    => [
        ['php', 'version' => '8.2'],
        ['app', 'code' => 'GM MS'],
        ['app', 'code' => 'GM CMS'],
        ['app', 'code' => 'GM CRM'],
    ]
];
