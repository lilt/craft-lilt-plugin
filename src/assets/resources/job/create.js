$('.addAnEntry').on('click', function(e) {
  const alreadySelected = $('#create-job-selected-entries').val();
  const elementIds = (alreadySelected !== undefined && alreadySelected !== '') ? JSON.parse(alreadySelected) : [];

  Craft.liltPluginModal = Craft.createElementSelectorModal(
      'craft\\elements\\Entry', {
        storageKey: null,
        sources: null,
        elementIndex: null,
        defaultSiteId: $('#sourceSite').val(),
        criteria: {},
        multiSelect: 1,
        disableOnSelect: true,
        disabledElementIds: elementIds,
        onCancel: function() {},
        onSelect: function(entries) {
          const entiriesSelected = entries.map((entry) => {
            return entry.id.toString();
          });

          const currentValue = $('.create-job-selected-entries').val().toString();
          let alreadySelected = [];
          try {
            alreadySelected = currentValue !== undefined && currentValue !== ''
                ? JSON.parse(currentValue)
                : [];
          } catch (e) {
            console.log(e);
          }

          let newSelected = [...entiriesSelected, ...alreadySelected];

          newSelected.forEach((element) => { return parseInt(element); })

          $('.create-job-selected-entries').
              val(JSON.stringify(newSelected));

          $('#entries-to-translate').show();
          Craft.elementIndex = new Craft.LiltElementIndex(
              'lilthq\\craftliltplugin\\elements\\TranslateEntry',
              $('#page-container'), {
                elementTypeName: 'Entry',
                elementTypePluralName: 'Entries',
                context: 'index',
                sources: '*',
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
                onUpdateElements: function() {

                  // save state
                  $('#entries-to-translate .select-element-version select').
                      each(function() {
                        const value = $(this).val();
                        let {elementId, draftId} = JSON.parse(atob(value));
                        let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);
                        if (hiddenInput.length > 0) {
                          draftId = hiddenInput.val();

                          const value = {
                            elementId: parseInt(elementId),
                            draftId: parseInt(draftId),
                          };
                          $(this).
                              val(btoa(JSON.stringify(value)));

                          return;
                        }

                        $('#create-job-form').
                            append(
                                `<input id="hidden-input-element-draft-${elementId}" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);
                      });

                  $('#entries-to-translate .select-element-version').
                      on('change', function() {
                        const value = $(this).find('select').val();
                        const {elementId, draftId} = JSON.parse(atob(value));

                        let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);

                        if (hiddenInput.length > 0) {
                          hiddenInput.val(draftId);

                          return;
                        }

                        $('#create-job-form').
                            append(
                                `<input id="hidden-input-element-draft-${elementId}" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);
                      });
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
            'lilthq\\craftliltplugin\\elements\\TranslateEntry',
            $('#page-container'), {
              elementTypeName: 'Entry',
              elementTypePluralName: 'Entries',
              context: 'index',
              storageKey: 'elementindex.craft\\elements\\Entry',
              criteria: {
                siteId: $('#sourceSite').val(), where: {
                  'elements.id': JSON.parse(selectedEntries),
                },
              },
              canHaveDrafts: true,
              sources: '*',
              hideSidebar: true,
              onSelectionChange: function() {
                if (Craft.elementIndex.getSelectedElementIds().length > 0) {
                  $('#entries-remove-action').css('visibility', 'visible');
                }

                if (Craft.elementIndex.getSelectedElementIds().length === 0) {
                  $('#entries-remove-action').css('visibility', 'hidden');
                }
              },
              onUpdateElements: function() {
                // PRE-SELECT VERSION
                const version = JSON.parse(
                    $('#create-job-selected-versions').val());
                $('#entries-to-translate tbody tr').each(function() {
                  const elementId = $(this).data('id');

                  if (elementId === undefined) {
                    return;
                  }

                  const draftId = version[elementId];

                  if (draftId === undefined) {
                    return;
                  }
                  const value = {
                    elementId: parseInt(elementId), draftId: parseInt(draftId),
                  };
                  $(this).find('select').val(btoa(JSON.stringify(value)));

                  let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);

                  if (hiddenInput.length > 0) {
                    hiddenInput.val(draftId);

                    return;
                  }

                  $('#create-job-form').
                      append(
                          `<input id="hidden-input-element-draft-${elementId}" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);

                });

                // UPDATE VERSIONS
                $('#entries-to-translate .select-element-version').
                    on('change', function() {
                      const value = $(this).find('select').val();
                      const {elementId, draftId} = JSON.parse(atob(value));

                      let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);

                      if (hiddenInput.length > 0) {
                        hiddenInput.val(draftId);

                        return;
                      }

                      $('#create-job-form').
                          append(
                              `<input id="hidden-input-element-draft-${elementId}" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);
                    });
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

  remove = remove.map((element) => element.toString());

  const currentValue = $('.create-job-selected-entries').val();
  let alreadySelected = [];
  try {
    alreadySelected = currentValue !== undefined && currentValue !== ''
        ? JSON.parse(currentValue)
        : [];
  } catch (e) {
    console.log(e);
  }

  alreadySelected = alreadySelected.filter((el) => remove.indexOf(el) === -1);

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
      siteId: $('#sourceSite').val(), where: {
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
      prop('disabled', true).
      prop('checked', false);
});

$(document).ready(function() {
  $('#create-job-form .disabled').on('click', function() {
    return false;
  });
});

$(document).ready(function() {
  $('#sourceSite').on('change', function(e) {

    if (Craft.elementIndex !== undefined &&
        parseInt(Craft.elementIndex.siteId) !== parseInt($(this).val())) {
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

$(document).ready(function() {
  $('#sourceSite').trigger('change');

  let formSubmitting = false;

  $('#create-order-submit-form').on('click', function() {
    $('#create-job-form').submit();
    formSubmitting = true;
  });

  window.addEventListener("beforeunload", function (e) {

    if(document.location.pathname.indexOf('/admin/craft-lilt-plugin/job/edit') === 0) {
      return undefined;
    }

    if (formSubmitting) {
      return undefined;
    }

    var confirmationMessage = 'It looks like you have been editing something. '
        + 'If you leave before saving, your changes will be lost.';

    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
    return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
  });
});