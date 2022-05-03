Craft.LiltElementIndex = Craft.BaseElementIndex.extend({
    initSources: function() {
        // The source selector
        if (!this.sourceSelect) {
            this.sourceSelect = new Garnish.Select(this.$sidebar.find('nav'), {
                multi: false,
                allowEmpty: false,
                vertical: true,
                onSelectionChange: this._handleSourceSelectionChange.bind(this),
            });
        }

        this.sourcesByKey = {};

        return true;
    },
    getDefaultSort: function() {
        return ['elements.dateCreated', 'desc'];
    },
    setStoredSortOptionsForSource: function() {
        // Default to whatever's first
        this.setSortAttribute();
        this.setSortDirection('asc');

        var sortAttr = this.getSelectedSourceState('order'),
            sortDir = this.getSelectedSourceState('sort');

        if (!sortAttr) {
            // Get the default
            sortAttr = this.getDefaultSort();

            if (Garnish.isArray(sortAttr)) {
                sortDir = sortAttr[1];
                sortAttr = sortAttr[0];
            }
        }

        if (sortDir !== 'asc' && sortDir !== 'desc') {
            sortDir = 'asc';
        }

        this.setSortAttribute(sortAttr);
        this.setSortDirection(sortDir);
    },
    selectSource: function($source) {
        if (!$source || !$source.length) {
            return false;
        }

        if (this.$source && this.$source[0] && this.$source[0] === $source[0] &&
            $source.data('key') === this.sourceKey) {
            return false;
        }

        // Hide action triggers if they're currently being shown
        this.hideActionTriggers();

        this.$source = $source;
        this.sourceKey = $source.data('key');
        this.setInstanceState('selectedSource', this.sourceKey);
        this.sourceSelect.selectItem($source);

        Craft.cp.updateSidebarMenuLabel();

        if (this.searching) {
            // Clear the search value without causing it to update elements
            this.searchText = null;
            this.$search.val('');
            this.stopSearching();
        }

        // Sort menu
        // ----------------------------------------------------------------------

        // Remove any existing custom sort options from the menu
        //this.$sortAttributesList.children('li[data-extra]').remove();

        // Does this source have any custom sort options?
        let $topSource = this.$source.closest('nav > ul > li').children('a');
        let sortOptions = $topSource.data('sort-options');
        if (sortOptions) {
            for (let i = 0; i < sortOptions.length; i++) {
                let $option = $('<li/>', {
                    'data-extra': true,
                }).append(
                    $('<a/>', {
                        text: sortOptions[i][0],
                        'data-attr': sortOptions[i][1],
                    }),
                ).appendTo(this.$sortAttributesList);
                //this.sortMenu.addOptions($option.children());
            }
        }

        // Does this source have a structure?
        if (Garnish.hasAttr(this.$source, 'data-has-structure')) {
            if (!this.$structureSortAttribute) {
                this.$structureSortAttribute = $(
                    '<li><a data-attr="structure">' + Craft.t('app', 'Structure') +
                    '</a></li>');
                this.sortMenu.addOptions(this.$structureSortAttribute.children());
            }

            this.$structureSortAttribute.prependTo(this.$sortAttributesList);
        } else if (this.$structureSortAttribute) {
            this.$structureSortAttribute.removeClass('sel').detach();
        }

        this.setStoredSortOptionsForSource();

        // Status menu
        // ----------------------------------------------------------------------

        if (this.$statusMenuBtn.length) {
            if (Garnish.hasAttr(this.$source, 'data-override-status')) {
                this.$statusMenuContainer.addClass('hidden');
            } else {
                this.$statusMenuContainer.removeClass('hidden');
            }

            if (this.trashed) {
                // Swap to the initial status
                var $firstOption = this.statusMenu.$options.first();
                this.setStatus($firstOption.data('status'));
            }
        }

        // View mode buttons
        // ----------------------------------------------------------------------

        // Clear out any previous view mode data
        if (this.$viewModeBtnContainer) {
            this.$viewModeBtnContainer.remove();
        }

        this.viewModeBtns = {};
        this.viewMode = null;

        // Get the new list of view modes
        this.sourceViewModes = this.getViewModesForSource();

        // Create the buttons if there's more than one mode available to this source
        if (this.sourceViewModes.length > 1) {
            this.$viewModeBtnContainer = $('<div class="btngroup"/>').
                appendTo(this.$toolbar);

            for (var i = 0; i < this.sourceViewModes.length; i++) {
                let sourceViewMode = this.sourceViewModes[i];

                let $viewModeBtn = $('<button/>', {
                    type: 'button',
                    class: 'btn' + (typeof sourceViewMode.className !== 'undefined'
                        ? ` ${sourceViewMode.className}`
                        : ''),
                    'data-view': sourceViewMode.mode,
                    'data-icon': sourceViewMode.icon,
                    'aria-label': sourceViewMode.title,
                    title: sourceViewMode.title,
                }).appendTo(this.$viewModeBtnContainer);

                this.viewModeBtns[sourceViewMode.mode] = $viewModeBtn;

                this.addListener($viewModeBtn, 'click', {mode: sourceViewMode.mode},
                    function(ev) {
                        this.selectViewMode(ev.data.mode);
                        this.updateElements();
                    });
            }
        }

        // Figure out which mode we should start with
        var viewMode = this.getSelectedViewMode();

        if (!viewMode || !this.doesSourceHaveViewMode(viewMode)) {
            // Try to keep using the current view mode
            if (this.viewMode && this.doesSourceHaveViewMode(this.viewMode)) {
                viewMode = this.viewMode;
            }
            // Just use the first one
            else {
                viewMode = this.sourceViewModes[0].mode;
            }
        }

        this.selectViewMode(viewMode);

        this.onSelectSource();

        return true;
    },
    getSortAttributeOption: function(attr) {
        return [];
    },
    setSortDirection: function(dir) {
        if (dir !== 'desc') {
            dir = 'asc';
        }

        this.$sortMenuBtn.attr('data-icon', dir);
        //this.$sortDirectionsList.find('a.sel').removeClass('sel');
        //this.getSortDirectionOption(dir).addClass('sel');
    },
    getSelectedSortAttribute: function() {
        return [];
    },
    getSelectedSortDirection: function() {
        return [];
    },
    getSortDirectionOption: function(dir) {
        return [];
    },
});
