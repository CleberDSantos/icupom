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

class StatsPersonalInfos extends StatsModule
{
    protected $type = 'Graph';
    protected $html = '';
    protected $option;

    public function __construct()
    {
        $this->name = 'statspersonalinfos';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = Translate::getModuleTranslation('statsmodule', 'Registered customer information', 'statsmodule');
        $this->description = Translate::getModuleTranslation('statsmodule', 'Adds information about your registered customers (such as gender and age) to the Stats dashboard.', 'statsmodule');
    }

    public function install()
    {
        return (parent::install() && $this->registerHook('AdminStatsModules'));
    }

    public function hookAdminStatsModules()
    {
        $this->html = '
			<div class="panel-heading">
				'.$this->displayName.'
			</div>
			<h4>'.Translate::getModuleTranslation('statsmodule', 'Guide', 'statsmodule').'</h4>
			<div class="alert alert-warning">
				<h4>'.Translate::getModuleTranslation('statsmodule', 'Target your audience', 'statsmodule').'</h4>
				<p>'.
            Translate::getModuleTranslation('statsmodule', 'In order for each message to have an impact, you need to know who it is being addressed to. ', 'statsmodule').
            '<br>'.
            Translate::getModuleTranslation('statsmodule', 'Defining your target audience is essential when choosing the right tools to win them over.', 'statsmodule').
            '<br>'.
            Translate::getModuleTranslation('statsmodule', 'It is best to limit an action to a group -- or to groups -- of clients.', 'statsmodule').
            '<br>'.
            Translate::getModuleTranslation('statsmodule', 'Storing registered customer information allows you to accurately define customer profiles so you can adapt your special deals and promotions.', 'statsmodule').'
				</p>
				<div>
					'.Translate::getModuleTranslation('statsmodule', 'You can increase your sales by:', 'statsmodule').'
					<ul>
						<li class="bullet">'.Translate::getModuleTranslation('statsmodule', 'Launching targeted advertisement campaigns.', 'statsmodule').'</li>
						<li class="bullet">'.Translate::getModuleTranslation('statsmodule', 'Contacting a group of clients by email or newsletter.', 'statsmodule').'</li>
					</ul>
				</div>
			</div>';
        $has_customers = (bool) Db::getInstance()->getValue('SELECT id_customer FROM '._DB_PREFIX_.'customer');
        if ($has_customers) {
            if (Tools::getValue('export'))
                if (Tools::getValue('exportType') == 'gender')
                    $this->csvExport(array(
                        'type'   => 'pie',
                        'option' => 'gender',
                    ));
                else if (Tools::getValue('exportType') == 'age')
                    $this->csvExport(array(
                        'type'   => 'pie',
                        'option' => 'age',
                    ));
                else if (Tools::getValue('exportType') == 'country')
                    $this->csvExport(array(
                        'type'   => 'pie',
                        'option' => 'country',
                    ));
                else if (Tools::getValue('exportType') == 'currency')
                    $this->csvExport(array(
                        'type'   => 'pie',
                        'option' => 'currency',
                    ));
                else if (Tools::getValue('exportType') == 'language')
                    $this->csvExport(array(
                        'type'   => 'pie',
                        'option' => 'language',
                    ));

            $this->html .= '
				<div class="row row-margin-bottom">
					<div class="col-lg-12">
						<div class="col-lg-8">
							'.$this->engine($this->type, array(
                    'type'   => 'pie',
                    'option' => 'gender',
                )).'
						</div>
						<div class="col-lg-4">
							<p>'.Translate::getModuleTranslation('statsmodule', 'Gender distribution allows you to determine the percentage of men and women shoppers on your store.', 'statsmodule').'</p>
							<hr/>
							<a class="btn btn-default export-csv" href="'.Tools::safeOutput($_SERVER['REQUEST_URI'].'&export=1&exportType=gender').'">
								<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
							</a>
						</div>
					</div>
				</div>
				<div class="row row-margin-bottom">
					<div class="col-lg-12">
						<div class="col-lg-8">
							'.$this->engine($this->type, array(
                    'type'   => 'pie',
                    'option' => 'age',
                )).'
						</div>
						<div class="col-lg-4">
							<p>'.Translate::getModuleTranslation('statsmodule', 'Age ranges allow you to better understand target demographics.', 'statsmodule').'</p>
							<hr/>
							<a class="btn btn-default export-csv" href="'.Tools::safeOutput($_SERVER['REQUEST_URI'].'&export=1&exportType=age').'">
								<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
							</a>
						</div>
					</div>
				</div>
				<div class="row row-margin-bottom">
					<div class="col-lg-12">
						<div class="col-lg-8">
							'.$this->engine($this->type, array(
                    'type'   => 'pie',
                    'option' => 'country',
                )).'
						</div>
						<div class="col-lg-4">
							<p>'.Translate::getModuleTranslation('statsmodule', 'Country distribution allows you to analyze which part of the World your customers are shopping from.', 'statsmodule').'</p>
							<hr/>
							<a class="btn btn-default export-csv" href="'.Tools::safeOutput($_SERVER['REQUEST_URI'].'&export=1&exportType=country').'">
								<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
							</a>
						</div>
					</div>
				</div>
				<div class="row row-margin-bottom">
					<div class="col-lg-12">
						<div class="col-lg-8">
							'.$this->engine($this->type, array(
                    'type'   => 'pie',
                    'option' => 'currency',
                )).'
						</div>
						<div class="col-lg-4">
							<p>'.Translate::getModuleTranslation('statsmodule', 'Currency range allows you to determine which currency your customers are using.', 'statsmodule').'</p>
							<hr/>
							<a class="btn btn-default export-csv" href="'.Tools::safeOutput($_SERVER['REQUEST_URI'].'&export=1&exportType=currency').'">
								<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
							</a>
						</div>
					</div>
				</div>
				<div class="row row-margin-bottom">
					<div class="col-lg-12">
						<div class="col-lg-8">
							'.$this->engine($this->type, array(
                    'type'   => 'pie',
                    'option' => 'language',
                )).'
						</div>
						<div class="col-lg-4">
							<p>'.Translate::getModuleTranslation('statsmodule', 'Language distribution allows you to analyze the browsing language used by your customers.', 'statsmodule').'</p>
							<hr/>
							<a class="btn btn-default export-csv" href="'.Tools::safeOutput($_SERVER['REQUEST_URI'].'&export=1&exportType=language').'">
								<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
							</a>
						</div>
					</div>
				</div>';
        } else
            $this->html .= '<p>'.Translate::getModuleTranslation('statsmodule', 'No customers have registered yet.', 'statsmodule').'</p>';

        return $this->html;
    }

    public function setOption($option, $layers = 1)
    {
        $this->option = $option;
    }

    protected function getData($layers)
    {
        switch ($this->option) {
            case 'gender':
                $this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Gender distribution', 'statsmodule');
                $genders = array(
                    0 => Translate::getModuleTranslation('statsmodule', 'Male', 'statsmodule'),
                    1 => Translate::getModuleTranslation('statsmodule', 'Female', 'statsmodule'),
                    2 => Translate::getModuleTranslation('statsmodule', 'Unknown', 'statsmodule'),
                );

                $sql = 'SELECT g.type, c.id_gender, COUNT(c.id_customer) AS total
						FROM '._DB_PREFIX_.'customer c
						LEFT JOIN '._DB_PREFIX_.'gender g ON c.id_gender = g.id_gender
						WHERE 1
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c').'
						GROUP BY c.id_gender';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

                $genders_results = array();
                foreach ($result as $row) {
                    $type = (is_null($row['type'])) ? 2 : $row['type'];
                    if (!isset($genders_results[$type]))
                        $genders_results[$type] = 0;
                    $genders_results[$type] += $row['total'];
                }

                foreach ($genders_results as $type => $total) {
                    $this->_values[] = $total;
                    $this->_legend[] = $genders[$type];
                }
                break;

            case 'age':
                $this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Age range', 'statsmodule');

                // 0 - 18 years
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) < 18
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
							AND `birthday` IS NOT NULL';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', '0-18', 'statsmodule');
                }

                // 18 - 24 years
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) >= 18
							AND (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) < 25
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
							AND `birthday` IS NOT NULL';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', '18-24', 'statsmodule');
                }

                // 25 - 34 years
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) >= 25
							AND (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) < 35
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
							AND `birthday` IS NOT NULL';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', '25-34', 'statsmodule');
                }

                // 35 - 49 years
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) >= 35
							AND (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) < 50
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
							AND `birthday` IS NOT NULL';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', '35-49', 'statsmodule');
                }

                // 50 - 59 years
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) >= 50
							AND (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) < 60
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
							AND `birthday` IS NOT NULL';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', '50-59', 'statsmodule');
                }

                // More than 60 years
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE (YEAR(CURDATE()) - YEAR(`birthday`)) - (RIGHT(CURDATE(), 5) < RIGHT(`birthday`, 5)) >= 60
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).'
							AND `birthday` IS NOT NULL';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', '60+', 'statsmodule');
                }

                // Total unknown
                $sql = 'SELECT COUNT(`id_customer`) AS total
						FROM `'._DB_PREFIX_.'customer`
						WHERE `birthday` IS NULL
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER);
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                if (isset($result['total']) && $result['total']) {
                    $this->_values[] = $result['total'];
                    $this->_legend[] = Translate::getModuleTranslation('statsmodule', 'Unknown', 'statsmodule');
                }
                break;

            case 'country':
                $this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Country distribution', 'statsmodule');
                $sql = 'SELECT cl.`name`, COUNT(c.`id_country`) AS total
						FROM `'._DB_PREFIX_.'address` a
						LEFT JOIN `'._DB_PREFIX_.'customer` cu ON cu.id_customer = a.id_customer
						LEFT JOIN `'._DB_PREFIX_.'country` c ON a.`id_country` = c.`id_country`
						LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.(int) $this->context->language->id.')
						WHERE a.id_customer != 0
							'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'cu').'
						GROUP BY c.`id_country`';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
                foreach ($result as $row) {
                    $this->_values[] = $row['total'];
                    $this->_legend[] = $row['name'];
                }
                break;

            case 'currency':
                $this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Currency distribution', 'statsmodule');
                $sql = 'SELECT c.`name`, COUNT(c.`id_currency`) AS total
						FROM `'._DB_PREFIX_.'orders` o
						LEFT JOIN `'._DB_PREFIX_.'currency` c ON o.`id_currency` = c.`id_currency`
						WHERE 1
							'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
						GROUP BY c.`id_currency`';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
                foreach ($result as $row) {
                    $this->_values[] = $row['total'];
                    $this->_legend[] = $row['name'];
                }
                break;

            case 'language':
                $this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Language distribution', 'statsmodule');
                $sql = 'SELECT c.`name`, COUNT(c.`id_lang`) AS total
						FROM `'._DB_PREFIX_.'orders` o
						LEFT JOIN `'._DB_PREFIX_.'lang` c ON o.`id_lang` = c.`id_lang`
						WHERE 1
							'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
						GROUP BY c.`id_lang`';
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
                foreach ($result as $row) {
                    $this->_values[] = $row['total'];
                    $this->_legend[] = $row['name'];
                }
                break;
        }
    }
}
