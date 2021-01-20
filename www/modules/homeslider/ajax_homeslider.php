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

include_once('../../config/config.inc.php');
include_once('../../init.php');
include_once('homeslider.php');

$home_slider = new HomeSlider();
$slides = array();

if (!Tools::isSubmit('secure_key') || Tools::getValue('secure_key') != $home_slider->secure_key || !Tools::getValue('action'))
	die(1);

if (Tools::getValue('action') == 'updateSlidesPosition' && Tools::getValue('slides'))
{
	$slides = Tools::getValue('slides');

	foreach ($slides as $position => $id_slide)
		$res = Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'homeslider_slides` SET `position` = '.(int)$position.'
			WHERE `id_homeslider_slides` = '.(int)$id_slide
		);

	$home_slider->clearCache();
}
