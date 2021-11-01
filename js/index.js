/*global $, dotclear */
'use strict';

$(function () {
  $('#improve_menu input[type=submit]').hide();
  $('#improve_menu #type').on('change', function () {this.form.submit();});
});