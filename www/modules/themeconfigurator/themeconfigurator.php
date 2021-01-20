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
 * Class ThemeConfigurator
 */
class ThemeConfigurator extends Module
{
    // @codingStandardsIgnoreStart
    /** @var int $max_image_size */
    protected $max_image_size = 1048576;
    /** @var mixed $default_language */
    protected $default_language;
    /** @var array $languages */
    protected $languages;
    /** @var string $admin_tpl_path */
    public $admin_tpl_path;
    /** @var string $hooks_tpl_path */
    public $hooks_tpl_path;
    /** @var string $uploads_path */
    public $uploads_path;
    /** @var string $secure_key */
    public $secure_key;
    /** @var string $module_path */
    public $module_path;
    /** @var string $module_url */
    public $module_url = '';
    /** @var array $fields_form */
    public $fields_form;
    // @codingStandardsIgnoreEnd

    /**
     * ThemeConfigurator constructor.
     */
    public function __construct()
    {
        $this->name = 'themeconfigurator';
        $this->tab = 'front_office_features';
        $this->version = '3.0.9';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name);
        $this->default_language = Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'));
        $this->languages = Language::getLanguages();
        parent::__construct();
        $this->displayName = $this->l('Theme Configurator');
        $this->description = $this->l('Configure the main elements of your theme.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->module_path = _PS_MODULE_DIR_.$this->name.'/';
        if (isset(Context::getContext()->employee->id) && Context::getContext()->employee->id && Context::getContext()->link instanceof Link) {
            $this->module_url = Context::getContext()->link->getAdminLink('AdminModules', true).'&'.http_build_query([
                'configure'   => $this->name,
                'tab_module'  => $this->tab,
                'module_name' => $this->name,
            ]);
        }
        $this->uploads_path = _PS_MODULE_DIR_.$this->name.'/img/';
        $this->admin_tpl_path = _PS_MODULE_DIR_.$this->name.'/views/templates/admin/';
        $this->hooks_tpl_path = _PS_MODULE_DIR_.$this->name.'/views/templates/hooks/';
    }

    /**
     * Install the module
     *
     * @return bool
     */
    public function install()
    {
        $themesColors = [
            'theme1',
            'theme2',
            'theme3',
            'theme4',
            'theme5',
            'theme6',
            'theme7',
            'theme8',
            'theme9',
        ];
        $themesFonts = [
            'font1'  => 'Open Sans',
            'font2'  => 'Josefin Slab',
            'font3'  => 'Arvo',
            'font4'  => 'Lato',
            'font5'  => 'Volkorn',
            'font6'  => 'Abril Fatface',
            'font7'  => 'Ubuntu',
            'font8'  => 'PT Sans',
            'font9'  => 'Old Standard TT',
            'font10' => 'Droid Sans',
        ];

        if (!parent::install()
            || !$this->installDB()
        ) {
            return false;
        }

        $this->registerHook('displayHeader');
        $this->registerHook('displayTopColumn');
        $this->registerHook('displayLeftColumn');
        $this->registerHook('displayRightColumn');
        $this->registerHook('displayHome');
        $this->registerHook('displayFooter');
        $this->registerHook('displayBackOfficeHeader');
        $this->registerHook('actionObjectLanguageAddAfter');
        // TODO: these two shouldn't be stored in the database, but provided
        //       as instance variables.
        Configuration::updateValue('PS_TC_THEMES', json_encode($themesColors));
        Configuration::updateValue('PS_TC_FONTS', json_encode($themesFonts));

        Configuration::updateValue('PS_TC_THEME', '');
        Configuration::updateValue('PS_TC_FONT', '');
        Configuration::updateValue('PS_TC_ACTIVE', 1);
        Configuration::updateValue('PS_SET_DISPLAY_SUBCATEGORIES', 1);

        $this->installFixtures(Language::getLanguages(true));

        return true;
    }

