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
 * Class BlockSpecials
 */
class BlockSpecials extends Module
{
    const CACHE_TTL = 'BLOCKSPECIALS_TTL';
    const CACHE_TIMESTAMP = 'BLOCKSPECIALS_TIMESTAMP';

    const NUMBER_OF_CACHES = 'BLOCKSPECIALS_NB_CACHES';
    const NUMBER = 'BLOCKSPECIALS_SPECIALS_NBR';
    const ALWAYS_DISPLAY = 'PS_BLOCK_SPECIALS_DISPLAY';

    protected $_html = '';
    protected $_postErrors = [];

    protected static $cache_specials;

    /**
     * BlockSpecials constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'blockspecials';
        $this->tab = 'pricing_promotion';
        $this->version = '2.1.2';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Specials');
        $this->description = $this->l('Adds a block displaying your current discounted products.');
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
        if (!Configuration::get(static::NUMBER_OF_CACHES)) {
            Configuration::updateValue(static::NUMBER_OF_CACHES, 20);
        }

        if (!Configuration::get(static::NUMBER)) {
            Configuration::updateValue(static::NUMBER, 5);
        }

        if (!Configuration::get(static::CACHE_TTL)) {
            Configuration::updateValue(static::CACHE_TTL, 300);
        }

        $this->clearCache();

        $success = parent::install()
            && $this->registerHook('header')
            && $this->registerHook('leftColumn')
            && $this->registerHook('addproduct')
            && $this->registerHook('updateproduct')
            && $this->registerHook('deleteproduct')
            && $this->registerHook('displayHomeTab')
            && $this->registerHook('displayHomeTabContent');

        return $success;
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
        if (Tools::isSubmit('submitSpecials')) {
            Configuration::updateValue(
                static::ALWAYS_DISPLAY,
                (int) Tools::getValue(static::ALWAYS_DISPLAY)
            );
            Configuration::updateValue(static::NUMBER_OF_CACHES, (int) Tools::getValue(static::NUMBER_OF_CACHES));
            Configuration::updateValue(static::NUMBER, (int) Tools::getValue(static::NUMBER));
            Configuration::updateValue(
                static::CACHE_TTL,
                (int) (Tools::getValue(static::CACHE_TTL) * 60)
            );
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        return $output.$this->renderForm();
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookRightColumn()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return '';
        }

        // We need to create multiple caches because the products are sorted randomly
        $random = date('Ymd').'|'.round(rand(1, max(Configuration::get(static::NUMBER_OF_CACHES), 1)));

        if (!Configuration::get(static::NUMBER_OF_CACHES)
            || !$this->isCached('blockspecials.tpl', $this->getCacheId('blockspecials|'.$random))
        ) {
            if (!($special = Product::getRandomSpecial((int) $this->context->language->id))
                && !Configuration::get(static::ALWAYS_DISPLAY)
            ) {
                return '';
            }

            $this->smarty->assign([
                'special'                        => $special,
                'priceWithoutReduction_tax_excl' => Tools::ps_round($special['price_without_reduction'], 2),
                'mediumSize'                     => Image::getSize(ImageType::getFormatedName('medium')),
            ]);
        }

        return $this->display(
            __FILE__,
            'blockspecials.tpl',
            (Configuration::get(static::NUMBER_OF_CACHES)
                ? $this->getCacheId('blockspecials|'.$random)
                : null)
        );
    }

    /**
     * @return string
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
     * @throws PrestaShopException
     */
    public function hookHeader()
    {
        if (Configuration::get('PS_CATALOG_MODE'))
            return;
        $this->context->controller->addCSS(($this->_path).'blockspecials.css', 'all');
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
     * @throws PrestaShopException
     */
    public function hookDeleteProduct()
    {
        $this->clearCache();
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHomeTab()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return '';
        }

        if (!$this->isCached('tab.tpl', $this->getCacheId('blockspecials-tab'))) {
            BlockSpecials::$cache_specials = Product::getPricesDrop(
                (int) $this->context->language->id,
                0,
                Configuration::get(static::NUMBER)
            );
        }

        if (BlockSpecials::$cache_specials === false) {
            return '';
        }

        return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blockspecials-tab'));
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
        if (Configuration::get('PS_CATALOG_MODE')) {
            return '';
        }

        if (!$this->isCached('blockspecials-home.tpl', $this->getCacheId('blockspecials-home'))) {
            $this->smarty->assign([
                'specials' => BlockSpecials::$cache_specials,
                'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
            ]);
        }

        if (BlockSpecials::$cache_specials === false) {
            return '';
        };

        return $this->display(__FILE__, 'blockspecials-home.tpl', $this->getCacheId('blockspecials-home'));
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
                        'type'   => 'switch',
                        'label'  => $this->l('Always display this block'),
                        'name'   => static::ALWAYS_DISPLAY,
                        'desc'   => $this->l('Show the block even if no products are available.'),
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
                        'type'  => 'text',
                        'label' => $this->l('Number of cached files'),
                        'name'  => static::NUMBER_OF_CACHES,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Specials are displayed randomly on the front-end, but since it takes a lot of resources, it is better to cache the results. The cache is reset daily. 0 will disable the cache.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Products to display'),
                        'name'  => static::NUMBER,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Define the number of products to be displayed in this block on home page.'),
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
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSpecials';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
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
            static::ALWAYS_DISPLAY   => Tools::getValue(
                static::ALWAYS_DISPLAY,
                Configuration::get(static::ALWAYS_DISPLAY)
            ),
            static::NUMBER_OF_CACHES => Tools::getValue(
                static::NUMBER_OF_CACHES,
                Configuration::get(static::NUMBER_OF_CACHES)
            ),
            static::NUMBER           => Tools::getValue(
                static::NUMBER,
                Configuration::get(static::NUMBER)
            ),
            static::CACHE_TTL        => Tools::getValue(
                static::CACHE_TTL,
                Configuration::get(static::CACHE_TTL) / 60
            ),
        ];
    }

    /**
     * @param string|null $name
     *
     * @return string
     */
    protected function getCacheId($name = null)
    {
        if ($name === null) {
            $name = 'blockspecials';
        }

        return parent::getCacheId($name.'|'.date('Ymd'));
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function clearCache()
    {
        $caches = [
            'blockspecials.tpl' => null,
            'blockspecials-home.tpl' => 'blockspecials-home',
            'tab.tpl' => 'blockspecials-tab',
        ];

        foreach ($caches as $template => $cacheId) {
            Tools::clearCache(Context::getContext()->smarty, $template, $cacheId);
        }

        Configuration::updateValue(static::CACHE_TIMESTAMP, time());
    }
}
