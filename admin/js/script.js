jQuery(function ($) {
  $(".repeat").each(function () {
    $(this).repeatable_fields();
  });

  let couponDetails = [];

  $(document).on("click", "#gform-settings-save", function (e) {
    // Reset index of all the coupon values before save
    $("input[name='coupon-discount[]']").each(function (index) {
      $(this).attr("id", "coupon-discount-" + (index - 1));
    });

    $("input[name='coupon-name[]']").each(function (index) {
      $(this).attr("id", "coupon-name-" + (index - 1));
    });

    $("input[name='coupon-minimum-quantity[]']").each(function (index) {
      $(this).attr("id", "coupon-minimum-quantity-" + (index - 1));
    });

    let element = {};
    const numberOfInputs = $('input[name="coupon-name[]"]').length;

    for (let i = 0; i < numberOfInputs - 1; i++) {
      element = {
        cN: $(`#coupon-name-${i}`).val(),
        cD: $(`#coupon-discount-${i}`).val(),
        cQ: $(`#coupon-minimum-quantity-${i}`).val(),
      };

      couponDetails.push(element);
    }
    $("#coupon_details").val(JSON.stringify(couponDetails)).trigger("change");
  });
});

jQuery(document).ready(function ($) {
  let couponDetails = JSON.parse($("#coupon_details").val());

  couponDetails.forEach((currentElement, i) => {
    $("tbody.container").append(`<tr class="template row">
    <td width="50%">
        <input type="text" name="coupon-name[]" value=${
          currentElement.cN ? currentElement.cN : ""
        } id="coupoun-name-${i}" />
    </td>
    <td width="15%">
        <input type="number" name="coupon-discount[]" value=${
          currentElement.cD ? currentElement.cD : ""
        } id="coupon-discount-${i}" />
    </td>
    <td width="15%">
        <input type="number" name="coupon-minimum-quantity[]" value=${
          currentElement.cQ ? currentElement.cQ : ""
        }  id="coupon-minimum-quantity-${i}" />
    </td>
    <td width="20%">
    <span id="removebutton" class="remove">Remove</span>
    </td>
    </tr>`);
  });
});
