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

<!-- Block user information module HEADER -->
<div id="header_user" {if $PS_CATALOG_MODE}class="header_user_catalog"{/if}>
	<ul id="header_nav">
		{if !$PS_CATALOG_MODE}
		<li id="shopping_cart">
			<a href="{$link->getPageLink($order_process, true)|escape:'html'}" title="{l s='View my shopping cart' mod='blockuserinfo'}" rel="nofollow">{l s='Cart' mod='blockuserinfo'}
			<span class="ajax_cart_quantity{if $cart_qties == 0} hidden{/if}">{$cart_qties}</span>
			<span class="ajax_cart_product_txt{if $cart_qties != 1} hidden{/if}">{l s='Product' mod='blockuserinfo'}</span>
			<span class="ajax_cart_product_txt_s{if $cart_qties < 2} hidden{/if}">{l s='Products' mod='blockuserinfo'}</span>
			<span class="ajax_cart_total{if $cart_qties == 0} hidden{/if}">
				{if $cart_qties > 0}
					{if $priceDisplay == 1}
						{assign var='blockuser_cart_flag' value='Cart::BOTH_WITHOUT_SHIPPING'|constant}
						{convertPrice price=$cart->getOrderTotal(false, $blockuser_cart_flag)}
					{else}
						{assign var='blockuser_cart_flag' value='Cart::BOTH_WITHOUT_SHIPPING'|constant}
						{convertPrice price=$cart->getOrderTotal(true, $blockuser_cart_flag)}
					{/if}
				{/if}
			</span>
			<span class="ajax_cart_no_product{if $cart_qties > 0} hidden{/if}">{l s='(empty)' mod='blockuserinfo'}</span>
			</a>
		</li>
		{/if}
		<li id="your_account"><a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='View my customer account' mod='blockuserinfo'}" rel="nofollow">{l s='Your Account' mod='blockuserinfo'}</a></li>
	</ul>
	<p id="header_user_info">
		{l s='Welcome' mod='blockuserinfo'}
		{if $logged}
			<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='View my customer account' mod='blockuserinfo'}" class="account" rel="nofollow"><span>{$cookie->customer_firstname} {$cookie->customer_lastname}</span></a>
			<a href="{$link->getPageLink('index', true, NULL, "mylogout")|escape:'html'}" title="{l s='Log me out' mod='blockuserinfo'}" class="logout" rel="nofollow">{l s='Sign out' mod='blockuserinfo'}</a>
		{else}
			<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Log in to your customer account' mod='blockuserinfo'}" class="login" rel="nofollow">{l s='Sign in' mod='blockuserinfo'}</a>
		{/if}
	</p>
</div>
<!-- /Block user information module HEADER -->
