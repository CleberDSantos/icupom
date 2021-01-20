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

$(document).ready(function () {
  // show new item panel
  $('.button-new-item').on('click', function () {
    $('.new-item').find('.item-container').slideToggle();
  });
  // cancel new item
  $('.button-new-item-cancel').on('click', function () {
    $('.new-item').find('.item-container').slideToggle();
  });
  // show item content edit panel
  $('.button-edit').on('click', function (e) {
    e.preventDefault();
    var $itemContainer = $(this).closest('.item');
    $itemContainer.find('.item-container').slideToggle();
    $(this).find('.button-edit-edit').toggleClass('hide');
    $(this).find('.button-edit-close').toggleClass('hide');
  });
  // cancel item edit
  $('.button-item-edit-cancel').on('click', function () {
    $(this).closest('form').find('.button-edit .button-edit-edit').toggleClass('hide');
    $(this).closest('form').find('.button-edit .button-edit-close').toggleClass('hide');
    $(this).closest('form').find('.item-container').slideToggle();
  });

  // set language for new item
  $('.new-lang-flag').on('click', function () {
    var idLang = (this.id).substr(5);
    $('.new-lang-flag').each(function () {
      $(this).removeClass('active');
    });
    $(this).addClass('active');
    $('#lang-id').val(idLang);
  });

  $('#live_conf_on').click(function () {
    if ($(this).is(':checked')) {
      $('#live_conf_button').attr('disabled', false);
    }
  });

  $('#live_conf_off').click(function () {
    if ($(this).is(':checked')) {
      $('#live_conf_button').attr('disabled', 'disabled');
    }
  });

  if ($('#live_conf_on').is(':checked')) {
    $('#live_conf_button').attr('disabled', false);
  } else {
    $('#live_conf_button').attr('disabled', 'disabled');
  }

});

$(function () {
  $(".list-unstyled").sortable().bind('sortupdate', function () {
    var test = $(this).sortable('toArray');
    var h4_title = $(this).prev('h4').html();
    $.ajax({
      type: 'POST',
      url: theme_url + '&configure=themeconfigurator&ajax',
      headers: {
        'cache-control': 'no-cache'
      },
      dataType: 'json',
      data: {
        action: 'updateposition',
        item: test,
        title: h4_title,
      },
      success: function (msg) {
        if (msg.error) {
          showErrorMessage(msg.error);
          return;
        }
        showSuccessMessage(msg.success);
      }
    });
  });
});
