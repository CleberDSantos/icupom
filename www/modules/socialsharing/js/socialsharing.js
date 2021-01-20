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

(function () {
  function initSocialSharing() {
    if (typeof $ === 'undefined') {
      setTimeout(initSocialSharing, 100);

      return;
    }

    $(document).ready(function () {
      $('button.social-sharing').on('click', function () {
        type = $(this).attr('data-type');
        if (type.length) {
          switch (type) {
            case 'twitter':
              window.open('https://twitter.com/intent/tweet?text=' + sharing_name + ' ' + encodeURIComponent(sharing_url), 'sharertwt', 'toolbar=0,status=0,width=640,height=445');
              if (typeof window.ga === 'function') {
                window.ga('send', 'social', 'Twitter', 'share', sharing_name);
              }

              break;
            case 'facebook':
              window.open('https://www.facebook.com/sharer.php?u=' + sharing_url, 'sharer', 'toolbar=0,status=0,width=660,height=445');
              if (typeof window.ga === 'function') {
                window.ga('send', 'social', 'Facebook', 'share', sharing_name);
              }

              break;
            case 'google-plus':
              window.open('https://plus.google.com/share?url=' + sharing_url, 'sharer', 'toolbar=0,status=0,width=660,height=445');
              // GA doesn't need to be added as GA will automatically track G+ social interactions
              break;
            case 'pinterest':
              var img_url = sharing_img;
              var $bigpic = $('#bigpic');
              if (typeof $bigpic.attr('src') !== 'undefined' && $bigpic.attr('src') !== '') {
                img_url = $bigpic.attr('src');
              }
              window.open('https://www.pinterest.com/pin/create/button/?media=' + img_url + '&url=' + sharing_url, 'sharerpinterest', 'toolbar=0,status=0,width=660,height=445');
              if (typeof window.ga === 'function') {
                ga('send', 'social', 'Pinterest', 'share', sharing_name);
              }
              break;
          }
        }
      });
    });
  }

  initSocialSharing();
}());
