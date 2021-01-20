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

class StatsGroups extends Module
{
    protected $html = '';

    public function __construct()
    {
        $this->name = 'statsgroups';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = Translate::getModuleTranslation('statsmodule', 'Stats by Groups', 'statsmodule');
        $this->description = Translate::getModuleTranslation('statsmodule', 'This is the main module for the Stats by Group. It displays a summary of all your group statistics.', 'statsmodule');
    }

    public function install()
    {
        return (parent::install() && $this->registerHook('AdminStatsModules'));
    }

    public function getContent()
    {
        Tools::redirectAdmin('index.php?controller=AdminStats&module=statsgroups&token='.Tools::getAdminTokenLite('AdminStats'));
    }

    public function hookAdminStatsModules()
    {
        if (!isset($this->context->cookie->stats_granularity)) {
            $this->context->cookie->stats_granularity = 10;
        }
        if (Tools::isSubmit('submitIdZone')) {
            $this->context->cookie->stats_id_zone = (int) Tools::getValue('stats_id_zone');
        }
        if (Tools::isSubmit('submitGranularity')) {
            $this->context->cookie->stats_granularity = Tools::getValue('stats_granularity');
        }

        $currency = $this->context->currency;
        $employee = $this->context->employee;

        $dateFromGinvoice = ($this->context->cookie->stats_granularity != 42
            ? 'LEFT(invoice_date, '.(int) $this->context->cookie->stats_granularity.')'
            : 'IFNULL(MAKEDATE(YEAR(invoice_date),DAYOFYEAR(invoice_date)-WEEKDAY(invoice_date)), CONCAT(YEAR(invoice_date),"-01-01*"))'
        );

        $this->html .= '<div>
            <div class="panel-heading"><i class="icon-dashboard"></i> '.$this->displayName.'</div>
            <div class="alert alert-info">'.Translate::getModuleTranslation('statsmodule', 'The listed amounts do not include tax.', 'statsmodule').'</div>';

        $resultSql = 'SELECT COUNT(*) as countOrders,
            SUM((SELECT SUM(od.product_quantity) FROM '._DB_PREFIX_.'order_detail od WHERE o.id_order = od.id_order)) as countProducts,
            SUM(o.total_paid_tax_excl / o.conversion_rate) as totalSales
            FROM '._DB_PREFIX_.'orders o
            WHERE o.valid = 1
            AND o.invoice_date BETWEEN '.ModuleGraph::getDateBetween().'
            '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o');

        if ($newResult = Db::getInstance()->getRow($resultSql)) {
            $this->html .= '<div>'.Translate::getModuleTranslation('statsmodule', 'Placed orders', 'statsmodule').': '.$newResult['countOrders'].' | '.Translate::getModuleTranslation('statsmodule', 'Bought items', 'statsmodule').': '.$newResult['countProducts'].' | '.Translate::getModuleTranslation('statsmodule', 'Revenue', 'statsmodule').': '.Tools::displayPrice($newResult['totalSales'], $currency).'</div><br /><br />';

            $this->html .= '<table class="table">
                <thead>
                  <tr>
                    <th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'ID', 'statsmodule').'</span></th>
                    <th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Group', 'statsmodule').'</span></th>
                    <th class="text-right"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Revenue', 'statsmodule').'</span></th>
                    <th class="text-right"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Average cart value', 'statsmodule').'</span></th>
                    <th class="text-center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Placed orders', 'statsmodule').'</span></th>
                    <th class="text-center"><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Members per group', 'statsmodule').'</span></th>
                  </tr>
                </thead>
                <tbody>';

            $groupSql = 'SELECT * FROM `'._DB_PREFIX_.'group_lang` WHERE `id_lang`='.(int) $this->context->language->id.' GROUP BY `id_group` ORDER BY `id_group`';
            if ($results = Db::getInstance()->ExecuteS($groupSql)) {
                foreach ($results as $grow) {
                    $this->html .= '<tr>';
                    $this->html .= '<td>'.$grow['id_group'].'</td>';
                    $this->html .= '<td>'.$grow['name'].'</td>';

                    $cagroupSql = 'SELECT SUM(o.total_paid_tax_excl / o.conversion_rate) as totalCA,
                        COUNT(o.id_order) as nbrCommandes
                        FROM '._DB_PREFIX_.'orders o
                        LEFT JOIN '._DB_PREFIX_.'customer c ON c.id_customer=o.id_customer
                        WHERE c.id_default_group='.$grow['id_group'].'
                        AND o.valid = 1
                        AND o.invoice_date BETWEEN '.ModuleGraph::getDateBetween().'
                        '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
                        GROUP BY '.$dateFromGinvoice;
                    if ($cagroup = Db::getInstance()->getrow($cagroupSql)) {
                        $this->html .= '<td class="text-right">'.Tools::displayPrice($cagroup['totalCA'], $currency).'</td>';
                        $this->html .= '<td class="text-right">'.Tools::displayPrice(($cagroup['totalCA'] / $cagroup['nbrCommandes']), $currency).'</td>';
                        $this->html .= '<td class="text-center">'.$cagroup['nbrCommandes'].'</td>';
                    } else {
                        $this->html .= '<td></td>';
                        $this->html .= '<td></td>';
                        $this->html .= '<td></td>';
                    }

                    $membersSql = 'SELECT COUNT(*) as nombread
                        FROM '._DB_PREFIX_.'customer WHERE id_default_group='.$grow['id_group'].'
                        AND date_add <= "'.$employee->stats_date_to.' 23:59:59"';

                    if ($members = Db::getInstance()->getrow($membersSql)) {
                        $this->html .= '<td class="text-center">'.$members['nombread'].'</td>';
                    }
                    $this->html .= '</tr>';
                }
            }
            $this->html .= '</table>';
        }

        return $this->html;
    }
}
