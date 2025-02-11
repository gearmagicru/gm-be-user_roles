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
use Gm\Panel\Widget\Form;
use Gm\Panel\Widget\EditWindow;
use Gm\Panel\Controller\FormController;

/**
 * Контроллер формы роли.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\UserRoles\Controller
 * @since 1.0
 */
class RoleForm extends FormController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'RoleForm';

    /**
     * {@inheritdoc}
     */
    public function createWidget(): EditWindow
    {
        /** @var EditWindow $window */
        $window = parent::createWidget();

        // панель формы (Gm.view.form.Panel GmJS)
        $window->form->autoScroll = true;
        $window->form->defaults = [
            'labelWidth' => 150,
            'anchor'     => '100%'
        ];
        $window->form->loadJSONFile('/role-form', 'items', [
            '@comboStoreUrl' => [Gm::alias('@match', '/trigger/combo')],
        ]);
        $window->form->bodyPadding = 10;
        $window->form->setStateButtons(
            Form::STATE_UPDATE, 
            ['help' => ['subject' => 'roleform'], 'reset', 'save', 'delete', 'cancel']
        );
        $window->form->setStateButtons(
            Form::STATE_INSERT, 
            ['help' => ['subject' => 'roleform'], 'add', 'cancel']
        );

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $window->width = 550;
        $window->height = 370;
        $window->layout = 'fit';
        $window->resizable = false;
        return $window;
    }
}
