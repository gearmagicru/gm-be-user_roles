<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\UserRoles\Model;

use Gm;
use Gm\Db\Sql;
use Gm\Helper\Url;
use Gm\Filesystem\Filesystem;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Data\Model\TreeGridModel;

/**
 * Модель данных списка прав доступа к модулям.
 * 
 * Полный доступ не включает следующие разрешения:
 * - Журнал аудита (запись действий пользователей в журнал аудита)
 * - Аудит записей (просмотр действий пользователей, добавляются столбцы)
 * - Свои записи (возможность видеть только свои записи)
 * - Настройки (настройка модуля)
 * - Информация (информация о модуле)
 * Эти разрешения устанавливают самостоятельно, даже если установлен полный доступ.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class ModulesGrid extends TreeGridModel
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\UserRoles\Module
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     */
    protected int $limit = 100;

    /**
     * {@inheritdoc}
     */
    protected array $order = ['name' => 'ASC'];

    /**
     * Значок модуля по умолчанию (если он отсутствует).
     * 
     * @var string
     */
    protected string $moduleIconNone = '';

    /**
     * Директория модулей.
     * 
     * @see Filesystem::module()
     * 
     * @var string
     */
    protected string $moduleDir = '';

    /**
     * URL-адрес ресурсов модулей.
     * 
     * @see Url::module()
     * 
     * @var string
     */
    protected string $moduleUrl = '';

    /**
     * Идентификаторы унаследованных родителей.
     * 
     * @see PermissionGrid::beforeSelect()
     * 
     * @var array
     */
    protected array $parents = [];

    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{module}}',
            'primaryKey' => 'id',
            'parentKey'  => 'parent_id',
            'countKey'   => 'count',
            'fields'     => [
                ['path'],
                ['name', 'direct' => 'locale.name'],
                ['description', 'direct' => 'locale.description']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this->moduleDir = Filesystem::module();
        $this->moduleUrl = Url::module();
        $this->moduleIconNone = Url::theme() . '/widgets/images/module/module-none_small.svg';
    }

    /**
     * Возвращает имена разрешений (прав доступа) модуля.
     * 
     * @param int $parentId Идентификатор модуля в базе данных.
     * 
     * @return array
     */
    public function getChildNodes(int|string $mparentId = null): array
    {
        /** @var \Gm\ModuleManager\ModuleRegistry $installed */
        $installed = Gm::$app->modules->getRegistry();
        // информация о роли из хранилища модуля
        $store = $this->module->getStorage();
        if (empty($store->role)) {
            // TODO: добавить уведомление в отладку
            return [
                'total' => 0,
                'nodes' => []
            ];
        }
        $role = $store->role;

        // информация о установленном модуле
        $moduleConfig = $installed->getAt($mparentId);
        if ($moduleConfig === null) {
            // TODO: добавить уведомление в отладку
            return [
                'total' => 0,
                'nodes' => []
            ];
        }

        /** @var string|null $permissions Разрешения модуля */
        $permissions = $moduleConfig['permissions'];
        // если модуль не имеет разрешений
        if (empty($permissions)) {
            return [
                'total' => 0,
                'nodes' => []
            ];
        }
        $permissions = explode(',', $permissions);
        // разрешения модуля в текущей локализации
        $transPermissions = $installed->getTranslatedPermissions($mparentId);

        /** @var RolePermission $rolePermission Разрешения модуля для указанной роли */
        $rolePermission = $this->module->getModel('RolePermission');
        // определяем разрешение для текущей роли
        $chPermission = $rolePermission->get($mparentId, $role['id']);
        // имена разрешений текущей роли (пример: ['any' => true, 'read' => true, ...])
        $chPermissionNames = $chPermission ? $chPermission->permissionToArray() : [];

        // определяем разрешения всех наследуемых родителей (если они есть)
        // идентификаторы всех наследуемых родителей
        $parentsId = $this->getParents();

        /** 
         * @var array $prPermissionNames Имена разрешений всех наследуемых родителей. 
         *     Имеет вид: `[{roleId} => [{permission} => true, ...], ...]`
         */
        $prPermissionNames = [];
        if ($parentsId) {
            foreach ($parentsId as $parentId) {
                $prPermission = $rolePermission->get($mparentId, $parentId);
                if ($prPermission !== null)
                    $prPermissionNames[$parentId] = $prPermission->permissionToArray();
                else
                    $prPermissionNames[$parentId] = [];
            }
        }

        $nodes = [];
        $index = 1;
        foreach ($permissions as $permission) {
            // имя разрешения
            $chPermissionName = $permission;
            // разрешение активно
            $active = isset($chPermissionNames[$chPermissionName]) ? 1 : 0;
            $node = [
                'id'          => $role['id']. '_' . $mparentId . '_' . ($index++),
                'icon'        => $this->module->getPemissionIcon($chPermissionName),
                'name'        => $transPermissions[$permission][0] . ' <span>(' . $chPermissionName . ')</span>',
                'description' => $transPermissions[$permission][1],
                'moduleId'    => $mparentId,
                'roleId'      => $role['id'],
                'permissions' => $chPermissionName,
                'active'      => $active,
                'leaf'        => 1
            ];
            // активность результирующего разрешения
            $permissionResult = $active;
            // если текущее разрешение является особенным (не наследуемым)
            $isSpecialPermission = $this->module->isSpecialPermission($chPermissionName);
            // если есть имена разрешений всех наследуемых родителей
            if ($prPermissionNames) {
                // для каждого родителя определяем активность разрешения и если это не 
                // особенное разрешение, выполняется расчёт результирующего разрешения
                foreach ($prPermissionNames as $parentId => $prPermissionName) {
                    if ($isSpecialPermission)
                        $node['active' . $parentId] = -1;
                    else {
                        $active = isset($prPermissionNames[$parentId][$chPermissionName]) ? 1 : 0;
                        $node['active' . $parentId] = $active;
                        $permissionResult += $active;
                    }
                }
            }
            // если нет необходимости устанавливать активность результирующему разрешению, т.к. 
            // оно является особенным, то его скрываем
            if ($isSpecialPermission) {
                $node['result'] = -1;
            } else {
                $node['result'] = $permissionResult > 0 ? 1 : 0;
            }
            $nodes[] = $node;
        }
        return [
            'total' => sizeof($nodes),
            'nodes' => $nodes
        ];
    }

    /**
     * Возвращает значок модуля.
     * 
     * @param string $path Путь к директории модуля.
     * 
     * @return string
     */
    protected function getModuleIcon(string $path): string
    {
        $icon = $this->moduleIconNone;
        if ($path) {
            $assets = $path . '/assets/images/icon_small.svg';
            if (file_exists($this->moduleDir . $assets)) {
                $icon = $this->moduleUrl . $assets;
            }
        }
        return $icon;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(string $tableName = null): false|int
    {
        $result = false;
        if ($this->beforeDelete(false)) {
            // информация о роли из хранилища модуля
            $store = $this->module->getStorage();
            if (empty($store->role)) {
                $this->afterDelete(false, $result);
                return false;
            }
            // условие запроса удаления записей
            $condition = [
                'role_id' => $store->role['id']
            ];
            $this->dataManager->tableName = '{{module_permissions}}';
            $result = $this->deleteRecord($condition);
            $this->afterDelete(false, $result);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     * 
     * Только для узлов дерева - модули.
     */
    public function prepareRow(array &$row): void
    {
        $row['icon'] = $this->getModuleIcon($row['path']); // значок модуля
        $row['description'] = ''; // описание модуля
        $row['count'] = 1; // кол-о элементов в модуле, всегда > 0
        $row['active'] = -1; // скрыть разрешение
        $row['result'] = -1; // скрыть результирующие разрешение
        // скрыть разрешение унаследованных родителей
        if ($this->parents) {
            foreach ($this->parents as $parentId) {
                $row['active' . $parentId] = -1; 
            }
        }
    }

   /**
     * {@inheritdoc}
     */
    public function buildFilter(Sql\AbstractSql $operator): void
    {
        // убрать из списка модули для которых нет необходимости устанавливать права доступа
        $operator->where(['_lock' => 0]);

        parent::buildFilter($operator);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSelect(mixed $command = null): void
    {
        $command->bindValues(array(
            ':language' => Gm::$app->language->code
        ));
        // идентификаторы унаследованных родителей
        $this->parents = $this->getParents();
    }

    /**
     * Возвращает идентификаторы всех наследуемых родителей по выбранному идентификатору 
     * потомка (роли).
     * 
     * @see RoleHierarchy::getAllParents()
     * 
     * @return array
     */
    public function getParents(): array
    {
        // информация о роли из хранилища модуля
        $store = $this->module->getStorage();
        if (!empty($store->role)) { 
            /** @var RoleHierarchy $hierarchy */
            $hierarchy = $this->module->getModel('RoleHierarchy');
            // если есть наследование от ролей
            return $hierarchy->getAllParents($store->role['id']);
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes(): array
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS `mod`.`id`, `mod`.`path`, `locale`.`name`,`locale`.`description` '
             . 'FROM `{{module}}` `mod` '
             . 'LEFT JOIN `{{module_locale}}` `locale` ON `mod`.`id`=`locale`.`module_id` AND `locale`.`language_id`=:language ';
        return $this->selectBySql($sql);
    }
}
