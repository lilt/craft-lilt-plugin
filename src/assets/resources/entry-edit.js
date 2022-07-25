CraftliltPlugin.EntryEditWarning = Garnish.Base.extend({
  init: function(config) {
    if (config === undefined
        || config.translationInProgress === undefined
        || config.jobs === undefined
        || config.translationInProgress === false) {
      return;
    }

    const url = Craft.getUrl('admin/craft-lilt-plugin/jobs', {
      'elementIds[]': [config.jobs],
      'statuses[0]': ['failed'],
      'statuses[1]': ['in-progress'],
      'statuses[2]': ['ready-for-review'],
      'statuses[3]': ['ready-to-publish'],
    });

    const container = jQuery('<div />').
        addClass('meta').
        addClass('read-only').
        addClass('warning');

    const label = jQuery('<label />').
        html(
            'Current entry is in translation, changes can affect translation');

    const button = jQuery('<a />').
        addClass('btn').
        attr('href', url).
        attr('target', '_blank').
        css('margin-top', '10px').
        html('View translation jobs');

    const buttonContainer = jQuery('<div />');
    buttonContainer.append(button);

    container.append(label);
    container.append(buttonContainer);

    jQuery('#details-container #details').prepend(container);
  },
});