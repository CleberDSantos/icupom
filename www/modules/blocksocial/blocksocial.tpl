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

<aside id="social_block">
	<h4 class="title_block">{l s='Follow us' mod='blocksocial'}</h4>
	<ul>
        {if $facebook_url != ''}<li class="facebook"><a id="blocksocial_facebook" class="_blank" href="{$facebook_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Facebook' mod='blocksocial'}</a></li>{/if}
        {if $twitter_url != ''}<li class="twitter"><a id="blocksocial_twitter" class="_blank" href="{$twitter_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Twitter' mod='blocksocial'}</a></li>{/if}
        {if $rss_url != ''}<li class="rss"><a id="blocksocial_rss" class="_blank" href="{$rss_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='RSS' mod='blocksocial'}</a></li>{/if}
        {if $youtube_url != ''}<li class="youtube"><a id="blocksocial_youtube" class="_blank" href="{$youtube_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='YouTube' mod='blocksocial'}</a></li>{/if}
        {if $google_plus_url != ''}<li class="google_plus"><a id="blocksocial_google_plus" class="_blank" href="{$google_plus_url|escape:html:'UTF-8'}" rel="publisher" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Google+' mod='blocksocial'}</a></li>{/if}
        {if $pinterest_url != ''}<li class="pinterest"><a id="blocksocial_pinterest" class="_blank" href="{$pinterest_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Pinterest' mod='blocksocial'}</a></li>{/if}
        {if $vimeo_url != ''}<li class="vimeo"><a id="blocksocial_vimeo" href="{$vimeo_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Vimeo' mod='blocksocial'}</a></li>{/if}
        {if $instagram_url != ''}<li class="instagram"><a id="blocksocial_instagram" class="_blank" href="{$instagram_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Instagram' mod='blocksocial'}</a></li>{/if}
        {if $vk_url != ''}<li class="vk"><a id="blocksocial_vk" class="_blank" href="{$vk_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='vk' mod='blocksocial'}</a></li>{/if}
        {if $linkedin_url != ''}<li class="linkedin"><a id="blocksocial_linkedin" class="_blank" href="{$linkedin_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Linkedin' mod='blocksocial'}</a></li>{/if}
        {if $wordpress_url != ''}<li class="wordpress"><a id="blocksocial_wordpress" class="_blank" href="{$wordpress_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Wordpress' mod='blocksocial'}</a></li>{/if}
        {if $amazon_url != ''}<li class="amazon"><a id="blocksocial_amazon" class="_blank" href="{$amazon_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Amazon Store' mod='blocksocial'}</a></li>{/if}
        {if $tumblr_url != ''}<li class="tumblr"><a id="blocksocial_tumblr" class="_blank" href="{$tumblr_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Tumblr' mod='blocksocial'}</a></li>{/if}
        {if $snapchat_url != ''}<li class="snapchat"><a id="blocksocial_snapchat" class="_blank" href="{$snapchat_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Snapchat' mod='blocksocial'}</a></li>{/if}
        {if $reddit_url != ''}<li class="reddit"><a id="blocksocial_reddit" class="_blank" href="{$reddit_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Reddit' mod='blocksocial'}</a></li>{/if}
        {if $yelp_url != ''}<li class="yelp"><a id="blocksocial_yelp" class="_blank" href="{$yelp_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Yelp' mod='blocksocial'}</a></li>{/if}
        {if $medium_url != ''}<li class="medium"><a id="blocksocial_medium" class="_blank" href="{$medium_url|escape:html:'UTF-8'}" onclick="window.blocksocial.clickHandler()" target="_blank">{l s='Medium' mod='blocksocial'}</a></li>{/if}
	</ul>
</aside>
