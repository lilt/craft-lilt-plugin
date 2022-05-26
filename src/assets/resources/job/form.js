$(document).ready(function() {
  const form = Object.create({
    init: (fields) => {
      this.loadData(fields);

      console.log(this);
    },

    loadData: (fields) => {
      this.fields = fields;
      this.formSubmitting = false;
      const createJobSelectedEntriesValue = this.fields.createJobSelectedEntries.val();
      if (createJobSelectedEntriesValue !== undefined &&
          createJobSelectedEntriesValue !== '') {
        this.elementsCurrent = this.elementsOriginal = JSON.parse(
            createJobSelectedEntriesValue);
      }
      const createJobSelectedVersionsValue = this.fields.createJobSelectedVersions.val();
      if (createJobSelectedVersionsValue !== undefined &&
          createJobSelectedVersionsValue !== '') {
        this.versionsCurrent = this.versionsOriginal = JSON.parse(
            createJobSelectedVersionsValue);
      }
    },

    enableSaveButton: () => {
    },

    versionSelectChange: (element, draft) => {
      this.versionsCurrent[element] = draft;
    },

    elementsChange: (elements) => {
      this.elementsCurrent = elements;
    },

    beforeUnload: (e) => {
      if (document.location.pathname.indexOf(
          '/admin/craft-lilt-plugin/job/edit') === 0) {
        return undefined;
      }

      if (this.formSubmitting) {
        return undefined;
      }

      var confirmationMessage = 'It looks like you have been editing something. '
          + 'If you leave before saving, your changes will be lost.';

      (e || window.event).returnValue = confirmationMessage; //Gecko + IE
      return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    },
  });

  form.init({
    titleField: $('#create-job-form #title'),
    sourceSiteField: $('#create-job-form #sourceSite'),
    targetSiteIds: $('#create-job-form #targetSiteIds-field .checkbox-select'),
    createJobSelectedEntries: $(
        '#create-job-form #create-job-selected-entries'),
    createJobSelectedVersions: $(
        '#create-job-form #create-job-selected-versions'),
  });

  const onFormSubmit = () => {
    form.formSubmitting = true;

    const $spinner = $(
        '<div class="spinner flex" style="margin-right: 10px"></div>');

    $('#action-button').prepend($spinner);
    $('#action-button').addClass('disabled');
  };

  $('#action-button .btngroup .submit[type="submit"]').on('click', function() {
    onFormSubmit();
  });

  $('.btn.submit.menubtn').on('click', function() {
    console.log('create-draft');
    $(this).data('menubtn').settings.onOptionSelect = function(o) {
      if ($(o).hasClass('formsubmit')) {
        onFormSubmit();
      }
    };
  });

  //window.addEventListener('beforeunload', form.beforeUnload);

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
                          }

                          if (!hiddenInput.length) {
                            $('#create-job-form').
                                append(
                                    `<input id="hidden-input-element-draft-${elementId}" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);
                          }

                          console.log($('#create-job-form').serializeArray());

                          let version = JSON.parse(
                              $('#create-job-selected-versions').val());

                          version[elementId] = draftId;

                          console.log(version);
                          $('#create-job-selected-versions').
                              val(JSON.stringify(version));
                        });
                  },
                });
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
                      }

                      if (!hiddenInput.length) {
                        $('#create-job-form').
                            append(
                                `<input id="hidden-input-element-draft-${elementId}" type="hidden" name="versions[${elementId}]" value="${draftId}"/>`);
                      }

                      form.versionSelectChange(elementId, draftId);
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