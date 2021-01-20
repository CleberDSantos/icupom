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

class SEKeywords extends StatsModule
{
    protected $type = 'Graph';
    protected $html = '';
    protected $query = '';
    protected $query2 = '';

    public function __construct()
    {
        $this->name = 'sekeywords';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->query = 'SELECT `keyword`, COUNT(TRIM(`keyword`)) AS occurences
				FROM `'._DB_PREFIX_.'sekeyword`
				WHERE '.(Configuration::get('SEK_FILTER_KW') == '' ? '1' : '`keyword` REGEXP \''.Configuration::get('SEK_FILTER_KW').'\'')
            .Shop::addSqlRestriction().
            ' AND `date_add` BETWEEN ';

        $this->query2 = 'GROUP BY TRIM(`keyword`)
				HAVING occurences > '.(int) Configuration::get('SEK_MIN_OCCURENCES').'
				ORDER BY occurences DESC';

        $this->displayName = Translate::getModuleTranslation('statsmodule', 'Search engine keywords', 'statsmodule');
        $this->description = Translate::getModuleTranslation('statsmodule', 'Displays which keywords have led visitors to your website.', 'statsmodule');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('top') || !$this->registerHook('AdminStatsModules'))
            return false;
        Configuration::updateValue('SEK_MIN_OCCURENCES', 1);
        Configuration::updateValue('SEK_FILTER_KW', '');

        return Db::getInstance()->execute('
		CREATE TABLE `'._DB_PREFIX_.'sekeyword` (
			id_sekeyword INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			keyword VARCHAR(256) NOT NULL,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_sekeyword)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8');
    }

    public function uninstall()
    {
        if (!parent::uninstall())
            return false;

        return (Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'sekeyword`'));
    }

    public function hookTop($params)
    {
        if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], Tools::getHttpHost(false, false) == 0))
            return;

        if ($keywords = $this->getKeywords($_SERVER['HTTP_REFERER']))
            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'sekeyword` (`keyword`, `date_add`, `id_shop`, `id_shop_group`)
										VALUES (\''.pSQL(Tools::strtolower(trim($keywords))).'\', NOW(), '.(int) $this->context->shop->id.', '.(int) $this->context->shop->id_shop_group.')');
    }

    public function hookAdminStatsModules()
    {
        if (Tools::isSubmit('submitSEK')) {
            Configuration::updateValue('SEK_FILTER_KW', trim(Tools::getValue('SEK_FILTER_KW')));
            Configuration::updateValue('SEK_MIN_OCCURENCES', (int) Tools::getValue('SEK_MIN_OCCURENCES'));
            Tools::redirectAdmin('index.php?tab=AdminStats&token='.Tools::getValue('token').'&module=');
        }

        if (Tools::getValue('export'))
            $this->csvExport(array('type' => 'pie'));
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query.ModuleGraph::getDateBetween().$this->query2);
        $total = count($result);
        $this->html = '
		<div class="panel-heading">'
            .$this->displayName.'
		</div>
		<h4>'.Translate::getModuleTranslation('statsmodule', 'Guide', 'statsmodule').'</h4>
		<div class="alert alert-warning">
			<h4>'.Translate::getModuleTranslation('statsmodule', 'Identify external search engine keywords', 'statsmodule').'</h4>
			<p>'
            .Translate::getModuleTranslation('statsmodule', 'This is one of the most common ways of finding a website through a search engine.', 'statsmodule').'&nbsp;'.
            Translate::getModuleTranslation('statsmodule', 'Identifying the most popular keywords entered by your new visitors allows you to see the products you should put in front if you want to achieve better visibility in search engines.', 'statsmodule').'
			</p>
			<p>&nbsp;</p>
			<h4>'.Translate::getModuleTranslation('statsmodule', 'How does it work?', 'statsmodule').'</h4>
			<p>'
            .Translate::getModuleTranslation('statsmodule', 'When a visitor comes to your website, the web server notes the URL of the site he/she comes from. This module then parses the URL, and if it finds a reference to a known search engine, it finds the keywords in it.', 'statsmodule').'<br>'.
            Translate::getModuleTranslation('statsmodule', 'This module can recognize all the search engines listed in PrestaShop\'s Stats/Search Engine page -- and you can add more!', 'statsmodule').'<br>'.
            Translate::getModuleTranslation('statsmodule', 'IMPORTANT NOTE: in September 2013, Google chose to encrypt its searches queries using SSL. This means all the referer-based tools in the World (including this one) cannot identify Google keywords anymore.', 'statsmodule').'
			</p>
		</div>
		<p>'.($total == 1 ? sprintf(Translate::getModuleTranslation('statsmodule', '%d keyword matches your query.', 'statsmodule'), $total) : sprintf(Translate::getModuleTranslation('statsmodule', '%d keywords match your query.', 'statsmodule'), $total)).'</p>';

        $form = '
		<form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post" class="form-horizontal">
			<div class="row row-margin-bottom">
				<label class="control-label col-lg-3">'.Translate::getModuleTranslation('statsmodule', 'Filter by keyword', 'statsmodule').'</label>
				<div class="col-lg-9">
					<input type="text" name="SEK_FILTER_KW" value="'.Tools::htmlentitiesUTF8(Configuration::get('SEK_FILTER_KW')).'" />
				</div>
			</div>
			<div class="row row-margin-bottom">
				<label class="control-label col-lg-3">'.Translate::getModuleTranslation('statsmodule', 'And min occurrences', 'statsmodule').'</label>
				<div class="col-lg-9">
					<input type="text" name="SEK_MIN_OCCURENCES" value="'.(int) Configuration::get('SEK_MIN_OCCURENCES').'" />
				</div>
			</div>
			<div class="row row-margin-bottom">
				<div class="col-lg-9 col-lg-offset-3">
					<button type="submit" class="btn btn-default" name="submitSEK">
						<i class="icon-ok"></i> '.Translate::getModuleTranslation('statsmodule', 'Apply', 'statsmodule').'
					</button>
				</div>
			</div>
		</form>';

        if ($result && $total) {
            $table = '
			<table class="table">
				<thead>
					<tr>
						<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Keywords', 'statsmodule').'</span></th>
						<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Occurrences', 'statsmodule').'</span></th>
					</tr>
				</thead>
				<tbody>';
            foreach ($result as $row) {
                $keyword =& $row['keyword'];
                $occurences =& $row['occurences'];
                $table .= '<tr><td>'.$keyword.'</td><td>'.$occurences.'</td></tr>';
            }
            $table .= '</tbody></table>';
            $this->html .= '<div>'.$this->engine($this->type, ['type' => 'pie']).'</div>
			<a class="btn btn-default" href="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'&export=1&exportType=language"><<i class="icon-cloud-upload"></i> '.Translate::getModuleTranslation('statsmodule', 'CSV Export', 'statsmodule').'</a>
			'.$form.'<br/>'.$table;
        } else
            $this->html .= $form.'<p><strong>'.Translate::getModuleTranslation('statsmodule', 'No keywords', 'statsmodule').'</strong></p>';

        return $this->html;
    }

    public function getKeywords($url)
    {
        if (!Validate::isAbsoluteUrl($url))
            return false;

        $parsed_url = parse_url($url);
        if (!isset($parsed_url['query']) && isset($parsed_url['fragment']))
            $parsed_url['query'] = $parsed_url['fragment'];

        if (!isset($parsed_url['query']))
            return false;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `server`, `getvar` FROM `'._DB_PREFIX_.'search_engine`');
        foreach ($result as $row) {
            $host =& $row['server'];
            $varname =& $row['getvar'];
            if (strstr($parsed_url['host'], $host)) {
                $k_array = array();
                preg_match('/[^a-zA-Z&]?'.$varname.'=.*\&'.'/U', $parsed_url['query'], $k_array);

                if (!isset($k_array[0]) || empty($k_array[0]))
                    preg_match('/[^a-zA-Z&]?'.$varname.'=.*$'.'/', $parsed_url['query'], $k_array);

                if (!isset($k_array[0]) || empty($k_array[0]))
                    return false;

                if ($k_array[0][0] == '&' && Tools::strlen($k_array[0]) == 1)
                    return false;

                return urldecode(str_replace('+', ' ', ltrim(Tools::substr(rtrim($k_array[0], '&'), Tools::strlen($varname) + 1), '=')));
            }
        }

        return false;
    }

    protected function getData($layers)
    {
        $this->_titles['main'] = Translate::getModuleTranslation('statsmodule', 'Top 10 keywords', 'statsmodule');
        $total_result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query.$this->getDate().$this->query2);
        $total = 0;
        $total2 = 0;
        foreach ($total_result as $total_row)
            $total += $total_row['occurences'];
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query.$this->getDate().$this->query2.' LIMIT 9');
        foreach ($result as $row) {
            $this->_legend[] = $row['keyword'];
            $this->_values[] = $row['occurences'];
            $total2 += $row['occurences'];
        }
        if ($total >= $total2) {
            $this->_legend[] = Translate::getModuleTranslation('statsmodule', 'Others', 'statsmodule');
            $this->_values[] = $total - $total2;
        }
    }
}
