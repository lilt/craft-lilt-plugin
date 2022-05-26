const showModal = function() {
  const hideFooter = false;

  const currentElementId = $(this).attr('id');
  let selectedValues = [];

  let translationId = $(this).data('id');
  let translationTitle = $(this).data('title');

  let isMultiView = false;

  let next = null;
  let previous = null;
  let current = 0;

  function showTranslationModal(translationId) {
    Craft.sendActionRequest(
        'GET',
        'craft-lilt-plugin/job/get-translation-review/invoke',
        {params: {translationId: translationId}},
    ).then(response => {
      $modalElementSpinner.hide();
      $modalElementBody.append($(response.data.html));

      $('#lilt-modal-preview-tabs nav ul li a').on('click', function() {
        const idToShow = $(this).data('id');
        $('#lilt-modal-preview-tabs nav ul li a').each(function() {

          console.log(idToShow, $(this).data('id'),
              $(this).data('id') !== idToShow, $(this).data('id') === idToShow);
          const currentId = $(this).data('id');
          if (currentId !== idToShow) {
            $(this).removeClass('sel');
            $(`#lilt-modal-preview-tabs #${currentId}`).addClass('hidden');
          }

          if (currentId === idToShow) {
            $(this).addClass('sel');
            $(`#lilt-modal-preview-tabs #${idToShow}`).removeClass('hidden');
          }
        });
      });

    }).catch(exception => {
      Craft.cp.displayError(
          Craft.t('app', 'Can\'t build preview, unexpected issue occurred'));
      $modal.hide();
    });
  }

  if (currentElementId !== 'translations-review-action') {
    translationId = $(this).data('id');
    translationTitle = $(this).data('title');
  }

  if (currentElementId === 'translations-review-action') {
    $('#lilt-translations-table tr td input.checkbox').each(function() {
      if ($(this).prop('checked') === true) {
        selectedValues.push($(this).val());
      }
    });

    if (selectedValues.length === 0) {
      return;
    }

    translationId = selectedValues[current];
    translationTitle = $(
        '#lilt-translations-table tr[data-id="' + selectedValues[current] +
        '"]').data('title');

    isMultiView = selectedValues.length > 1;

    next = current + 1;
  }

  let $modalElement = $('<div id="lilt-preview-modal" />').
      addClass('modal').
      addClass('elementselectormodal').
      css('padding-bottom', 0)
  ;

  let $modalElementBody = $('<div id="lilt-preview-modal-body" />').
      addClass('body');

  if (hideFooter) {
    $modalElement.addClass('hidden-footer');
  }

  let $modalElementSpinner = $(
      '<div id="lilt-preview-modal-body-spinner" style="margin-left: 10px"/>').
      addClass('spinner').
      addClass('big');

  let $modalFooter = $('<div />').
      addClass('footer');

  let $modalActionsSpinner = $('<div />').
      addClass('spinner').
      addClass('hidden');

  let $modalActionButtons = $('<div />').
      addClass('buttons').
      addClass('right');

  let $modalFooterRightActionButtons = $('<div />').
      addClass('buttons').
      addClass('right');

  let $modalFooterLeftActionButtons = $('<div />').
      addClass('buttons').
      addClass('left');

  let $modalFooterNext = $(
      ' <button type="button" class="btn" tabindex="0">Next <span style="margin-left: 10px" data-icon="rarr"></span></button>');

  let $modalFooterPrevious = $(
      ' <button type="button" class="btn" tabindex="0"><span style="margin-right: 10px" data-icon="larr"></span> Previous</button>');

  $modalFooterPrevious.attr('disabled', true);
  $modalFooterPrevious.addClass('disabled');

  $modalFooterNext.on('click', function() {
    $modalElementBody.html('');
    $modalElementBody.append(
        $modalElementSpinner,
    );
    $modalElementSpinner.show();

    showTranslationModal(selectedValues[next]);
    previous = current;
    current = next;
    next = current + 1;

    translationId = selectedValues[current];
    translationTitle = $(
        '#lilt-translations-table tr[data-id="' + translationId + '"]').
        data('title');
    $headerTitle.html(
        '<h1 style="float: left">Review: ' + translationTitle + '</h1>');

    console.log('previous', previous);
    console.log('current', current);
    console.log('next', next);

    if (selectedValues[previous] !== undefined) {
      $modalFooterPrevious.attr('disabled', false);
      $modalFooterPrevious.removeClass('disabled');
    }

    if (selectedValues.length <= next) {
      $modalFooterNext.attr('disabled', true);
      $modalFooterNext.addClass('disabled');
    }

  });

  $modalFooterPrevious.on('click', function() {
    $modalElementBody.html('');
    $modalElementBody.append(
        $modalElementSpinner,
    );
    $modalElementSpinner.show();

    showTranslationModal(selectedValues[previous]);
    next = current;
    current = previous;
    previous = current - 1;

    translationId = selectedValues[current];
    translationTitle = $(
        '#lilt-translations-table tr[data-id="' + translationId + '"]').
        data('title');

    $headerTitle.html(
        '<h1 style="float: left">Review: ' + translationTitle + '</h1>');

    console.log('previous', previous);
    console.log('current', current);
    console.log('next', next);

    if (selectedValues[previous] === undefined) {
      $modalFooterPrevious.attr('disabled', true);
      $modalFooterPrevious.addClass('disabled');

      return;
    }

    if (selectedValues[next] !== undefined) {
      $modalFooterNext.attr('disabled', false);
      $modalFooterNext.removeClass('disabled');
    }
  });

  if (!isMultiView) {
    $modalFooterNext.attr('disabled', true);
    $modalFooterNext.addClass('disabled');
    $modalFooterPrevious.attr('disabled', true);
    $modalFooterPrevious.addClass('disabled');
  }

  $modalFooterRightActionButtons.append($modalFooterNext);
  $modalFooterLeftActionButtons.append($modalFooterPrevious);

  let $modalFooterButtonsCancel = $(
      ' <button type="button" class="btn" tabindex="0">Cancel</button>');

  let $modalFooterButtonsSubmit = $(
      '<button type="button" data-translation-id="' + translationId +
      '" class="btn">Submit review</button>\n');

  let $modalFooterButtonsPublish = $(
      '<button type="button" data-translation-id="' + translationId +
      '" class="btn submit">Publish changes</button>\n');

  $modalFooterButtonsSubmit.on('click',
      function() {
        if ($(this).hasClass('disabled')) {
          return;
        }
        const $spinner = $('<div class="spinner"></div>');
        $(this).append($spinner);
        $(this).disable();
        $(this).addClass('disabled');

        Craft.sendActionRequest(
            'POST',
            'craft-lilt-plugin/job/post-translation-review/invoke',
            {params: {translationId: translationId, reviewed: true}},
        ).then(response => {
          $(this).enable();
          $(this).removeClass('disabled');
          $spinner.remove();

          Craft.cp.displayNotice(Craft.t('app', 'Review complete'));
          if (!isMultiView) {
            location.reload();
          }
        }).catch(exception => {
          Craft.cp.displayError(Craft.t('app',
              'Can\'t submit review, unexpected issue occurred'));
          $(this).enable();
          $modalActionsSpinner.addClass('hidden');
        });
      },
  );

  $modalFooterButtonsPublish.on('click',
      function() {
        if ($(this).hasClass('disabled')) {
          return;
        }

        const $spinner = $('<div class="spinner"></div>');
        $(this).append($spinner);
        $(this).addClass('disabled');
        $(this).disable();

        Craft.sendActionRequest(
            'POST',
            'craft-lilt-plugin/job/post-translation-publish/invoke',
            {params: {translationId: translationId, reviewed: true}},
        ).then(response => {
          Craft.cp.displayNotice(Craft.t('app', 'Translation published'));

          $(this).enable();
          $spinner.remove();
          $(this).removeClass('disabled');

          if (!isMultiView) {
            location.reload();
          }
        }).catch(exception => {
          Craft.cp.displayError(Craft.t('app',
              'Can\'t publish translation, unexpected issue occurred'));
          $(this).enable();
          $modalActionsSpinner.addClass('hidden');
        });
      },
  );
  $modalActionButtons.append($modalFooterButtonsSubmit);
  $modalActionButtons.append($modalFooterButtonsPublish);
  $modalActionButtons.append(
      '<span class="close-modal" data-icon="remove" style="padding: 5px;margin-left: 10px;float: right;cursor: pointer"></span>');

  $modalActionButtons.css('margin', 0);

  const header = $('<div />').addClass('header');
  let $headerTitle = $(
      '<h1 style="float: left">Review: ' + translationTitle + '</h1>');
  //<span class="close-modal" data-icon="remove" style="float: right;cursor: pointer"></span>

  header.append($headerTitle);
  header.append($modalActionButtons);

  const headerText = '<header></header>';
  //let $modalElementBodyHeader = $(headerText).addClass('header');

  //$modalActionButtons.append($modalFooterButtonsCancel);

  header.append($modalActionsSpinner);
  //$modalFooter.append($modalActionButtons);

  $modalFooter.append($modalFooterLeftActionButtons);
  $modalFooter.append($modalFooterRightActionButtons);

  $modalElementBody.append(
      $modalElementSpinner,
  );
  $modalElement.append(
      header,
  );
  $modalElement.append(
      $modalElementBody,
  );
  $modalElement.append(
      $modalFooter,
  );

  let $modal = new Garnish.Modal(
      $modalElement,
      {
        visible: false,
        resizable: true,
        desiredHeight: $(window).height(),
        desiredWidth: $(window).width(),
      },
  );

  $modal.desiredHeight = $(window).height();
  $modal.desiredWidth = $(window).width();

  Craft.modalForPreview = $modal;

  console.log($modal);

  showTranslationModal(
      translationId,
  );

  $('#lilt-preview-modal .close-modal').on('click', function() {

    $('#content-container #content #lilt-translations-table').addClass('disabled');
    $('#content-container #content').append(
        $('<div style="margin-left: 10px; position: absolute;top: 50%;left: 50%;margin: -24px 0 0 -24px;"/>').
            addClass('spinner').
            addClass('big').show(),
    );

    location.reload();
    $modal.hide();
  });

  $modalFooterButtonsCancel.on('click', function() {
    location.reload();
    $modal.hide();
  });
  return false;

};

