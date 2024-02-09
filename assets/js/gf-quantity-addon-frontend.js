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
    var couponValue = null;
    $.each(coupon_details, function (index, coupon) {
      console.log(coupon);
      if (coupon.cN === couponCode) {
        couponValue = coupon.cD;
        return false; // exit the loop if the coupon is found
      }
    });
    return couponValue;
  }

  setTimeout(function () {
    let discountValue = 0;
    let coupon_value = 0;
    const minQuantity = reqRes.feed[0].meta.minimum_quantity;
    const quantityDiscountValue = reqRes.feed[0].meta.discount_amount;
    const discountType = reqRes.feed[0].meta.discount_type;
    const discount_method = reqRes.feed[0].meta.discount_method;
    let coupon_details = reqRes.feed[0].meta.coupon_details;

    $(document).on("click", "#gf_coupon_button", function (e) {
      if (discount_method === "coupon_discount") {
        const inputCoupon = $(".gf_coupon_code_entry").val();
        $(".gf_coupon_code").val(inputCoupon).trigger("change");
        coupon_value = searchCoupon(inputCoupon, coupon_details);
        if (coupon_value) {
          gformCalculateTotalPrice(
            $(".gform_wrapper form").attr("data-formid")
          );
          $(this).prop("disabled", true);
        }
      }
    });

    gform.addFilter("gform_product_total", function (total, formId) {
      if (discount_method === "quantity_discount") {
        if (Number($(".ginput_quantity").val()) >= minQuantity) {
          if (discountType == "percent") {
            discountValue = total * (quantityDiscountValue / 100);
          } else if (discountType == "cash") {
            discountValue = quantityDiscountValue;
          }
        }
      } else if (discount_method === "coupon_discount") {
        if (discountType == "percent") {
          discountValue = total * (coupon_value / 100);
        } else if (discountType == "cash") {
          discountValue = coupon_value;
        }
      }

      total -= discountValue;
      return total;
    });
  }, 2000);
});

// const pQuantityIdentifier =
//   reqRes.feed[0].meta.mappedFields_product_quantity;
// const pPricetIdentifier = reqRes.feed[0].meta.mappedFields_product_price;

// const Quantity = $(`[name="input_${pQuantityIdentifier}"]`).val();
// const productPrice = $(`[name="input_${pPricetIdentifier}`).val();
