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

class StatsOrdersProfit extends StatsModule
{
    protected $type = 'Grid';
    protected $html = null;
    protected $query = null;
    protected $columns = null;
    protected $default_sort_column = null;
    protected $default_sort_direction = null;
    protected $empty_message = null;
    protected $paging_message = null;

    public function __construct()
    {
        $this->name = 'statsordersprofit';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->default_sort_column = 'date_add';
        $this->default_sort_direction = 'ASC';
        $this->empty_message = Translate::getModuleTranslation('statsmodule', 'An empty record-set was returned.', 'statsmodule');
        $this->paging_message = sprintf(Translate::getModuleTranslation('statsmodule', 'Displaying %1$s of %2$s', 'statsmodule'), '{0} - {1}', '{2}');

        $this->columns = array(
            array(
                'id'        => 'number',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Order ID', 'statsmodule'),
                'dataIndex' => 'number',
                 'align'    => 'center',
            ),
            array(
                'id'        => 'invoice_number',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Invoice Number', 'statsmodule'),
                'dataIndex' => 'invoice_number',
                'align'     => 'center',
            ),
            array(
                'id'        => 'invoice_date',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Invoice Date', 'statsmodule'),
                'dataIndex' => 'invoice_date',
                'align'     => 'center',
            ),
            array(
                'id'        => 'paid',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Paid', 'statsmodule'),
                'dataIndex' => 'paid',
                'align'     => 'center',
            ),
            array(
                'id'        => 'total',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Total', 'statsmodule'),
                'dataIndex' => 'total',
                'align'     => 'center',
            ),
            array(
                'id'        => 'shipping',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Shipping', 'statsmodule'),
                'dataIndex' => 'shipping',
                'align'     => 'center',
            ),
            array(
                'id'        => 'TaxTotal',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Tax', 'statsmodule'),
                'dataIndex' => 'TaxTotal',
                'align'     => 'center',
            ),
            array(
                'id'        => 'cost',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Cost', 'statsmodule'),
                'dataIndex' => 'cost',
                'align'     => 'center',
            ),
            array(
                'id'        => 'totalDiscount',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Discount', 'statsmodule'),
                'dataIndex' => 'totalDiscount',
                'align'     => 'center',
            ),
            array(
                'id'        => 'profit',
                'header'    => Translate::getModuleTranslation('statsmodule', 'Profit', 'statsmodule'),
                'dataIndex' => 'profit',
                'align'     => 'center',
            ),
        );

        $this->displayName = Translate::getModuleTranslation('statsmodule', 'Orders Profit', 'statsmodule');
        $this->description = Translate::getModuleTranslation('statsmodule', 'Adds a list of the orders profit to the Stats dashboard..', 'statsmodule');
    }

    public function install()
    {
        return (parent::install() && $this->registerHook('AdminStatsModules'));
    }

    public function hookAdminStatsModules($params)
    {
        $engine_params = array(
            'id'                   => 'id_product',
            'title'                => $this->displayName,
            'columns'              => $this->columns,
            'defaultSortColumn'    => $this->default_sort_column,
            'defaultSortDirection' => $this->default_sort_direction,
            'emptyMessage'         => $this->empty_message,
            'pagingMessage'        => $this->paging_message,
        );

        if (Tools::getValue('export'))
            $this->csvExport($engine_params);

        return '<div class="panel-heading">'.$this->displayName.'</div>
		'.$this->engine($this->type, $engine_params).'
		<a class="btn btn-default export-csv" href="'.Tools::safeOutput($_SERVER['REQUEST_URI'].'&export=1').'">
			<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
		</a>';
    }

    public function getData($layers = null)
    {
        $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $date_between = $this->getDate();
        $array_date_between = explode(' AND ', $date_between);

        $this->query = 'SELECT o.id_order as number, o.invoice_number as invoice_number, DATE_FORMAT(o.invoice_date, \'%Y-%m-%d\') as invoice_date, ROUND( o.total_paid / o.conversion_rate , 2 ) as paid,  ROUND((o.total_paid / o.conversion_rate + o.total_discounts_tax_incl / o.conversion_rate), 2 ) as total, ROUND((o.total_paid / o.conversion_rate - total_paid_tax_excl / o.conversion_rate) , 2 ) as TaxTotal, ROUND( o.total_shipping_tax_excl / o.conversion_rate , 2 ) AS shipping, ROUND(SUM(o.total_discounts_tax_incl / o.conversion_rate),2) as totalDiscount, ((
			SELECT ROUND(SUM(p.wholesale_price / o.conversion_rate * od.product_quantity / o.conversion_rate), 2)
                        FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
			LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
			WHERE od.id_order = o.`id_order`
			)) AS cost,
			((
			SELECT ROUND(o.`total_paid` / o.conversion_rate  - SUM(p.wholesale_price / o.conversion_rate * od.product_quantity) , 2)
			FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
			LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
			WHERE od.id_order = o.`id_order`
			) -
			(ROUND( o.total_paid_tax_incl / o.conversion_rate - o.total_paid_tax_excl / o.conversion_rate, 2 )) -
			ROUND( o.total_shipping_tax_excl / o.conversion_rate , 2 )
			) AS profit
			FROM `'._DB_PREFIX_.'orders` o
			WHERE o.valid = 1
			AND o.invoice_date BETWEEN '.$date_between.'
			GROUP BY o.`id_order`';

        if (Validate::IsName($this->_sort)) {
            $this->query .= ' ORDER BY `'.bqSQL($this->_sort).'`';
            if (isset($this->_direction) && Validate::isSortDirection($this->_direction))
                $this->query .= ' '.$this->_direction;
        }


        $values = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query);
        foreach ($values as &$value) {
            $value['paid'] = Tools::displayPrice($value['paid'], $currency);
            $value['total'] = Tools::displayPrice($value['total'], $currency);
            $value['shipping'] = Tools::displayPrice($value['shipping'], $currency);
            $value['TaxTotal'] = Tools::displayPrice($value['TaxTotal'], $currency);
            $value['cost'] = Tools::displayPrice($value['cost'], $currency);
            $value['totalDiscount'] = Tools::displayPrice($value['totalDiscount'], $currency);
            $value['profit'] = Tools::displayPrice($value['profit'], $currency);
        }
        unset($value);

        $this->_values = $values;
        $this->_totalCount = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()');
    }
}
