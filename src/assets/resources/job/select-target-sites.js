$(document).ready(function() {
  const sourceSite = $('#sourceSite');

  let selected = [];
  let allSelected = false;

  const sourceSiteValue = sourceSite.val();

  $(`#targetSiteIds-field input.checkbox[value="${sourceSiteValue}"]`).
      on('change', function() {

        console.log(`#targetSiteIds-field input.checkbox[value="${sourceSiteValue}"]`, 'change')

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
      checkBox.prop('disabled', true)
      return;
    }

    selected[checkBox.val()] = checkBox.prop('checked');
  });

  const checkboxSelect = $('.checkbox-select').data('checkboxSelect');

  if(checkboxSelect !== undefined) {
    checkboxSelect.addListener(checkboxSelect.$all, 'change', function() {
      const sourceSite = $('#sourceSite').val();

      this.$options.map((option) => {
        const value = $(this.$options[option]).val();

        if (value === sourceSite) {
          $(this.$options[option]).prop({
            checked: false,
            disabled: true,
          });
        }
      });
    });
  }

  if (selected.indexOf(false) === -1) {
    $('#targetSiteIds-field input.checkbox.all').prop('checked', true);
    allSelected = true;
  }

  if(checkboxSelect !== undefined && allSelected) {
    checkboxSelect.$all.trigger('change');
  }

  sourceSite.on('change', function(e) {
    $('#targetSiteIds-field input.checkbox').each(function() {

      if ($(this).hasClass('all')) {
        return;
      }

      $(this).prop('disabled', false);
      $(this).removeClass('disabled');

      if ($('#targetSiteIds-field input.all').prop('checked') === true) {
        $(this).prop('checked', true);
        $(this).attr('disabled', true);
      }
    });

    $('#targetSiteIds-field input.checkbox[value=' + $(this).val() + ']').
        prop('disabled', true).
        prop('checked', false);
  });
});