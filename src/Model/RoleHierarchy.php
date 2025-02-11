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
use Gm\Db\Sql\Select;
use Gm\Db\ActiveRecord;

/**
 * Модель данных иерархии ролей пользователей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class RoleHierarchy extends ActiveRecord
{
    /**
     * {@inheritdoc}
     * 
     * Среди составного первичного ключа, играет основную роль `role_id`, т.к. он 
     * обеспечивает связь 1-н ко многим.
     */
    public function primaryKey(): string
    {
        return 'role_id';
    }

    /**
     * {@inheritdoc}
     */
    public function tableName(): string
    {
        return '{{role_hierarchy}}';
    }

    /**
     * {@inheritdoc}
     */
    public function maskedAttributes(): array
    {
        return [
            'roleId'   => 'role_id',
            'parentId' => 'parent_id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteProcessCondition(array &$where): void
    {
        $where['role_id']   = $this->roleId;
        $where['parent_id'] = $this->parentId;
    }

    /**
     * Возвращает запись по указанному значению первичного ключа.
     * 
     * @see ActiveRecrod::selectOne()
     * 
     * @param int $roleId Идентификатор роли пользователя.
     * @param int $parentId Идентификатор наследования.
     * 
     * @return RoleHierarchy|null Активная запись при успешном запросе, иначе `null`.
     */
    public function get(int $roleId, int $parentId): ?static
    {
        return $this->selectOne([
            'role_id'   => $roleId,
            'parent_id' => $parentId
        ]);
    }

    /**
     * Возвращает набор всех строк (ассоциативные массивы) текущей таблицы.
     * 
     * Ключом каждой строки является значение первичного ключа {@see ActiveRecord::tableName()} 
     * текущей таблицы.
     * 
     * @param bool $caching Указывает на принудительное кэширование. Если служба кэширования 
     *     отключена, кэширование не будет выполнено (по умолчанию `true`).
     * 
     * @return array
     */
    public function getAll(bool $caching = true): ?array
    {
        if ($caching)
            return $this->cache(
                function () { return $this->fetchToGroups('roleId', null, $this->maskedAttributes()); },
                null,
                true
            );
        else
            return $this->fetchToGroups('roleId', null, $this->maskedAttributes());
    }

    /**
     * Обновляет (сохраняет) иерархии с изменёнными идентификаторами ролей.
     * 
     * @param int $roleId Идентификатор потомка (роли).
     * @param array|null $parentsId Идентификаторы родителей.
     * 
     * @return $this
     */
    public function saveHierarchy(int $roleId, ?array $parentsId): static
    {
        $oldParentsId = $this->getParentsId($roleId, false);
        // если не указано, от кого наследовать
        if (empty($parentsId)) {
            if ($oldParentsId) {
                $this->deleteHierarchy($roleId, null);
            }
            return $this;
        }
        // определяем, какие роли необходимо добавить
        $toAdd = array_diff($parentsId, $oldParentsId);
        if ($toAdd) {
            $this->addHierarchy($roleId, $toAdd);
        }
        // определяем, какие роли необходимо удалить
        $toDelete = array_diff($oldParentsId, $parentsId);
        if ($toDelete) {
            $this->deleteHierarchy($roleId, $toDelete);
        }
        return $this;
    }

    /**
     * Удаляет из иерархии роли родителей и потомков.
     * 
     * @param int|null $roleId Идентификатор потомка (роли).
     * @param array|null $parentsId Идентификаторы родителей.
     * 
     * @return $this
     */
    public function deleteHierarchy(?int $roleId, ?array $parentsId): static
    {
        $where = [];
        if ($roleId) {
            $where['role_id'] = $roleId;
        }
        if ($parentsId) {
            $where['parent_id'] = $parentsId;
        }
        if ($where) {
            $this->deleteRecord($where);
        }
        return $this;
    }

    /**
     * Добавляет в иерархию идентификаторы роли родителей к идентификатору роли потомка.
     * 
     * @param int|null $roleId Идентификатор потомка (роли) к которой добавляются родители.
     * @param array $parentsId Идентификаторы родителей.
     * 
     * @return $this
     */
    public function addHierarchy(?int $roleId, array $parentsId): static
    {
        if (empty($parentsId) || empty($roleId)) {
            return $this;
        }
        foreach ($parentsId as $parentId) {
            $this->insertRecord(
                [
                    'role_id'   => $roleId,
                    'parent_id' => $parentId
                ]
            );
        }
        return $this;
    }

    /**
     * Возвращает идентификаторы потомков по указанному идентификатору родителя.
     * 
     * @param int $parentId Идентификатор родителя.
     * @param bool $toString Преобразовать массив идентификаторов в строку (по умолчанию `true`).
     * 
     * @return array|string
     */
    public function getChildrenId(int $parentId, bool $toString = true): array|string
    {
        if (empty($parentId)) {
            return $toString ? '' : [];
        }
        /** @var Select $select */
        $select = $this->select(
            ['role_id'],
            ['parent_id' => $parentId]
        );
        $childrenId = $this
            ->getDb()
                ->createCommand($select)
                    ->query()
                    ->queryColumn();
        return $toString ? implode(',', $childrenId) : $childrenId;
    }

    /**
     * Возвращает идентификаторы родителей по указанному идентификатору потомка.
     * 
     * @param int $childrenId Идентификатор потомка.
     * @param bool $toString Преобразовать массив идентификаторов в строку (по умолчанию `true`).
     * 
     * @return array|string
     */
    public function getParentsId(int $childrenId, bool $toString = true): array|string
    {
        if (empty($childrenId)) {
            return $toString ? '' : [];
        }
        /** @var Select $select */
        $select = $this->select(
            ['parent_id'],
            ['role_id' => $childrenId]
        );
        $parentsId = $this
            ->getDb()
                ->createCommand($select)
                    ->query()
                    ->queryColumn();
        return $toString ? implode(',', $parentsId) : $parentsId;
    }

    /**
     * Обходит рекурсивно всех родителей указанной роли.
     * 
     * @param int $roleId Идентификатор роли.
     * @param array $rows Все потомки с их родителями.
     * @param array $parents Результат - все роли иерархии.
     * 
     * @return void
     */
    public function bypassParents(int $roleId, array $rows, &$parents): void
    {
        $parents[$roleId] = true;
        foreach($rows as $row) {
            if ($row['role_id'] == $roleId) {
                $this->bypassParents($row['parent_id'], $rows, $parents);
            }
        }
    }

    /**
     * Возвращает всех родителей (идентификаторы) по указанному идентификатору потомка.
     * 
     * @param int $childrenId Идентификатор потомка.
     * 
     * @return array
     */
    public function getAllParents(int $childrenId): array
    {
        $this->bypassParents($childrenId, $this->fetchAll(), $parents);
        // убираем, т.к. он не является родителем
        unset($parents[$childrenId]);

        return $parents ? array_keys($parents) : [];
    }
}
