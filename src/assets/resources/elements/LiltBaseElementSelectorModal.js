Craft.LiltBaseElementSelectorModal = Craft.BaseElementSelectorModal.extend({
  _createElementIndex: function() {
    // Get the modal body HTML based on the settings
    var data = {
      context: 'modal',
      elementType: this.elementType,
      sources: this.settings.sources,
    };

    if (this.settings.showSiteMenu !== null && this.settings.showSiteMenu !==
        'auto') {
      data.showSiteMenu = this.settings.showSiteMenu ? '1' : '0';
    }

    Craft.postActionRequest('elements/get-modal-body', data,
        (response, textStatus) => {
          if (textStatus === 'success') {
            this.$body.html(response.html);

            if (this.$body.has('.sidebar:not(.hidden)').length) {
              this.$body.addClass('has-sidebar');
            }

            const displayWarnings = () => {
              const elements = this.elementIndex.view.getAllElements().get();
              elements.forEach((element) => {

                const title = jQuery(element).find('th');
                const titleDiv = jQuery(element).find('th div');

                const hasWarning = (titleDiv.length > 0 &&
                    titleDiv.data('has-active-lilt-job') === 1);

                if (hasWarning) {
                  const url = titleDiv.data('active-lilt-job-url');

                  const span = jQuery(
                      '<span class="info warning lilt-warning-span-centred"></span>');

                  span.on('click', function() {
                    new Garnish.HUD(span,
                        `This entry already in translation. See <a href="${url}" target="_blank">list of jobs</a>`,
                        {
                          orientations: [
                            'top',
                            'bottom',
                            'right',
                            'left'],
                        });
                  });

                  title.append(span);
                }
              });
            };
            // Initialize the element index
            this.elementIndex = Craft.createElementIndex(this.elementType,
                this.$body, {
                  context: 'modal',
                  modal: this,
                  storageKey: this.settings.storageKey,
                  criteria: this.settings.criteria,
                  disabledElementIds: this.settings.disabledElementIds,
                  selectable: true,
                  multiSelect: this.settings.multiSelect,
                  buttonContainer: this.$secondaryButtons,
                  onSelectionChange: this.onSelectionChange.bind(this),
                  hideSidebar: this.settings.hideSidebar,
                  defaultSiteId: this.settings.defaultSiteId,
                  defaultSource: this.settings.defaultSource,
                  onAppendElements: displayWarnings,
                  onUpdateElements: displayWarnings,
                });

            // Double-clicking or double-tapping should select the elements
            this.addListener(this.elementIndex.$elements, 'doubletap',
                function(ev, touchData) {
                  // Make sure the touch targets are the same
                  // (they may be different if Command/Ctrl/Shift-clicking on multiple elements quickly)
                  if (touchData.firstTap.target ===
                      touchData.secondTap.target) {
                    this.selectElements();
                  }
                });
          }
        });
  },
}, {
  defaults: {
    resizable: true,
    storageKey: null,
    sources: null,
    criteria: null,
    multiSelect: false,
    showSiteMenu: null,
    disabledElementIds: [],
    disableElementsOnSelect: false,
    hideOnSelect: true,
    onCancel: $.noop,
    onSelect: $.noop,
    hideSidebar: false,
    defaultSiteId: null,
    defaultSource: null,
  },
});

Craft.createLiltElementSelectorModal = function(elementType, settings) {
  var func = Craft.LiltBaseElementSelectorModal;

  return new func(elementType, settings);
};
