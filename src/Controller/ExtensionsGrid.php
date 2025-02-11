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
use Gm\Panel\Data\Model\FormModel;
use Gm\Panel\Controller\TreeGridController;
use Gm\Panel\Helper\ExtGridTree as ExtGrid;
use Gm\Panel\Helper\HtmlNavigator as HtmlNav;

/**
 * Контроллер списка прав доступа к модулям.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Controller
 * @since 1.0
 */
class ExtensionsGrid extends TreeGridController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'ExtensionsGrid';

    /**
     * Идентификатора для зависимой модели вида
     * (необходим для разграничения ViewId между моделями)
     * 
     * @var string
     */
    protected string $subViewId = 'extensions';

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
                    return $this->t('{view permissions action}', [$this->t('{permission.title}', [$store->role['name']])]);
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
     * Возвращает идентификатор роли.
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
        
        /** @var TabTreeGrid $tab Сетка данных в виде дерева (Gm.view.grid.Tree Gm JS) */
        $tab = parent::createWidget();

        // новый идентификатор вкладки, чтобы не было конфликта с 
        // главной вкладкой ("Роли и права доступа пользователей")
        $tab->setViewID('extensions-tab');

        // заголовок вкладки
        $tab->title = $this->t('{extensions.title}', [$store->role['name']]);
        $tab->tooltip['text'] = $tab->title;
        $tab->iconCls = 'g-icon-svg g-icon_extension_small';
        unset($tab->icon);

        // столбцы (Gm.view.grid.Tree.columns GmJS)
        $tab->treeGrid->columns = [
            [
                'xtype'     => 'templatecolumn',
                'text'      => '#Module name',
                'filter'    => ['type' => 'string'],
                'dataIndex' => 'module',
                'tpl'       => '<img src="{moduleIcon}" align="absmiddle"> {module}', 
                'width'     => 170
            ],
            [
                'xtype'     => 'treecolumn',
                'text'      => ExtGrid::columnInfoIcon($this->t('Extension name / Permissions')),
                'cellTip'   => '{description}',
                'dataIndex' => 'name',
                'filter'    => ['type' => 'string'],
                'width'     => 320
            ],
            [
                'text'        => '#Access',
                'xtype'       => 'g-gridcolumn-switch',
                'selector'    => 'treepanel',
                'collectData' => ['extensionId', 'roleId', 'permissions'],
                'filter'      => ['type' => 'boolean'],
                'dataIndex'   => 'active',
                'width'       => 110
            ]
        ];

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
                    'items' => ['profiling', 'columns']
                ],
                'search' => [
                    'items' => ['help' => ['subject' => 'extensionsgrid'], 'search']
                ]
            ])
        ];

        // контекстное меню записи (Gm.view.grid.Tree.popupMenu GmJS)
        $tab->treeGrid->popupMenu = [];

        // новый идентификатор и маршрут сетки, чтобы не было конфликта с 
        // главной сеткой ("Роли и права доступа пользователей")
        $tab->treeGrid->setViewID('extensions-grid');
        $tab->treeGrid->router->route = Gm::alias('@match', '/extensions/grid');

        // поле аудита записи
        $tab->treeGrid->logField = 'name';
        // количество строк в сетке
        $tab->treeGrid->store->pageSize = 100;
        // локальная фильтрация и сортировка
        $tab->treeGrid->store->remoteFilter = false;
        $tab->treeGrid->store->remoteSort = false;
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
            ->addCss('/extensions-grid.css')
            ->addRequire('Gm.view.grid.column.Switch');
        return $tab;
    }
}
