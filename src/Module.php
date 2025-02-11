<?php
/**
 * Модуль веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\UserRoles;

use Gm;

/**
 * Модуль ролей и прав доступа.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles
 * @since 1.0
 */
class Module extends \Gm\Panel\Module\Module
{
    /**
     * {@inheritdoc}
     */
    public string $id = 'gm.be.user_roles';

    /**
     * {@inheritdoc}
     */
    public string $defaultController = 'grid';

    /**
     * Значки разрешений.
     * 
     * @var array
     */
    protected array $permissionIcons = [
        'unlock' => 'permission-unlock.svg',
        'lock'   => 'permission-lock.svg',
        // особые разрешения
        'recordRls'  => 'permission-record-rls.svg', // доступ на уровне записей
        'viewAudit'  => 'permission-audit.svg', // просмотр аудита записей
        'writeAudit' => 'permission-log-write.svg', // запись действий в журнал аудита
        'info'       => 'permission-info.svg', // информация о модуле
        'settings'   => 'permission-settings.svg' // настройка модуля
    ];

    /**
     * Наименование особых разрешений (которые не наследуются).
     * 
     * @var array
     */
    protected array $specialPermissions = [
        'recordRls', 'viewAudit', 'writeAudit', 'info', 'settings'
    ];

    /**
     * {@inheritdoc}
     */
    public function controllerMap(): array
    {
        return [
            'form'  => 'RoleForm', // редактирование роли
            'grid'  => 'RoleGrid', // список ролей
            'mgrid' => 'ModulesGrid', // разрешения роли пользователя
            'egrid' => 'ExtensionsGrid' // разрешения расширений
        ];
    }

    /**
     * Проверяет, является ли указанное разрешение особым.
     * 
     * @param string $name Имя разрешения.
     * 
     * @return bool
     */
    public function isSpecialPermission(string $name): bool
    {
        return in_array($name, $this->specialPermissions);
    }

    /**
     * Возвращает наименование особых разрешений.
     * 
     * @return array
     */
    public function getSpecialPermissions(): array
    {
        return $this->specialPermissions;
    }

    /**
     * Возвращает значок указанного разрешения.
     * 
     * @see Model\PermissionGrid
     * @see Model\PermissionGridRow
     * 
     * @param string $permission Имя разрешения.
     * 
     * @return string
     */
    public function getPemissionIcon(string $permission): string
    {
        $filename = $this->permissionIcons[$permission] ?? 'permission-lock.svg';
        return  $this->getAssetsUrl() . '/images/permission/' . $filename;
    }

    /**
     * Возвращает все роли пользователей.
     * 
     * Если роли пользователей отсутствуют в кэше, выполнит кэширование.
     * 
     * @return array|null
     */
    public function getRoles(): ?array
    {
        $roles = null;
        // если кэширование доступно модулю
        if ($this->caching) {
            /** @var \Gm\Cache\CacheTable $table */
            $table = Gm::$app->tables;
            // если есть шаблоны запроса "Роли пользователей" для кэш-таблицы, 
            // тогда заполняем кэш или используем кэш самой модели
            if ($table->pattern('role')) {
                $roles = $table->getAll();
            } else {
                /** @var Model\Role $role */
                $role = $this->getModel('Role');
                $roles = $role->getAll(true);
            }
        }
        if ($roles === null) {
            /** @var \Gm\Backend\Role\Model\Role $role */
            $role = $this->getModel('Role');
            $roles = $role->getAll(false);
        }
        return $roles;
    }

    /**
     * Возвращает иерархию ролей пользователей в виде пар "children - parent".
     * 
     * Если иерархия пользователей отсутствуют в кэше, выполнит кэширование.
     * 
     * @return array|null
     */
    public function getHierarchy(): ?array
    {
        $hierarchy = null;
        // если кэширование доступно модулю
        if ($this->caching) {
            /** @var \Gm\Cache\CacheTable $table */
            $table = Gm::$app->tables;
            // если есть шаблоны запроса "Иерархия ролей пользователей" для кэш-таблицы, 
            // тогда заполняем кэш или используем кэш самой модели
            if ($table->pattern('roleHierarchy')) {
                $hierarchy = $table->getAll();
            } else {
                /** @var Model\RoleHierarchy $roleHierarchy */
                $roleHierarchy = $this->getModel('RoleHierarchy');
                $hierarchy = $roleHierarchy->getAll(true);
            }
        }
        if ($hierarchy === null) {
            /** @var \Gm\Backend\Role\Model\RoleHierarchy $roleHierarchy */
            $roleHierarchy = $this->getModel('RoleHierarchy');
            $hierarchy = $roleHierarchy->getAll(false);
        }
        return $hierarchy;
    }

    /**
     * Подготавливает кэш-таблицы. Если они не созданы, создаёт и заполняет их.
     * 
     * Применяется если кэширование доступно модулю.
     * 
     * @return void
     */
    public function prepareCache() :void
    {
        // если кэширование доступно модулю
        if ($this->caching) {
            /** @var \Gm\Cache\CacheTable $table */
            $table = Gm::$app->tables;
            // если есть шаблон запроса кэш-таблицы "Роли пользователей"
            if ($table->pattern('role')) {
                $table->fill(true);
            } else {
                /** @var \Gm\Backend\Role\Model\Role $role */
                $role = $this->getModel('Role');
                $role->getAll(true);
            }
            // если есть шаблон запроса кэш-таблицы "Иерархия ролей пользователей"
            if ($table->pattern('roleHierarchy')) {
                $table->fill(true);
            } else {
                /** @var \Gm\Backend\Role\Model\RoleHierarchy $hierarchy */
                $hierarchy = $this->getModel('RoleHierarchy');
                $hierarchy->getAll(true);
            }
        }
    }

    /**
     * Сбрасывает кэш-таблицы (роли пользователей, иерархия ролей пользователей).
     * 
     * @return void
     */
    public function flushCache(): void
    {
        // если кэширование доступно модулю
        if ($this->caching) {
            /** @var \Gm\Cache\CacheTable $table */
            $table = Gm::$app->tables;
            if ($table->enabled) {
                // если есть шаблон запроса кэш-таблицы "Роли пользователей"
                if ($table->pattern('role')) {
                    $table->flushCache();
                } else {
                    /** @var Model\Role $role */
                    $role = $this->getModel('Role');
                    $role->flushCache();
                }
                // если есть шаблон запроса кэш-таблицы "Иерархия ролей пользователей"
                if ($table->pattern('roleHierarchy')) {
                    $table->flushCache();
                } else {
                    /** @var Model\RoleHierarchy $hierarchy */
                    $hierarchy = $this->getModel('RoleHierarchy');
                    $hierarchy->flushCache();
                }
            }
        }
    }
}
