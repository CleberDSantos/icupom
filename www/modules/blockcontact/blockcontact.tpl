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

<div id="contact_block" class="block">
	<h4 class="title_block">{l s='Contact us' mod='blockcontact'}</h4>
	<div class="block_content clearfix">
			<p>{l s='Our support hotline is available 24/7.' mod='blockcontact'}</p>
			{if $telnumber != ''}<p class="tel"><span class="label">{l s='Phone:' mod='blockcontact'}</span><span itemprop="telephone"><a href="tel:{$telnumber|escape:'html':'UTF-8'}">{$telnumber|escape:'html':'UTF-8'}</a></span></p>{/if}
			{if $email != ''}<a href="mailto:{$email|escape:'html':'UTF-8'}" title="{l s='Contact our expert support team!' mod='blockcontact'}">{l s='Contact our expert support team!' mod='blockcontact'}</a>{/if}
	</div>
</div>
