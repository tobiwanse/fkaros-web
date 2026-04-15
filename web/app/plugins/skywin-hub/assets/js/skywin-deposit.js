"use strict";
jQuery(document).ready(function ($) {
  const quick_add_to_cart_btn = jQuery(".quick_add_to_cart_button");
  const add_to_cart_btn = jQuery( ".single_add_to_cart_button, .add_to_cart_button, .ajax_add_to_cart" );
  const search_account = jQuery('[name="search_account"]');
  const custom_amount = jQuery('[name="amount"]');
  const accountNo = jQuery('[name="accountNo"]');
  const emailAddress = jQuery('[name="emailAddress"]');
  const remember_me = jQuery('[name="remember-me"]');
  const min_amount = ajax_deposit_params.min_amount;
  const max_amount = ajax_deposit_params.max_amount;

  const defaultRememberMe = localStorage.getItem("rememberMe") || "";
  const defaultAccount = localStorage.getItem("search_account") || "";
  const defaultAccountNo = localStorage.getItem("accountNo") || "";
  const defaultEmailAddress = localStorage.getItem("emailAddress") || "";
  const defaultAmount = localStorage.getItem("amount") || min_amount;

  search_account.val(defaultAccount);
  accountNo.val(defaultAccountNo);
  emailAddress.val(defaultEmailAddress);
  custom_amount.val(defaultAmount);

  custom_amount.prop("disabled", true);
  add_to_cart_btn.prop("disabled", true);
  quick_add_to_cart_btn.prop("disabled", true);

  if (defaultRememberMe == "checked") {
    remember_me.prop("checked", true);
  }
  if ( accountNo.val() ) {
    custom_amount.prop("disabled", false);
    add_to_cart_btn.prop("disabled", false);
    quick_add_to_cart_btn.prop("disabled", false);
  }
  quick_add_to_cart_btn.on("click", function (event) {
    const amount = jQuery(this).data("amount") || custom_amount.val();
    custom_amount.val(amount);
  });
  custom_amount.on("change input", function (event) {
    event.preventDefault();
    const value = $(this).val();
    if (isNaN(value) || value == "") {
      add_to_cart_btn.prop("disabled", true);
      return;
    }
    if (min_amount && parseInt(value) < min_amount) {
      add_to_cart_btn.prop("disabled", true);
      custom_amount.addClass("error");
      return;
    }
    if (max_amount && parseInt(value) > max_amount) {
      custom_amount.addClass("error");
      add_to_cart_btn.prop("disabled", true);
      return;
    }
    custom_amount.removeClass("error");
    add_to_cart_btn.prop("disabled", false);
    set_add_to_cart_btn_text();
  });
  search_account.on("change input", function () {
    console.log("change input");
    custom_amount.prop("disabled", true);
    add_to_cart_btn.prop("disabled", true);
    quick_add_to_cart_btn.prop("disabled", true);
  });
  const search_member = function (term, callback) {
    return jQuery
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
        return response;
        //return callback(response);
      })
      .fail(function (result) { })
      .always(function (result) {
        search_account.removeClass("ui-autocomplete-loading");
      });
  };
  search_account.autocomplete({
    delay: 250,
    minLength: 2,
    source: function (request, suggests) {
      jQuery.ajax({
        method: "POST",
        dataType: "json",
        url: ajax_deposit_params.ajax_url,
        data: {
          _ajax_nonce: ajax_deposit_params._ajax_nonce,
          action: ajax_deposit_params.action,
          terms: request.term,
        },
      })
      .done(function (response) {
        suggests(response);
      })
      .fail(function (result) {
        console.log('error');
      })
      .always(function (result) {
        search_account.removeClass("ui-autocomplete-loading");
      });
    },
    change: function (event, ui) {
      if (ui.item !== null && ui.item != undefined) {
        accountNo.val(ui.item.value);
        emailAddress.val(ui.item.emailAddress);
        custom_amount.prop("disabled", false);
        add_to_cart_btn.prop("disabled", false);
        quick_add_to_cart_btn.prop("disabled", false);
      } else {
        accountNo.val("");
        emailAddress.val("");
        custom_amount.prop("disabled", true);
        add_to_cart_btn.prop("disabled", true);
        quick_add_to_cart_btn.prop("disabled", true);
        localStorage.clear();
      }
    },
    select: function (event, ui) {
      event.preventDefault();
      if (ui.item == undefined) {
        accountNo.val("");
        emailAddress.val("");
        custom_amount.prop("disabled", true);
        add_to_cart_btn.prop("disabled", true);
        search_account.addClass("error");
        quick_add_to_cart_btn.prop("disabled", true);
      } else {
        accountNo.val(ui.item.value);
        emailAddress.val(ui.item.emailAddress);
        search_account.addClass("success");
        search_account.removeClass("error");
        custom_amount.prop("disabled", false);
        if (custom_amount.val() >= min_amount) {
          add_to_cart_btn.prop("disabled", false);
        }
        quick_add_to_cart_btn.prop("disabled", false);
      }
    },
    focus: function (event, ui) { },
    close: function (event, ui) { },
    response: function (event, ui) {
      if (typeof undefined == ui.content || !ui.content.length) {
        var noResult = { value: 0, label: "No results found" };
        ui.content.push(noResult);
        custom_amount.prop("disabled", true);
        add_to_cart_btn.prop("disabled", true);
        quick_add_to_cart_btn.prop("disabled", true);
      }
    },
  });
});
