$('#lilt-translations-table tbody tr td .lilt-review-translation:not(.disabled)').on('click', function() {

  const translationId = $(this).data('id');
  const translationTitle = $(this).data('title');

  let $modalElement = $('<div id="lilt-preview-modal" />').
      addClass('modal').
      addClass('elementselectormodal');
  let $modalElementBody = $('<div id="lilt-preview-modal-body" />').
      addClass('body');
  let $modalElementSpinner = $(
      '<div id="lilt-preview-modal-body-spinner" style="margin-left: 10px"/>').
      addClass('spinner').
      addClass('big');

  let $modalFooter = $('<div />').
      addClass('footer');
  let $modalFooterSpinner = $('<div />').
      addClass('spinner').
      addClass('hidden');

  let $modalFooterButtons = $('<div />').
      addClass('buttons').
      addClass('right');

  let $modalFooterButtonsCancel = $(
      ' <button type="button" class="btn" tabindex="0">Cancel</button>');


  let $modalFooterButtonsSubmit = $(
      '<button type="button" data-translation-id="'+translationId+'" class="btn submit">Submit review</button>\n');

  let $modalFooterButtonsPublish = $(
      '<button type="button" data-translation-id="'+translationId+'" class="btn submit">Publish changes</button>\n');


  $modalFooterButtonsSubmit.on('click',
      function() {
        $modalFooterSpinner.removeClass('hidden');
        $(this).disable();

        Craft.sendActionRequest(
            'POST',
            'craft-lilt-plugin/job/post-translation-review/invoke',
            {params: {translationId: translationId, reviewed: true}}
        ).then(response => {
          $modalElementSpinner.hide();
          Craft.cp.displayNotice(Craft.t('app', 'Review complete'));
          location.reload();
        }).catch(exception => {
          Craft.cp.displayError(Craft.t('app', 'Can\'t submit review, unexpected issue occurred'));
          $(this).enable();
          $modalFooterSpinner.addClass('hidden');
        })
      }
  );

  $modalFooterButtonsPublish.on('click',
      function() {
        $modalFooterSpinner.removeClass('hidden');
        $(this).disable();

        Craft.sendActionRequest(
            'POST',
            'craft-lilt-plugin/job/post-translation-publish/invoke',
            {params: {translationId: translationId, reviewed: true}}
        ).then(response => {
          $modalElementSpinner.hide();
          Craft.cp.displayNotice(Craft.t('app', 'Review complete'));
          location.reload();
        }).catch(exception => {
          Craft.cp.displayError(Craft.t('app', 'Can\'t submit review, unexpected issue occurred'));
          $(this).enable();
          $modalFooterSpinner.addClass('hidden');
        })
      }
  );

  $modalFooterButtons.append($modalFooterButtonsPublish);
  $modalFooterButtons.append($modalFooterButtonsSubmit);
  $modalFooterButtons.append($modalFooterButtonsCancel);

  $modalFooter.append($modalFooterSpinner);
  $modalFooter.append($modalFooterButtons);

  const headerText = '<header><h1>Review: ' + translationTitle + ' <span class="close-modal" data-icon="remove" style="float: right;cursor: pointer"></span></h1></header>';
  let $modalElementBodyHeader = $(headerText).addClass('header');

  $modalElementBody.append(
      $modalElementSpinner,
  );
  $modalElement.append(
      $modalElementBodyHeader,
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

  Craft.sendActionRequest(
      'GET',
      'craft-lilt-plugin/job/get-translation-review/invoke',
      {params: {translationId: translationId}}
  ).then(response => {
    $modalElementSpinner.hide();
    $modalElementBody.append($(response.data.html));

    $('#lilt-modal-preview-tabs nav ul li a').on('click', function() {
      const idToShow = $(this).data('id');
      $('#lilt-modal-preview-tabs nav ul li a').each(function() {

        console.log(idToShow, $(this).data('id'), $(this).data('id') !== idToShow, $(this).data('id') === idToShow);
        const currentId = $(this).data('id');
        if(currentId !== idToShow) {
          $(this).removeClass('sel');
          $(`#lilt-modal-preview-tabs #${currentId}`).addClass('hidden');
        }

        if(currentId === idToShow) {
          $(this).addClass('sel');
          $(`#lilt-modal-preview-tabs #${idToShow}`).removeClass('hidden');
        }
      })
    })

  }).catch(exception => {
    Craft.cp.displayError(Craft.t('app', 'Can\'t build preview, unexpected issue occurred'));
    $modal.hide();
  })

  $('#lilt-preview-modal .close-modal').on('click', function() {
    $modal.hide();
  });

  $modalFooterButtonsCancel.on('click', function() {
    $modal.hide();
  });
  return false;

});

/*
$('#lilt-translations-table tr a').on('click', function() {
  window.open(($(this).attr('href')));
  return false;
})
 */