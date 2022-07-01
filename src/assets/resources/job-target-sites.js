CraftliltPlugin.TargetSites = Garnish.Base.extend({
  $container: null,
  $selectAllContainer: null,
  $selectAllCheckbox: null,
  $searchBox: null,
  $clearSearchBtn: null,
  $allCheckboxes: null,
  $elements: null,
  $elementsContainer: null,

  init: function(container, settings) {
    this.$container = jQuery(container);
    this.setSettings(settings, Craft.Grid.defaults);

    if (this.$container.data('target-sites')) {
      console.warn('Double-instantiating a target-sites on an element');
      this.$container.data('target-sites').destroy();
    }
    this._initElements();

    this.updateState();
    this.sortElements();
    this.disableSourceSite();
  },
  _initElements: function() {
    this.$container.data('target-sites', this);
    this.$selectAllContainer = this.$container.find(
        '.selectallcontainer:first');
    this.$selectAllCheckbox = this.$container.find(
        '.selectallcontainer:first .selectallcheckbox');
    this.$allCheckboxes = this.$container.find(
        '.checkbox:not(.selectallcheckbox)');
    this.$elements = this.$container.find('tbody tr');
    this.$elementsContainer = this.$container.find('tbody');
    this.$searchBox = this.$container.find('#search');
    this.$clearSearchBtn = this.$container.find('.search .clear');

    this._initListeners();
  },
  _initListeners: function() {
    this.addListener(this.$allCheckboxes, 'click', (e) => {
      if (jQuery(e.target).parent().parent().hasClass('disabled')) {
        return false;
      }
      this.updateState();
    });

    this.addListener(this.$clearSearchBtn, 'click', () => {
      this.$searchBox.val('');
      this.search();
    });

    this.addListener(this.$searchBox, 'keyup', this.search);

    this.addListener(this.settings.$sourceSiteField, 'change', () => {
      this.disableSourceSite();
    });

    this.addListener(this.$selectAllContainer, 'click', () => {

      if (this.$selectAllCheckbox.hasClass('indeterminate')) {
        this.deselectAll();
        this.$selectAllCheckbox.removeClass('indeterminate');
        return;
      }

      this.$selectAllCheckbox.prop('checked')
          ? this.deselectAll()
          : this.selectAll();
    });
  },
  getSourceSiteValue: function() {
    return this.settings.$sourceSiteField.val();
  },
  disableSourceSite: function() {
    const siteId = this.getSourceSiteValue();

    this.$container.find('tr').removeClass('disabled');
    this.$container.find(`tr[data-id="${siteId}"]`).addClass('disabled');
    this.$container.find(`tr[data-id="${siteId}"] input[type="checkbox"]`).
        prop('checked', false);
  },
  updateState: function() {
    const selectedElementsLength = this.getSelectedElements().length;

    if (selectedElementsLength === this.$allCheckboxes.length) {
      this.$selectAllCheckbox.removeClass('indeterminate');
      this.$selectAllCheckbox.prop('checked', true);
      this.$selectAllCheckbox.addClass('checked');

      return;
    }

    if (selectedElementsLength === 0) {
      this.$selectAllCheckbox.prop('checked', false);
      this.$selectAllCheckbox.removeClass('indeterminate');
      this.$selectAllCheckbox.removeClass('checked');

      return;
    }

    this.$selectAllCheckbox.prop('checked', false);
    this.$selectAllCheckbox.addClass('indeterminate');
  },
  getSelectedElements: function() {
    return this.$container.find('.checkbox:checked:not(.selectallcheckbox)');
  },
  getSelectedElementIds: function() {
    let selectedElementIds = [];

    this.getSelectedElements().map((index, value) => {
      selectedElementIds.push(jQuery(value).data('site-id'));
    });

    return selectedElementIds;
  },
  sortElements: function() {
    this.$elements.each((index, element) => {
      if (jQuery(element).find('.checkbox').prop('checked')) {
        this.$elementsContainer.prepend(element);
      }
    });
  },
  search: function() {
    const query = this.$searchBox.val().toLowerCase();

    if (query === '') {
      this.$elements.show();
      this.sortElements();

      return;
    }

    this.$elements.each((index, value) => {
      const element = jQuery(value);

      if (element.data('language').toLowerCase().indexOf(query) === -1
          && element.data('name').toLowerCase().indexOf(query) === -1) {
        element.hide();

        return;
      }

      element.show();

    });
  },
  selectAll: function() {
    this.$selectAllCheckbox.prop('checked', true);
    this.$selectAllCheckbox.addClass('checked');

    this.$allCheckboxes.each((index, element) => {
      if (jQuery(element).parent().parent().hasClass('disabled')) {
        return;
      }
      jQuery(element).addClass('checked');
      jQuery(element).prop('checked', true);
    });
  },
  deselectAll: function() {
    this.$selectAllCheckbox.prop('checked', false);
    this.$selectAllCheckbox.removeClass('checked');

    this.$allCheckboxes.each((index, element) => {
      if (jQuery(element).parent().parent().hasClass('disabled')) {
        return;
      }
      jQuery(element).removeClass('checked');
      jQuery(element).prop('checked', false);
    });
  },
});

jQuery(document).ready(function() {
  CraftliltPlugin.targetSites = new CraftliltPlugin.TargetSites(
      '#lilt-target-sites',
      {
        $sourceSiteField: jQuery('#sourceSite-field select'),
      },
  );
});
