"use strict";

jQuery(document).ready(function ($) {
  let reqRes = null;

  $(document).on("gform_post_render", function (event, form_id, current_page) {
    $.ajax({
      type: "GET",
      url: "/wp-admin/admin-ajax.php",
      data: {
        action: "get_feed_data",
        form_id: form_id,
      },
      success: function (response) {
        reqRes = JSON.parse(response);
        console.log(reqRes);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX Error:", textStatus, errorThrown);
      },
    });
  });

  function searchCoupon(couponCode, coupon_details) {
    let couponValue,
      couponQuantity = null;

    $.each(coupon_details, function (index, coupon) {
      if (coupon.cN === couponCode) {
        couponValue = coupon.cD;
        couponQuantity = coupon.cQ;
        return false;
      }
    });
    return [couponValue, couponQuantity];
  }

  function getActiveProductTotal(productID, formID) {
    let productQuantity = $(`#input_${formID}_${productID}_1`).val();
    let productPrice = $(`#ginput_base_price_${formID}_${productID}`).attr(
      "value"
    );
    productPrice = parseInt(productPrice.replace("$", ""), 10);
    let total = productPrice * productQuantity;
    return [total, productQuantity];
  }

  setTimeout(function () {
    let discountValue = 0;
    let couponValue,
      couponQuantity = 0;
    const minQuantity = reqRes.feed[0].meta.minimum_quantity;
    const quantityDiscountValue = reqRes.feed[0].meta.discount_amount;
    const discountType = reqRes.feed[0].meta.discount_type;
    const discount_method = reqRes.feed[0].meta.discount_method;
    let coupon_details = reqRes.feed[0].meta.coupon_details;
    let productID = Math.floor(reqRes.feed[0].meta.mappedFields_product_name);
    const formID = $(".gform_wrapper form").attr("data-formid");

    $(document).on("click", "#gf_coupon_button", function (e) {
      if (discount_method === "coupon_discount") {
        const inputCoupon = $(".gf_coupon_code_entry").val();
        $(".gf_coupon_code").val(inputCoupon).trigger("change");
        [couponValue, couponQuantity] = searchCoupon(
          inputCoupon,
          coupon_details
        );
        if (couponValue) {
          let [total, activeProductQuantity] = getActiveProductTotal(
            productID,
            formID
          );
          if (Number(activeProductQuantity) >= couponQuantity) {
            if (discountType == "percent") {
              discountValue = total * (couponValue / 100);
            } else if (discountType == "cash") {
              discountValue = couponValue;
            }
            gformCalculateTotalPrice(formID);
            $(this).prop("disabled", true);
          }
        }
      }
    });

    gform.addFilter("gform_product_total", function (total, formId) {
      if (discount_method === "quantity_discount") {
        let [ptotal, activeProductQuantity] = getActiveProductTotal(
          productID,
          formID
        );
        if (Number(activeProductQuantity) >= minQuantity) {
          if (discountType == "percent") {
            discountValue = ptotal * (quantityDiscountValue / 100);
          } else if (discountType == "cash") {
            discountValue = quantityDiscountValue;
          }
        }
      }

      total -= discountValue;
      return total;
    });
  }, 2000);
});
