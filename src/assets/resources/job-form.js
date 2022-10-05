CraftliltPlugin.JobForm = Garnish.Base.extend({
  $container: null,
  $actionButton: null,
  $addEntryButton: null,
  $removeEntryButton: null,
  $selectedVersions: null,
  $selectedEntries: null,

  translationInProgressWarning: null,

  init: function(container, settings) {
    this.$container = $(container);
    this.setSettings(settings, Craft.Grid.defaults);

    $('.disabled', this.$container).on('click', function() {
      return false;
    });

    if (this.$container.data('job-form')) {
      console.warn('Double-instantiating a job-form on an element');
      this.$container.data('job-form').destroy();
    }
    this.createTranslationInProgressWarning();
    this.$container.data('job-form', this);

    this.$actionButton = $('#action-button', this.$container);
    this.$addEntryButton = $('.addAnEntry', this.$container);
    this.$addEntryButton.on('click', () => { this.onAddEntryClick(); });

    this.$removeEntryButton = $('#entries-remove-action', this.$container);
    this.$removeEntryButton.on('click', () => { this.onRemoveEntry(); });

    this.$selectedVersions = $('#create-job-selected-versions');
    this.$selectedEntries = $('.create-job-selected-entries');

    /** Site changed, let's refresh entries */
    $('#sourceSite').on('change', function() {
      if (Craft.elementIndex !== undefined &&
          parseInt(Craft.elementIndex.siteId) !== parseInt($(this).val())) {
        Craft.elementIndex.siteId = $(this).val();
        Craft.elementIndex.updateElements();
      }
    });

    this._initSubmitButtons();
    this.preloadElementIndex();
  },
  /** Trigger on submit function, when we clicked on any form action */
  _initSubmitButtons: function() {
    $('#action-button .btngroup .submit[type="submit"]', this.$container).
        on('click', () => {
          this.onSubmit();
        });
    $('.btn.submit.menubtn').on('click', (e) => {
      $(e.target).data('menubtn').settings.onOptionSelect = (o) => {
        if ($(o).hasClass('formsubmit')) {
          this.onSubmit();
        }
      };
    });
  },
  /** _refreshFormState will update initialSerializedValue for current form */
  _refreshFormState: function() {
    let serialized;
    if (typeof this.$container.data('serializer') === 'function') {
      serialized = this.$container.data('serializer')();
    } else {
      serialized = this.$container.serialize();
    }
    this.$container.data('initialSerializedValue', serialized);
  },
  onAddEntryClick: function() {
    const alreadySelected = $('#create-job-selected-entries').val();
    const elementIds = (alreadySelected !== undefined && alreadySelected !== '')
        ? JSON.parse(alreadySelected)
        : [];

    Craft.liltPluginModal = Craft.createLiltElementSelectorModal(
        'craft\\elements\\Entry', {
          storageKey: null,
          sources: null,
          elementIndex: null,
          defaultSiteId: $('#sourceSite').val(),
          criteria: {},
          multiSelect: 1,
          disableOnSelect: true,
          disabledElementIds: elementIds,
          showEntryVersions: false,
          onCancel: function() {},
          onSelect: (entries) => {
            const entiriesSelected = entries.map((entry) => {
              return entry.id.toString();
            });

            const currentValue = this.$selectedEntries.
                val().
                toString();
            let alreadySelected = [];
            try {
              alreadySelected = currentValue !== undefined && currentValue !==
              '' ? JSON.parse(currentValue) : [];
            } catch (e) {
              console.log(e);
            }

            let newSelected = [...entiriesSelected, ...alreadySelected];

            newSelected.forEach((element) => { return parseInt(element); });

            $('form#create-job-form').
                data('job-form').
                $selectedEntries.
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
                    siteId: $('#sourceSite').val(), where: {
                      'elements.id': newSelected,
                    },
                  },
                  canHaveDrafts: true,
                  hideSidebar: true,
                  showEntryVersions: true,
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
                    $('form#create-job-form').
                        data('job-form').
                        onUpdateElements();
                  },
                  onAppendElements: function() {
                    $('form#create-job-form').
                        data('job-form').
                        onUpdateElements();
                  },
                });
          },
        });
  },
  serializeArray: function() {
    return this.$container.serializeArray();
  },
  elementIndexUpdateElements: function() {
    this.onUpdateElements();
  },
  elementIndexSelectionChange: function() {
    if (Craft.elementIndex.getSelectedElementIds().length > 0) {
      $('#entries-remove-action').css('visibility', 'visible');
    }

    if (Craft.elementIndex.getSelectedElementIds().length === 0) {
      $('#entries-remove-action').css('visibility', 'hidden');
    }
  },
  _getSelectedEntries: function() {
    const selectedEntries = $('#create-job-selected-entries').val();

    if (selectedEntries === undefined || selectedEntries === '') {
      return [];
    }
    try {
      return JSON.parse(selectedEntries);
    } catch (e) {
      return [];
    }
  },
  preloadElementIndex: function() {
    const selectedEntries = this._getSelectedEntries();

    if (selectedEntries.length <= 0) {
      return;
    }

    try {
      this.createElementIndex({
        elementTypeName: 'Entry',
        elementTypePluralName: 'Entries',
        context: 'index',
        storageKey: 'elementindex.craft\\elements\\Entry',
        criteria: {
          siteId: $('#sourceSite').val(), where: {
            'elements.id': selectedEntries,
          },
        },
        canHaveDrafts: true,
        showEntryVersions: true,
        sources: '*',
        disabledElementIds: $(
            '#create-job-form #entries-to-translate .elements').
            hasClass('disabled') ? selectedEntries : [],
        actions: null,
        onSelectionChange: this.elementIndexSelectionChange,
        onUpdateElements: () => {
          this.elementIndexUpdateElements();
          /* on preload we have to refresh job state, otherwise it will be a warning when user leave a pahe */
          this._refreshFormState();
        },
        onAppendElements: this.onUpdateElements,
      });

      $('#entries-to-translate').show();

      $('#create-job-form #entries-to-translate .disabled.elements').
          on('DOMSubtreeModified', function() {
            $('#create-job-form .disabled select').each(function() {
              $(this).on('mousedown', function(e) {
                e.preventDefault();
                return false;
              });
            });
          });

    } catch (e) {
      console.log(e);
    }

  },
  createElementIndex: function(settings) {
    Craft.elementIndex = new Craft.LiltElementIndex(
        'lilthq\\craftliltplugin\\elements\\TranslateEntry',
        $('#entries-to-translate'), settings);
  },
  preloadVersions: function() {
    if (!this.$selectedVersions || !this.$selectedVersions.length > 0) {
      return;
    }

    const version = JSON.parse(this.$selectedVersions.val());

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

    this.$selectedVersions.remove();
  },
  createTranslationInProgressWarning: function() {
    const message =
        'Some of entries are already in other translation job. Possible unexpected behaviour here. <br /><br /> ' +
        'Please check all entries with <span class="info warning"></span> sign before submit';

    this.translationInProgressWarning = jQuery('<div />').
        addClass('meta').
        addClass('read-only').
        addClass('warning');

    const label = jQuery('<label />').
        html(message);

    this.translationInProgressWarning.append(label);
    this.translationInProgressWarning.hide();

    jQuery('#details-container #details').
        prepend(this.translationInProgressWarning);
  },
  onUpdateElements: function() {
    this.preloadVersions();

    if (Craft.elementIndex === null) {
      this.translationInProgressWarning.hide();

      return;
    }

    if(Craft.elementIndex.view === null) {
      return;
    }

    const elements = Craft.elementIndex.view.getAllElements().get();

    let hasActiveJobAttributeValues = [];

    elements.forEach((element) => {
      const elementId = jQuery(element).data('id');
      let hiddenInput = $(`#hidden-input-element-draft-${elementId}`);

      if (this.$container.data('job-status') === 'new' ||
          this.$container.data('job-status') === 'draft') {
        const title = jQuery(element).find('th');
        const titleDiv = jQuery(element).find('th div');

        const hasWarning = (titleDiv.length > 0 &&
            titleDiv.data('has-active-lilt-job') === 1);
        hasActiveJobAttributeValues[jQuery(element).data('id')] = hasWarning;

        if (hasWarning) {
          const url = titleDiv.data('active-lilt-job-url');

          const span = jQuery(
              '<span class="info warning lilt-warning-span-centred"></span>');

          span.on('click', function() {
            new Garnish.HUD(span,
                `This entry already in translation. See <a href="${url}" target="_blank">list of jobs</a>`,
                {
                  orientations: ['top', 'bottom', 'right', 'left'],
                });
          });

          title.append(span);
        }
      }

      if (hiddenInput.length > 0) {
        const value = {
          elementId: parseInt(elementId), draftId: parseInt(hiddenInput.val()),
        };
        $(`#entries-to-translate-field tr[data-id="${elementId}"]`).
            find('select').
            val(btoa(JSON.stringify(value)));
      }
    });

    if (hasActiveJobAttributeValues.indexOf(true) !== -1) {
      this.translationInProgressWarning.show();
    } else {
      this.translationInProgressWarning.hide();
    }

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
  onSubmit: function() {
    const $spinner = $(
        '<div class="spinner flex" style="margin-right: 10px"></div>');

    this.$actionButton.prepend($spinner);
    this.$actionButton.addClass('disabled');
  },
  onRemoveEntry: function() {
    let remove = Craft.elementIndex.getSelectedElementIds();

    remove = remove.map((element) => element.toString());

    const currentValue = this.$selectedEntries.val();
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

    alreadySelected = alreadySelected.filter((el) => {
      return remove.indexOf(el.toString()) === -1;
    });

    if (alreadySelected.length === 0) {
      Craft.elementIndex = null;
      $('#entries-to-translate-field div.elements').html('');
      $('#entries-to-translate').hide();
    }

    $('#entries-remove-action').css('visibility', 'hidden');

    this.$selectedEntries.
        val(JSON.stringify(alreadySelected));

    if (alreadySelected.length > 0) {
      Craft.elementIndex.settings.criteria = {
        siteId: $('#sourceSite').val(), where: {
          'elements.id': alreadySelected,
        },
      };
      Craft.elementIndex.updateElements();
    }

    this.preloadVersions();
    this.onUpdateElements();
  },
});

/** Init form#create-job-form as JobForm */
$(document).ready(function() {
  CraftliltPlugin.jobForm = new CraftliltPlugin.JobForm('form#create-job-form');
});