$('#translations-review-action').on('click', showModal);
$('#lilt-translations-table tbody tr td .lilt-review-translation:not(.disabled)').
    on('click', showModal);

$('#lilt-translations-table th.checkbox-cell.selectallcontainer .checkbox').
    on('click', function() {
      const allChecked = $(this).prop('checked');
      $('#lilt-translations-table tr td input.checkbox').each(function() {
        const status = $('#lilt-translations-table tr[data-id="' + $(this).val() + '"]').data('status');
        if (status === 'published' || status === 'failed'|| status === 'new') {
          return;
        }
        $(this).prop('checked', allChecked);
      });
    });

$(document).ready(
    function() {
      $('#translations-review-action').
          addClass('disabled').
          attr('disabled', true);

      $('#lilt-translations-table tr').each(function() {
        const status = $(this).data('status');
        if (status === 'published' || status === 'failed'|| status === 'new') {
          $(this).find('.checkbox-cell').addClass('disabled').disable();
          $(this).find('.checkbox-cell').on('click', function(){return false;})
        }
      });

      $('#lilt-translations-table .checkbox').on('click', function() {
        let selectedValues = [];
        $('#lilt-translations-table tr td input.checkbox').each(function() {
          if ($(this).prop('checked') === true) {
            selectedValues.push($(this).val());
          }
        });

        if (selectedValues.length > 0) {
          $('#translations-review-action').
              removeClass('disabled').
              attr('disabled', false);

          return;
        }

        $('#translations-review-action').
            addClass('disabled').
            attr('disabled', true);
      });
    },
);

/*
$('#lilt-translations-table tr a').on('click', function() {
  window.open(($(this).attr('href')));
  return false;
})
 */