CraftliltPlugin.JobEditSyncButton = Garnish.Base.extend({
  /**
   * JobSyncButton needed to add functionality to sync button on jobs overview page
   */
  $container: null,
  $_spinner: null,

  init: function(container, settings) {
    this.$container = $(container);
    this.setSettings(settings, {});

    if (this.$container.data('job-edit-sync-button')) {
      console.warn('Double-instantiating a job-edit-sync-button on an element');
      this.$container.data('job-edit-sync-button').destroy();
    }
    this.$container.data('job-edit-sync-button', this);

    this._registerOnClick()
  },
  _registerOnClick: function() {
    this.addListener(this.$container, 'click', () => {
      if (this.$container.hasClass('disabled')) {
        return;
      }

      this.sync([this.$container.data('job-id')])
    })
  },
  sync: function(selectedJobIds) {
    this.processing();

    Craft.sendActionRequest(
        'POST',
        'craft-lilt-plugin/job/post-sync/invoke',
        {data: {jobIds: selectedJobIds}},
    ).then(response => {
      Craft.cp.displayNotice(Craft.t('app', 'Sync complete'));
      this.done();
      location.reload()
    }).catch(exception => {
      this.done();
      Craft.cp.displayError(Craft.t('app',
          'Can\'t sync, unexpected issue occurred'));

    });
  },
  processing: function() {
    this.$container.addClass('disabled');

    this.$_spinner = $('<div class="spinner" style="float: right"></div>');
    this.$container.parent().parent().prepend(this.$_spinner);
  },
  done: function() {
    this.$_spinner.remove();
    this.$container.removeClass('disabled');
  },
});

/** Init #lilt-sync-jobs as JobSyncButton */
$(document).ready(
    function() {
      CraftliltPlugin.jobSyncButton = new CraftliltPlugin.JobEditSyncButton(
          $('#lilt-job-edit-sync-button'),
      );
    },
);
