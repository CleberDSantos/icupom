/**
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
 */

(function() {
  window.blocksocial = {
    clickHandler: function (e) {
      if (typeof window.ga === 'function') {
      e.preventDefault();
      $elem = $(e.target);
      var id;
      var depth = 0;
      do {
        id = $elem.parent().attr('id');
        depth += 1;
      } while (!id && depth < 10);

      var medium = '';

      switch (id) {
        case 'blocksocial_facebook':
          medium = 'Facebook';
          break;
        case 'blocksocial_twitter':
          medium = 'Twitter';
          break;
        case 'blocksocial_rss':
          medium = 'RSS';
          break;
        case 'blocksocial_youtube':
          medium = 'YouTube';
          break;
        case 'blocksocial_pinterest':
          medium = 'Pinterest';
          break;
        case 'blocksocial_vimeo':
          medium = 'Vimeo';
          break;
        case 'blocksocial_instagram':
          medium = 'Instagram';
          break;
        case 'blocksocial_vk':
          medium = 'VK';
          break;
        case 'blocksocial_linkedin':
          medium = 'LinkedIn';
          break;
        case 'blocksocial_wordpress':
          medium = 'Wordpress';
          break;
        case 'blocksocial_blogger':
          medium = 'Blogger';
          break;
        case 'blocksocial_tumblr':
          medium = 'Tumblr';
          break;
        case 'blocksocial_snapchat':
          medium = 'Snapchat';
          break;
        case 'blocksocial_reddit':
          medium = 'Reddit';
          break;
        case 'blocksocial_yelp':
          medium = 'Yelp';
          break;
        case 'blocksocial_medium':
          medium = 'Medium';
          break;
        default:
          medium = '';
          break;
      }

        if (medium) {
        window.ga('send', 'social', medium, 'send');
      }
      }

      return true;
    },
  };

  function initBlockSocial() {
    if (typeof $ === 'undefined') {
      setTimeout(initBlockSocial, 100);

      return;
    }
    $(document).ready(function () {
      $('#blocksocial_facebook').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_twitter').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_rss').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_youtube').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_pinterest').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_vimeo').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_instagram').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_vk').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_linkedin').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_wordpress').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_blogger').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_tumblr').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_snapchat').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_reddit').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_yelp').on('click', window.blocksocial.clickHandler);
      $('#blocksocial_medium').on('click', window.blocksocial.clickHandler);
    });
  }

  initBlockSocial();
}());
