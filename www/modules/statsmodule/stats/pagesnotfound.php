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

class PagesNotFound extends StatsModule
{
    protected $html = '';

    public function __construct()
    {
        $this->name = 'pagesnotfound';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = Translate::getModuleTranslation('statsmodule', 'Pages not found', 'statsmodule');
        $this->description = Translate::getModuleTranslation('statsmodule', 'Adds a tab to the Stats dashboard, showing the pages requested by your visitors that have not been found.', 'statsmodule');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('top') || !$this->registerHook('AdminStatsModules')) {
            return false;
        }

        return Db::getInstance()->execute(
            'CREATE TABLE `'._DB_PREFIX_.'pagenotfound` (
			id_pagenotfound INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			request_uri VARCHAR(256) NOT NULL,
			http_referer VARCHAR(256) NOT NULL,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_pagenotfound),
			INDEX (`date_add`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }

    public function uninstall()
    {
        return (parent::uninstall() && Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'pagenotfound`'));
    }

    private function getPages()
    {
        $sql = 'SELECT http_referer, request_uri, COUNT(*) AS nb
				FROM `'._DB_PREFIX_.'pagenotfound`
				WHERE date_add BETWEEN '.ModuleGraph::getDateBetween()
            .Shop::addSqlRestriction().
            'GROUP BY http_referer, request_uri';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        $pages = array();
        foreach ($result as $row) {
            $row['http_referer'] = parse_url($row['http_referer'], PHP_URL_HOST).parse_url($row['http_referer'], PHP_URL_PATH);
            if (!isset($row['http_referer']) || empty($row['http_referer']))
                $row['http_referer'] = '--';
            if (!isset($pages[$row['request_uri']]))
                $pages[$row['request_uri']] = array('nb' => 0);
            $pages[$row['request_uri']][$row['http_referer']] = $row['nb'];
            $pages[$row['request_uri']]['nb'] += $row['nb'];
        }
        uasort($pages, 'pnfSort');

        return $pages;
    }

    public function hookAdminStatsModules()
    {
        if (Tools::isSubmit('submitTruncatePNF')) {
            Db::getInstance()->execute('TRUNCATE `'._DB_PREFIX_.'pagenotfound`');
            $this->html .= '<div class="alert alert-warning"> '.Translate::getModuleTranslation('statsmodule', 'The "pages not found" cache has been emptied.', 'statsmodule').'</div>';
        } else if (Tools::isSubmit('submitDeletePNF')) {
            Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'pagenotfound`
				WHERE date_add BETWEEN '.ModuleGraph::getDateBetween()
            );
            $this->html .= '<div class="alert alert-warning"> '.Translate::getModuleTranslation('statsmodule', 'The "pages not found" cache has been deleted.', 'statsmodule').'</div>';
        }

        $this->html .= '
			<div class="panel-heading">
				'.$this->displayName.'
			</div>
			<h4>'.Translate::getModuleTranslation('statsmodule', 'Guide', 'statsmodule').'</h4>
			<div class="alert alert-warning">
				<h4>'.Translate::getModuleTranslation('statsmodule', '404 errors', 'statsmodule').'</h4>
				<p>'
            .Translate::getModuleTranslation('statsmodule', 'A 404 error is an HTTP error code which means that the file requested by the user cannot be found. In your case it means that one of your visitors entered a wrong URL in the address bar, or that you or another website has a dead link. When possible, the referrer is shown so you can find the page/site which contains the dead link. If not, it generally means that it is a direct access, so someone may have bookmarked a link which doesn\'t exist anymore.', 'statsmodule').'
				</p>
				<p>&nbsp;</p>
				<h4>'.Translate::getModuleTranslation('statsmodule', 'How to catch these errors?', 'statsmodule').'</h4>
				<p>'
            .sprintf(Translate::getModuleTranslation('statsmodule', 'If your webhost supports .htaccess files, you can create one in the root directory of PrestaShop and insert the following line inside: "%s".', 'statsmodule'), 'ErrorDocument 404 '.__PS_BASE_URI__.'404.php').'<br />'.
            sprintf(Translate::getModuleTranslation('statsmodule', 'A user requesting a page which doesn\'t exist will be redirected to the following page: %s. This module logs access to this page.', 'statsmodule'), __PS_BASE_URI__.'404.php').'
				</p>
			</div>';
        if (!file_exists($this->_normalizeDirectory(_PS_ROOT_DIR_).'.htaccess'))
            $this->html .= '<div class="alert alert-warning">'.Translate::getModuleTranslation('statsmodule', 'You must use a .htaccess file to redirect 404 errors to the "404.php" page.', 'statsmodule').'</div>';

        $pages = $this->getPages();
        if (count($pages)) {
            $this->html .= '
			<table class="table">
				<thead>
					<tr>
						<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Page', 'statsmodule').'</span></th>
						<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Referrer', 'statsmodule').'</span></th>
						<th><span class="title_box active">'.Translate::getModuleTranslation('statsmodule', 'Counter', 'statsmodule').'</span></th>
					</tr>
				</thead>
				<tbody>';
            foreach ($pages as $ru => $hrs)
                foreach ($hrs as $hr => $counter)
                    if ($hr != 'nb')
                        $this->html .= '
						<tr>
							<td><a href="'.$ru.'-admin404">'.wordwrap($ru, 30, '<br />', true).'</a></td>
							<td><a href="'.Tools::getProtocol().$hr.'">'.wordwrap($hr, 40, '<br />', true).'</a></td>
							<td>'.$counter.'</td>
						</tr>';
            $this->html .= '
				</tbody>
			</table>';
        } else
            $this->html .= '<div class="alert alert-warning"> '.Translate::getModuleTranslation('statsmodule', 'No "page not found" issue registered for now.', 'statsmodule').'</div>';

        if (count($pages))
            $this->html .= '
				<h4>'.Translate::getModuleTranslation('statsmodule', 'Empty database', 'statsmodule').'</h4>
				<form action="'.Tools::htmlEntitiesUtf8($_SERVER['REQUEST_URI']).'" method="post">
					<button type="submit" class="btn btn-default" name="submitDeletePNF">
						<i class="icon-remove"></i> '.Translate::getModuleTranslation('statsmodule', 'Empty ALL "pages not found" notices for this period', 'statsmodule').'
					</button>
					<button type="submit" class="btn btn-default" name="submitTruncatePNF">
						<i class="icon-remove"></i> '.Translate::getModuleTranslation('statsmodule', 'Empty ALL "pages not found" notices', 'statsmodule').'
					</button>
				</form>';

        return $this->html;
    }

    public function hookTop($params)
    {
        if (!Validate::isUrl($request_uri = $_SERVER['REQUEST_URI'])
            || strpos($_SERVER['REQUEST_URI'], '-admin404') !== false) {
            return;
        }

        if (get_class(Context::getContext()->controller) == 'PageNotFoundController') {
            $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            if (empty($http_referer) || Validate::isAbsoluteUrl($http_referer)) {
                Db::getInstance()->execute(
                    '
										INSERT INTO `'._DB_PREFIX_.'pagenotfound` (`request_uri`, `http_referer`, `date_add`, `id_shop`, `id_shop_group`)
					VALUES (\''.pSQL($request_uri).'\', \''.pSQL($http_referer).'\', NOW(), '.(int) $this->context->shop->id.', '.(int) $this->context->shop->id_shop_group.')
				'
                );
            }
        }
    }

    private function _normalizeDirectory($directory)
    {
        $last = $directory[strlen($directory) - 1];

        if (in_array($last, array('/', '\\'))) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
            return $directory;
        }

        $directory .= DIRECTORY_SEPARATOR;
        return $directory;
    }
}

function pnfSort($a, $b)
{
    if ($a['nb'] == $b['nb'])
        return 0;

    return ($a['nb'] > $b['nb']) ? -1 : 1;
}
