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
use Gm\Panel\Data\Model\FormModel;

/**
 * Модель данных профиля записи прав доступа к модулю.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class ModulesGridRow extends FormModel
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\UserRoles\Module
     */
    public BaseModule $module;

    /**
     * Активность разрешения.
     * 
     * @var bool
     */
    protected bool $active = false;

    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{module_permissions}}',
            'fields'     => [
                ['module_id', 'alias' => 'moduleId'],
                ['role_id', 'alias' => 'roleId'],
                ['permissions']
            ],
            'validationRules' => [
                'checkEmpty' => [['moduleId', 'roleId', 'permissions'], 'notEmpty']
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
                /** @var \Gm\Panel\Http\Response $response */
                $response = $this->response();
                // если успешная установка прав
                if ($message['success']) {
                    $store = $this->module->getStorage();
                    $message['message'] = $this->module->t(
                        'Permission "{0}" for role "{1}"',
                        [
                            $this->permissions,
                            $store->role['name'],
                            $this->t('{accessible}')[$this->active]
                        ]
                    );
                    $message['title'] = $this->t('Permission to access');
                }
                // всплывающие сообщение
                $response
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                $set  = [
                    'result' => $this->calcActiveResult()
                ];
                $response
                    ->meta
                        ->command('setTreeNode', $this->module->viewId('modules-grid'), $this->getIdentifier(), $set);
            });
    }

    /**
     * calcActiveResult
     * 
     */
    protected function calcActiveResult(): int
    {
        $result = 0;
        // где все имена: 'active1', 'active2'...
        foreach ($this->unsafeAttributes as $name => $value) {
            $result += (int) $value;
        }
        return $result > 0 ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): mixed
    {
        return Gm::$app->router->get('roleId');
    }

    /**
     * {@inheritdoc}
     */
    public function get(mixed $identifier = null): static
    {
        // т.к. права доступа формируются при выводе списка, то нет
        // необходимости делать запрос к бд (нет основной таблицы)
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function afterValidate(bool $isValid): bool
    {
        if ($isValid) {
            $this->active = ((int) $this->getUnsafeAttribute('active', 0)) > 0 ? true : false;
        }
        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    protected function insertProcess(array $attributes = null): false|int|string
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        /** @var \Gm\Backend\Role\Model\RolePermission  $rolePermission */
        $rolePermission = $this->module->getModel('RolePermission');
        // определяем разрешение модуля для роли
        $rolePermission = $rolePermission->get($this->moduleId, $this->roleId);
        // записи ещё нет с разрешением
        if ($rolePermission === null) {
            // добавить разрешение
            if ($this->active) {
                $columns = [
                    'role_id'     => $this->roleId,
                    'module_id'   => $this->moduleId,
                    'permissions' => $this->permissions
                ];
                $this->insertRecord($columns);
                // т.к. первичный ключ является составным (значение не определяется 
                // через `getLastGeneratedValue`), то 
                $this->result = $this->roleId . $this->moduleId;
            // убрать разрешение
            } else {
                // TODO: не должно быть на стороне клиента, т.к. запись не существует
            }
        // запись уже есть с разрешением
        } else {
            // добавить разрешение
            if ($this->active) {
                $permissions = $rolePermission->includePermission($this->permissions);
            // убрать разрешение
            } else {
                $permissions = $rolePermission->excludePermission($this->permissions);
            }
            $columns = ['permissions' => $permissions];
            $this->result = $this->updateRecord(
                $columns,
                [
                    'role_id'   => $this->roleId,
                    'module_id' => $this->moduleId,
                ]
            );
        }
        $this->afterSave(true, $columns, $this->result);
        return $this->result;
    }
}
