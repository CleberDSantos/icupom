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
    return;
}

/**
 * Class HomeFeatured
 */
class HomeFeatured extends Module
{
    const CACHE_TTL = 'HOME_FEATURED_TTL';
    const CACHE_TIMESTAMP = 'HOME_FEATURED_TIMESTAMP';

    const NUMBER = 'HOME_FEATURED_NBR';
    const CATEGORY_ID = 'HOME_FEATURED_CAT';
    const RANDOMIZE = 'HOME_FEATURED_RANDOMIZE';

    // @codingStandardsIgnoreStart
    /** @var array $cache_products */
    protected static $cache_products;
    // @codingStandardsIgnoreEnd

    /**
     * HomeFeatured constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'homefeatured';
        $this->tab = 'front_office_features';
        $this->version = '2.2.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Featured Products');
        $this->description = $this->l('Displays featured products in the central column of your homepage.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';

        if (Configuration::get(static::CACHE_TIMESTAMP) < (time() - Configuration::get(static::CACHE_TTL))) {
            $this->clearCache();
        }
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        $this->clearCache();
        Configuration::updateValue(static::NUMBER, 8);
        Configuration::updateValue(static::CATEGORY_ID, (int) Context::getContext()->shop->getCategory());
        Configuration::updateValue(static::RANDOMIZE, false);

        if (!parent::install()) {
            return false;
        }

        $this->registerHook('header');
        $this->registerHook('addproduct');
        $this->registerHook('updateproduct');
        $this->registerHook('deleteproduct');
        $this->registerHook('categoryUpdate');
        $this->registerHook('displayHomeTab');
        $this->registerHook('displayHomeTabContent');

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $this->clearCache();

        return parent::uninstall();
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';
        $errors = [];
        if (Tools::isSubmit('submitHomeFeatured')) {
            $nbr = Tools::getValue(static::NUMBER);
            if (!Validate::isInt($nbr) || $nbr <= 0) {
                $errors[] = $this->l('The number of products is invalid. Please enter a positive number.');
            }

            $cat = Tools::getValue(static::CATEGORY_ID);
            if (!Validate::isInt($cat) || $cat <= 0)
                $errors[] = $this->l('The category ID is invalid. Please choose an existing category ID.');

            $rand = Tools::getValue(static::RANDOMIZE);
            if (!Validate::isBool($rand)) {
                $errors[] = $this->l('Invalid value for the "randomize" flag.');
            }
            $ttl = Tools::getValue(static::CACHE_TTL);
            if (!Validate::isUnsignedInt($ttl)) {
                $errors[] = $this->l('Invalid value for the "Cache lifetime" flag.');
            }
            if (isset($errors) && count($errors)) {
                $output = $this->displayError(implode('<br />', $errors));
            }
            else {
                Configuration::updateValue(static::NUMBER, (int) $nbr);
                Configuration::updateValue(static::CATEGORY_ID, (int) $cat);
                Configuration::updateValue(static::RANDOMIZE, (bool) $rand);
                Configuration::updateValue(static::CACHE_TTL, (int) $ttl * 60);
                Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath('homefeatured.tpl'));
                $output = $this->displayConfirmation($this->l('Your settings have been updated.'));
            }
        }

        return $output.$this->renderForm();
    }

    /**
     * @return string
     */
    public function hookDisplayHeader()
    {
        return $this->hookHeader();
    }

    /**
     * @return string
     */
    public function hookHeader()
    {
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index') {
            $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
        }
        $this->context->controller->addCSS(($this->_path).'css/homefeatured.css', 'all');

