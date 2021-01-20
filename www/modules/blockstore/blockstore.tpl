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

<!-- Block stores module -->
<div id="stores_block_left" class="block">
	<h4 class="title_block">
		<a href="{$link->getPageLink('stores')|escape:'html'}" title="{l s='Our store(s)!' mod='blockstore'}">
			{l s='Our store(s)!' mod='blockstore'}
		</a>
	</h4>
	<div class="block_content blockstore">
		<p class="store_image">
			<a href="{$link->getPageLink('stores')|escape:'html'}" title="{l s='Our store(s)!' mod='blockstore'}">
				<img src="{$link->getMediaLink("`$module_dir``$store_img|escape:'htmlall':'UTF-8'`")}" alt="{l s='Our store(s)!' mod='blockstore'}" width="174" height="115" />
			</a>
		</p>
		{if !empty($store_text)}
        <p class="store-description">
        	{$store_text}
        </p>
        {/if}
		<p>
			<a href="{$link->getPageLink('stores')|escape:'html'}" title="{l s='Our store(s)!' mod='blockstore'}">
				&raquo; {l s='Discover our store(s)!' mod='blockstore'}
			</a>
		</p>
	</div>
</div>
<!-- /Block stores module -->
