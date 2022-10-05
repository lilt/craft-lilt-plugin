//src/web/assets/cp/src/js/BaseElementIndex.js:3.7.0
CraftliltPlugin.PreviewTranslationsIndex = Craft.BaseElementIndex.extend({
  $publishTrigger: null,
  $reviewTrigger: null,

  showActionTriggers: function() {
    // Ignore if they're already shown
    if (this.showingActionTriggers) {
      return;
    }

    // Hard-code the min toolbar height in case it was taller than the actions toolbar
    // (prevents the elements from jumping if this ends up being a double-click)
    this.$toolbar.css('min-height', this.$toolbar.height());

    // Hide any toolbar inputs
    this._$detachedToolbarItems = this.$toolbar.children();
    this._$detachedToolbarItems.detach();

    this._$triggers = jQuery('<div id="translations-review-triggers" class="toolbar flex flex-nowrap"></div>');

    this.$reviewTrigger = jQuery('<div id="translations-review-action" data-icon="view" class="btn" style="float:right">' +
        '            Review changes' +
        '        </div>');
    this.$publishTrigger = jQuery('<div id="translations-publish-action" data-icon="check" class="btn submit" style="float:right">' +
        'Publish' +
        '</div>');

    this.$reviewTrigger.appendTo(this._$triggers)
    this.$publishTrigger.appendTo(this._$triggers)

    this.$reviewTrigger.on('click', () => {
      this.onReviewTriggered()
    })

    this.$publishTrigger.on('click', () => {
      this.onPublishTriggered()
    })

    this._$triggers.appendTo(this.$toolbar)
    this.showingActionTriggers = true;
  },

  onReviewTriggered: function() {
    this.settings.onReviewTriggered();
    this.trigger('onReviewTriggered');
  },
  onPublishTriggered: function() {
    this.settings.onPublishTriggered();
    this.trigger('onPublishTriggered');
  },
});