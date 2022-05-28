$(document).ready(
    function() {

      const liltSyncJobs = $('#lilt-sync-jobs');

      Craft.elementIndex.on('selectionChange', function() {
        if (Craft.elementIndex.getSelectedElementIds().length > 0) {
          liltSyncJobs.removeClass('disabled');
        }

        if (Craft.elementIndex.getSelectedElementIds().length === 0) {
          liltSyncJobs.addClass('disabled');
        }
      });

      liltSyncJobs.on('click', function() {
        if ($(this).hasClass('disabled')) {
          return;
        }

        const selectedJobIds = Craft.elementIndex.getSelectedElementIds();

        if (selectedJobIds.length === 0) {
          return;
        }

        if ($(this).hasClass('disabled')) {
          return;
        }
        const $spinner = $('<div class="spinner"></div>');
        $(this).parent().prepend($spinner);
        $(this).addClass('disabled');

        Craft.sendActionRequest(
            'POST',
            'craft-lilt-plugin/job/post-sync/invoke',
            {data: {jobIds: selectedJobIds}},
        ).then(response => {

          Craft.elementIndex.updateElements();
          $spinner.remove();
          Craft.cp.displayNotice(Craft.t('app', 'Sync complete'));
        }).catch(exception => {
          Craft.elementIndex.updateElements();

          Craft.cp.displayError(Craft.t('app',
              'Can\'t sync, unexpected issue occurred'));
          $spinner.remove();
        });
      });
    },
);