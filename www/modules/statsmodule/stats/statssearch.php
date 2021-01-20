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

class StatsSearch extends StatsModule
{
	protected $type = 'Graph';
	protected $html = '';
	protected $query = '';
	protected $query_group_by = '';

	public function __construct()
	{
		$this->name = 'statssearch';
		$this->tab = 'analytics_stats';
		$this->version = '2.0.0';
		$this->author = 'thirty bees';
		$this->need_instance = 0;

		parent::__construct();

		$this->query = 'SELECT `keywords`, COUNT(TRIM(`keywords`)) as occurences, MAX(results) as total
				FROM `'._DB_PREFIX_.'statssearch`
				WHERE 1
					'.Shop::addSqlRestriction().'
					AND `date_add` BETWEEN ';

		$this->query_group_by = 'GROUP BY `keywords`
				HAVING occurences > 1
				ORDER BY occurences DESC';

		$this->displayName = Translate::getModuleTranslation('statsmodule', 'Shop search', 'statsmodule');
		$this->description = Translate::getModuleTranslation('statsmodule', 'Adds a tab to the Stats dashboard, showing which keywords have been searched by your store\'s visitors.', 'statsmodule');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('search') || !$this->registerHook('AdminStatsModules'))
			return false;

		return Db::getInstance()->execute('
		CREATE TABLE `'._DB_PREFIX_.'statssearch` (
			id_statssearch INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
		  	id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			keywords VARCHAR(255) NOT NULL,
			results INT(6) NOT NULL DEFAULT 0,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_statssearch)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8');
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;

		return (Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'statssearch`'));
	}

	/**
	 * Insert keywords in statssearch table when a search is launched on FO
	 */
	public function hookSearch($params)
	{
		$sql = 'INSERT INTO `'._DB_PREFIX_.'statssearch` (`id_shop`, `id_shop_group`, `keywords`, `results`, `date_add`)
				VALUES ('.(int)$this->context->shop->id.', '.(int)$this->context->shop->id_shop_group.', \''.pSQL($params['expr']).'\', '.(int)$params['total'].', NOW())';
		Db::getInstance()->execute($sql);
	}

	public function hookAdminStatsModules()
	{
		if (Tools::getValue('export'))
			$this->csvExport(array('type' => 'pie'));

		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query.ModuleGraph::getDateBetween().$this->query_group_by);
		$this->html = '
		<div class="panel-heading">
			'.$this->displayName.'
		</div>';
		$table = '
		<table class="table">
			<thead>
				<tr>
					<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Keywords', 'statsmodule').'</span></th>
					<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Occurrences', 'statsmodule').'</span></th>
					<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Results', 'statsmodule').'</span></th>
				</tr>
			</thead>
			<tbody>';

		foreach ($result as $row)
		{
			if (Tools::strlen($row['keywords']) >= Configuration::get('PS_SEARCH_MINWORDLEN'))
				$table .= '<tr>
					<td>'.$row['keywords'].'</td>
					<td>'.$row['occurences'].'</td>
					<td>'.$row['total'].'</td>
				</tr>';
		}
		$table .= '
			</tbody>
		</table>';

		if (count($result))
			$this->html .= '<div>'.$this->engine($this->type, ['type' => 'pie']).'</div>
							<a class="btn btn-default" href="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'&export=1">
								<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'
							</a>'.$table;
		else
			$this->html .= '<p>'.Translate::getModuleTranslation('statsmodule', 'Cannot find any keywords that have been searched for more than once.', 'statsmodule').'</p>';

		return $this->html;
	}

	protected function getData($layers)
	{
		$this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Top 10 keywords', 'statsmodule');
		$total_result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query.$this->getDate().$this->query_group_by);
		$total = 0;
		$total2 = 0;
		foreach ($total_result as $total_row)
			$total += $total_row['occurences'];
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query.$this->getDate().$this->query_group_by.' LIMIT 9');
		foreach ($result as $row)
		{
			if (!$row['occurences'])
				continue;
			$this->_legend[] = $row['keywords'];
			$this->_values[] = $row['occurences'];
			$total2 += $row['occurences'];
		}
		if ($total > $total2)
		{
			$this->_legend[] = Translate::getModuleTranslation('statsmodule', 'Others', 'statsmodule');
			$this->_values[] = $total - $total2;
		}
	}
}
