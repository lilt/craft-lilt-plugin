

$(document).ready(function() {
  $('#sourceSite').trigger('change');

  $('#sourceSite').on('change', function(e) {
    $('#targetSiteIds-field input.checkbox').each(function() {
      $(this).prop('disabled', false);
      if ($('#targetSiteIds-field input.all').prop('checked') === true) {
        $(this).prop('checked', true);
      }
      $(this).removeClass('disabled');
    });

    $('#targetSiteIds-field input.checkbox[value=' + $(this).val() + ']').
        prop('disabled', true).
        prop('checked', false);
  });


  $('#targetSiteIds-field input.all').on('click', function(e) {
    let checked = $(this).prop('checked');

    $('#targetSiteIds-field input.checkbox').each(function() {
      if ($(this).hasClass('all')) {
        return;
      }

      $(this).prop('disabled', true);

      if ($('#sourceSite').val() !== $(this).val()) {
        $(this).prop('checked', checked);
      }

      if ($('#sourceSite').val() === $(this).val()) {
        if (checked) {
          $(this).addClass('disabled');
        }

        if (!checked) {
          $(this).removeClass('disabled');
        }
      }
    });
  });

  let selected = [];
  const sourceSiteValue = $('#sourceSite').val();

  const index = $('#sourceSite').val();

  $(`#targetSiteIds-field input.checkbox[value="${index}"]`).
      on('change', function() {

        console.log(`#targetSiteIds-field input.checkbox[value="${index}"]`, 'change')

        if ($('#sourceSite').val() === $(this).val()) {
          $(this).prop('disabled', true)
        }
      });

  $('#targetSiteIds-field input.checkbox').each(function() {
    const checkBox = $(this);

    if (checkBox.hasClass('all')) {
      return;
    }

    if (checkBox.val() === sourceSiteValue) {
      return;
    }

    selected[checkBox.val()] = checkBox.prop('checked');
  });

  if (selected.indexOf(false) === -1) {
    $('#targetSiteIds-field input.checkbox.all').prop('checked', true);

    //TODO why checkbox predisabling is not working?
    /* selected.forEach((value, index) => {
      $(`#targetSiteIds-field input.checkbox[value="${index}"]`).prop('disabled', true);
      $(`#targetSiteIds-field input.checkbox[value="${index}"]`).addClass('disabled');
      $(`#targetSiteIds-field input.checkbox[value="${index}"]`).attr('disabled', true);
    }) */
  }
});