        return '';
    }

    /**
     * @throws PrestaShopException
     */
    public function _cacheProducts()
    {
        if (!isset(HomeFeatured::$cache_products)) {
            $category = new Category(
                (int) Configuration::get(static::CATEGORY_ID),
                (int) Context::getContext()->language->id
            );
            $nb = (int) Configuration::get(static::NUMBER);
            if (Configuration::get(static::RANDOMIZE)) {
                HomeFeatured::$cache_products = $category->getProducts(
                    (int) Context::getContext()->language->id,
                    1,
                    ($nb ? $nb : 8),
                    null,
                    null,
                    false,
                    true,
                    true,
                    ($nb ? $nb : 8)
                );
            } else {
                HomeFeatured::$cache_products = $category->getProducts(
                    (int) Context::getContext()->language->id,
                    1,
                    ($nb ? $nb : 8),
                    'position'
                );
            }
        }

        if (HomeFeatured::$cache_products === false || empty(HomeFeatured::$cache_products)) {
            return;
        }
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHomeTab()
    {
        if (!$this->isCached('tab.tpl', $this->getCacheId('homefeatured-tab'))) {
            $this->_cacheProducts();

            if (!HomeFeatured::$cache_products) {
                return '';
            }
        }

        return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('homefeatured-tab'));
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHome()
    {
        if (!$this->isCached('homefeatured.tpl', $this->getCacheId('homefeatured-home'))) {
            $this->_cacheProducts();
            if (!HomeFeatured::$cache_products) {
                return '';
            }
            $this->smarty->assign(
                [
                    'products'         => HomeFeatured::$cache_products,
                    'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                    'homeSize'         => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        }

        return $this->display(__FILE__, 'homefeatured.tpl', $this->getCacheId('homefeatured-home'));
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHomeTabContent()
    {
        if (!$this->isCached('homefeatured_home.tpl', $this->getCacheId('homefeatured-home'))) {
            $this->_cacheProducts();
            if (!HomeFeatured::$cache_products) {
                return '';
            }
            $this->smarty->assign(
                [
                    'products'         => HomeFeatured::$cache_products,
                    'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                    'homeSize'         => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        }

        return $this->display(__FILE__, 'homefeatured_home.tpl', $this->getCacheId('homefeatured-home'));
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookAddProduct()
    {
        $this->clearCache();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookUpdateProduct()
    {
        $this->clearCache();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookDeleteProduct()
    {
        $this->clearCache();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookCategoryUpdate()
    {
        $this->clearCache();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function clearCache()
    {
        $caches = [
            'homefeatured.tpl' => 'homefeatured-home',
            'tab.tpl'          => 'homefeatured-tab',
        ];

        foreach ($caches as $template => $cacheId) {
            Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath($template), $cacheId);
        }

        Configuration::updateValue(static::CACHE_TIMESTAMP, time());
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend'      => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'description' => $this->l('To add products to your homepage, simply add them to the corresponding product category (default: "Home").'),
                'input'       => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Number of products to be displayed'),
                        'name'  => static::NUMBER,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Set the number of products that you would like to display on homepage (default: 8).'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Category from which to pick products to be displayed'),
                        'name'  => static::CATEGORY_ID,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Choose the category ID of the products that you would like to display on homepage (default: 2 for "Home").'),
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Randomly display featured products'),
                        'name'   => static::RANDOMIZE,
                        'class'  => 'fixed-width-xs',
                        'desc'   => $this->l('Enable if you wish the products to be displayed randomly (default: no).'),
                        'values' => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type'   => 'text',
                        'label'  => $this->l('Cache lifetime'),
                        'name'   => static::CACHE_TTL,
                        'desc'   => $this->l('Determines for how long the featured products block stays cached.'),
                        'suffix' => $this->l('Minutes'),
                        'class'  => 'fixed-width-xs',
                    ],
                ],
                'submit'      => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $this->fields_form = [];
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitHomeFeatured';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            static::NUMBER      => Tools::getValue(
                static::NUMBER,
                (int) Configuration::get(static::NUMBER)
            ),
            static::CATEGORY_ID => Tools::getValue(
                static::CATEGORY_ID,
                (int) Configuration::get(static::CATEGORY_ID)
            ),
            static::RANDOMIZE   => Tools::getValue(
                static::RANDOMIZE,
                (bool) Configuration::get(static::RANDOMIZE)
            ),
            static::CACHE_TTL   => Tools::getValue(
                static::CACHE_TTL,
                (int) Configuration::get(static::CACHE_TTL) / 60
            ),
        ];
    }
}
