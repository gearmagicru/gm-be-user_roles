<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\UserRoles\Model;

use Gm\Db\ActiveRecord;

/**
 * Модель данных разрешений для ролей пользователей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class RolePermission extends ActiveRecord
{
    /**
     * {@inheritdoc}
     * 
     * Среди составного первичного ключа, играет основную роль `module_id`, т.к. он 
     * обеспечивает связь 1-н ко многим.
     */
    public function primaryKey(): string
    {
        return 'module_id';
    }

    /**
     * {@inheritdoc}
     */
    public function tableName(): string
    {
        return '{{module_permissions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function maskedAttributes(): array
    {
        return [
            'moduleId'    => 'module_id',
            'roleId'      => 'role_id',
            'permissions' => 'permissions'
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteProcessCondition(array &$where): void
    {
        $where['module_id'] = $this->moduleId;
        $where['role_id']   = $this->roleId;
    }

    /**
     * Возвращает запись по указанному значению первичного ключа.
     * 
     * @see ActiveRecrod::selectOne()
     * 
     * @param int $moduleId Идентификатор модуля.
     * @param int $roleId Идентификатор роли пользователя.
     * 
     * @return RolePermission|null Активная запись при успешном запросе, иначе `null`.
     */
    public function get(int $moduleId, int $roleId): ?static
    {
        return $this->selectOne([
            'module_id' => $moduleId,
            'role_id'   => $roleId
        ]);
    }

    /**
     * Преобразует текущее значение поля разрешений (permission) в массив разрешений.
     * 
     * @param bool $fill Если `true`, результатом будет ассоциативный массив имён 
     *     разрешений (по умолчанию `true`).
     * 
     * @return array
     */
    public function permissionToArray(bool $fill = true) :array
    {
        $permissions = [];
        if ($this->permissions) {
            if (is_string($this->permissions)) {
                $permissions = explode(',', $this->permissions);
                if ($fill) {
                    if ($permissions !== false) {
                        $permissions = array_fill_keys($permissions, true);
                    } else
                    $permissions = [];
                }
            } else
            if (is_array($this->permissions)) {
                $permissions = $this->permissions;
            }
        }
        return $permissions;
    }

    /**
     * Добавляет указанное разрешение в текущий массив (поле permission) наименований 
     * разрешений.
     * 
     * @param string $permission Наименование добавляемого разрешения.
     * 
     * @return string Наименования разрешений через разделитеть ','.
     */
    public function includePermission(string $permission): string
    {
        $permissions = $this->permissionToArray(false);
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
        }
        return implode(',', $permissions);
    }

    /**
     * Удаляет указанное разрешение из текущего массива (поле permission) наименований 
     * разрешений.
     * 
     * @param string $permission Наименование удаляемого разрешения.
     * 
     * @return string Наименования разрешений через разделитеть ','.
     */
    public function excludePermission(string $permission): string
    {
        $permissions = $this->permissionToArray(false);
        $index = array_search($permission, $permissions, true);
        if ($index !== false) {
            unset($permissions[$index]);
        }
        return implode(',', $permissions);
    }

    public function getModulesPermissions(int $moduleId, bool $explodePermissons = true): array
    {
        $rows = $this->fetchAll(null, ['*'], ['module_id' => $moduleId]);
        if ($rows) {
            $permissions = [];
            foreach ($rows as $index => $row) {
                $permission = $row['permissions'];
                if ($explodePermissons && $permission) {
                    $permission = explode(',', $permission);
                }
                $permissions[$row['role_id']] = $permission;
            }
            return $permissions;
        }
        return [];
    }

    /**
     * Удаляет записи по указанному идентификатору модуля.
     * 
     * @param int $moduleId Идентификатор модуля.
     * 
     * @return false|int Возвращает значение `false`, если ошибка выполнения запроса. 
     *     Иначе, количество удалённых записей.
     */
    public function deleteByModule(int $moduleId): false|int
    {
        return $this->deleteRecord(['module_id' => $moduleId]);
    }

    /**
     * Удаляет записи по указанному идентификатору роли.
     * 
     * @param int $roleId Идентификатор роли.
     * 
     * @return bool|int Возвращает значение `false`, если ошибка выполнения запроса. 
     *     Иначе, количество удалённых записей.
     */
    public function deleteByRole(int $roleId): false|int
    {
        return $this->deleteRecord(['role_id' => $roleId]);
    }
}
