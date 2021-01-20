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

<meta property="og:type" content="product" />
<meta property="og:url" content="{$request}" />
<meta property="og:title" content="{$meta_title|escape:'html':'UTF-8'}" />
<meta property="og:site_name" content="{$shop_name}" />
<meta property="og:description" content="{$meta_description|escape:'html':'UTF-8'}" />
{if isset($link_rewrite) && isset($cover) && isset($cover.id_image)}
<meta property="og:image" content="{$link->getImageLink($link_rewrite, $cover.id_image, large_default)}" />
{/if}
{if isset($pretax_price)}
<meta property="product:pretax_price:amount" content="{$pretax_price}" />
{/if}
<meta property="product:pretax_price:currency" content="{$currency->iso_code}" />
{if isset($price)}
<meta property="product:price:amount" content="{$price}" />
{/if}
<meta property="product:price:currency" content="{$currency->iso_code}" />
{if isset($weight) && ($weight != 0)}
<meta property="product:weight:value" content="{$weight}" />
<meta property="product:weight:units" content="{$weight_unit}" />
{/if}
