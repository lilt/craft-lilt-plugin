CraftliltPlugin.JobSyncButton = Garnish.Base.extend({
  /**
   * JobSyncButton needed to add functionality to sync button on jobs overview page
   */
  $container: null,
  $_spinner: null,

  init: function(container, settings) {
    this.$container = $(container);
    this.setSettings(settings, {});

    if (this.$container.data('job-sync-button')) {
      console.warn('Double-instantiating a job-sync-button on an element');
      this.$container.data('job-sync-button').destroy();
    }
    this.$container.data('job-sync-button', this);

    this._registerOnClick()
    this._registerElementIndexChange()
  },
  _registerElementIndexChange: function() {
    Craft.elementIndex.on('selectionChange', () => {
      if (Craft.elementIndex.getSelectedElementIds().length > 0) {
        this.$container.removeClass('disabled');
      }

      if (Craft.elementIndex.getSelectedElementIds().length === 0) {
        this.$container.addClass('disabled');
      }
    });
  },
  _registerOnClick: function() {
    this.addListener(this.$container, 'click', () => {
      if (this.$container.hasClass('disabled')) {
        return;
      }

      const selectedJobIds = Craft.elementIndex.getSelectedElementIds();
      if (selectedJobIds.length === 0) {
        return;
      }

      this.sync(selectedJobIds)
    })
  },
  sync: function(selectedJobIds) {
    this.processing();

    Craft.sendActionRequest(
        'POST',
        'craft-lilt-plugin/job/post-sync/invoke',
        {data: {jobIds: selectedJobIds}},
    ).then(response => {
      this.done();
      Craft.cp.displayNotice(Craft.t('app', 'Sync complete'));
    }).catch(exception => {
      this.done();
      Craft.cp.displayError(Craft.t('app',
          'Can\'t sync, unexpected issue occurred'));

    });
  },
  processing: function() {
    this.$container.addClass('disabled');

    this.$_spinner = $('<div class="spinner"></div>');
    this.$container.parent().prepend(this.$_spinner);
  },
  done: function() {
    Craft.elementIndex.updateElements();
    this.$_spinner.remove();
    Craft.elementIndex.trigger('selectionChange')
  },
});

/** Init #lilt-sync-jobs as JobSyncButton */
$(document).ready(
    function() {
      CraftliltPlugin.jobSyncButton = new CraftliltPlugin.JobSyncButton(
          $('#lilt-sync-jobs'),
      );
    },
);
