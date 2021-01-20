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

<div id="htmlcontent" class="panel">
    <div class="panel-heading">{$htmlcontent.info.name|escape:'htmlall':'UTF-8'} (v.{$htmlcontent.info.version|escape:'htmlall':'UTF-8'})</div>
    {if isset($error) && $error}
        {include file="{$htmlcontent.admin_tpl_path|escape:'htmlall':'UTF-8'}messages.tpl" id="main" text=$error class='error'}
    {/if}
    {if isset($confirmation) && $confirmation}
        {include file="{$htmlcontent.admin_tpl_path|escape:'htmlall':'UTF-8'}messages.tpl" id="main" text=$confirmation class='conf'}
    {/if}
    <!-- New -->
    {include file="{$htmlcontent.admin_tpl_path|escape:'htmlall':'UTF-8'}new.tpl"}
    <!-- Slides -->
    {include file="{$htmlcontent.admin_tpl_path|escape:'htmlall':'UTF-8'}items.tpl"}
</div>
