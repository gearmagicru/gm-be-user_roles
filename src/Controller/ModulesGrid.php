<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\UserRoles\Controller;

use Gm;
use Gm\Panel\Widget\TabTreeGrid;
use Gm\Panel\Helper\ExtGridTree as ExtGrid;
use Gm\Panel\Helper\HtmlNavigator as HtmlNav;
use Gm\Panel\Data\Model\FormModel;
use Gm\Panel\Controller\TreeGridController;

/**
 * Контроллер списка прав доступа к модулям.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Controller
 * @since 1.0
 */
class ModulesGrid extends TreeGridController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'ModulesGrid';

    /**
     * Идентификатора для зависимой модели вида
     * (необходим для разграничения ViewId между моделями)
     * 
     * @var string
     */
    protected string $subViewId = 'modules';

    /**
     * Идентификатор роли пользователя.
     * 
     * @var int
     */
    protected int $roleId;

    /**
     * {@inheritdoc}
     */
    public function translateAction(mixed $params, string $default = null): ?string
    {
        switch ($this->actionName) {
            // вывод интерфейса
            case 'view':
                // переопределяем идентификатор запрашиваемой записи, т.к. идентификатор 
                // используется для вывода списка
                $params->queryId = $this->getRoleId();
                $store = $this->module->getStorage();
                if ($store && isset($store->role))
                    return $this->t('{view permissions action}', [$this->t('{modules.title}', [$store->role['name']])]);
                else
                    return $this->t('{view permissions action}', ['unknow']);
                break;

            // изменение записи по указанному идентификатору
            case 'update':
                /** @var FormModel $model */
                $model = $this->lastDataModel;
                if ($model instanceof FormModel) {
                    $store = $this->module->getStorage();
                    if ($model->active)
                        return $this->t('accessible permission {0} for role {1}', [$model->permissions, $store->role['name']]);
                    else
                        return $this->t('inaccessible permission {0} for role {1}', [$model->permissions, $store->role['name']]);
                }

            default:
                return parent::translateAction($params, $default);
        }
    }

    /**
     * Возвращает идентификатор роли пользователя.
     * 
     * @return int
     */
    public function getRoleId(): int
    {
        if (!isset($this->roleId)) {
            /** @var mixed $route */
            $route = Gm::$app->router->getRouteMatch();
            if ($route === false) {
                return 0;
            }
            $this->roleId = (int) $route->get('roleId');
        }
        return $this->roleId;
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): TabTreeGrid
    {
        // идентификатор выбранной роли
        $roleId = $this->getRoleId();
        if (empty($roleId)) {
            $this->getResponse()
                ->meta->error('Role ID missing or incorrectly passed.');
            return false;
        }

        /** @var \Gm\Backend\Role\Model\Role $role */
        $role = $this->module->getModel('Role');
        // информация о выбранной роли
        $role = $role->get($roleId);
        if ($role == null) {
            $this->getResponse()
                ->meta->error('Role ID missing or incorrectly passed.');
            return false;
        }

        // добавляем информацию о роли в хранилище модуля
        $store = $this->module->getStorage();
        $store->role = $role->getAttributes();
        /** @var \Gm\Backend\UserRoles\Model\RoleHierarchy $hierarchy */
        $hierarchy = $this->module->getModel('RoleHierarchy');
        // если есть наследование от ролей
        $parents = $hierarchy->getAllParents($role->id);
        if ($parents) {
            $parents = $role->fetchAll(null, ['*'], ['id' => $parents]);
        }

       /** @var \Gm\Panel\Widget\TabTreeGrid $tab Сетка данных в виде дерева (Gm.view.grid.Tree Gm JS) */
        $tab = parent::createWidget();
        
        // новый идентификатор вкладки, чтобы не было конфликта с 
        // главной вкладкой ("Роли и права доступа пользователей")
        $tab->setViewID('modules-tab');

        // заголовок вкладки
        $tab->title = $this->t('{modules.title}', [$store->role['name']]);
        $tab->tooltip['text'] = $tab->title;
        $tab->iconCls = 'g-icon-svg gm-userroles__icon-permissions';
        unset($tab->icon);

        // столбцы (Gm.view.grid.Tree.columns GmJS)
        $columns = [
            [
                'xtype'     => 'treecolumn',
                'text'      => ExtGrid::columnInfoIcon($this->t('Module name / Permission')),
                'cellTip'   => '{description}',
                'dataIndex' => 'name',
                'filter'    => ['type' => 'string'],
                'width'     => 320
            ]
        ];

        // если унаследован от ролей
        if ($parents) {
            // значения полей, которые будут отправлены при клике на флажок столбца
            $collectData = ['moduleId', 'roleId', 'permissions'];
            // разрешения для унаследованных ролей
            foreach ($parents as $parent) {
                $columns[] = [
                    'xtype'      => 'g-gridcolumn-checker',
                    'text'       => 'роль «' . $parent['shortname'] . '»',
                    'clsChecker' => 'g-gridcolumn-permission_pr',
                    'tooltip'    => $parent['description'],
                    'dataIndex'  => 'active' . $parent['id'],
                    'width'      => 110
                ];
                $collectData[] = 'active' . $parent['id'];
            }
            // разрешение для текущей роли
            $columns[] = [
                'text'        => 'роль «' . $role->shortname . '»',
                'xtype'       => 'g-gridcolumn-switch',
                'tooltip'     => $role->name,
                'selector'    => 'treepanel',
                'collectData' => $collectData,
                'dataIndex'   => 'active',
                'width'       => 110
            ];
            // результирующие разрешение
            $columns[] = [
                'xtype'      => 'g-gridcolumn-checker',
                'tooltip'     => '#Resultant resolution',
                'text'        => ExtGrid::columnIcon('g-icon-m_key', 'svg'),
                'clsChecker' => 'g-gridcolumn-permission_res',
                'dataIndex'  => 'result'
            ];
        } else {
            // разрешение для текущей роли
            $columns[] = [
                'text'        => 'роль «' . $role->shortname . '»',
                'xtype'       => 'g-gridcolumn-switch',
                'tooltip'     => $role->name,
                'selector'    => 'treepanel',
                'collectData' => ['moduleId', 'roleId', 'permissions'],
                'filter'      => ['type' => 'boolean'],
                'dataIndex'   => 'active',
                'width'       => 110
            ];
        }
        $tab->treeGrid->columns = $columns;

        // панель инструментов (Gm.view.grid.Tree.tbar GmJS)
        $tab->treeGrid->tbar = [
            'padding' => 1,
            'items'   => ExtGrid::buttonGroups([
                'edit' => [
                    'items' => [
                        'cleanup' => [
                            'msgConfirm' => '#Are you sure you want to remove all permissions (access rights) for the current role?'
                        ],
                        '-',
                        'refresh'
                    ]
                ],
                'columns' => [
                    'items' => [
                        'profiling',
                        'columns'
                    ]
                ],
                'search' => [
                    'items' => [
                        'help' => ['subject' => 'modulesgrid'],
                        'search'
                    ]
                ]
            ])
        ];

        // контекстное меню записи (Gm.view.grid.Tree.popupMenu GmJS)
        $tab->treeGrid->popupMenu = [];

        // новый идентификатор и маршрут сетки, чтобы не было конфликта с 
        // главной сеткой ("Роли и права доступа пользователей")
        $tab->treeGrid->setViewID('modules-grid');
        $tab->treeGrid->router->route = Gm::alias('@match', '/modules/grid');

        // поле аудита записи
        $tab->treeGrid->logField = 'name';
        // количество строк в сетке
        $tab->treeGrid->store->pageSize = 100;
        // класс CSS применяемый к элементу body сетки
        $tab->treeGrid->bodyCls = 'g-grid_background';
        // выделять только одну строку
        $tab->treeGrid->multiSelect = false;
        $tab->treeGrid->rowLines = false;

        // панель навигации (Gm.view.navigator.Info GmJS)
        $tab->navigator->info['tpl'] = HtmlNav::tags([
            HtmlNav::header('{name}'),
            ['div', '{description}'],
        ]);

        $tab
            ->addCss('/modules-grid.css')
            ->addRequire('Gm.view.grid.column.Switch');
        return $tab;
    }
}
