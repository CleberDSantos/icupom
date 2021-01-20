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

<!-- Block manufacturers module -->
<div id="manufacturers_block_left" class="block blockmanufacturer">
	<h4 class="title_block">{if $display_link_manufacturer}<a href="{$link->getPageLink('manufacturer')|escape:'html'}" title="{l s='Manufacturers' mod='blockmanufacturer'}">{/if}{l s='Manufacturers' mod='blockmanufacturer'}{if $display_link_manufacturer}</a>{/if}</h4>
	<div class="block_content">
{if $manufacturers}
	{if $text_list}
	<ul class="bullet">
	{foreach from=$manufacturers item=manufacturer name=manufacturer_list}
		{if $smarty.foreach.manufacturer_list.iteration <= $text_list_nb}
		<li class="{if $smarty.foreach.manufacturer_list.last}last_item{elseif $smarty.foreach.manufacturer_list.first}first_item{else}item{/if}"><a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'html'}" title="{l s='More about %s' sprintf=[$manufacturer.name] mod='blockmanufacturer'}">{$manufacturer.name|escape:'html':'UTF-8'}</a></li>
		{/if}
	{/foreach}
	</ul>
	{/if}
	{if $form_list}
		<form action="{$smarty.server.SCRIPT_NAME|escape:'html':'UTF-8'}" method="get">
			<p>
				<select id="manufacturer_list" onchange="autoUrl('manufacturer_list', '');">
					<option value="0">{l s='All manufacturers' mod='blockmanufacturer'}</option>
				{foreach from=$manufacturers item=manufacturer}
					<option value="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'html'}">{$manufacturer.name|escape:'html':'UTF-8'}</option>
				{/foreach}
				</select>
			</p>
		</form>
	{/if}
{else}
	<p>{l s='No manufacturer' mod='blockmanufacturer'}</p>
{/if}
	</div>
</div>
<!-- /Block manufacturers module -->
