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
 * Class BlockCart
 *
 * @since 1.0.0
 */
class BlockCart extends Module
{
    /**
     * BlockCart constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'blockcart';
        $this->tab = 'front_office_features';
        $this->version = '2.0.4';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Cart');
        $this->description = $this->l('Adds a block containing the customer\'s shopping cart.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitBlockCart')) {
            $ajax = Tools::getValue('PS_BLOCK_CART_AJAX');
            if ($ajax != 0 && $ajax != 1) {
                $output .= $this->displayError($this->l('Ajax: Invalid choice.'));
            } else {
                Configuration::updateValue('PS_BLOCK_CART_AJAX', (int) ($ajax));
            }

            if (($productNbr = (int) Tools::getValue('PS_BLOCK_CART_XSELL_LIMIT') < 0)) {
                $output .= $this->displayError($this->l('Please complete the "Products to display" field.'));
            } else {
                Configuration::updateValue('PS_BLOCK_CART_XSELL_LIMIT', (int) (Tools::getValue('PS_BLOCK_CART_XSELL_LIMIT')));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }

            Configuration::updateValue('PS_BLOCK_CART_SHOW_CROSSSELLING', (int) (Tools::getValue('PS_BLOCK_CART_SHOW_CROSSSELLING')));
        }

        return $output.$this->renderForm();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Ajax cart'),
                        'name'    => 'PS_BLOCK_CART_AJAX',
                        'is_bool' => true,
                        'desc'    => $this->l('Activate Ajax mode for the cart (compatible with the default theme).'),
                        'values'  => [
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
                        'type'    => 'switch',
                        'label'   => $this->l('Show cross-selling'),
                        'name'    => 'PS_BLOCK_CART_SHOW_CROSSSELLING',
                        'is_bool' => true,
                        'desc'    => $this->l('Activate cross-selling display for the cart.'),
                        'values'  => [
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
                        'label' => $this->l('Products to display in cross-selling'),
                        'name'  => 'PS_BLOCK_CART_XSELL_LIMIT',
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Define the number of products to be displayed in the cross-selling block.'),
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
        $this->fields_form = [];

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBlockCart';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getConfigFieldsValues()
    {
        return [
            'PS_BLOCK_CART_AJAX'              => (bool) Tools::getValue('PS_BLOCK_CART_AJAX', Configuration::get('PS_BLOCK_CART_AJAX')),
            'PS_BLOCK_CART_SHOW_CROSSSELLING' => (bool) Tools::getValue('PS_BLOCK_CART_SHOW_CROSSSELLING', Configuration::get('PS_BLOCK_CART_SHOW_CROSSSELLING')),
            'PS_BLOCK_CART_XSELL_LIMIT'       => (int) Tools::getValue('PS_BLOCK_CART_XSELL_LIMIT', Configuration::get('PS_BLOCK_CART_XSELL_LIMIT')),
        ];
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function install()
    {
        if (
            parent::install() == false
            || $this->registerHook('top') == false
            || $this->registerHook('header') == false
            || $this->registerHook('actionCartListOverride') == false
            || Configuration::updateValue('PS_BLOCK_CART_AJAX', 1) == false
            || Configuration::updateValue('PS_BLOCK_CART_XSELL_LIMIT', 12) == false
            || Configuration::updateValue('PS_BLOCK_CART_SHOW_CROSSSELLING', 1) == false
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookLeftColumn($params)
    {
        return $this->hookRightColumn($params);
    }

    /**
     * @param array $params
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function hookRightColumn($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return null;
        }

        // @todo this variable seems not used
        $this->smarty->assign(
            [
                'order_page'    => (strpos($_SERVER['PHP_SELF'], 'order') !== false),
                'blockcart_top' => (isset($params['blockcart_top']) && $params['blockcart_top']) ? true : false,
            ]
        );
        $this->assignContentVars($params);

        return $this->display(__FILE__, 'blockcart.tpl');
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function assignContentVars($params)
    {
        global $errors;

        // Set currency
        if ((int) $params['cart']->id_currency && (int) $params['cart']->id_currency != $this->context->currency->id) {
            $currency = new Currency((int) $params['cart']->id_currency);
        } else {
            $currency = $this->context->currency;
        }

        $taxCalculationMethod = Group::getPriceDisplayMethod((int) Group::getCurrent()->id);

        $useTax = !($taxCalculationMethod == PS_TAX_EXC);

        $products = $params['cart']->getProducts(true);
        $nbTotalProducts = 0;
        foreach ($products as $product) {
            $nbTotalProducts += (int) $product['cart_quantity'];
        }
        $cartRules = $params['cart']->getCartRules();

        if (empty($cartRules)) {
            $baseShipping = $params['cart']->getOrderTotal($useTax, Cart::ONLY_SHIPPING);
        } else {
            $baseShippingWithTax = $params['cart']->getOrderTotal(true, Cart::ONLY_SHIPPING);
            $baseShippingWithoutTax = $params['cart']->getOrderTotal(false, Cart::ONLY_SHIPPING);
            if ($useTax) {
                $baseShipping = $baseShippingWithTax;
            } else {
                $baseShipping = $baseShippingWithoutTax;
            }
        }
        $shippingCost = Tools::displayPrice($baseShipping, $currency);
        $shippingCostFloat = Tools::convertPrice($baseShipping, $currency);
        $wrappingCost = (float) ($params['cart']->getOrderTotal($useTax, Cart::ONLY_WRAPPING));
        $totalToPay = $params['cart']->getOrderTotal($useTax);

        if ($useTax && Configuration::get('PS_TAX_DISPLAY') == 1) {
            $totalToPayWithoutTaxes = $params['cart']->getOrderTotal(false);
            $this->smarty->assign('tax_cost', Tools::displayPrice($totalToPay - $totalToPayWithoutTaxes, $currency));
        }

        $displayPrecision = 0;
        if ($currency->decimals) {
            $displayPrecision = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }

        // The cart content is altered for display
        foreach ($cartRules as &$cartRule) {
            if ($cartRule['free_shipping']) {
                $shippingCost = Tools::displayPrice(0, $currency);
                $shippingCostFloat = 0;
                $cartRule['value_real'] -= Tools::convertPrice($baseShippingWithTax, $currency);
                $cartRule['value_tax_exc'] = Tools::convertPrice($baseShippingWithoutTax, $currency);
            }
            if ($cartRule['gift_product']) {
                foreach ($products as $key => &$product) {
                    if ($product['id_product'] == $cartRule['gift_product']
                        && $product['id_product_attribute'] == $cartRule['gift_product_attribute']
                    ) {
                        $product['total_wt'] = Tools::ps_round(
                            $product['total_wt'] - $product['price_wt'],
                            $displayPrecision
                        );
                        $product['total'] = Tools::ps_round(
                            $product['total'] - $product['price'],
                            $displayPrecision
                        );
                        if ($product['cart_quantity'] > 1) {
                            array_splice($products, $key, 0, [$product]);
                            $products[$key]['cart_quantity'] = $product['cart_quantity'] - 1;
                            $product['cart_quantity'] = 1;
                        }
                        $product['is_gift'] = 1;
                        $cartRule['value_real'] = Tools::ps_round(
                            $cartRule['value_real'] - $product['price_wt'],
                            $displayPrecision
                        );
                        $cartRule['value_tax_exc'] = Tools::ps_round(
                            $cartRule['value_tax_exc'] - $product['price'],
                            $displayPrecision
                        );
                    }
                }
            }
        }

        $totalFreeShipping = 0;
        if ($freeShipping = Tools::convertPrice(floatval(Configuration::get('PS_SHIPPING_FREE_PRICE')), $currency)) {
            $totalFreeShipping = floatval(
                $freeShipping - ($params['cart']->getOrderTotal(true, Cart::ONLY_PRODUCTS) +
                    $params['cart']->getOrderTotal(true, Cart::ONLY_DISCOUNTS))
            );
            $discounts = $params['cart']->getCartRules(CartRule::FILTER_ACTION_SHIPPING);
            if ($totalFreeShipping < 0) {
                $totalFreeShipping = 0;
            }
            if (is_array($discounts) && count($discounts)) {
                $totalFreeShipping = 0;
            }
        }

        $this->smarty->assign(
            [
                'products'            => $products,
                'customizedDatas'     => Product::getAllCustomizedDatas((int) ($params['cart']->id)),
                'CUSTOMIZE_FILE'      => Product::CUSTOMIZE_FILE,
                'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
                'discounts'           => $cartRules,
                'nb_total_products'   => (int) ($nbTotalProducts),
                'shipping_cost'       => $shippingCost,
                'shipping_cost_float' => $shippingCostFloat,
                'show_wrapping'       => $wrappingCost > 0 ? true : false,
                'show_tax'            => (int) (Configuration::get('PS_TAX_DISPLAY') == 1 && (int) Configuration::get('PS_TAX')),
                'wrapping_cost'       => Tools::displayPrice($wrappingCost, $currency),
                'product_total'       => Tools::displayPrice($params['cart']->getOrderTotal($useTax, Cart::BOTH_WITHOUT_SHIPPING), $currency),
                'total'               => Tools::displayPrice($totalToPay, $currency),
                'order_process'       => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
                'ajax_allowed'        => (int) (Configuration::get('PS_BLOCK_CART_AJAX')) == 1 ? true : false,
                'static_token'        => Tools::getToken(false),
                'free_shipping'       => $totalFreeShipping,
            ]
        );
        if (is_array($errors) && count($errors)) {
            $this->smarty->assign('errors', $errors);
        }
        if (isset($this->context->cookie->ajax_blockcart_display)) {
            $this->smarty->assign('colapseExpandStatus', $this->context->cookie->ajax_blockcart_display);
        }
    }

    /**
     * @param array $params
     *
     * @return array|mixed|string|void
     *
     * @since 1.0.0
     */
    public function hookAjaxCall($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }

        $this->assignContentVars($params);
        $res = json_decode($this->display(__FILE__, 'blockcart-json.tpl'), true);

        if (is_array($res) && ($idProduct = Tools::getValue('id_product')) && Configuration::get('PS_BLOCK_CART_SHOW_CROSSSELLING')) {
            $this->smarty->assign(
                'orderProducts', OrderDetail::getCrossSells(
                $idProduct, $this->context->language->id,
                Configuration::get('PS_BLOCK_CART_XSELL_LIMIT')
            )
            );
            $res['crossSelling'] = $this->display(__FILE__, 'crossselling.tpl');
        }

        $res = json_encode($res);

        return $res;
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function hookActionCartListOverride($params)
    {
        if (!Configuration::get('PS_BLOCK_CART_AJAX')) {
            return;
        }

        $this->assignContentVars(['cookie' => $this->context->cookie, 'cart' => $this->context->cart]);
        $params['json'] = $this->display(__FILE__, 'blockcart-json.tpl');
    }

    /**
     * @since 1.0.0
     */
    public function hookHeader()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }

        $this->context->controller->addCSS(($this->_path).'blockcart.css', 'all');
        if ((int) (Configuration::get('PS_BLOCK_CART_AJAX'))) {
            $this->context->controller->addJS(($this->_path).'ajax-cart.js');
            $this->context->controller->addJqueryPlugin(['scrollTo', 'serialScroll', 'bxslider']);
        }
    }

    /**
     * @param array $params
     *
     * @return null|string
     *
     * @since 1.0.0
     */
    public function hookDisplayNav($params)
    {
        $params['blockcart_top'] = true;

        return $this->hookTop($params);
    }

    /**
     * @param array $params
     *
     * @return null|string
     *
     * @since 1.0.0
     */
    public function hookTop($params)
    {
        $params['blockcart_top'] = true;

        return $this->hookRightColumn($params);
    }
}