    /**
     * Install the module's fixtures
     *
     * @param array|null $languages
     *
     * @return bool
     */
    public function installFixtures($languages = null)
    {
        $result = true;

        if ($languages === null) {
            $languages = Language::getLanguages(true);
        }

        foreach ($languages as $language) {
            $result &= $this->installFixture('top', 1, $this->context->shop->id, $language['id_lang']);
        }

        return $result;
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        $images = [];
        if (count(Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SHOW TABLES LIKE \''._DB_PREFIX_.'themeconfigurator\''))) {
            $images = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`image`')
                    ->from('themeconfigurator')
            );
        }
        foreach ($images as $image) {
            $this->deleteImage($image['image']);
        }

        if (!$this->runQueries('uninstall') || !parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Display back office header
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->controller->addCSS($this->_path.'css/admin.css');
        $this->context->controller->addJquery();
        $this->context->controller->addJS($this->_path.'js/admin.js');
    }

    /**
     * Display header
     *
     * @return string
     */
    public function hookdisplayHeader()
    {
        $this->context->controller->addCss($this->_path.'css/hooks.css', 'all');

        if ((int) Configuration::get('PS_TC_ACTIVE') == 1 && Tools::getValue('live_configurator_token') && Tools::getValue('live_configurator_token') == $this->getLiveConfiguratorToken() && $this->checkEnvironment()) {
            $this->context->controller->addCSS($this->_path.'css/live_configurator.css');
            $this->context->controller->addJS($this->_path.'js/live_configurator.js');

            if (Tools::getValue('theme')) {
                $this->context->controller->addCss($this->_path.'css/'.Tools::getValue('theme').'.css', 'all');
            }

            if (Tools::getValue('theme_font')) {
                $this->context->controller->addCss($this->_path.'css/'.Tools::getValue('theme_font').'.css', 'all');
            }
        } else {
            if (Configuration::get('PS_TC_THEME') != '') {
                $this->context->controller->addCss($this->_path.'css/'.Configuration::get('PS_TC_THEME').'.css', 'all');
            }

            if (Configuration::get('PS_TC_FONT') != '') {
                $this->context->controller->addCss($this->_path.'css/'.Configuration::get('PS_TC_FONT').'.css', 'all');
            }
        }

        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'category') {
            $this->context->smarty->assign(
                [
                    'display_subcategories' => (int) Configuration::get('PS_SET_DISPLAY_SUBCATEGORIES'),
                ]
            );

            return $this->display(__FILE__, 'hook.tpl');
        }

        return '';
    }

    /**
     * Live configurator token
     *
     * @return bool|string
     */
    public function getLiveConfiguratorToken()
    {
        return Tools::getAdminToken($this->name.(int) Tab::getIdFromClassName($this->name).((Context::getContext()->employee instanceof Employee) ? (int) Context::getContext()->employee->id : Tools::getValue('id_employee')));
    }

    /**
     * Hook to adding a language object
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixtures([['id_lang' => (int) $params['object']->id]]);
    }

    /**
     * @return string
     */
    public function hookdisplayTopColumn()
    {
        return $this->hookdisplayTop();
    }

    /**
     * @return string
     */
    public function hookdisplayTop()
    {
        if (!isset($this->context->controller->php_self) || $this->context->controller->php_self != 'index') {
            return '';
        }
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('top'),
                'hook'      => 'top',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    /**
     * Hook display home
     *
     * @return string
     */
    public function hookDisplayHome()
    {
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('home'),
                'hook'      => 'home',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    /**
     * Display left column
     *
     * @return string
     */
    public function hookDisplayLeftColumn()
    {
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('left'),
                'hook'      => 'left',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    /**
     * Display right column
     *
     * @return string
     */
    public function hookDisplayRightColumn()
    {
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('right'),
                'hook'      => 'right',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    /**
     * Display footer
     *
     * @return string
     */
    public function hookDisplayFooter()
    {
        $html = '';

        if ((int) Configuration::get('PS_TC_ACTIVE') == 1 && Tools::getValue('live_configurator_token') && Tools::getValue('live_configurator_token') == $this->getLiveConfiguratorToken() && Tools::getIsset('id_employee') && $this->checkEnvironment()) {
            if (Tools::isSubmit('submitLiveConfigurator')) {
                Configuration::updateValue('PS_TC_THEME', Tools::getValue('theme'));
                Configuration::updateValue('PS_TC_FONT', Tools::getValue('theme_font'));
            }

            $adImage = $this->_path.'img/'.$this->context->language->iso_code.'/advertisement.png';

            if (!file_exists($adImage)) {
                $adImage = $this->_path.'img/en/advertisement.png';
            }

            $themes = json_decode(Configuration::get('PS_TC_THEMES'), true);
            $fonts = json_decode(Configuration::get('PS_TC_FONTS'), true);

            // Retrocompatibility for module versions <= 3.0.7, which happened
            // to use serialize() rather than json_encode().
            if (!$themes) {
                $themes = Tools::unSerialize(Configuration::get('PS_TC_THEMES'));
            }
            if (!$fonts) {
                $fonts = Tools::unSerialize(Configuration::get('PS_TC_FONTS'));
            }

            $this->smarty->assign(
                [
                    'themes'                  => $themes,
                    'fonts'                   => $fonts,
                    'theme_font'              => Tools::getValue('theme_font', Configuration::get('PS_TC_FONT')),
                    'live_configurator_token' => $this->getLiveConfiguratorToken(),
                    'id_shop'                 => (int) $this->context->shop->id,
                    'id_employee'             => is_object($this->context->employee) ? (int) $this->context->employee->id : Tools::getValue('id_employee'),
                    'advertisement_image'     => $adImage,
                    'advertisement_url'       => '',
                    'advertisement_text'      => '',
                ]
            );

            $html .= $this->display(__FILE__, 'live_configurator.tpl');
        }

        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('footer'),
                'hook'      => 'footer',
            ]
        );

        return $html.$this->display(__FILE__, 'hook.tpl');
    }

    /**
     * Get module configuration page
     *
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitModule')) {
            Configuration::updateValue('PS_QUICK_VIEW', (int) Tools::getValue('quick_view'));
            Configuration::updateValue('PS_TC_ACTIVE', (int) Tools::getValue('live_conf'));
            Configuration::updateValue('PS_GRID_PRODUCT', (int) Tools::getValue('grid_list'));
            Configuration::updateValue('PS_SET_DISPLAY_SUBCATEGORIES', (int) Tools::getValue('sub_cat'));
            foreach ($this->getConfigurableModules() as $module) {
                if (!isset($module['is_module']) || !$module['is_module'] || !Validate::isModuleName($module['name']) || !Tools::isSubmit($module['name'])) {
                    continue;
                }

                $moduleInstance = Module::getInstanceByName($module['name']);
                if ($moduleInstance === false || !is_object($moduleInstance)) {
                    continue;
                }

                $isInstalled = (int) Validate::isLoadedObject($moduleInstance);
                if ($isInstalled) {
                    if (($active = (int) Tools::getValue($module['name'])) == $moduleInstance->active) {
                        continue;
                    }

                    if ($active) {
                        $moduleInstance->enable();
                    } else {
                        $moduleInstance->disable();
                    }
                } else {
                    if ((int) Tools::getValue($module['name'])) {
                        $moduleInstance->install();
                    }
                }
            }
        }

        if (Tools::isSubmit('newItem')) {
            $this->addItem();
        } elseif (Tools::isSubmit('updateItem')) {
            $this->updateItem();
        } elseif (Tools::isSubmit('removeItem')) {
            $this->removeItem();
        }

        $html = $this->renderConfigurationForm();
        $html .= $this->renderThemeConfiguratorForm();

        return $html;
    }

    /**
     * Ajax position update
     */
    public function ajaxProcessUpdatePosition()
    {
        $items = Tools::getValue('item');
        $total = count($items);
        $success = true;
        for ($i = 1; $i <= $total; $i++) {
            $success &= Db::getInstance()->update(
                'themeconfigurator',
                ['item_order' => $i],
                '`id_item` = '.preg_replace('/(item-)([0-9]+)/', '${2}', $items[$i - 1])
            );
        }
        if (!$success) {
            die(json_encode(['error' => 'Update Fail']));
        }
        die(json_encode(['success' => 'Update Success !', 'error' => false]));
    }

    protected function getConfigurableModules()
    {
        // Construct the description for the 'Enable Live Configurator' switch
        if ($this->context->shop->getBaseURL()) {
            $request = 'live_configurator_token='.$this->getLiveConfiguratorToken().'&id_employee='.(int) $this->context->employee->id.'&id_shop='.(int) $this->context->shop->id.(Configuration::get('PS_TC_THEME') != '' ? '&theme='.Configuration::get('PS_TC_THEME') : '').(Configuration::get('PS_TC_FONT') != '' ? '&theme_font='.Configuration::get('PS_TC_FONT') : '');
            $url = $this->context->link->getPageLink('index', null, $idLang = null, $request);
            $desc = '<a class="btn btn-default" href="'.$url.'" onclick="return !window.open($(this).attr(\'href\'));" id="live_conf_button">'.$this->l('View').' <i class="icon-external-link"></i></a><br />'.$this->l('Only you can see this on your front office - your visitors will not see this tool.');
        } else {
            $desc = $this->l('Only you can see this on your front office - your visitors will not see this tool.');
        }

        $ret = [
            [
                'label'     => $this->l('Display links to your store\'s social accounts (Twitter, Facebook, etc.)'),
                'name'      => 'blocksocial',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blocksocial')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display your contact information'),
                'name'      => 'blockcontactinfos',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockcontactinfos')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display social sharing buttons on the product\'s page'),
                'name'      => 'socialsharing',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('socialsharing')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display the Facebook block on the home page'),
                'name'      => 'blockfacebook',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockfacebook')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display the custom CMS information block'),
                'name'      => 'blockcmsinfo',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockcmsinfo')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label' => $this->l('Display quick view window on homepage and category pages'),
                'name'  => 'quick_view',
                'value' => (int) Tools::getValue('PS_QUICK_VIEW', Configuration::get('PS_QUICK_VIEW')),
            ],
            [
                'label' => $this->l('Display categories as a list of products instead of the default grid-based display'),
                'name'  => 'grid_list',
                'value' => (int) Configuration::get('PS_GRID_PRODUCT'),
                'desc'  => $this->l('Works only for first-time users. This setting is overridden by the user\'s choice as soon as the user cookie is set.'),
            ],
            [
                'label'     => $this->l('Display top banner'),
                'name'      => 'blockbanner',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockbanner')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display logos of available payment methods'),
                'name'      => 'productpaymentlogos',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('productpaymentlogos')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
        ];

        $ret[] = [
            'label' => $this->l('Display Live Configurator'),
            'name'  => 'live_conf',
            'value' => (int) Tools::getValue('PS_TC_ACTIVE', Configuration::get('PS_TC_ACTIVE')),
            'hint'  => $this->l('This customization tool allows you to make color and font changes in your theme.'),
            'desc'  => $desc,
        ];

        $ret[] = [
                'label' => $this->l('Display subcategories'),
                'name'  => 'sub_cat',
                'value' => (int) Tools::getValue('PS_SET_DISPLAY_SUBCATEGORIES', Configuration::get('PS_SET_DISPLAY_SUBCATEGORIES')),
        ];

        return $ret;
    }

    protected function addItem()
    {
        $title = Tools::getValue('item_title');
        $content = Tools::getValue('item_html');

        if (!Validate::isCleanHtml($title, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))
            || !Validate::isCleanHtml($content, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))
        ) {
            $this->context->smarty->assign('error', $this->l('Invalid content'));

            return false;
        }

        if (!$currentOrder = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`item_order` + 1')
                ->from('themeconfigurator')
                ->where('`id_shop` = '.(int) $this->context->shop->id)
                ->where('`id_lang` = '.(int) Tools::getValue('id_lang'))
                ->where('`hook` = \''.pSQL(Tools::getValue('item_hook')).'\'')
                ->orderBy('`item_order` DESC')
        )) {
            $currentOrder = 1;
        }

        $imageW = is_numeric(Tools::getValue('item_img_w')) ? (int) Tools::getValue('item_img_w') : '';
        $imageH = is_numeric(Tools::getValue('item_img_h')) ? (int) Tools::getValue('item_img_h') : '';

        if (!empty($_FILES['item_img']['name'])) {
            if (!$image = $this->uploadImage($_FILES['item_img'], $imageW, $imageH)) {
                return false;
            }
        } else {
            $image = '';
            $imageW = '';
            $imageH = '';
        }

        if (!Db::getInstance()->insert(
            'themeconfigurator',
            [
                'id_shop'    => (int) $this->context->shop->id,
                'id_lang'    => (int) Tools::getValue('id_lang'),
                'item_order' => (int) $currentOrder,
                'title'      => pSQL($title),
                'title_use'  => (int) Tools::getValue('item_title_use'),
                'hook'       => pSQL(Tools::getValue('item_hook')),
                'url'        => pSQL(Tools::getValue('item_url')),
                'target'     => (int) Tools::getValue('item_target'),
                'image'      => pSQL($image),
                'image_w'    => pSQL($imageW),
                'image_h'    => pSQL($imageH),
                'html'       => pSQL($this->filterVar($content), true),
                'active'     => 1,
            ]
        )) {
            if (!Tools::isEmpty($image)) {
                $this->deleteImage($image);
            }

            $this->context->smarty->assign('error', $this->l('An error occurred while saving data.'));

            return false;
        }

        $this->context->smarty->assign('confirmation', $this->l('New item successfully added.'));

        return true;
    }

    /**
     * @param array  $image
     * @param string $imageW
     * @param string $imageH
     *
     * @return bool|string
     */
    protected function uploadImage($image, $imageW = '', $imageH = '')
    {
        $res = false;
        if (is_array($image) && (ImageManager::validateUpload($image, $this->max_image_size) === false) && ($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) && move_uploaded_file($image['tmp_name'], $tmpName)) {
            $salt = sha1(microtime());
            $pathinfo = pathinfo($image['name']);
            $imgName = $salt.'_'.Tools::str2url(str_replace('%', '', urlencode($pathinfo['filename']))).'.'.$pathinfo['extension'];

            if (ImageManager::resize($tmpName, dirname(__FILE__).'/img/'.$imgName, $imageW, $imageH)) {
                $res = true;
            }
        }

        if (!$res) {
            $this->context->smarty->assign('error', $this->l('An error occurred during the image upload.'));

            return false;
        }

        return isset($imgName) ? $imgName : false;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function filterVar($value)
    {
        return Tools::purifyHTML($value);
    }

    /**
     * @return bool
     */
    protected function updateItem()
    {
        $idItem = (int) Tools::getValue('item_id');
        $title = Tools::getValue('item_title');
        $content = Tools::getValue('item_html');

        if (!Validate::isCleanHtml($title, (int) Configuration::get('PS_ALLOW_HTML_IFRAME')) || !Validate::isCleanHtml($content, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))) {
            $this->context->smarty->assign('error', $this->l('Invalid content'));

            return false;
        }

        $imageW = (is_numeric(Tools::getValue('item_img_w'))) ? (int) Tools::getValue('item_img_w') : '';
        $imageH = (is_numeric(Tools::getValue('item_img_h'))) ? (int) Tools::getValue('item_img_h') : '';

	    $oldImage = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
		    (new DbQuery())
			    ->select('`image`')
			    ->from('themeconfigurator')
			    ->where('`id_item` = '.(int) $idItem)
	    );

	    if (!empty($_FILES['item_img']['name'])) {
            if ($oldImage) {
                if (file_exists(dirname(__FILE__).'/img/'.$oldImage)) {
                    @unlink(dirname(__FILE__).'/img/'.$oldImage);
                }
            }

            if (!$image = $this->uploadImage($_FILES['item_img'], $imageW, $imageH)) {
                return false;
            }
        }

        if (!Db::getInstance()->update(
            'themeconfigurator',
            [
                'title'     => pSQL($title),
                'title_use' => (int) Tools::getValue('item_title_use'),
                'hook'      => pSQL(Tools::getValue('item_hook')),
                'url'       => pSQL(Tools::getValue('item_url')),
                'target'    => (int) Tools::getValue('item_target'),
                'image'     => isset($image) ? pSQL($image) : $oldImage,
                'image_w'   => (int) $imageW,
                'image_h'   => (int) $imageH,
                'active'    => (int) Tools::getValue('item_active'),
                'html'      => pSQL($this->filterVar($content), true),
            ],
            '`id_item` = '.(int) Tools::getValue('item_id')
        )) {
            if ($image = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
	            (new DbQuery())
		            ->select('`image`')
		            ->from('themeconfigurator')
		            ->where('`id_item` = '.(int) Tools::getValue('item_id'))
            )) {
                $this->deleteImage($image);
            }

            $this->context->smarty->assign('error', $this->l('An error occurred while saving data.'));

            return false;
        }

        $this->context->smarty->assign('confirmation', $this->l('Successfully updated.'));

        return true;
    }

    protected function removeItem()
    {
        $idItem = (int) Tools::getValue('item_id');

        if ($image = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`image`')
                ->from('themeconfigurator')
                ->where('`id_item` = '.(int) $idItem)
        )) {
            $this->deleteImage($image);
        }

        Db::getInstance()->delete(_DB_PREFIX_.'themeconfigurator', 'id_item = '.(int) $idItem);

        if (Db::getInstance()->Affected_Rows() == 1) {
            Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'themeconfigurator`
				SET item_order = item_order-1
				WHERE (
					item_order > '.(int) Tools::getValue('item_order').' AND
					id_shop = '.(int) $this->context->shop->id.' AND
					hook = \''.pSQL(Tools::getValue('item_hook')).'\')'
            );
            Tools::redirectAdmin('index.php?tab=AdminModules&configure='.$this->name.'&conf=6&token='.Tools::getAdminTokenLite('AdminModules'));
        } else {
            $this->context->smarty->assign('error', $this->l('Can\'t delete the slide.'));
        }
    }

    /**
     * @return string
     */
    protected function renderConfigurationForm()
    {
        $inputs = [];

        foreach ($this->getConfigurableModules() as $module) {
            $desc = '';

            if (isset($module['is_module']) && $module['is_module']) {
                $moduleInstance = Module::getInstanceByName($module['name']);
                if (Validate::isLoadedObject($moduleInstance) && method_exists($moduleInstance, 'getContent')) {
                    $moduleLink = $this->context->link->getAdminLink('AdminModules', true).'&configure='.urlencode($moduleInstance->name).'&tab_module='.$moduleInstance->tab.'&module_name='.urlencode($moduleInstance->name);
                    $desc = '<a class="btn btn-default" href="'.$moduleLink.'">'.$this->l('Configure').' <i class="icon-external-link"></i></a>';
                }
            }
            if (!$desc && isset($module['desc']) && $module['desc']) {
                $desc = $module['desc'];
            }

            $inputs[] = [
                'type'   => 'switch',
                'label'  => $module['label'],
                'name'   => $module['name'],
                'desc'   => $desc,
                'values' => [
                    [
                        'id'    => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id'    => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ];
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => $inputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * @return array
     */
    protected function getConfigFieldsValues()
    {
        $values = [];
        foreach ($this->getConfigurableModules() as $module) {
            $values[$module['name']] = $module['value'];
        }

        return $values;
    }

    /**
     * @return string
     */
    protected function renderThemeConfiguratorForm()
    {
        $idShop = (int) $this->context->shop->id;
        $items = [];
        $hooks = [];

        $this->context->smarty->assign(
            'htmlcontent',
            [
                'admin_tpl_path' => $this->admin_tpl_path,
                'hooks_tpl_path' => $this->hooks_tpl_path,

                'info' => [
                    'module'    => $this->name,
                    'name'      => $this->displayName,
                    'version'   => $this->version,
                    'context'   => (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 0) ? 1 : ($this->context->shop->getTotalShops() != 1) ? $this->context->shop->getContext() : 1,
                ],
            ]
        );

        foreach ($this->languages as $language) {
            $hooks[$language['id_lang']] = [
                'home',
                'top',
                'left',
                'right',
                'footer',
            ];

            foreach ($hooks[$language['id_lang']] as $hook) {
                $items[$language['id_lang']][$hook] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('themeconfigurator')
                        ->where('`id_shop` = '.(int) $idShop)
                        ->where('`id_lang` = '.(int) $language['id_lang'])
                        ->where('`hook` = \''.pSQL($hook).'\'')
                        ->orderBy('`item_order` ASC')
                );
            }
        }

        $this->context->smarty->assign(
            'htmlitems',
            [
                'items'      => $items,
                'theme_url'  => $this->module_url,
                'lang'       => [
                    'default'  => $this->default_language,
                    'all'      => $this->languages,
                    'lang_dir' => _THEME_LANG_DIR_,
                    'user'     => $this->context->language->id,
                ],
                'postAction' => 'index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=other&module_name='.$this->name.'',
                'id_shop'    => $idShop,
            ]
        );

        $this->context->controller->addJqueryUI('ui.sortable');

        return $this->display(__FILE__, 'views/templates/admin/admin.tpl');
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    protected function runQueries($file)
    {
        if (!file_exists(__DIR__.DIRECTORY_SEPARATOR.'sql'.DIRECTORY_SEPARATOR.$file.'.sql')) {
            return false;
        } elseif (!$sql = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'sql'.DIRECTORY_SEPARATOR.$file.'.sql')) {
            return false;
        }
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE', 'DB_NAME'], [_DB_PREFIX_, _MYSQL_ENGINE_, _DB_NAME_], $sql);
        $sql = preg_split("/;\s*[\r\n]+/", trim($sql));

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute(trim($query))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Install the module's DB table(s)
     *
     * @return bool
     */
    protected function installDB()
    {
        return $this->runQueries('install');
    }

    /**
     * Install a fixture
     *
     * @param string $hook
     * @param int    $idImage
     * @param int    $idShop
     * @param int    $idLang
     *
     * @return bool
     */
    protected function installFixture($hook, $idImage, $idShop, $idLang)
    {
        $result = true;

        $sizes = @getimagesize((dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'banner-img'.(int) $idImage.'.jpg'));
        $width = (isset($sizes[0]) && $sizes[0]) ? (int) $sizes[0] : 0;
        $height = (isset($sizes[1]) && $sizes[1]) ? (int) $sizes[1] : 0;

        $result &= Db::getInstance()->insert(
            'themeconfigurator',
            [
                'id_shop'    => (int) $idShop,
                'id_lang'    => (int) $idLang,
                'item_order' => (int) $idImage,
                'title'      => '',
                'title_use'  => '0',
                'hook'       => pSQL($hook),
                'url'        => 'https://www.thirtybees.com/',
                'target'     => '0',
                'image'      => 'banner-img'.(int) $idImage.'.jpg',
                'image_w'    => (int) $width,
                'image_h'    => (int) $height,
                'html'       => '',
                'active'     => 1,
            ]
        );

        return $result;
    }

    /**
     * Delete an image
     *
     * @param $image
     */
    protected function deleteImage($image)
    {
        $fileName = $this->uploads_path.$image;

        if (realpath(dirname($fileName)) != realpath($this->uploads_path)) {
            Logger::addLog(sprintf('Could not find upload directory'), 2);
        }

        if ($image != '' && is_file($fileName) && !strpos($fileName, 'banner-img') && !strpos($fileName, 'bg-theme') && !strpos($fileName, 'footer-bg')) {
            unlink($fileName);
        }
    }

    /**
     * Check environment
     *
     * @return bool
     */
    protected function checkEnvironment()
    {
        $cookie = new Cookie('psAdmin', '', (int) Configuration::get('PS_COOKIE_LIFETIME_BO'));

        return isset($cookie->id_employee) && isset($cookie->passwd) && Employee::checkPassword($cookie->id_employee, $cookie->passwd);
    }

    /**
     * Get items from hook
     *
     * @param string $hook
     *
     * @return array|bool|false|null|PDOStatement
     */
    protected function getItemsFromHook($hook)
    {
        if (!$hook) {
            return false;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('themeconfigurator')
                ->where('`id_shop` = '.(int) $this->context->shop->id)
                ->where('`id_lang` = '.(int) $this->context->language->id)
                ->where('`hook` = \''.pSQL($hook).'\'')
                ->where('`active` = 1')
                ->orderBY('`item_order` ASC')
        );
    }
}
