"use strict";
jQuery(document).ready(function ($) {
  const form = jQuery(".cart");
  const add_to_cart = jQuery(
    ".quick_add_to_cart_button, .single_add_to_cart_button, .add_to_cart_button"
  );
  const quick_add_to_cart_btn = jQuery(".quick_add_to_cart_button");
  const add_to_cart_btn = jQuery(
    ".single_add_to_cart_button, .add_to_cart_button, .ajax_add_to_cart"
  );
  const search_account = jQuery('[name="search_account"]');
  const custom_amount = jQuery('[name="amount"]');
  const internalNo = jQuery('[name="internalNo"]');
  const accountNo = jQuery('[name="accountNo"]');
  const productId = jQuery('[name="product_id"]');
  const quantity = jQuery('[name="quantity"]');
  const remember_me = jQuery('[name="remember-me"]');
  const checkout_modal = jQuery(".skywin_hub-checkout-modal");
  const close_modal_btn = jQuery(".skywin_hub-close-checkout-modal");
  const open_modal = jQuery(".skywin_hub-checkout-modal");

  const currency = ajax_deposit_params.currency;
  const min_amount = ajax_deposit_params.min_amount;
  const max_amount = ajax_deposit_params.max_amount;
  const add_to_cart_text = ajax_deposit_params.add_to_cart_text;

  const defaultRememberMe = localStorage.getItem("rememberMe") || "";
  const defaultAccount = localStorage.getItem("search_account") || "";
  const defaultAccountNo = localStorage.getItem("accountNo") || "";
  const defaultAmount = localStorage.getItem("amount") || min_amount;

  search_account.val(defaultAccount);
  accountNo.val(defaultAccountNo);
  custom_amount.val(defaultAmount);

  custom_amount.prop("disabled", true);
  add_to_cart_btn.prop("disabled", true);
  quick_add_to_cart_btn.prop("disabled", true);

  if (defaultRememberMe == "checked") {
    remember_me.prop("checked", true);
  }
  if (accountNo.val()) {
    custom_amount.prop("disabled", false);
    add_to_cart_btn.prop("disabled", false);
    quick_add_to_cart_btn.prop("disabled", false);
  }
  open_modal.on("click", function (event) {
    event.preventDefault();
    checkout_modal.css("visibility", "hidden");
  });
  close_modal_btn.on("click", function (event) {
    event.preventDefault();
    checkout_modal.css("visibility", "hidden");
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
    search_account.removeClass("success");
    quick_add_to_cart_btn.prop("disabled", true);
  });
  const validateEmail = function (email) {
    const emailPattern = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
    return emailPattern.test(email);
  };
  const set_add_to_cart_btn_text = function () {
    const val = custom_amount.val();
    add_to_cart_btn.text(add_to_cart_text + " " + val + " " + currency);
  };
  set_add_to_cart_btn_text();
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
        callback(response);
      })
      .fail(function (result) {})
      .always(function (result) {
        search_account.removeClass("ui-autocomplete-loading");
      });
  };
  const openCheckoutModal = function () {
    if (!checkout_modal.length) return;
    checkout_modal.css("visibility", "visible");
    checkout_modal.addClass("open");
  };
  search_account.autocomplete({
    delay: 800,
    minLength: 2,
    autoFocus: false,
    source: function (request, suggests) {
      search_member(request.term, function (response) {
        console.log(response);
        suggests(response.data);
      });
    },
    change: function (event, ui) {
      console.log("change");
      if (ui.item !== null && ui.item != undefined) {
        accountNo.val(ui.item.value);
        custom_amount.prop("disabled", false);
        add_to_cart_btn.prop("disabled", false);
        quick_add_to_cart_btn.prop("disabled", false);
      } else {
        accountNo.val("");
        internalNo.val("");
        custom_amount.prop("disabled", true);
        add_to_cart_btn.prop("disabled", true);
        quick_add_to_cart_btn.prop("disabled", true);
        localStorage.clear();
      }
    },
    select: function (event, ui) {
      console.log("autocomplete select", ui);
      event.preventDefault();
      if (ui.item == undefined) {
        accountNo.val("");
        internalNo.val("");
        custom_amount.prop("disabled", true);
        add_to_cart_btn.prop("disabled", true);
        search_account.addClass("error");
        quick_add_to_cart_btn.prop("disabled", true);
      } else {
        console.log(ui.item.value);
        accountNo.val(ui.item.value);
        search_account.addClass("success");
        search_account.removeClass("error");
        custom_amount.prop("disabled", false);
        if (custom_amount.val() >= min_amount) {
          add_to_cart_btn.prop("disabled", false);
        }
        quick_add_to_cart_btn.prop("disabled", false);
      }
    },
    focus: function (event, ui) {},
    close: function (event, ui) {},
    response: function (event, ui) {
      console.log("autocomplete response", ui);
      if (!ui.content.length) {
        var noResult = { value: 0, label: "No results found" };
        ui.content.push(noResult);
        custom_amount.prop("disabled", true);
        add_to_cart_btn.prop("disabled", true);
        quick_add_to_cart_btn.prop("disabled", true);
      }
    },
  });
  add_to_cart.on("click", function (event) {
    event.preventDefault();
    console.log("add_to_cart clicked");
    const amount = jQuery(this).data("amount") || custom_amount.val();
    custom_amount.val(amount);
    set_add_to_cart_btn_text();
    if (remember_me.is(":checked")) {
      localStorage.setItem("search_account", search_account.val());
      localStorage.setItem("accountNo", accountNo.val());
      localStorage.setItem("amount", custom_amount.val());
      localStorage.setItem("rememberMe", "checked");
    } else {
      localStorage.clear();
    }
    const data = {
      product_id: productId.val(),
      quantity: quantity.val() || 1,
      amount: custom_amount.val(),
      accountNo: accountNo.val(),
      action: ajax_add_to_cart_params.action,
      _ajax_nonce: ajax_add_to_cart_params._ajax_nonce,
    };
    $.ajax({
      type: "POST",
      url: ajax_add_to_cart_params.ajax_url,
      data: data,
      dataType: "json",
      beforeSend: function (xhr) {
        jQuery("body").block({ message: null });
      },
      complete: function (res) {
        console.log("complete", res);
        jQuery("body").unblock();
      },
      success: function (res) {
        console.log("added to cart", res);
        jQuery(document.body).trigger("add_to_cart");
        jQuery(document.body).trigger("added_to_cart");
        jQuery(document.body).trigger("update_checkout");
        jQuery(document.body).trigger("updated_checkout");
        openCheckoutModal();
      },
      error: function (res) {
        console.log("error", res);
        localStorage.clear();
        window.location.replace("/deposit/");
      },
    });
  });
});
