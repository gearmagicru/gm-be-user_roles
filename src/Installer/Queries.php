<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Файл конфигурации Карты SQL-запросов.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    'drop'   => ['{{role}}', '{{role_hierarchy}}'],
    'create' => [
        '{{role}}' => function () {
            return "CREATE TABLE `{{role}}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL DEFAULT '',
                `shortname` varchar(30) DEFAULT NULL,
                `description` varchar(255) DEFAULT NULL,
                `permission` text,
                `_updated_date` datetime DEFAULT NULL,
                `_updated_user` int(11) unsigned DEFAULT NULL,
                `_created_date` datetime DEFAULT NULL,
                `_created_user` int(11) unsigned DEFAULT NULL,
                `_lock` tinyint(1) unsigned DEFAULT '0',
                PRIMARY KEY (`id`)
                ) ENGINE={engine} 
                DEFAULT CHARSET={charset} COLLATE {collate}";
        },

        '{{role_hierarchy}}' => function () {
            return "CREATE TABLE `{{role_hierarchy}}` (
                `role_id` int(11) unsigned NOT NULL,
                `parent_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`parent_id`,`role_id`)
                ) ENGINE={engine} 
                DEFAULT CHARSET={charset} COLLATE {collate}";
        }
    ],

    'run' => [
        'install'   => ['drop', 'create'],
        'uninstall' => ['drop']
    ]
];