{**
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
 *}

<!-- Block payment logo module -->
<div id="paiement_logo_block_left" class="paiement_logo_block">
	<a href="{$link->getCMSLink($cms_payement_logo)|escape:'html'}">
		<img src="{$img_dir}logo_paiement_visa.jpg" alt="visa" width="33" height="21" />
		<img src="{$img_dir}logo_paiement_mastercard.jpg" alt="mastercard" width="32" height="21" />
		<img src="{$img_dir}logo_paiement_paypal.jpg" alt="paypal" width="61" height="21" />
	</a>
</div>
<!-- /Block payment logo module -->
