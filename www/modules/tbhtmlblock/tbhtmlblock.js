/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

$(document).ready(function() {
  $('table.tableDnD').tableDnD({
    onDragStart: function(table, row) {
      originalOrder = $.tableDnD.serialize();
      reOrder = ':even';

      if (table.tBodies[0].rows[1]
          && $('#' + table.tBodies[0].rows[1].id).hasClass('alt_row')
      ) {
        reOrder = ':odd';
      }

      $('#'+table.id+ '#' + row.id).parent('tr').addClass('myDragClass');
    },
    dragHandle: 'dragHandle',
    onDragClass: 'myDragClass',
    onDrop: function(table, row) {
      var tableDrag = $('#' + table.id);
      tableDrag.find('tr').not('.nodrag').removeClass('alt_row');
      tableDrag.find('tr:not(".nodrag"):odd').addClass('alt_row');
    }
  });
})
