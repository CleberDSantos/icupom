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

if (!defined('_TB_VERSION_'))
    exit;

class StatsLive extends Module
{
    protected $html = '';

    public function __construct()
    {
        $this->name = 'statslive';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = Translate::getModuleTranslation('statsmodule', 'Visitors online', 'statsmodule');
        $this->description = Translate::getModuleTranslation('statsmodule', 'Adds a list of customers and visitors who are currently online to the Stats dashboard.', 'statsmodule');
    }

    public function install()
    {
        return parent::install() && $this->registerHook('AdminStatsModules');
    }

    /**
     * Get the number of online customers
     *
     * @return array(array, int) array of online customers entries, number of online customers
     */
    private function getCustomersOnline()
    {
        if ($maintenance_ips = Configuration::get('PS_MAINTENANCE_IP'))
            $maintenance_ips = implode(',', array_map('ip2long', array_map('trim', explode(',', $maintenance_ips))));

        if (Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            $sql = 'SELECT u.id_customer, u.firstname, u.lastname, pt.name AS page
					FROM `'._DB_PREFIX_.'connections` c
					LEFT JOIN `'._DB_PREFIX_.'connections_page` cp ON c.id_connections = cp.id_connections
					LEFT JOIN `'._DB_PREFIX_.'page` p ON p.id_page = cp.id_page
					LEFT JOIN `'._DB_PREFIX_.'page_type` pt ON p.id_page_type = pt.id_page_type
					INNER JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
					INNER JOIN `'._DB_PREFIX_.'customer` u ON u.id_customer = g.id_customer
					WHERE cp.`time_end` IS NULL
						'.Shop::addSqlRestriction(false, 'c').'
						AND TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', cp.`time_start`)) < 900
					'.($maintenance_ips ? 'AND c.ip_address NOT IN ('.preg_replace('/[^,0-9]/', '', $maintenance_ips).')' : '').'
					GROUP BY u.id_customer
					ORDER BY u.firstname, u.lastname';
        } else {
            $sql = 'SELECT u.id_customer, u.firstname, u.lastname, "-" AS page
					FROM `'._DB_PREFIX_.'connections` c
					INNER JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
					INNER JOIN `'._DB_PREFIX_.'customer` u ON u.id_customer = g.id_customer
					WHERE TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', c.`date_add`)) < 900
						'.Shop::addSqlRestriction(false, 'c').'
					'.($maintenance_ips ? 'AND c.ip_address NOT IN ('.preg_replace('/[^,0-9]/', '', $maintenance_ips).')' : '').'
					GROUP BY u.id_customer
					ORDER BY u.firstname, u.lastname';
        }
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return array($results, Db::getInstance()->NumRows());
    }

