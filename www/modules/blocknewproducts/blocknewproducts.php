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
 * Class BlockNewProducts
 */
class BlockNewProducts extends Module
{
    const CACHE_TTL = 'NEW_PRODUCTS_TTL';
    const CACHE_TIMESTAMP = 'NEW_PRODUCTS_TIMESTAMP';

    const NUMBER = 'NEW_PRODUCTS_NBR';
    const NUMBER_OF_DAYS = 'PS_NB_DAYS_NEW_PRODUCT';
    const ALWAYS_DISPLAY = 'PS_BLOCK_NEWPRODUCTS_DISPLAY';

    // @codingStandardsIgnoreStart
    /** @var array $cache_new_products */
    protected static $cache_new_products;
    // @codingStandardsIgnoreEnd

    /**
     * BlockNewProducts constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'blocknewproducts';
        $this->tab = 'front_office_features';
        $this->version = '2.2.1';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block New Products');
        $this->description = $this->l('Displays a block featuring your store\'s newest products.');
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
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('header');
        $this->registerHook('leftColumn');
        $this->registerHook('addproduct');
        $this->registerHook('updateproduct');
        $this->registerHook('deleteproduct');
        $this->registerHook('displayHome');
        $this->registerHook('displayHomeTab');
        $this->registerHook('displayHomeTabContent');
        Configuration::updateValue('NEW_PRODUCTS_NBR', 4);

        $this->clearCache();

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
        if (Tools::isSubmit('submitBlockNewProducts')) {
            if (!($productNbr = Tools::getValue(static::NUMBER)) || empty($productNbr))
                $output .= $this->displayError($this->l('Please complete the "products to display" field.'));
            elseif ((int) ($productNbr) == 0)
                $output .= $this->displayError($this->l('Invalid number.'));
            else {
                Configuration::updateValue(
                    static::NUMBER_OF_DAYS,
                    (int) (Tools::getValue(static::NUMBER_OF_DAYS))
                );
                Configuration::updateValue(
                    static::ALWAYS_DISPLAY,
                    (int) (Tools::getValue(static::ALWAYS_DISPLAY))
                );
                Configuration::updateValue(static::NUMBER, (int) ($productNbr));
                Configuration::updateValue(
                    static::CACHE_TTL,
                    (int) (Tools::getValue(static::CACHE_TTL) * 60)
                );
                $this->clearCache();
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->renderForm();
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getNewProducts()
    {
        if (!Configuration::get(static::NUMBER)) {
            return [];
        }

        $newProducts = false;
        if (Configuration::get(static::NUMBER_OF_DAYS)) {
            $newProducts = Product::getNewProducts(
                (int) $this->context->language->id,
                0,
                (int) Configuration::get(static::NUMBER)
            );
        }

        if (!$newProducts && Configuration::get(static::ALWAYS_DISPLAY)) {
            return [];
        }

        return $newProducts;
    }

    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookRightColumn()
    {
        if (!$this->isCached('blocknewproducts.tpl', $this->getCacheId())) {
            if (!isset(BlockNewProducts::$cache_new_products))
                BlockNewProducts::$cache_new_products = $this->getNewProducts();

            $this->smarty->assign([
                'new_products' => BlockNewProducts::$cache_new_products,
                'mediumSize'   => Image::getSize(ImageType::getFormatedName('medium')),
                'homeSize'     => Image::getSize(ImageType::getFormatedName('home')),
            ]);
        }

        if (!BlockNewProducts::$cache_new_products) {
            return false;
        }

        return $this->display(__FILE__, 'blocknewproducts.tpl', $this->getCacheId());
    }

    /**
     * @param string|null $name
     *
     * @return string
     */
    protected function getCacheId($name = null)
    {
        if ($name === null) {
            $name = 'blocknewproducts';
        }

        return parent::getCacheId($name.'|'.date('Ymd'));
    }

    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayHomeTab()
    {
        if (!$this->isCached('tab.tpl', $this->getCacheId('blocknewproducts-tab')))
            BlockNewProducts::$cache_new_products = $this->getNewProducts();

        if (BlockNewProducts::$cache_new_products === false)
            return false;

        return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blocknewproducts-tab'));
    }

    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayHomeTabContent()
    {
        if (!$this->isCached(
            'blocknewproducts_home.tpl',
            $this->getCacheId('blocknewproducts-home'))
        ) {
            $this->smarty->assign([
                'new_products' => BlockNewProducts::$cache_new_products,
                'mediumSize'   => Image::getSize(ImageType::getFormatedName('medium')),
                'homeSize'     => Image::getSize(ImageType::getFormatedName('home')),
            ]);
        }

        if (BlockNewProducts::$cache_new_products === false) {
            return false;
        }

        return $this->display(
            __FILE__,
            'blocknewproducts_home.tpl',
            $this->getCacheId('blocknewproducts-home')
        );
    }

    public function hookHeader($params)
    {
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index')
            $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');

        $this->context->controller->addCSS($this->_path.'blocknewproducts.css', 'all');
    }

    public function hookAddProduct()
    {
        $this->clearCache();
    }

    public function hookUpdateProduct()
    {
        $this->clearCache();
    }

    public function hookDeleteProduct()
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
            'blocknewproducts.tpl'      => null,
            'blocknewproducts_home.tpl' => 'blocknewproducts-home',
            'tab.tpl'                   => 'blocknewproducts-tab',
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
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Products to display'),
                        'name'  => static::NUMBER,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Define the number of products to be displayed in this block.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Number of days for which the product is considered \'new\''),
                        'name'  => static::NUMBER_OF_DAYS,
                        'class' => 'fixed-width-xs',
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Always display this block'),
                        'name'   => static::ALWAYS_DISPLAY,
                        'desc'   => $this->l('Show the block even if no new products are available.'),
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
                    ],
                    [
                        'type'   => 'text',
                        'label'  => $this->l('Cache lifetime'),
                        'name'   => static::CACHE_TTL,
                        'desc'   => $this->l('Determines for how long the bestseller block stays cached'),
                        'suffix' => $this->l('Minutes'),
                        'class'  => 'fixed-width-xs',
                    ],
                ],
                'submit' => [
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
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBlockNewProducts';
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
            static::NUMBER_OF_DAYS => Tools::getValue(
                static::NUMBER_OF_DAYS,
                Configuration::get(static::NUMBER_OF_DAYS)
            ),
            static::ALWAYS_DISPLAY => Tools::getValue(
                static::ALWAYS_DISPLAY,
                Configuration::get(static::ALWAYS_DISPLAY)
            ),
            static::NUMBER         => Tools::getValue(
                static::NUMBER,
                Configuration::get(static::NUMBER)
            ),
            static::CACHE_TTL      => Tools::getValue(
                static::CACHE_TTL,
                (int) Configuration::get(static::CACHE_TTL) / 60
            ),
        ];
    }
}
