"use strict";

jQuery(document).ready(function ($) {
  let reqRes = null;

  $(document).on("gform_post_render", function (event, formID, current_page) {
    $.ajax({
      type: "GET",
      url: "/wp-admin/admin-ajax.php",
      data: {
        action: "get_feed_data",
        form_id: formID,
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

  function getRigpassProductTotal() {
    let total =
      Number(localStorage.getItem("productPrice")) *
      Number(localStorage.getItem("productQuantity"));
    return [total, Number(localStorage.getItem("productQuantity"))];
  }

  setTimeout(function () {
    let discountValue = 0;
    let couponValue,
      couponQuantity = 0;

    const gfDiscountFeed = $.grep(reqRes.feed, function (obj) {
      return obj.addon_slug === "gf-quantity-discount";
    });

    const minQuantity = gfDiscountFeed[0].meta.minimum_quantity;
    const quantityDiscountValue = gfDiscountFeed[0].meta.discount_amount;
    const discountType = gfDiscountFeed[0].meta.discount_type;
    const discount_method = gfDiscountFeed[0].meta.discount_method;
    let coupon_details = gfDiscountFeed[0].meta.coupon_details;
    let productID = Math.floor(
      gfDiscountFeed[0].meta.mappedFields_product_name
    );
    let productQuantityField = Math.floor(
      gfDiscountFeed[0].meta.mappedFields_product_quantity
    );

    const formID = $(".gform_wrapper form").attr("data-formid");

    let fieldLength = reqRes.field.length;
    let productPrice,
      productQuantity = 0;
    let showBreakdown = false;
    let showError = true;
    let inputCoupon = null;

    let quantityFieldType = null;

    for (let i = 0; i < fieldLength; i++) {
      if (reqRes.field[i].id === productID) {
        if (reqRes.field[i].inputType === "radio") {
          productPrice = parseInt(
            reqRes.field[i].choices[0].price.replace("$", ""),
            10
          );
        } else if (reqRes.field[i].inputType === "singleproduct") {
          productPrice = parseInt(
            reqRes.field[i].basePrice.replace("$", ""),
            10
          );
        }
        localStorage.setItem("productPrice", productPrice);
      }

      if (reqRes.field[i].id === productQuantityField) {
        console.log("here");
        if (reqRes.field[i].inputType === "number") {
          quantityFieldType = "input";
        } else if (reqRes.field[i].inputType === "select") {
          quantityFieldType = "select";
        }
      }
    }

    $(document).on("click", ".gform_next_button", function (e) {
      productQuantity = $(`#input_${formID}_${productQuantityField}`)
        .find(":selected")
        .val();
      localStorage.setItem("productQuantity", productQuantity);
    });

    if (gfDiscountFeed[0].meta.mappedFields_product_quantity)
      var selector =
        quantityFieldType +
        '[name="input_' +
        gfDiscountFeed[0].meta.mappedFields_product_quantity +
        '"]';

    $(selector).on("change", function () {
      productQuantity = $(this).val();
      localStorage.setItem("productQuantity", productQuantity);
    });

    $(document).on("click", "#gf_coupon_button", function (e) {
      if (discount_method === "coupon_discount") {
        inputCoupon = $(".gf_coupon_code_entry").val();
        $(".gf_coupon_code").val(inputCoupon).trigger("change");
        [couponValue, couponQuantity] = searchCoupon(
          inputCoupon,
          coupon_details
        );
        if (couponValue) {
          let [total, activeProductQuantity] = getRigpassProductTotal();
          if (Number(activeProductQuantity) >= couponQuantity) {
            if (discountType == "percent") {
              discountValue = total * (couponValue / 100);
            } else if (discountType == "cash") {
              discountValue = couponValue;
            }
            gformCalculateTotalPrice(formID);
            $(this).prop("disabled", true);
            $(this).addClass("disabled-button");
            showBreakdown = true;
            if ($(".invalid-coupon").length) {
              $(".invalid-coupon").remove();
            }
          }
        } else {
          if (showError) {
            $("#gf_coupons_container_1").append(
              "<span class='invalid-coupon'> Invalid Coupon Code. Please recheck your coupon code and re-enter it. </span>"
            );
          }
          showError = false;
        }
      }
    });

    gform.addFilter("gform_product_total", function (total, formId) {
      if (showBreakdown) {
        $(".gfield_total").prepend(`
        <div class="cart">
        <div class="cart-item">
          <span>Subtotal:</span> $${total}
        </div>
        <div class="cart-item">
          <span>Discount code ${inputCoupon}:
          <span class="discount-value"> 
                -$${Math.ceil(discountValue)}
                </span> 
                </span> 
        </div>
      </div>`);
        showBreakdown = false;
      }

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
      return Math.floor(total);
    });
  }, 2000);
});
