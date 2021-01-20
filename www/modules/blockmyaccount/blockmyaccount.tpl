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

<div class="block myaccount">
    <h4 class="title_block"><a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Your account' mod='blockmyaccount'}">{l s='Your account' mod='blockmyaccount'}</a></h4>
    <div class="block_content">
        <ul class="bullet">
            <li><a href="{$link->getPageLink('history', true)|escape:'html'}" title="">{l s='Orders' mod='blockmyaccount'}</a></li>
            {if $returnAllowed}<li><a href="{$link->getPageLink('order-follow', true)|escape:'html'}" title="{l s='Merchandise returns' mod='blockmyaccount'}">{l s='Merchandise returns' mod='blockmyaccount'}</a></li>{/if}
            <li><a href="{$link->getPageLink('order-slip', true)|escape:'html'}" title="{l s='Credit slips' mod='blockmyaccount'}">{l s='Credit slips' mod='blockmyaccount'}</a></li>
            <li><a href="{$link->getPageLink('addresses', true)|escape:'html'}" title="{l s='Addresses' mod='blockmyaccount'}">{l s='Addresses' mod='blockmyaccount'}</a></li>
            <li><a href="{$link->getPageLink('identity', true)|escape:'html'}" title="{l s='Personal info' mod='blockmyaccount'}">{l s='Personal info' mod='blockmyaccount'}</a></li>
            {if $voucherAllowed}<li><a href="{$link->getPageLink('discount', true)|escape:'html'}" title="{l s='Vouchers' mod='blockmyaccount'}">{l s='Vouchers' mod='blockmyaccount'}</a></li>{/if}
            {$HOOK_BLOCK_MY_ACCOUNT}
        </ul>
    </div>
</div>
