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

require_once __DIR__.'/classes/InfoBlock.php';

/**
 * Class Blockcmsinfo
 */
class Blockcmsinfo extends Module
{
    // @codingStandardsIgnoreStart
    /** @var string $html */
    public $html = '';
    /** @var array $fields_list */
    public $fields_list = [];
    // @codingStandardsIgnoreEnd

    /**
     * Blockcmsinfo constructor.
     */
    public function __construct()
    {
        $this->name = 'blockcmsinfo';
        $this->tab = 'front_office_features';
        $this->version = '2.0.7';
        $this->author = 'thirty bees';
        $this->bootstrap = true;
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Block CMS Info');
        $this->description = $this->l('Adds custom information blocks in your store.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
    }

    /**
     * Install this module
     *
     * @return bool
     */
    public function install()
    {
        return parent::install() &&
            $this->installDB() &&
            $this->registerHook('home') &&
            $this->installFixtures();
    }



    /**
     * Install fixtures
     *
     * @return bool
     */
    public function installFixtures()
    {
        $tabTexts = json_decode(file_get_contents(__DIR__.'/data/fixtures.json'), true);

        $shopsIds = Shop::getShops(true, null, true);
        $return = true;
        foreach ($tabTexts as $tab) {
            $info = new InfoBlock();
            foreach (Language::getLanguages(false) as $lang) {
                $info->text[$lang['id_lang']] = $tab['text'];
            }
            foreach ($shopsIds as $idShop) {
                $info->id_shop = $idShop;
                $return &= $info->add();
            }
        }

        return $return;
    }

    /**
     * Uninstall this module
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDB();
    }

    /**
     * Remove database tables
     *
     * @param bool $dropTable
     *
     * @return bool
     */
    public function uninstallDB($dropTable = true)
    {
        if ($dropTable) {
            return $this->execDbQueries('uninstall');
        }

        return true;
    }

    /**
     * Get module configuration page
     *
     * @return string
     */
    public function getContent()
    {
        $idInfo = (int) Tools::getValue('id_info');

        if (Tools::isSubmit('saveblockcmsinfo')) {
            if (!Tools::getValue('text_'.(int) Configuration::get('PS_LANG_DEFAULT'), false)) {
                return $this->html.$this->displayError($this->l('You must fill in all fields.')).$this->renderForm();
            } elseif ($this->processSaveCmsInfo()) {
                return $this->html.$this->renderList();
            } else {
                return $this->html.$this->renderForm();
            }
        } elseif (Tools::isSubmit('updateblockcmsinfo') || Tools::isSubmit('addblockcmsinfo')) {
            $this->html .= $this->renderForm();

            return $this->html;
        } else {
            if (Tools::isSubmit('deleteblockcmsinfo')) {
                $info = new InfoBlock((int) $idInfo);
                $info->delete();
                $this->_clearCache('blockcmsinfo.tpl');
                Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
            } else {
                $this->html .= $this->renderList();

                return $this->html;
            }
        }

        return '';
    }

    /**
     * Get form values
     *
     * @return array
     */
    public function getFormValues()
    {
        $fieldsValue = [];
        $idInfo = (int) Tools::getValue('id_info');

        foreach (Language::getLanguages(false) as $lang) {
            if ($idInfo) {
                $info = new InfoBlock((int) $idInfo);
                $fieldsValue['text'][(int) $lang['id_lang']] = $info->text[(int) $lang['id_lang']];
            } else {
                $fieldsValue['text'][(int) $lang['id_lang']] = Tools::getValue('text_'.(int) $lang['id_lang'], '');
            }
        }

        $fieldsValue['id_info'] = $idInfo;

        return $fieldsValue;
    }

    /**
     * Process save CMS info
     *
     * @return bool
     */
    public function processSaveCmsInfo()
    {
        if ($idInfo = Tools::getValue('id_info')) {
            $info = new InfoBlock((int) $idInfo);
        } else {
            $info = new InfoBlock();
            if (Shop::isFeatureActive()) {
                $shopIds = Tools::getValue('checkBoxShopAsso_configuration');
                if (!$shopIds) {
                    $this->html .= '<div class="alert alert-danger conf error">'.$this->l('You have to select at least one shop.').'</div>';

                    return false;
                }
            } else {
                $info->id_shop = Shop::getContextShopID();
            }
        }

        $languages = Language::getLanguages(false);
        $text = [];
        foreach ($languages as $lang) {
            $text[$lang['id_lang']] = Tools::getValue('text_'.$lang['id_lang']);
        }
        $info->text = $text;

        if (Shop::isFeatureActive() && !$info->id_shop) {
            $saved = true;
            if (isset($shopIds) && is_array($shopIds)) {
                foreach ($shopIds as $idShop) {
                    $info->id_shop = $idShop;
                    try {
                        $saved &= $info->save();
                    } catch (Exception $e) {
                        $saved = false;
                        $this->html .= $this->displayError($e->getMessage());
                    }
                }
            }
        } else {
            try {
                $saved = $info->save();
            } catch (Exception $e) {
                $saved = false;
                $this->html .= $this->displayError($e->getMessage());
            }
        }

        if ($saved) {
            $this->_clearCache('blockcmsinfo.tpl');
        } else {
            $this->html .= '<div class="alert alert-danger conf error">'.$this->l('An error occurred while attempting to save.').'</div>';
        }

        return $saved;
    }

    /**
     * @return string
     */
    public function hookHome()
    {
        $this->context->controller->addCSS($this->_path.'style.css', 'all');
        if (!$this->isCached('blockcmsinfo.tpl', $this->getCacheId())) {
            $infos = $this->getInfos($this->context->language->id, $this->context->shop->id);
            $this->context->smarty->assign(['infos' => $infos, 'nbblocks' => count($infos)]);
        }

        return $this->display(__FILE__, 'blockcmsinfo.tpl', $this->getCacheId());
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return array|false|null|PDOStatement
     */
    public function getInfos($idLang, $idShop)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('r.`id_info`, r.`id_shop`, rl.`text`')
                ->from('info', 'r')
                ->leftJoin('info_lang', 'rl', 'r.`id_info` = rl.`id_info`')
                ->where('`id_lang` = '.(int) $idLang)
                ->where('`id_shop` = '.(int) $idShop)
        );
    }