    /**
     * Get the number of online visitors
     *
     * @return array(array, int) array of online visitors entries, number of online visitors
     */
    private function getVisitorsOnline()
    {
        if ($maintenance_ips = Configuration::get('PS_MAINTENANCE_IP'))
            $maintenance_ips = implode(',', array_map('ip2long', array_filter(array_map('trim', explode(',', $maintenance_ips)))));

        if (Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            $sql = 'SELECT c.id_guest, c.ip_address, c.date_add, c.http_referer, pt.name AS page
					FROM `'._DB_PREFIX_.'connections` c
					LEFT JOIN `'._DB_PREFIX_.'connections_page` cp ON c.id_connections = cp.id_connections
					LEFT JOIN `'._DB_PREFIX_.'page` p ON p.id_page = cp.id_page
					LEFT JOIN `'._DB_PREFIX_.'page_type` pt ON p.id_page_type = pt.id_page_type
					INNER JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
					WHERE (g.id_customer IS NULL OR g.id_customer = 0)
						'.Shop::addSqlRestriction(false, 'c').'
						AND cp.`time_end` IS NULL
					AND TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', cp.`time_start`)) < 900
					'.($maintenance_ips ? 'AND c.ip_address NOT IN ('.preg_replace('/[^,0-9]/', '', $maintenance_ips).')' : '').'
					GROUP BY c.id_connections
					ORDER BY c.date_add DESC';
        } else {
            $sql = 'SELECT c.id_guest, c.ip_address, c.date_add, c.http_referer, "-" AS page
					FROM `'._DB_PREFIX_.'connections` c
					INNER JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
					WHERE (g.id_customer IS NULL OR g.id_customer = 0)
						'.Shop::addSqlRestriction(false, 'c').'
						AND TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', c.`date_add`)) < 900
					'.($maintenance_ips ? 'AND c.ip_address NOT IN ('.preg_replace('/[^,0-9]/', '', $maintenance_ips).')' : '').'
					ORDER BY c.date_add DESC';
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return array($results, Db::getInstance()->NumRows());
    }

    public function hookAdminStatsModules($params)
    {
        list($customers, $total_customers) = $this->getCustomersOnline();
        list($visitors, $total_visitors) = $this->getVisitorsOnline();
        $irow = 0;

        $this->html .= '<script type="text/javascript">
			$("#calendar").remove();
		</script>';
        if (!Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS'))
            $this->html .= '
				<div class="alert alert-info">'.
                Translate::getModuleTranslation('statsmodule', 'You must activate the "Save page views for each customer" option in the "Data mining for statistics" (StatsData) module in order to see the pages that your visitors are currently viewing.', 'statsmodule').'
				</div>';
        $this->html .= '
			<h4> '.Translate::getModuleTranslation('statsmodule', 'Current online customers', 'statsmodule').'</h4>';
        if ($total_customers) {
            $this->html .= Translate::getModuleTranslation('statsmodule', 'Total:', 'statsmodule').' '.(int) $total_customers.'
			<table class="table">
				<thead>
					<tr>
						<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Customer ID', 'statsmodule').'</span></th>
						<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Name', 'statsmodule').'</span></th>
						<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Current page', 'statsmodule').'</span></th>
						<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'View customer profile', 'statsmodule').'</span></th>
					</tr>
				</thead>
				<tbody>';
            foreach ($customers as $customer)
                $this->html .= '
					<tr'.($irow++ % 2 ? ' class="alt_row"' : '').'>
						<td class="center">'.$customer['id_customer'].'</td>
						<td class="center">'.$customer['firstname'].' '.$customer['lastname'].'</td>
						<td class="center">'.$customer['page'].'</td>
						<td class="center">
							<a href="'.Tools::safeOutput('index.php?tab=AdminCustomers&id_customer='.$customer['id_customer'].'&viewcustomer&token='.Tools::getAdminToken('AdminCustomers'.(int) Tab::getIdFromClassName('AdminCustomers').(int) $this->context->employee->id)).'"
								target="_blank">
								<img src="../modules/statslive/logo.gif" />
							</a>
						</td>
					</tr>';
            $this->html .= '
				</tbody>
			</table>';
        } else
            $this->html .= '<p class="alert alert-warning">'.Translate::getModuleTranslation('statsmodule', 'There are no active customers online right now.', 'statsmodule').'</p>';
        $this->html .= '
			<h4> '.Translate::getModuleTranslation('statsmodule', 'Current online visitors', 'statsmodule').'</h4>';
        if ($total_visitors) {
            $this->html .= Translate::getModuleTranslation('statsmodule', 'Total:', 'statsmodule').' '.(int) $total_visitors.'
			<div>
				<table class="table">
					<thead>
						<tr>
							<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Guest ID', 'statsmodule').'</span></th>
							<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'IP', 'statsmodule').'</span></th>
							<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Last activity', 'statsmodule').'</span></th>
							<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Current page', 'statsmodule').'</span></th>
							<th class="center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Referrer', 'statsmodule').'</span></th>
						</tr>
					</thead>
					<tbody>';
            foreach ($visitors as $visitor)
                $this->html .= '<tr'.($irow++ % 2 ? ' class="alt_row"' : '').'>
						<td class="center">'.$visitor['id_guest'].'</td>
						<td class="center">'.long2ip($visitor['ip_address']).'</td>
						<td class="center">'.Tools::substr($visitor['date_add'], 11).'</td>
						<td class="center">'.(isset($visitor['page']) ? $visitor['page'] : Translate::getModuleTranslation('statsmodule', 'Undefined', 'statsmodule')).'</td>
						<td class="center">'.(empty($visitor['http_referer']) ? Translate::getModuleTranslation('statsmodule', 'None', 'statsmodule') : parse_url($visitor['http_referer'], PHP_URL_HOST)).'</td>
					</tr>';
            $this->html .= '
					</tbody>
				</table>
			</div>';
        } else
            $this->html .= '<p class="alert alert-warning">'.Translate::getModuleTranslation('statsmodule', 'There are no visitors online.', 'statsmodule').'</p>';
        $this->html .= '
			<h4>'.Translate::getModuleTranslation('statsmodule', 'Notice', 'statsmodule').'</h4>
			<p class="alert alert-info">'.Translate::getModuleTranslation('statsmodule', 'Maintenance IPs are excluded from the online visitors.', 'statsmodule').'</p>
			<a class="btn btn-default" href="'.Tools::safeOutput('index.php?controller=AdminMaintenance&token='.Tools::getAdminTokenLite('AdminMaintenance')).'">
				<i class="icon-share-alt"></i> '.Translate::getModuleTranslation('statsmodule', 'Add or remove an IP address.', 'statsmodule').'
			</a>
		';

        return $this->html;
    }
}
