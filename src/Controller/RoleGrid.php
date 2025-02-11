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
use Gm\Panel\Widget\TabGrid;
use Gm\Panel\Helper\ExtGrid;
use Gm\Panel\Helper\ExtCombo;
use Gm\Panel\Helper\HtmlGrid;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Helper\HtmlNavigator as HtmlNav;
use Gm\Panel\Controller\GridController;

/**
 * Контроллер списка ролей пользователей.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Controller
 * @since 1.0
 */
class RoleGrid extends GridController
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
    protected string $defaultModel = 'RoleGrid';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {

        parent::init();

        $this
            ->on(self::EVENT_BEFORE_ACTION, function ($controller, $action) {
                if ($action === 'view') {
                    // подготовить кэш-таблицы
                    $this->module->prepareCache();
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): TabGrid
    {
        /** @var TabGrid $tab Сетка данных (Gm.view.grid.Grid GmJS) */
        $tab = parent::createWidget();

        // столбцы (Gm.view.grid.Grid.columns GmJS)
        $tab->grid->columns = [
            ExtGrid::columnNumberer(),
            ExtGrid::columnAction(),
            [
                'xtype' => 'g-gridcolumn-control',
                'width' => 72,
                'items' => [
                    [
                        'iconCls'   => 'g-icon-svg g-icon_extension_small',
                        'dataIndex' => 'extensionsUrl',
                        'tooltip'   => '#Extensions permissions',
                        'handler'   => 'loadWidgetFromCell'
                    ],
                    [
                        'iconCls'   => 'g-icon-svg g-icon_module_small',
                        'dataIndex' => 'permissionUrl',
                        'tooltip'   => '#Modules permissions',
                        'handler'   => 'loadWidgetFromCell'
                    ]
                ]
            ],
            [
                'text'      => '#Name',
                'dataIndex' => 'name',
                'cellTip'   => '{name}',
                'filter'    => ['type' => 'string'],
                'width'     => 200
            ],
            [
                'text'      => '#Shortname',
                'dataIndex' => 'shortname',
                'cellTip'   => '{shortname}',
                'filter'    => ['type' => 'string'],
                'width'     => 170
            ],
            [
                'text'      => '#Description',
                'dataIndex' => 'description',
                'cellTip'   => '{description}',
                'filter'    => ['type' => 'string'],
                'width'     => 200
            ],
            [
                'xtype'     => 'g-gridcolumn-template',
                'text'      => '#Parents',
                'dataIndex' => 'parents',
                'hidden'    => true,
                'tpl'       => HtmlGrid::tpl('{.} ', ['for' => '.']),
                'cellTip'   => '{parents}',
                'sortable'  => false,
                'width'     => 200
            ]
        ];

        // панель инструментов (Gm.view.grid.Grid.tbar GmJS)
        $tab->grid->tbar = [
            'padding' => 1,
            'items'   => ExtGrid::buttonGroups([
                'edit' => [
                    'items' => [
                        // инструмент "Добавить"
                        'add' => [
                            'iconCls' => 'g-icon-svg gm-userroles__icon-add',
                            'caching' => true
                        ],
                        // инструмент "Удалить"
                        'delete' => [
                            'iconCls' => 'g-icon-svg gm-userroles__icon-delete',
                        ],
                        'cleanup',
                        '-',
                        'edit',
                        'select',
                        '-',
                        'refresh'
                    ]
                ],
                'columns',
                'search' => [
                    'items' => [
                        'help' => ['subject' => 'rolegrid'],
                        'search',
                        'filter' => ExtGrid::popupFilter([
                            ExtCombo::trigger('#Parents', 'parents', 'role'),
                            ExtGrid::fieldsetAudit()
                        ], [
                            'defaults' => ['labelWidth' => 150],
                        ])
                    ]
                ]
            ])
        ];

        // контекстное меню записи (Gm.view.grid.Grid.popupMenu GmJS)
        $tab->grid->popupMenu = [
            'items' => [
                [
                    'text'    => '#Edit record',
                    'iconCls' => 'g-icon-svg g-icon-m_edit g-icon-m_color_default',
                    'handlerArgs' => [
                        'route'   => Gm::alias('@match', '/form/view/{id}'),
                        'pattern' => 'grid.popupMenu.activeRecord'
                    ],
                    'handler' => 'loadWidget'
                ],
                '-',
                [
                    'text'    => '#Extensions permissions',
                    'iconCls' => 'g-icon-svg g-icon_extension_small',
                    'handlerArgs' => [
                        'route'   => Gm::alias('@match', '/extensions/grid/view/{id}'),
                        'pattern' => 'grid.popupMenu.activeRecord'
                    ],
                    'handler' => 'loadWidget'
                ],
                [
                    'text'    => '#Modules permissions',
                    'iconCls' => 'g-icon-svg gm-userroles__icon-permissions',
                    'handlerArgs' => [
                        'route'   => Gm::alias('@match', '/modules/grid/view/{id}'),
                        'pattern' => 'grid.popupMenu.activeRecord'
                    ],
                    'handler' => 'loadWidget'
                ]
            ]
        ];

        // 2-й клик на записи
        $tab->grid->rowDblClickConfig = [
            'allow' => true,
            'route' => Gm::alias('@match', '/form/view/{id}')
        ];
        // сортировка строк в сетке
        $tab->grid->sorters = [
            ['property' => 'name', 'direction' => 'ASC']
        ];
        // количество строк в сетке
        $tab->grid->store->pageSize = 50;
        // поле аудита записи
        $tab->grid->logField = 'name';
        // плагины сетки
        $tab->grid->plugins = [
            'gridfilters', ['ptype' => 'g-rowexpander']
        ];
        // класс CSS применяемый к элементу body сетки
        $tab->grid->bodyCls = 'g-grid_background';

        // панель навигации (Gm.view.navigator.Info GmJS)
        $tab->navigator->info['tpl'] = HtmlNav::tags([
            HtmlNav::header('{name}'),
            ['fieldset',
                [
                    HtmlNav::fieldLabel($this->t('Description'), '{description}'),
                    HtmlNav::fieldLabel($this->t('Shortname'), '{shortname}'),
                    HtmlNav::fieldLabel($this->t('Parents'), '{parents}')
                ]
            ],
            HtmlNav::widgetButton(
                $this->t('Edit record'),
                ['route' => Gm::alias('@match', '/form/view/{id}'), 'long' => true],
                ['title' => $this->t('Edit record')]
            ),
            HtmlNav::widgetButton(
                $this->t('Extensions permissions'),
                ['route' => Gm::alias('@match', '/extensions/grid/view/{id}'), 'long' => true],
                ['title' => $this->t('Extensions permissions')]
            ),
            HtmlNav::widgetButton(
                $this->t('Modules permissions'),
                ['route' => Gm::alias('@match', '/modules/grid/view/{id}'), 'long' => true],
                ['title' => $this->t('Modules permissions')]
            )
        ]);

        $tab
            ->addCss('/grid.css')
            ->addRequire('Gm.view.grid.plugin.RowExpander');
        return $tab;
    }
}
