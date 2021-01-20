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

<!-- MODULE Block contact infos -->
<div id="block_contact_infos">
	<h4 class="title_block">{l s='Contact us' mod='blockcontactinfos'}</h4>
	<ul>
		{if $blockcontactinfos_company != ''}<li><strong>{$blockcontactinfos_company|escape:'html':'UTF-8'}</strong></li>{/if}
		{if $blockcontactinfos_address != ''}<li>{$blockcontactinfos_address|escape:'html':'UTF-8'|nl2br}</li>{/if}
		{if $blockcontactinfos_phone != ''}<li>{l s='Tel' mod='blockcontactinfos'} {$blockcontactinfos_phone|escape:'html':'UTF-8'}</li>{/if}
		{if $blockcontactinfos_email != ''}<li>{l s='Email:' mod='blockcontactinfos'} {mailto address=$blockcontactinfos_email|escape:'html':'UTF-8' encode="hex"}</li>{/if}
	</ul>
</div>
<!-- /MODULE Block contact infos -->
