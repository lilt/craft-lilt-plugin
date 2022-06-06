$(document).ready(function() {
      if (document.location.pathname.indexOf('/admin/entries') === 0) {
        var $btngroup = $('<div>', {'class': 'btngroup left'}).
            css('margin-right', '5px');
        var $button = $('<button>', {'class': 'btn', 'type': 'button'});
        $button.appendTo($btngroup);
        $button.html('Translate');

        $button.attr('data-icon', 'language');
        $button.addClass('disabled');

        $button.on('click', function() {
          if (!$(this).hasClass('disabled')) {
            const selectedElements = Craft.elementIndex.getSelectedElementIds();
            let queryParams = {};
            selectedElements.forEach(function(value, i) {
              queryParams['elementIds[' + i + ']'] = value;
            });

            queryParams.sourceSiteId = Craft.elementIndex.siteId;

            document.location.href = Craft.getCpUrl(
                'craft-lilt-plugin/job/create',
                queryParams,
            );
          }
        });

        $btngroup.prependTo('#header #action-button');

        console.log(Craft.elementIndex);

        Craft.elementIndex.on('selectionChange', function(e) {
          if (Craft.elementIndex.getSelectedElementIds().length > 0) {
            $button.removeClass('disabled');
          }
        });
      }
    },
);
