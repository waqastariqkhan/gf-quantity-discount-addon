"use strict";

jQuery(document).ready(function () {
  let reqRes = null;

  jQuery(document).on(
    "gform_post_render",
    function (event, form_id, current_page) {
      jQuery.ajax({
        type: "GET",
        url: `/wp-json/gf/v2/forms/${form_id}/feeds`,
        success: function (response) {
          reqRes = response;
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("AJAX Error:", textStatus, errorThrown);
        },
      });
    }
  );

  setTimeout(function () {
    gform.addFilter("gform_product_total", function (total, formId) {
      const minQuantity = reqRes[0].meta.minimum_quantity;
      const discountAmount = reqRes[0].meta.discount_amount;
      const discountType = reqRes[0].meta.discount_type;
      let discountValue = 0;

      if (discountType == "percent") {
        discountValue = total * (discountAmount / 100);
      } else if (discountType == "cash") {
        discountValue = discountAmount;
      }

      if (Number(jQuery(".ginput_quantity").val()) >= minQuantity) {
        total -= discountValue;
      }
      return total;
    });
  }, 2000);
});
