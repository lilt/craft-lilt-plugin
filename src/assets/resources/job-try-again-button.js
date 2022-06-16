CraftliltPlugin.JobTryAgainButton = Garnish.Base.extend({
  $container: null,
  $spinner: null,

  init: function(container, settings) {
    this.$container = $(container);
    this.setSettings(settings, {});

    if (this.$container.data('job-try-again-button')) {
      console.warn('Double-instantiating a job-try-again-button on an element');
      this.$container.data('job-try-again-button').destroy();
    }
    this.$container.data('job-try-again-button', this);

    this.addListener(this.$container, 'click', () => {
      if (this.isDisabled()) {
        return;
      }
      this.process();
    });
  },
  process: function() {
    this._disable();
    this._addSpinner();

    const jobId = this._getJobId();

    Craft.sendActionRequest('POST',
        'craft-lilt-plugin/job/post-job-retry/invoke',
        {data: {jobIds: [jobId]}}).then(response => {
      location.reload();
    }).catch(exception => {
      Craft.cp.displayError(
          Craft.t('app', 'Can\'t submit review, unexpected issue occurred'));
      this.$spinner.remove();
      $(this).removeClass('disabled');
    });
  },
  _getJobId: function() {
    return this.$container.data('job-id');
  },
  _addSpinner: function() {
    this.$spinner = $(
        '<div class="spinner flex" style="margin-right: 10px; float: right"></div>');
    this.$container.parent().prepend(this.$spinner);
  },
  _disable: function() {
    this.$container.addClass('disabled');
  },
  isDisabled: function() {
    return this.$container.hasClass('disabled');
  },
});

/** Init #lilt-try-again-sync as JobTryAgainButton */
$(document).ready(function() {
  const selector = '#lilt-try-again-sync';
  if ($(selector).length === 0) {
    return;
  }
  CraftliltPlugin.jobTryAgainButton = new CraftliltPlugin.JobTryAgainButton(
      selector);
});
