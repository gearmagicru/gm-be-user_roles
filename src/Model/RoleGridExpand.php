<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\UserRoles\Model;

use Gm\Panel\Data\Model\FormModel;

/**
 * Модель данных списка ролей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Model
 * @since 1.0
 */
class RoleGridExpand extends FormModel
{
    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{role}}',
            'tableAlias' => 'role',
            'primaryKey' => 'id',
            'fields'     => [
                ['id'],
                ['name', 'direct' => 'role.name'],
                ['shortname', 'direct' => 'role.shortname'],
                ['description', 'direct' => 'role.description'],
                ['parents', 'direct' => 'hierarchy.parents', 'assign' => 'column'],
                ['roles', 'assign' => 'field']
                
            ],
            'lockRows' => true,
            'useAudit' => true
        ];
    }
}
