<?php
/**
 * Copyright (C) 2017-2019 thirty bees
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
 * @copyright 2017-2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

/**
 * Class CTConfiguration
 *
 * @property $bootstrap
 */
class CTConfiguration extends Module
{
    /**
     * CTConfiguration constructor.
     */
    public function __construct()
    {
        $this->name = 'ctconfiguration';
        $this->tab = 'front_office_features';
        $this->version = '1.0.5';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Community Theme Configuration');
        $this->description = $this->l('Configuration for community theme blocks and content.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';

        $this->bootstrap = true;
    }

    /**
     * Installs module to PrestaShop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        parent::install();

        $hooksToUnhook = [
            ['module' => 'blockcategories', 'hook' => 'footer'],
        ];
        foreach ($hooksToUnhook as $unhook) {
            $this->unhookModule($unhook['module'], $unhook['hook']);
        }

        $hooksToInstall = ['displayHeader', 'displayFooterProduct'];
        foreach ($hooksToInstall as $hookName) {
            $this->registerHook($hookName);
        }

        // Disable scenes in config for faster loading, scenes are removed in category template
        Configuration::updateValue('PS_SCENE_FEATURE_ACTIVE', false);

        // Translatable configuration items
        foreach (Language::getLanguages(false) as $language) {
            $idLanguage = (int) $language['id_lang'];

            Configuration::updateValue(
                'CT_CFG_COPYRIGHT_CONTENT',
                [
                    $idLanguage => '&copy; Acme Corporation 2018',
                ]
            );
        }

        return true;
    }

    /**
     * Unhooks a module hook
     *
     * @param string $module
     * @param string $hook
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function unhookModule($module, $hook)
    {
        $id_module = Module::getModuleIdByName($module);
        $id_hook = Hook::getIdByName($hook);

        return Db::getInstance()->delete('hook_module', 'id_module = '.(int) $id_module.' AND id_hook = '.(int) $id_hook);
    }

    /***
     * Uninstalls module from PrestaShop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $keysToDrop = [
            'CT_CFG_BLOCKCATEGORIES_FOOTER',
            'CT_CFG_COPYRIGHT_CONTENT',
        ];
        foreach ($keysToDrop as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Compiles and returns module configuration page content
     *
     * @return string
     * @throws Exception
     * @throws HTMLPurifier_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            $this->postProcess();
        }

        $moduleUrl = $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name;

        $fieldSets = [
            'general' => [
                'title'   => $this->l('Module settings'),
                'fields'  => $this->getOptionFields(),
                'buttons' => [
                    'cancelBlock' => [
                        'title' => $this->l('Cancel'),
                        'href'  => $moduleUrl,
                        'icon'  => 'process-icon-cancel',
                    ],
                ],
                'submit'  => [
                    'name'  => 'submit'.$this->name,
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $h = new HelperOptions();
        $h->token = Tools::getAdminTokenLite('AdminModules');
        $h->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $h->id = Tab::getIdFromClassName('AdminTools');

        return $h->generateOptions($fieldSets);
    }

    /**
     * Processes submitted configuration variables
     *
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    protected function postProcess()
    {
        $castFunctions = ['boolval', 'doubleval', 'floatval', 'intval', 'strval'];
        $langIds = Language::getIDs(false);

        $values = [];
        foreach ($this->getOptionFields() as $key => $field) {
            $htmlAllowed = isset($field['html']) && $field['html'];

            if ($field['type'] == 'textareaLang' || $field['type'] == 'textLang') {
                $values[$key] = [];
                foreach ($langIds as $idLang) {
                    $value = Tools::getValue($key.'_'.$idLang);
                    if (isset($field['cast']) && $field['cast'] && in_array($field['cast'], $castFunctions)) {
                        $value = call_user_func($field['cast'], $value);
                    }

                    $values[$key][$idLang] = $value;
                }
            } else {
                $value = Tools::getValue($key);
                if (isset($field['cast']) && $field['cast'] && in_array($field['cast'], $castFunctions)) {
                    $value = call_user_func($field['cast'], $value);
                }

                $values[$key] = $value;
            }

            Configuration::updateValue($key, $values[$key], $htmlAllowed);
        }

        if ($values['CT_CFG_BLOCKCATEGORIES_FOOTER']) {
            $this->hookModule('blockcategories', 'footer');
        } else {
            $this->unhookModule('blockcategories', 'footer');
        }
    }

    /**
     * Return HelperOptions fields that are using in module configuration form.
     *
     * @return array
     */
    protected function getOptionFields()
    {
        return [
            'CT_CFG_BLOCKCATEGORIES_FOOTER' => [
                'title' => $this->l('Show blockcategories footer block'),
                'desc'  => $this->l('If enabled, shows category tree block in the footer.'),
                'cast'  => 'boolval',
                'type'  => 'bool',
            ],
            'CT_CFG_COPYRIGHT_CONTENT'      => [
                'title' => $this->l('Copyright footer text'),
                'desc'  => $this->l('Text to be displayed in the copyright footer block.').' '.$this->l('Leave empty to not display the block.'),
                'hint'  => $this->l('HTML is allowed. Enter &amp;copy; for copyright symbol.'),
                'cast'  => 'strval',
                'type'  => 'textareaLang',
                'html'  => true,
                'size'  => 50,
            ],
        ];
    }

    /**
     * Registers a module hook
     *
     * @param string $module
     * @param string $hook
     *
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function hookModule($module, $hook)
    {
        $module = Module::getInstanceByName($module);

        return $module->registerHook($hook);
    }

    /**
     * Adds assets to page header
     * and passes configuration variables to smarty
     */
    public function hookDisplayHeader()
    {
        $idLang = (int) $this->context->language->id;
        static $copyrightContent = [];
        if (!isset($copyrightContent[$idLang])) {
            try {
                $copyrightContent[$idLang] = Configuration::get('CT_CFG_COPYRIGHT_CONTENT', $idLang);
            } catch (PrestaShopException $e) {
                $copyrightContent[$idLang] = '';
            }
        }

        $this->context->smarty->assign(
            [
                'ctheme' => [
                    'footer' => [
                        'copyright' => [
                            'display' => true,
                            'html'    => $copyrightContent[$idLang],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Adds JS files to product page
     */
    public function hookDisplayFooterProduct()
    {
        try {
            $jqZoom = (bool) Configuration::get('PS_DISPLAY_JQZOOM');
        } catch (PrestaShopException $e) {
            $jqZoom = false;
        }

        if ($jqZoom) {
            // Remove jQuery Zoom
            $jqZoomPluginPath = Media::getJqueryPluginPath('jqzoom');
            $this->context->controller->removeJS($jqZoomPluginPath['js']);
            $this->context->controller->removeCSS($jqZoomPluginPath['css']);

            // Add new version of jqZoom plugin
            $this->context->controller->addJS($this->_path.'views/js/vendor/jquery.zoom.min.js');
        }
    }
}
