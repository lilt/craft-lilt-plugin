$(document).ready(function() {

  const form = Object.create({
    formSubmitting: false,
    state: {},
    fetchState: function() {
      let newState = {};

      newState.title = $('#create-job-form #title').val();
      newState.sourceSite = $('#create-job-form #sourceSite').val();

      const checkboxSelect = $(
          '#create-job-form #targetSiteIds-field .checkbox-select').
          data('checkboxSelect');

      if (checkboxSelect !== undefined) {
        const options = checkboxSelect.$options.get();

        const optionsSelected = options.map(
            function(o) {
              if ($(o).val() === '') {
                return;
              }
              return [$(o).val(), $(o).prop('checked')];
            },
        );

        newState.targetSiteIds = optionsSelected;
      }

      const alreadySelected = $('#create-job-selected-entries').val();
      newState.selectedElements = (alreadySelected !== undefined &&
          alreadySelected !== '')
          ? JSON.parse(alreadySelected)
          : [];

      return newState;

    },
    detectChanges: () => {
      //TODO: check changes and enable save button?
      const newState = form.fetchState();

      return JSON.stringify(form.state) === JSON.stringify(newState);
    },
    preloadVersions: function(createJobSelectedVersions) {
      const version = JSON.parse(createJobSelectedVersions.val());
      this.state.versions = version;

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
                `<input id="hidden-input-element-draft-${elementId}" class="hidden-input-element-draft-version" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);

      });

      createJobSelectedVersions.remove();
    },
    onUpdateElements: function() {
      const createJobSelectedVersions = $('#create-job-selected-versions');
      if (createJobSelectedVersions.length > 0) {
        form.preloadVersions(createJobSelectedVersions);
      }

      const elements = Craft.elementIndex.view.getAllElements().get();

      elements.forEach((element) => {
        const elementId = $(element).data('id');
        let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);

        if (hiddenInput.length > 0) {
          const value = {
            elementId: parseInt(elementId),
            draftId: parseInt(hiddenInput.val()),
          };
          $(`#entries-to-translate-field tr[data-id="${elementId}"]`).
              find('select').
              val(btoa(JSON.stringify(value)));
        }
      });

      // UPDATE VERSIONS
      $('#entries-to-translate .select-element-version').
          on('change', function() {

            const value = $(this).find('select').val();
            const {elementId, draftId} = JSON.parse(atob(value));

            let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);

            if (hiddenInput.length > 0) {
              hiddenInput.val(draftId);
            }

            if (!hiddenInput.length) {
              $('#create-job-form').
                  append(
                      `<input id="hidden-input-element-draft-${elementId}" class="hidden-input-element-draft-version" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);
            }
          });
    },
  });
  let formSubmitting = true;

  /*
  $('#content-container').on('click', function() {
    formSubmitting = form.detectChanges();
    if(formSubmitting) {
    } else {
      $(window).on('beforeunload', function(e) {
        if (document.location.pathname.indexOf(
            '/admin/craft-lilt-plugin/job/edit') === 0) {
          return false;
        }

        if (formSubmitting) {
          return false;
        }

        var confirmationMessage = 'Changes that you made may not be saved.';

        //(e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
      });
    }
  });

  $('#content-container').on('change', function() {
    formSubmitting =  form.detectChanges();
    if(formSubmitting) {
    } else {
      $(window).on('beforeunload', function(e) {
        if (document.location.pathname.indexOf(
            '/admin/craft-lilt-plugin/job/edit') === 0) {
          return undefined;
        }
        if (formSubmitting) {
          return undefined;
        }

        var confirmationMessage = 'Changes that you made may not be saved.';

        //(e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
      });
    }

  });
  */

  const onFormSubmit = () => {
    formSubmitting = true;

    window.onbeforeunload = null;

    const $spinner = $(
        '<div class="spinner flex" style="margin-right: 10px"></div>');

    $('#action-button').prepend($spinner);
    $('#action-button').addClass('disabled');
  };

  $('#action-button .btngroup .submit[type="submit"]').on('click', function() {
    onFormSubmit();
  });

  /* TODO: looks like we show alert only when form was change
  $('#lilt-btn-create-new-job').on('click', function() {
    //TODO: I think we shouldn't depend on it so much
    Craft.forceConfirmUnload = false;
    const createJobForm = $('#create-job-form');
    let serialized;
    if (typeof createJobForm.data('serializer') === 'function') {
      serialized = createJobForm.data('serializer')();
    } else {
      serialized = createJobForm.serialize();
    }
    createJobForm.data('initialSerializedValue', serialized);
  });
   */

  $('.btn.submit.menubtn').on('click', function() {
    $(this).data('menubtn').settings.onOptionSelect = function(o) {
      if ($(o).hasClass('formsubmit')) {
        onFormSubmit();
      }
    };
  });

  $('.addAnEntry').on('click', function(e) {
    const alreadySelected = $('#create-job-selected-entries').val();
    const elementIds = (alreadySelected !== undefined && alreadySelected !== '')
        ? JSON.parse(alreadySelected)
        : [];

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

            const currentValue = $('.create-job-selected-entries').
                val().
                toString();
            let alreadySelected = [];
            try {
              alreadySelected = currentValue !== undefined && currentValue !==
              ''
                  ? JSON.parse(currentValue)
                  : [];
            } catch (e) {
              console.log(e);
            }

            let newSelected = [...entiriesSelected, ...alreadySelected];

            newSelected.forEach((element) => { return parseInt(element); });

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

                    if (Craft.elementIndex.getSelectedElementIds().length ===
                        0) {
                      $('#entries-remove-action').css('visibility', 'hidden');
                    }
                  },
                  onUpdateElements: form.onUpdateElements,
                  onAppendElements: form.onUpdateElements,
                },
            );
          },
        });
  });

  const selectedEntries = $('#create-job-selected-entries').val();
  if (selectedEntries !== undefined && selectedEntries !== '') {
    try {
      const entryIds = JSON.parse(selectedEntries);
      if (entryIds.length > 0) {
        Craft.elementIndex = new Craft.LiltElementIndex(
            'lilthq\\craftliltplugin\\elements\\TranslateEntry',
            $('#entries-to-translate'), {
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
              disabledElementIds: $(
                  '#create-job-form #entries-to-translate .elements').
                  hasClass('disabled') ? JSON.parse(selectedEntries) : [],
              actions: null,
              onSelectionChange: function() {
                if (Craft.elementIndex.getSelectedElementIds().length > 0) {
                  $('#entries-remove-action').css('visibility', 'visible');
                }

                if (Craft.elementIndex.getSelectedElementIds().length === 0) {
                  $('#entries-remove-action').css('visibility', 'hidden');
                }
              },
              onUpdateElements: function() {
                form.onUpdateElements();

                //REFRESH FORM STATE:
                const createJobForm = $('#create-job-form');
                let serialized;
                if (typeof createJobForm.data('serializer') === 'function') {
                  serialized = createJobForm.data('serializer')();
                } else {
                  serialized = createJobForm.serialize();
                }
                createJobForm.data('initialSerializedValue', serialized)
              },
            });
        $('#entries-to-translate').show();

        $('#create-job-form #entries-to-translate .disabled.elements').
            on('DOMSubtreeModified', function() {
              $('#create-job-form .disabled select').each(
                  function() {
                    $(this).on('mousedown', function(e) {
                      e.preventDefault();
                      return false;
                    });
                  },
              );
            });

      }
    } catch (e) {
      console.log(e);
    }
  }

  form.state = form.fetchState();
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

  remove.forEach(function(e) {
    const hiddenInput = $(`#hidden-input-element-draft-${e}`);

    if (hiddenInput.length > 0) {
      hiddenInput.remove();
    }
  });

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

