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

class BlockCurrencies extends Module
{
	public function __construct()
	{
		$this->name = 'blockcurrencies';
		$this->tab = 'front_office_features';
		$this->version = '1.0.2';
		$this->author = 'thirty bees';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Block Currencies');
		$this->description = $this->l('Adds a block allowing customers to choose their preferred shopping currency.');
		$this->tb_versions_compliancy = '> 1.0.0';
		$this->tb_min_version = '1.0.0';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
	}

	public function install()
	{
		return parent::install() && $this->registerHook('displayNav') && $this->registerHook('displayHeader');
	}

	protected function _prepareHook($params)
	{
		if (Configuration::get('PS_CATALOG_MODE'))
			return false;

		if (!Currency::isMultiCurrencyActivated())
			return false;

		$this->smarty->assign('blockcurrencies_sign', $this->context->currency->sign);

		return true;
	}

	/**
	* Returns module content for header
	*
	* @param array $params Parameters
	* @return string Content
	*/
	public function hookDisplayTop($params)
	{
		if ($this->_prepareHook($params))
			return $this->display(__FILE__, 'blockcurrencies.tpl');
	}

	public function hookDisplayNav($params)
	{
			return $this->hookDisplayTop($params);
	}

	public function hookDisplayHeader($params)
	{
		if (Configuration::get('PS_CATALOG_MODE'))
			return;
		$this->context->controller->addCSS(($this->_path).'blockcurrencies.css', 'all');
	}
}
