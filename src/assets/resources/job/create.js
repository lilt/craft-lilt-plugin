$('.addAnEntry').on('click', function(e) {
  Craft.liltPluginModal = Craft.createElementSelectorModal(
      'craft\\elements\\Entry', {
        storageKey: null,
        sources: null,
        elementIndex: null,
        defaultSiteId: $('#sourceSite').val(),
        criteria: {},
        multiSelect: 1,
        disableOnSelect: true,
        onCancel: function() {},
        onSelect: function(entries) {
          const entiriesSelected = entries.map((entry) => {
            return entry.id;
          });

          const currentValue = $('.create-job-selected-entries').val();
          let alreadySelected = [];
          try {
            alreadySelected = currentValue !== undefined && currentValue !==
            ''
                ? JSON.parse(
                    currentValue)
                : [];
          } catch (e) {
            console.log(e);
          }

          const newSelected = [...entiriesSelected, ...alreadySelected];

          $('.create-job-selected-entries').
              val(JSON.stringify(newSelected));

          $('#entries-to-translate').show();
          Craft.elementIndex = new Craft.LiltElementIndex(
              'craft\\elements\\Entry',
              $('#page-container'), {
                elementTypeName: 'Entry',
                elementTypePluralName: 'Entries',
                context: 'index',
                storageKey: 'elementindex.craft\\elements\\Entry',
                siteId: $('#sourceSite').val(),
                criteria: {
                  siteId: $('#sourceSite').val(),
                  where: {
                    'elements.id': newSelected,
                  },
                },
                canHaveDrafts: true,
                hideSidebar: true,
                onSelectionChange: function() {
                  if (Craft.elementIndex.getSelectedElementIds().length > 0) {
                    $('#entries-remove-action').css('visibility', 'visible');
                  }

                  if (Craft.elementIndex.getSelectedElementIds().length === 0) {
                    $('#entries-remove-action').css('visibility', 'hidden');
                  }
                },
              });
        },
      });
});

/** Preload entries */
$(document).ready(function() {
  const selectedEntries = $('#create-job-selected-entries').val();
  if (selectedEntries !== undefined && selectedEntries !== '') {

    try {
      const entryIds = JSON.parse(selectedEntries);
      if (entryIds.length > 0) {
        Craft.elementIndex = new Craft.LiltElementIndex(
            'craft\\elements\\Entry',
            $('#page-container'), {
              elementTypeName: 'Entry',
              elementTypePluralName: 'Entries',
              context: 'index',
              storageKey: 'elementindex.craft\\elements\\Entry',
              criteria: {
                siteId: $('#sourceSite').val(),
                where: {
                  'elements.id': JSON.parse(selectedEntries),
                },
              },
              canHaveDrafts: true,
              hideSidebar: true,
              onSelectionChange: function() {
                if (Craft.elementIndex.getSelectedElementIds().length > 0) {
                  $('#entries-remove-action').css('visibility', 'visible');
                }

                if (Craft.elementIndex.getSelectedElementIds().length === 0) {
                  $('#entries-remove-action').css('visibility', 'hidden');
                }
              },
            });

        $('#entries-to-translate').show();
      }
    } catch (e) {
      console.log(e);
    }
  }
});

$('#entries-remove-action').on('click', function() {
  let remove = Craft.elementIndex.getSelectedElementIds();

  const currentValue = $('.create-job-selected-entries').val();
  let alreadySelected = [];
  try {
    alreadySelected = currentValue !== undefined && currentValue !==
    ''
        ? JSON.parse(
            currentValue)
        : [];
  } catch (e) {
    console.log(e);
  }

  alreadySelected = alreadySelected.filter(function(el) {
    return remove.indexOf(el) < 0;
  });

  if (alreadySelected.length === 0) {
    Craft.elementIndex = null;
    $('#entries-to-translate-field div.elements').html('');
    $('#entries-to-translate').hide();
  }
  $('#entries-remove-action').css('visibility', 'hidden');

  $('.create-job-selected-entries').
      val(JSON.stringify(alreadySelected));

  if (alreadySelected.length > 0) {
    Craft.elementIndex.settings.criteria = {
      siteId: $('#sourceSite').val(),
      where: {
        'elements.id': alreadySelected,
      },
    };
    Craft.elementIndex.updateElements();
  }
});

$('#sourceSite').on('change', function(e) {
  $('#targetSiteIds-field input.checkbox').each(function() {
    $(this).prop('disabled', false);
    if ($('#targetSiteIds-field input.all').prop('checked') === true) {
      $(this).prop('checked', true);
    }
    $(this).removeClass('disabled');
  });

  $('#targetSiteIds-field input.checkbox[value=' + $(this).val() + ']').
      prop('disabled', true).prop('checked', false);
});

$(document).ready(function() {
  $('#sourceSite').on('change', function(e) {

    if (parseInt(Craft.elementIndex.siteId) !== parseInt($(this).val())) {
      Craft.elementIndex.siteId = $(this).val();
      Craft.elementIndex.updateElements();
    }
  });
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
//END

$('#create-order-submit-form').on('click', function() {
  $('#create-job-form').submit();
});

$(document).ready(
    function() {
      $('#sourceSite').trigger('change');
    },
);
