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

class BlockMyAccount extends Module
{
	public function __construct()
	{
		$this->name = 'blockmyaccount';
		$this->tab = 'front_office_features';
		$this->version = '2.1.1';
		$this->author = 'thirty bees';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Block My Account');
		$this->description = $this->l('Displays a block with links relative to a user\'s account.');
		$this->tb_versions_compliancy = '> 1.0.0';
		$this->tb_min_version = '1.0.0';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
	}

	public function install()
	{
		if (!$this->addMyAccountBlockHook()
			|| !parent::install()
			|| !$this->registerHook('displayLeftColumn')
			|| !$this->registerHook('displayHeader')
			|| !$this->registerHook('actionModuleRegisterHookAfter')
			|| !$this->registerHook('actionModuleUnRegisterHookAfter'))
			return false;
		return true;
	}

	public function uninstall()
	{
		return (parent::uninstall() && $this->removeMyAccountBlockHook());
	}

	public function hookActionModuleUnRegisterHookAfter($params)
	{
		return $this->hookActionModuleRegisterHookAfter($params);
	}

	public function hookActionModuleRegisterHookAfter($params)
	{
		if ($params['hook_name'] == 'displayMyAccountBlock')
			$this->_clearCache('blockmyaccount.tpl');
	}

	public function hookDisplayLeftColumn($params)
	{
		$this->smarty->assign(array(
			'voucherAllowed' => CartRule::isFeatureActive(),
			'returnAllowed' => (int)Configuration::get('PS_ORDER_RETURN'),
			'HOOK_BLOCK_MY_ACCOUNT' => Hook::exec('displayMyAccountBlock'),
		));
		return $this->display(__FILE__, $this->name.'.tpl');
	}

	public function hookDisplayRightColumn($params)
	{
		return $this->hookDisplayLeftColumn($params);
	}

	public function hookDisplayFooter($params)
	{
		return $this->hookDisplayLeftColumn($params);
	}

	private function addMyAccountBlockHook()
	{
		return Db::getInstance()->execute('INSERT IGNORE INTO `'._DB_PREFIX_.'hook` (`name`, `title`, `description`, `position`) VALUES (\'displayMyAccountBlock\', \'My account block\', \'Display extra informations inside the "my account" block\', 1)');
	}

	private function removeMyAccountBlockHook()
	{
		return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'hook` WHERE `name` = \'displayMyAccountBlock\'');
	}

	public function hookDisplayHeader($params)
	{
		$this->context->controller->addCSS(($this->_path).'blockmyaccount.css', 'all');
	}
}
