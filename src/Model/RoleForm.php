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
use Gm\Helper\Json;
use Gm\Mvc\Module\BaseModule;
use Gm\Db\Sql\ExpressionInterface;
use Gm\Panel\Data\Model\FormModel;

/**
 * Модель данных профиля роли пользователей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class RoleForm extends FormModel
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
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{role}}',
            'tableAlias' => 'role',
            'primaryKey' => 'id',
            'lockRows'   => true,
            'useAudit'   => true,
            // параметры полей таблицы
            'fields'     => [
                ['id'],
                ['name', 'label' => 'Name', 'unique' => true],
                ['shortname', 'label' => 'Shortname', 'unique' => true],
                ['description', 'label' => 'Description']
            ],
            // правила форматирования полей
            'formatterRules' => [
                [['name', 'shortname', 'description'], 'safe']
            ],
            // правила валидации полей
            'validationRules' => [
                [['name', 'shortname'], 'notEmpty'],
                // название роли
                [
                    'name',
                    'between',
                    'min' => 2, 'max' => 50, 'type' => 'string'
                ],
                // сокращенное название
                [
                    'shortname',
                    'between',
                    'min' => 3, 'max' => 30, 'type' => 'string'
                ],
                // описание
                [
                    'description',
                    'between',
                    'min' => 3, 'max' => 255, 'type' => 'string', 'required' => false
                ],
            ],
            // правила удаления зависимых записей
            'dependencies' => [
                'deleteAll' => [
                    '{{module_permissions}}', '{{role_hierarchy}}', '{{user_roles}}', '{{panel_partitionbar_roles}}', '{{panel_menu_roles}}', '{{panel_traybar_roles}}'
                ],
                'delete' => [
                    '{{panel_traybar_roles}}'      => ['role_id' => 'id'],
                    '{{panel_menu_roles}}'         => ['role_id' => 'id'],
                    '{{panel_partitionbar_roles}}' => ['role_id' => 'id'],
                    '{{user_roles}}'         => ['role_id' => 'id'],
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
            ->on(self::EVENT_AFTER_SAVE, function ($isInsert, $columns, $result, $message) {
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                /** @var \Gm\Panel\Controller\FormController $controller */
                $controller = $this->controller();
                // обновить список
                $controller->cmdReloadGrid();
            })
            ->on(self::EVENT_AFTER_DELETE, function ($result, $message) {
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                /** @var \Gm\Panel\Controller\FormController $controller */
                $controller = $this->controller();
                // обновить список
                $controller->cmdReloadGrid();
            });
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete(false|int|null $result = null): void
    {
        if ($result) {
            // сбрасываем кэш
            $this->module->flushCache();
        }

        parent::afterDelete($result);
    }

   /**
     * {@inheritdoc}
     */
    public function afterSave(
        bool $isInsert, 
        array $columns = null, 
        false|int|string|null $result = null
    ): void
    {
        // при обновлении записи может $result == null, т.к. значения во всех поля не изменилось,
        // кроме поля "Унаследован от ролей"
        if (!$isInsert && $result === null) {
            $result = true;
        }
        if ($result) {
            $id = $isInsert ? $result : $this->getIdentifier();
            /** @var \Gm\Backend\Role\Model\RoleHierarchy $roleHierarchy */
            $roleHierarchy = $this->module->getModel('RoleHierarchy');
            if ($isInsert)
                $roleHierarchy->addHierarchy($id, $this->parentsId);
            else
                $roleHierarchy->saveHierarchy($id, $this->parentsId);
            // сбрасываем кэш
            $this->module->flushCache();
        }

        parent::afterSave($isInsert, $columns, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function afterValidate(bool $isValid): bool
    {
        if ($isValid) {
            // проверяем поле "Унаследован от ролей"
            $this->parentsId = $this->getUnsafeAttribute('parentsId');
            if ($this->parentsId) {
                $this->parentsId = Json::decode($this->parentsId);
                if ($error = Json::error()) {
                    $this->addError($this->t($error));
                    return false;
                }
                // проверяем, чтобы не было наследования роли от самой себя
                if (!$this->IsNewRecord()) {
                    if (in_array($this->getIdentifier(), $this->parentsId)) {
                        $this->addError($this->t('Cannot inherit roles from itself'));
                        return false;
                    }
                }
            } else 
                $this->parentsId = [];
        }
        return $isValid;
    }

    /**
     * {@inheritdoc}
     * 
     * Для проверки уникальности значений одного из полей, устанавливаем `$combination = 'OR'`.
     */
    public function checkUniqueness(array $fields, string $combination = 'OR'): bool
    {
        return parent::checkUniqueness($fields, $combination);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionTitle(): string
    {
        return isset($this->name) ? $this->name : parent::getActionTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function processing(): void
    {
        parent::processing();

        /** @var \Gm\Backend\Role\Model\RoleHierarchy $roleHierarchy */
        $roleHierarchy = $this->module->getModel('RoleHierarchy');
        // унаследован от родителей
        $this->parentsId = $roleHierarchy->getParentsId($this->getIdentifier());
    }
}
