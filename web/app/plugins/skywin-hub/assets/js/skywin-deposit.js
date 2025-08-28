"use strict";
jQuery(document).ready(function ($) {
  const add_to_cart_btn = jQuery('[name="add-to-cart"]');
  const amount_input = jQuery('[name="deposit_amount"]');
  const search_input = jQuery('[name="skywin_account"]');
  const accountNo = jQuery('[name="skywin_accountNo"]');

  add_to_cart_btn.prop("disabled", true);

  const currency_symbol = jQuery(".woocommerce-Price-currencySymbol").first().text();

  jQuery(document.body).on("change input", "#deposit_amount", function () {
    let new_value = amount_input.val();
    if (!new_value) {
      new_value = "0,00";
    }
    const price_amount = jQuery(".woocommerce-Price-amount.amount bdi");

    price_amount.text(new_value + " " + currency_symbol);
  });

  const get = function (params, callback) {
    jQuery
      .ajax({
        method: "POST",
        dataType: "json",
        url: params.ajax_url,
        data: {
          _ajax_nonce: params._ajax_nonce,
          action: params.action,
        },
      })
      .done(function (response) {
        callback(response);
      })
      .fail(function (result) {
        console.log(params.action + " Not connected");
        console.log("Error", result);
      })
      .always(function (result) {});
  };

  const search_member = function (term, callback) {
    jQuery
      .ajax({
        method: "POST",
        dataType: "json",
        url: ajax_deposit_params.ajax_url,
        data: {
          _ajax_nonce: ajax_deposit_params._ajax_nonce,
          action: ajax_deposit_params.action,
          terms: term,
        },
      })
      .done(function (response) {
        console.log(response);
        callback(response);
      })
      .fail(function (result) {})
      .always(function (result) {});
  };

  jQuery("#skywin_account").autocomplete({
    delay: 600,
    minLength: 1,
    autoFocus: false,
    source: function (request, suggests) {
      if (!request.term.replace(/\s/g, "").length) {
        return suggests([]);
      }
      search_member(request.term, function (response) {
        suggests(response);
      });
    },
    change: function (event, ui) {
      if (ui.item !== null) {
        jQuery('input[name="skywin_accountNo"]').val(ui.item.data.AccountNo);
      }
      if (ui.item === null) {
        jQuery('input[name="skywin_accountNo"]').val("");
        add_to_cart_btn.prop("disabled", true);
      }
    },
    select: function (event, ui) {
      if (ui.item.value == 0) {
        event.preventDefault();
        add_to_cart_btn.prop("disabled", true);
      } else {
        jQuery('input[name="skywin_accountNo"]').val(ui.item.data.AccountNo);
        add_to_cart_btn.prop("disabled", false);
      }
    },
    focus: function (event, ui) {},
    close: function (event, ui) {},
    response: function (event, ui) {
      if (!ui.content.length) {
        var noResult = { value: 0, label: "No results found" };
        ui.content.push(noResult);
        add_to_cart_btn.prop("disabled", true);
      }
    },
  });
});