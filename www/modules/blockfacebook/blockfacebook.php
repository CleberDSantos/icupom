<?php
/**
 * Copyright (C) 2017-2019 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BlockFacebook
 *
 * @since 1.0.0
 */
class BlockFacebook extends Module
{
    /**
     * BlockFacebook constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'blockfacebook';
        $this->tab = 'front_office_features';
        $this->version = '2.0.2';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Block Facebook');
        $this->description = $this->l('Displays a block for subscribing to your Facebook Page.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
    }

    /**
     * Install the module
     *
     * @return bool Indicates whether the module was successfully installed
     */
    public function install()
    {
        return parent::install() &&
            Configuration::updateValue('blockfacebook_url', 'https://www.facebook.com/thirtybees') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayHeader');
    }

    /**
     * Uninstall the module
     *
     * @return bool Indicates whether the module was successfully uninstalled
     */
    public function uninstall()
    {
        // Delete configuration
        return Configuration::deleteByName('blockfacebook_url') && parent::uninstall();
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $html = '';
        // If we try to update the settings
        if (Tools::isSubmit('submitModule')) {
            Configuration::updateValue('blockfacebook_url', Tools::getValue('blockfacebook_url'));
            $html .= $this->displayConfirmation($this->l('Configuration updated'));
            $this->_clearCache('blockfacebook.tpl');
            Tools::redirectAdmin('index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
        }

        $html .= $this->renderForm();
        $facebookurl = Configuration::get('blockfacebook_url');
        if (!strstr($facebookurl, "facebook.com")) {
            $facebookurl = "https://www.facebook.com/".$facebookurl;
        }
        $this->context->smarty->assign('facebookurl', $facebookurl);
        $this->context->smarty->assign('facebook_js_url', $this->_path.'blockfacebook.js');
        $this->context->smarty->assign('facebook_css_url', $this->_path.'css/blockfacebook.css');
        $html .= $this->context->smarty->fetch($this->local_path.'views/admin/_configure/preview.tpl');

        return $html;
    }

    /**
     * @return string
     */
    public function renderForm()
    {
        $formFields = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'  => 'text',
                        'label' => $this->l('Facebook link (full URL is required)'),
                        'name'  => 'blockfacebook_url',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($formFields));
    }

    /**
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'blockfacebook_url' => Tools::getValue('blockfacebook_url', Configuration::get('blockfacebook_url')),
        );
    }

    /**
     * @return string
     */
    public function hookDisplayLeftColumn()
    {
        if ($this->page_name !== 'index') {
            $this->_assignMedia();
        }

        return $this->hookDisplayHome();
    }

    /**
     *
     */
    protected function _assignMedia()
    {
        $this->context->controller->addCss(($this->_path).'css/blockfacebook.css');
        $this->context->controller->addJS(($this->_path).'blockfacebook.js');
    }

    /**
     * @return string
     */
    public function hookDisplayHome()
    {
        if (!$this->isCached('blockfacebook.tpl', $this->getCacheId())) {
            $facebookurl = Configuration::get('blockfacebook_url');
            if (!strstr($facebookurl, 'facebook.com')) {
                $facebookurl = 'https://www.facebook.com/'.$facebookurl;
            }
            $this->context->smarty->assign('facebookurl', $facebookurl);
        }

        return $this->display(__FILE__, 'blockfacebook.tpl', $this->getCacheId());
    }

    /**
     * @return string
     */
    public function hookDisplayRightColumn()
    {
        if ($this->page_name !== 'index') {
            $this->_assignMedia();
        }

        return $this->hookDisplayHome();
    }

    /**
     *
     */
    public function hookHeader()
    {
        $this->page_name = Dispatcher::getInstance()->getController();
        if ($this->page_name == 'index') {
            $this->_assignMedia();
        }
    }
}
