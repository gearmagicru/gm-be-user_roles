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
use Gm\Panel\User\UserRoles;
use Gm\Mvc\Module\BaseModule;
use Gm\Db\Sql\ExpressionInterface;
use Gm\Panel\Data\Model\GridModel;

/**
 * Модель данных списка ролей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class RoleGrid extends GridModel
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\UserRoles\Module
     */
    public BaseModule $module;

    /**
     * Роли пользователя.
     * 
     * @see RoleGrid::beforeSelect()
     * 
     * @var UserRoles
     */
    protected UserRoles $userRoles;

    /**
     * Иерархия ролей пользователей.
     * 
     * @see RoleGrid::beforeSelect()
     * 
     * @var array|null
     */
    protected ?array $hierarchy = null;

    /**
     * Иерархия ролей пользователей.
     * 
     * @see RoleGrid::beforeSelect()
     * 
     * @var array|null
     */
    protected ?array $roles = null;

    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{role}}',
            'primaryKey' => 'id',
            'fields'     => [
                ['name'],
                ['shortname'],
                ['description'],
                ['parents'],
            ],
            'lockRows' => true,
            'useAudit' => true,
            'dependencies' => [
                'deleteAll' => [
                    '{{module_permissions}}', '{{role_hierarchy}}', '{{user_roles}}', '{{panel_partitionbar_roles}}', 
                    '{{panel_menu_roles}}', '{{panel_traybar_roles}}', '{{extension_permissions}}'
                ],
                'delete' => [
                    '{{extension_permissions}}'    => ['role_id' => 'id'],
                    '{{panel_traybar_roles}}'      => ['role_id' => 'id'],
                    '{{panel_menu_roles}}'         => ['role_id' => 'id'],
                    '{{panel_partitionbar_roles}}' => ['role_id' => 'id'],
                    '{{user_roles}}'               => ['role_id' => 'id'],
                    '{{module_permissions}}' => ['role_id' => 'id'],
                    '{{role_hierarchy}}'     => function ($where, $table, $primaryTable) {
                        $where
                            ->nest()
                                ->equalTo(
                                    $table . '.role_id',
                                    $primaryTable . '.id',
                                    ExpressionInterface::TYPE_IDENTIFIER,
                                    ExpressionInterface::TYPE_IDENTIFIER
                                )
                                ->OR
                                ->equalTo(
                                    $table . '.parent_id',
                                    $primaryTable . '.id',
                                    ExpressionInterface::TYPE_IDENTIFIER,
                                    ExpressionInterface::TYPE_IDENTIFIER
                                )
                            ->unnest();
                    }
                ]
            ],
            'filter' => [
                'parents' => [
                    'operator' => 'where',
                    'where'    => '`id` IN (SELECT `role_id` FROM {{role_hierarchy}} WHERE `parent_id`=%s)'
                ]
            ]
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
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                /** @var \Gm\Panel\Controller\GridController $controller */ 
                $controller = $this->controller();
                // обновить список
                $controller->cmdReloadGrid();
            })
            ->on(self::EVENT_AFTER_SET_FILTER, function ($filter) {
                /** @var \Gm\Panel\Controller\GridController $controller */ 
                $controller = $this->controller();
                // обновить список
                $controller->cmdReloadGrid();
            });
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete(bool $someRecords = true, $result = null)
    {
        if ($result) {
            $this->module->flushCache();
        }

        parent::afterDelete($someRecords, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareRow(array &$row): void
    {
        // если унаследован от ролей, определяем родителей
        $parents = [];
        if (isset($this->hierarchy[$row['id']])) {
            $hParents = $this->hierarchy[$row['id']];
            $count = sizeof($hParents);
            for ($i = 0; $i < $count; $i++) {
                $parentId = $hParents[$i]['parentId'];
                if (isset($this->roles[$parentId])) {
                    $parents[] = $this->roles[$parentId]['name'] . ($i < $count -1 ? ', ' : '');
                } else {
                    $parents[] = SYMBOL_NONAME;
                }
            }
        }
        $row['parents'] = $parents;
        // URL-адрес разрешений модулей
        $row['permissionUrl'] = Gm::alias('@match', '/modules/grid/view/' . $row['id']);
        // URL-адрес разрешений расширений
        $row['extensionsUrl'] = Gm::alias('@match', '/extensions/grid/view/' . $row['id']);
        // заголовок контекстного меню записи
        $row['popupMenuTitle'] = $row['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSelect(mixed $command = null): void
    {
        // все роли пользователей
        $this->roles = $this->module->getRoles();
        // вся иерархия ролей пользователей
        $this->hierarchy = $this->module->getHierarchy();
        // роли текущего пользователя
        $this->userRoles = Gm::userIdentity()->getRoles();
    }

    /**
     * {@inheritdoc}
     */
    public function afterFetchRow(array $row, array &$rows): void
    {
        // если запись не активна (только если записям установлен аудит)
        if (isset($row['lockRow'])) {
            $lockRow = (int) $row['lockRow'];
            if ($lockRow) {
                // если пользователь имеет эту роль
                if ($this->userRoles->has($row['id'])) {
                    $row['lockRow'] = 0;
                }
            }
        }
        $rows[] = $row;
    }
}