    /**
     * Install the database tables for this module
     *
     * @return bool
     */
    protected function installDB()
    {
        return $this->execDbQueries('install');
    }

    protected function execDbQueries($file = 'install')
    {
        if (!file_exists(__DIR__."/sql/$file.sql")) {
            return false;
        } elseif (!$sql = file_get_contents(__DIR__."/sql/$file.sql")) {
            return false;
        }
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
        $sql = preg_split("/;\s*[\r\n]+/", trim($sql));

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute(trim($query))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render form
     *
     * @return string
     */
    protected function renderForm()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('New custom CMS block'),
            ],
            'input'   => [
                'id_info' => [
                    'type' => 'hidden',
                    'name' => 'id_info',
                ],
                'content' => [
                    'type'         => 'textarea',
                    'label'        => $this->l('Text'),
                    'lang'         => true,
                    'name'         => 'text',
                    'cols'         => 40,
                    'rows'         => 10,
                    'class'        => 'rte',
                    'autoload_rte' => true,
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
            'buttons' => [
                [
                    'href'  => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->l('Back to list'),
                    'icon'  => 'process-icon-back',
                ],
            ],
        ];

        if (Shop::isFeatureActive() && Tools::getValue('id_info') == false) {
            $fieldsForm['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso_theme',
            ];
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = 'blockcmsinfo';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = [
                'id_lang'    => $lang['id_lang'],
                'iso_code'   => $lang['iso_code'],
                'name'       => $lang['name'],
                'is_default' => ($defaultLang == $lang['id_lang'] ? 1 : 0),
            ];
        }

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'saveblockcmsinfo';

        $helper->fields_value = $this->getFormValues();

        return $helper->generateForm([['form' => $fieldsForm]]);
    }

    protected function renderList()
    {
        $this->fields_list['id_info'] = [
            'title'   => $this->l('Block ID'),
            'type'    => 'text',
            'search'  => false,
            'orderby' => false,
        ];

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->fields_list['shop_name'] = [
                'title'   => $this->l('Shop'),
                'type'    => 'text',
                'search'  => false,
                'orderby' => false,
            ];
        }

        $this->fields_list['text'] = [
            'title'   => $this->l('Block text'),
            'type'    => 'text',
            'search'  => false,
            'orderby' => false,
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_info';
        $helper->actions = ['edit', 'delete'];
        $helper->show_toolbar = true;
        $helper->imageType = 'jpg';
        $helper->toolbar_btn['new'] = [
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Add new'),
        ];

        $helper->title = $this->displayName;
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $content = $this->getListContent($this->context->language->id);

        return $helper->generateList($content, $this->fields_list);
    }

    /**
     * Get list content
     *
     * @param int|null $idLang
     *
     * @return array|false|null|PDOStatement
     */
    protected function getListContent($idLang = null)
    {
        if (is_null($idLang)) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $content = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('r.`id_info`, rl.`text`, s.`name` AS `shop_name`')
                ->from('info', 'r')
                ->leftJoin('info_lang', 'rl', 'r.`id_info` = rl.`id_info`')
                ->leftJoin('shop', 's', 'r.`id_shop` = s.`id_shop`')
                ->where('`id_lang` = '.(int) $idLang.' '.Shop::addSqlRestriction(false, 'r'))
        );

        foreach ($content as $key => $value) {
            $content[$key]['text'] = substr(strip_tags($value['text']), 0, 200);
        }

        return $content;
    }
}
