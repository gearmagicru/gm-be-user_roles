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
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Data\Model\TreeGridModel;

/**
 * Модель данных списка прав доступа к расширению модуля.
 * 
 * Полный доступ не включает следующие разрешения:
 * - Журнал аудита (запись действий пользователей в журнал аудита)
 * - Аудит записей (просмотр действий пользователей, добавляются столбцы)
 * - Свои записи (возможность видеть только свои записи)
 * - Настройки (настройка расширения модуля)
 * - Информация (информация о расширении модуля)
 * Эти разрешения устанавливают самостоятельно, даже если установлен полный доступ.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class ExtensionsGrid extends TreeGridModel
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
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName' => '{{extension_permissions}}'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_AFTER_DELETE, function ($someRecords, $result, $message) {
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']) // всплывающие сообщение
                        ->cmdReloadTreeGrid($this->module->viewId('extensions-grid')); // обновить дерево
            });
    }


    /**
     * Возвращает имена разрешений расширения модуля.
     * 
     * @param int $parentId Идентификатор расширения в базе данных.
     * 
     * @return array
     */
    public function getChildNodes(int|string $parentId = null): array
    {
        $nodes = [];
        /** @var \Gm\ExtensionManager\ExtensionRegistry $installed */
        $installed = Gm::$app->extensions->getRegistry();
        // информация о выбранной роли из хранилища модуля
        $store = $this->module->getStorage();
        if (empty($store->role)) {
            // TODO: добавить уведомление в отладку
            return [
                'total' => 0,
                'nodes' => $nodes
            ];
        }
        $role = $store->role;
        /** @var array|null Парамтеры конфигурации установленного расширения */
        $extensionConfig = $installed->getAt($parentId);
        if ($extensionConfig === null) {
            // TODO: добавить уведомление в отладку
            return [
                'total' => 0,
                'nodes' => $nodes
            ];
        }
        /** @var string|null $permissions Разрешения расширения */
        $permissions = $extensionConfig['permissions'];
        // если расширениt не имеет разрешений
        if (empty($permissions)) {
            return [
                'total' => 0,
                'nodes' => $nodes
            ];
        }
        /**
         * @var string|array $permissions Разрешения расширения.
         * Имеет вид: `['any', 'read', ...]`
         */
        $permissions = explode(',', $permissions);

        /**
         * @var string|array $transPermissions Локализация имён разрешений.
         * Имеет вид: `['any' => ['name' => 'Полный доступ', 'description' => 'Полный доступ к расширению'], ...]`.
         */
        $transPermissions = $installed->getTranslatedPermissions($parentId);
        /** @var ExtensionPermission $extensionPermission Разрешения выбранного расширения */
        $extensionPermission = $this->module->getModel('ExtensionPermission');
        /** @var ExtensionPermission|null $availablePermissions Доступные разрешения (расширения) для текущей роли */
        $availablePermissions = $extensionPermission->get($parentId, $role['id']);

        /** 
         * @var array $permissionNames Имена доступных разрешений (расширения) для текущей роли.
         *  Имеет вид: `['any' => true, 'read' => true, ...]`.
         */
        $permissionNames = $availablePermissions ? $availablePermissions->permissionsToArray() : [];
        $index = 1;
        foreach ($permissions as $permission) {
            $name = $transPermissions[$permission][0] ?? ucfirst($permission);
            $description = $transPermissions[$permission][1] ?? ucfirst($permission);

            $active = isset($permissionNames[$permission]) ? 1 : 0;
            $node = [
                'id'          => $role['id']. '_' . $parentId . '_' . ($index++),
                'icon'        => $this->module->getPemissionIcon($permission),
                'name'        => $name. ' <span>(' . $permission . ')</span>',
                'description' => $description,
                'extensionId' => $parentId,
                'roleId'      => $role['id'],
                'permissions' => $permission,
                'active'      => $active,
                'leaf'        => 1
            ];
            $nodes[] = $node;
        }
        return [
            'total' => sizeof($nodes),
            'nodes' => $nodes
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(string $tableName = null): false|int
    {
        $result = false;
        if ($this->beforeDelete(false)) {
            // информация о выбранной роли из хранилища модуля
            $store = $this->module->getStorage();
            if (empty($store->role)) {
                $this->afterDelete(false, $result);
                return false;
            }
            $result = $this->deleteRecord(
                [
                    'role_id' => (int) $store->role['id']
                ]
            );
            $this->afterDelete(false, $result);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes(): array
    {
        $nodes = [];
        /** @var \Gm\ModuleManager\ModuleRegistry $installed */
        $installed = Gm::$app->modules->getRegistry();

        /**
         * @var array $moduleNames Имена и описание модулей в текущей локализации. 
         * Имеет вид: `[moduleId => ['name' => 'Name', ...], ...]`.
         */
        $moduleNames = $installed->getListNames();

        /**
         * @var array $extensions Конфигурация установленных расширений. 
         * Имеет вид: `[rowId => ['id' => 'extension.name', 'rowId' => 1, ...], ...]`.
         */
        $extensions = Gm::$app->extensions->getRegistry()->getListInfo();
        foreach ($extensions as $rowId => $extension) {
            $moduleRowId = $extension['moduleRowId'];
            $nodes[] = [
                'id'         => $rowId,
                'name'       => $extension['name'],
                'icon'       => $extension['smallIcon'],
                'module'     => $moduleNames[$moduleRowId]['name'] ?? SYMBOL_NONAME,
                'moduleIcon' => $installed->getIcon($moduleRowId, 'small'),
                'active'     => -1,
                'leaf'       => 0,
                'expanded'   => false
            ];
        }
        return [
            'total' => sizeof($nodes),
            'nodes' => $nodes
        ];
    }
}
