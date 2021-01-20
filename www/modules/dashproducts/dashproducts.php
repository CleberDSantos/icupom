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
 * Class DashProducts
 */
class DashProducts extends Module
{
    /**
     * DashProducts constructor.
     */
    public function __construct()
    {
        $this->name = 'dashproducts';
        $this->tab = 'dashboard';
        $this->version = '2.0.3';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->push_filename = _PS_CACHE_DIR_.'push/activity';
        $this->allow_push = true;

        parent::__construct();
        $this->displayName = $this->l('Dashboard Products');
        $this->description = $this->l('Adds a block with a table of your latest orders and a ranking of your products');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.1.99'];
    }

    /**
     * @return bool
     */
    public function install()
    {
        Configuration::updateValue('DASHPRODUCT_NBR_SHOW_LAST_ORDER', 10);
        Configuration::updateValue('DASHPRODUCT_NBR_SHOW_BEST_SELLER', 10);
        Configuration::updateValue('DASHPRODUCT_NBR_SHOW_MOST_VIEWED', 10);
        Configuration::updateValue('DASHPRODUCT_NBR_SHOW_TOP_SEARCH', 10);

        return (parent::install()
            && $this->registerHook('dashboardZoneTwo')
            && $this->registerHook('dashboardData')
            && $this->registerHook('actionObjectOrderAddAfter')
            && $this->registerHook('actionSearch')
        );
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDashboardZoneTwo($params)
    {
        $this->context->smarty->assign(
            [
                'DASHACTIVITY_CART_ACTIVE'         => Configuration::get('DASHACTIVITY_CART_ACTIVE'),
                'DASHACTIVITY_VISITOR_ONLINE'      => Configuration::get('DASHACTIVITY_VISITOR_ONLINE'),
                'DASHPRODUCT_NBR_SHOW_LAST_ORDER'  => Configuration::get('DASHPRODUCT_NBR_SHOW_LAST_ORDER'),
                'DASHPRODUCT_NBR_SHOW_BEST_SELLER' => Configuration::get('DASHPRODUCT_NBR_SHOW_BEST_SELLER'),
                'DASHPRODUCT_NBR_SHOW_TOP_SEARCH'  => Configuration::get('DASHPRODUCT_NBR_SHOW_TOP_SEARCH'),
                'date_from'                        => Tools::displayDate($params['date_from']),
                'date_to'                          => Tools::displayDate($params['date_to']),
                'dashproducts_config_form'         => $this->renderConfigForm(),
            ]
        );

        return $this->display(__FILE__, 'dashboard_zone_two.tpl');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function hookDashboardData($params)
    {
        $tableRecentOrders = $this->getTableRecentOrders();
        $tableBestSellers = $this->getTableBestSellers($params['date_from'], $params['date_to']);
        $tableMostViewed = $this->getTableMostViewed($params['date_from'], $params['date_to']);
        $tableTop10MostSearch = $this->getTableTop10MostSearch($params['date_from'], $params['date_to']);

        //$table_top_5_search = $this->getTableTop5Search();
        return [
            'data_table' => [
                'table_recent_orders'      => $tableRecentOrders,
                'table_best_sellers'       => $tableBestSellers,
                'table_most_viewed'        => $tableMostViewed,
                'table_top_10_most_search' => $tableTop10MostSearch,
                //'table_top_5_search' => $table_top_5_search
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTableRecentOrders()
    {
        $header = [
            ['title' => $this->l('Customer Name'), 'class' => 'text-left'],
            ['title' => $this->l('Products'), 'class' => 'text-center'],
            ['title' => $this->l('Total').' '.$this->l('Tax excl.'), 'class' => 'text-center'],
            ['title' => $this->l('Date'), 'class' => 'text-center'],
            ['title' => $this->l('Status'), 'class' => 'text-center'],
            ['title' => '', 'class' => 'text-right'],
        ];

        $limit = (int) Configuration::get('DASHPRODUCT_NBR_SHOW_LAST_ORDER') ? (int) Configuration::get('DASHPRODUCT_NBR_SHOW_LAST_ORDER') : 10;
        $orders = Order::getOrdersWithInformations($limit);

        $body = [];
        foreach ($orders as $order) {
            $currency = Currency::getCurrency((int) $order['id_currency']);
            $tr = [];
            $tr[] = [
                'id'    => 'firstname_lastname',
                'value' => '<a href="'.$this->context->link->getAdminLink('AdminCustomers', true).'&id_customer='.$order['id_customer'].'&viewcustomer">'.Tools::htmlentitiesUTF8($order['firstname']).' '.Tools::htmlentitiesUTF8($order['lastname']).'</a>',
                'class' => 'text-left',
            ];
            $tr[] = [
                'id'    => 'total_products',
                'value' => count(OrderDetail::getList((int) $order['id_order'])),
                'class' => 'text-center',
            ];
            $tr[] = [
                'id'            => 'total_paid',
                'value'         => Tools::displayPrice((float) $order['total_paid'], $currency),
                'class'         => 'text-center',
                'wrapper_start' => $order['valid'] ? '<span class="badge badge-success">' : '',
                'wrapper_end'   => '<span>',
            ];
            $tr[] = [
                'id'    => 'date_add',
                'value' => Tools::displayDate($order['date_add']),
                'class' => 'text-center',
            ];
            $tr[] = [
                'id'    => 'status',
                'value' => Tools::htmlentitiesUTF8($order['state_name']),
                'class' => 'text-center',
            ];
            $tr[] = [
                'id'            => 'details',
                'value'         => '',
                'class'         => 'text-right',
                'wrapper_start' => '<a class="btn btn-default" href="index.php?tab=AdminOrders&id_order='.(int) $order['id_order'].'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders').'" title="'.$this->l('Details').'"><i class="icon-search"></i>',
                'wrapper_end'   => '</a>',
            ];

            $body[] = $tr;
        }

        return ['header' => $header, 'body' => $body];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function getTableBestSellers($dateFrom, $dateTo)
    {
        $header = [
            [
                'id'    => 'image',
                'title' => $this->l('Image'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'product',
                'title' => $this->l('Product'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'category',
                'title' => $this->l('Category'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'total_sold',
                'title' => $this->l('Total sold'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'sales',
                'title' => $this->l('Sales'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'net_profit',
                'title' => $this->l('Net profit'),
                'class' => 'text-center',
            ],
        ];

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`product_id`')
                ->select('`product_name`')
                ->select('SUM(`product_quantity`) AS `total`')
                ->select('p.`price` AS `price`')
                ->select('pa.`price` AS `price_attribute`')
                ->select('SUM(`total_price_tax_excl` / `conversion_rate`) AS `sales`')
                ->select('SUM(`product_quantity` * `purchase_supplier_price` / `conversion_rate`) AS `expenses`')
                ->from(bqSQL(Order::$definition['table']), 'o')
                ->leftJoin(bqSQL(OrderDetail::$definition['table']), 'od', 'o.`id_order` = od.`id_order`')
                ->leftJoin(bqSQL(Product::$definition['table']), 'p', 'p.`id_product` = `product_id`')
                ->leftJoin(bqSQL(Product::$definition['table']).'_attribute', 'pa', 'pa.`id_product_attribute` = od.`product_attribute_id`')
                ->where('`invoice_date` BETWEEN "'.psQL($dateFrom).' 00:00:00" AND "'.pSQL($dateTo).' 23:59:59"')
                ->where('`valid` = 1 '.Shop::addSqlRestriction(false, 'o'))
                ->groupBy('`product_id`, product_attribute_id')
                ->orderBy('`total` DESC')
                ->limit((int) Configuration::get('DASHPRODUCT_NBR_SHOW_BEST_SELLER', 10))
        );

        $body = [];
        foreach ($products as $product) {
            $productObj = new Product((int) $product['product_id'], false, $this->context->language->id);
            if (!Validate::isLoadedObject($productObj)) {
                continue;
            }
            $category = new Category($productObj->getDefaultCategory(), $this->context->language->id);

            $img = '';
            if (($rowImage = Product::getCover($productObj->id)) && $rowImage['id_image']) {
                $image = new Image($rowImage['id_image']);
                $pathToImage = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$this->context->controller->imageType;
                $img = ImageManager::thumbnail($pathToImage, 'product_mini_'.$productObj->id.'.'.$this->context->controller->imageType, 45, $this->context->controller->imageType);
            }

            $productPrice = $product['price'];
            if (isset($product['price_attribute']) && $product['price_attribute'] != '0.000000') {
                $productPrice = $product['price_attribute'];
            }

            $body[] = [
                [
                    'id'    => 'product',
                    'value' => $img,
                    'class' => 'text-center',
                ],
                [
                    'id'    => 'product',
                    'value' => '<a href="'.$this->context->link->getAdminLink('AdminProducts', true).'&id_product='.$productObj->id.'&updateproduct">'.Tools::htmlentitiesUTF8($product['product_name']).'</a>'.'<br/>'.Tools::displayPrice($productPrice),
                    'class' => 'text-center',
                ],
                [
                    'id'    => 'category',
                    'value' => $category->name,
                    'class' => 'text-center',
                ],
                [
                    'id'    => 'total_sold',
                    'value' => $product['total'],
                    'class' => 'text-center',
                ],
                [
                    'id'    => 'sales',
                    'value' => Tools::displayPrice($product['sales']),
                    'class' => 'text-center',
                ],
                [
                    'id'    => 'net_profit',
                    'value' => Tools::displayPrice($product['sales'] - $product['expenses']),
                    'class' => 'text-center',
                ],
            ];
        }

        return ['header' => $header, 'body' => $body];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function getTableMostViewed($dateFrom, $dateTo)
    {
        $header = [
            [
                'id'    => 'image',
                'title' => $this->l('Image'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'product',
                'title' => $this->l('Product'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'views',
                'title' => $this->l('Views'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'added_to_cart',
                'title' => $this->l('Added to cart'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'purchased',
                'title' => $this->l('Purchased'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'rate',
                'title' => $this->l('Percentage'),
                'class' => 'text-center',
            ],
        ];

        if (Configuration::get('PS_STATSDATA_PAGESVIEWS')) {
            $products = $this->getTotalViewed($dateFrom, $dateTo, (int) Configuration::get('DASHPRODUCT_NBR_SHOW_MOST_VIEWED'));
            $body = [];
            if (is_array($products) && count($products)) {
                foreach ($products as $product) {
                    $productObj = new Product((int) $product['id_object'], true, $this->context->language->id);
                    if (!Validate::isLoadedObject($productObj)) {
                        continue;
                    }

                    $img = '';
                    if (($rowImage = Product::getCover($productObj->id)) && $rowImage['id_image']) {
                        $image = new Image($rowImage['id_image']);
                        $pathToImage = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$this->context->controller->imageType;
                        $img = ImageManager::thumbnail($pathToImage, 'product_mini_'.$productObj->id.'.'.$this->context->controller->imageType, 45, $this->context->controller->imageType);
                    }

                    $tr = [];
                    $tr[] = [
                        'id'    => 'product',
                        'value' => $img,
                        'class' => 'text-center',
                    ];
                    $tr[] = [
                        'id'    => 'product',
                        'value' => Tools::htmlentitiesUTF8($productObj->name).'<br/>'.Tools::displayPrice(Product::getPriceStatic((int) $productObj->id)),
                        'class' => 'text-center',
                    ];
                    $tr[] = [
                        'id'    => 'views',
                        'value' => $product['counter'],
                        'class' => 'text-center',
                    ];
                    $addedCart = $this->getTotalProductAddedCart($dateFrom, $dateTo, (int) $productObj->id);
                    $tr[] = [
                        'id'    => 'added_to_cart',
                        'value' => $addedCart,
                        'class' => 'text-center',
                    ];
                    $purchased = $this->getTotalProductPurchased($dateFrom, $dateTo, (int) $productObj->id);
                    $tr[] = [
                        'id'    => 'purchased',
                        'value' => $this->getTotalProductPurchased($dateFrom, $dateTo, (int) $productObj->id),
                        'class' => 'text-center',
                    ];
                    $tr[] = [
                        'id'    => 'rate',
                        'value' => ($product['counter'] ? round(100 * $purchased / $product['counter'], 1).'%' : '-'),
                        'class' => 'text-center',
                    ];
                    $body[] = $tr;
                }
            }
        } else {
            $body = '<div class="alert alert-info">'.$this->l('You must enable the "Save global page views" option from the "Data mining for statistics" module in order to display the most viewed products, or use the Google Analytics module.').'</div>';
        }

        return ['header' => $header, 'body' => $body];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function getTableTop10MostSearch($dateFrom, $dateTo)
    {
        $header = [
            [
                'id'    => 'reference',
                'title' => $this->l('Term'),
                'class' => 'text-left',
            ],
            [
                'id'    => 'name',
                'title' => $this->l('Search'),
                'class' => 'text-center',
            ],
            [
                'id'    => 'totalQuantitySold',
                'title' => $this->l('Results'),
                'class' => 'text-center',
            ],
        ];

        $terms = $this->getMostSearchTerms($dateFrom, $dateTo, (int) Configuration::get('DASHPRODUCT_NBR_SHOW_TOP_SEARCH'));
        $body = [];
        if (is_array($terms) && count($terms)) {
            foreach ($terms as $term) {
                $tr = [];
                $tr[] = [
                    'id'    => 'product',
                    'value' => $term['keywords'],
                    'class' => 'text-left',
                ];
                $tr[] = [
                    'id'    => 'product',
                    'value' => $term['count_keywords'],
                    'class' => 'text-center',
                ];
                $tr[] = [
                    'id'    => 'product',
                    'value' => $term['results'],
                    'class' => 'text-center',
                ];
                $body[] = $tr;
            }
        }

        return ['header' => $header, 'body' => $body];
    }

    /**
     * @return array
     */
    public function getTableTop5Search()
    {
        $header = [
            [
                'id'    => 'reference',
                'title' => $this->l('Product'),
            ],
        ];

        $body = [];

        return ['header' => $header, 'body' => $body];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int    $idProduct
     *
     * @return int
     */
    public function getTotalProductSales($dateFrom, $dateTo, $idProduct)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(od.`product_quantity` * od.`product_price`) AS total')
                ->from(bqSQL(OrderDetail::$definition['table']), 'od')
                ->innerJoin(bqSQL(Order::$definition['table']), 'o', 'o.`id_order` = od.`id_order`')
                ->where('od.`product_id` = '.(int) $idProduct.' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o'))
                ->where('o.`valid` = 1')
                ->where('o.`date_add` BETWEEN "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).'"')
        );
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int    $idProduct
     *
     * @return false|null|string
     */
    public function getTotalProductAddedCart($dateFrom, $dateTo, $idProduct)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(`id_product`) AS count')
                ->from('cart_product', 'cp')
                ->where('cp.`id_product` = '.(int) $idProduct.' '.Shop::addSqlRestriction(false, 'cp'))
                ->where('cp.`date_add` BETWEEN "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).'"')
        );
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int    $idProduct
     *
     * @return false|null|string
     */
    public function getTotalProductPurchased($dateFrom, $dateTo, $idProduct)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('count(`product_id`) AS count')
                ->from(bqSQL(OrderDetail::$definition['table']), 'od')
                ->innerJoin(bqSQL(Order::$definition['table']), 'o', 'o.`id_order` = od.`id_order`')
                ->where('od.`product_id` = '.(int) $idProduct.' '.Shop::addSqlRestriction(false, 'od'))
                ->where('o.`valid` = 1')
                ->where('o.`date_add` BETWEEN "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).'"')
        );
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int    $limit
     *
     * @return array|false|null|PDOStatement
     */
    public function getTotalViewed($dateFrom, $dateTo, $limit = 10)
    {
        /** @var Gapi $gapi */
        $gapi = Module::isInstalled('gapi') ? Module::getInstanceByName('gapi') : false;
        if (Validate::isLoadedObject($gapi) && $gapi->isConfigured()) {
            $products = [];
            // Only works with the default product URL pattern at this time
            if ($result = $gapi->requestReportData('ga:pagePath', 'ga:visits', $dateFrom, $dateTo, '-ga:visits', 'ga:pagePath=~/([a-z]{2}/)?([a-z]+/)?[0-9][0-9]*\-.*\.html$', 1, 10)) {
                foreach ($result as $row) {
                    if (preg_match('@/([a-z]{2}/)?([a-z]+/)?([0-9]+)\-.*\.html$@', $row['dimensions']['pagePath'], $matches)) {
                        $products[] = ['id_object' => (int) $matches[3], 'counter' => $row['metrics']['visits']];
                    }
                }
            }

            return $products;
        } else {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('p.`id_object`, pv.`counter`')
                    ->from('page_viewed', 'pv')
                    ->leftJoin('date_range', 'dr', 'pv.`id_date_range` = dr.`id_date_range`')
                    ->leftJoin('page', 'p', 'pv.`id_page` = p.`id_page`')
                    ->leftJoin('page_type', 'pt', 'pt.`id_page_type` = p.`id_page_type`')
                    ->where('pt.`name` = \'product\' '.Shop::addSqlRestriction(false, 'pv'))
                    ->where('dr.`time_start` BETWEEN "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).'"')
                    ->where('dr.`time_end` BETWEEN "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).'"')
                    ->limit((int) $limit)
            );
        }
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int    $limit
     *
     * @return array|false|null|PDOStatement
     */
    public function getMostSearchTerms($dateFrom, $dateTo, $limit = 10)
    {
        if (!Module::isInstalled('statssearch')) {
            return [];
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`keywords`, count(`id_statssearch`) AS count_keywords, `results`')
                ->from('statssearch', 'ss')
                ->where('ss.`date_add` BETWEEN "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).' "'.Shop::addSqlRestriction(false, 'ss'))
                ->groupBy('ss.`keywords`')
                ->orderBy('`count_keywords` DESC')
                ->limit((int) $limit)
        );
    }

    /**
     * @return string
     */
    public function renderConfigForm()
    {
        $fieldsForm = [
            'form' => [
                'input'  => [],
                'submit' => [
                    'title' => $this->l('   Save   '),
                    'class' => 'btn btn-default pull-right submit_dash_config',
                    'reset' => [
                        'title' => $this->l('Cancel'),
                        'class' => 'btn btn-default cancel_dash_config',
                    ],
                ],
            ],
        ];

        $inputs = [
            [
                'label'       => $this->l('Number of "Recent Orders" to display'),
                'config_name' => 'DASHPRODUCT_NBR_SHOW_LAST_ORDER',
            ],
            [
                'label'       => $this->l('Number of "Best Sellers" to display'),
                'config_name' => 'DASHPRODUCT_NBR_SHOW_BEST_SELLER',
            ],
            [
                'label'       => $this->l('Number of "Most Viewed" to display'),
                'config_name' => 'DASHPRODUCT_NBR_SHOW_MOST_VIEWED',
            ],
            [
                'label'       => $this->l('Number of "Top Searches" to display'),
                'config_name' => 'DASHPRODUCT_NBR_SHOW_TOP_SEARCH',
            ],
        ];

        foreach ($inputs as $input) {
            $fieldsForm['form']['input'][] = [
                'type'    => 'select',
                'label'   => $input['label'],
                'name'    => $input['config_name'],
                'options' => [
                    'query' => [
                        ['id' => 5, 'name' => 5],
                        ['id' => 10, 'name' => 10],
                        ['id' => 20, 'name' => 20],
                        ['id' => 50, 'name' => 50],
                    ],
                    'id'    => 'id',
                    'name'  => 'name',
                ],
            ];
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDashConfig';
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
    public function getConfigFieldsValues()
    {
        return [
            'DASHPRODUCT_NBR_SHOW_LAST_ORDER'  => Configuration::get('DASHPRODUCT_NBR_SHOW_LAST_ORDER'),
            'DASHPRODUCT_NBR_SHOW_BEST_SELLER' => Configuration::get('DASHPRODUCT_NBR_SHOW_BEST_SELLER'),
            'DASHPRODUCT_NBR_SHOW_MOST_VIEWED' => Configuration::get('DASHPRODUCT_NBR_SHOW_MOST_VIEWED'),
            'DASHPRODUCT_NBR_SHOW_TOP_SEARCH'  => Configuration::get('DASHPRODUCT_NBR_SHOW_TOP_SEARCH'),
        ];
    }

    /**
     * @param mixed $params
     */
    public function hookActionObjectOrderAddAfter($params)
    {
        Tools::changeFileMTime($this->push_filename);
    }

    /**
     * @param mixed $params
     */
    public function hookActionSearch($params)
    {
        Tools::changeFileMTime($this->push_filename);
    }
}
