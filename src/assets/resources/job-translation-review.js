CraftliltPlugin.TranslationReview = Garnish.Base.extend({
  $modalElement: null,
  $modalElementBody: null,
  $modalElementSpinner: null,
  $modalFooter: null,
  $modalActionsSpinner: null,
  $modalActionButtons: null,
  $modalFooterRightActionButtons: null,
  $modalFooterLeftActionButtons: null,
  $modalFooterNext: null,
  $modalFooterPrevious: null,
  $modalFooterButtonsCancel: null,
  $modalFooterButtonsSubmit: null,
  $modalFooterButtonsPublish: null,
  $modal: null,

  next: null,
  previous: null,
  current: 0,
  translationId: null,
  isMultiView: false,
  selectedValues: [],
  modalForPreview: null,
  $headerTitle: null,

  $container: null,

  // Actions
  translationsReviewSelector: null,

  init: function(container, settings) {
    this.translationsReviewSelector = settings.translationsReviewSelector;
    this.translationReviewSelector = settings.translationReviewSelector;

    this.$container = $(container);
  },

  getTranslationData: function(translationId) {
    const selector = `#translations-element-index span.translation-status[data-id="${translationId}"]`;

    const translationRow = $(selector);

    const translationIsReviewed = translationRow.data('is-reviewed');
    const translationIsPublished = translationRow.data('is-published');
    const translationTitle = translationRow.data('title');

    return {
      translationId,
      translationTitle,
      translationIsPublished,
      translationIsReviewed,
    };
  },
  loadTranslationData: function(translationId) {
    this.translationId = translationId;

    Craft.sendActionRequest('GET',
        'craft-lilt-plugin/job/get-translation-review/invoke',
        {params: {translationId: translationId}}).then(response => {
      this.$modalElementSpinner.hide();
      this.$modalElementBody.append($(response.data));

      $('#lilt-modal-preview-tabs header a').on('click', function() {
        const idToShow = $(this).data('id');
        $('#lilt-modal-preview-tabs header a').each(function() {
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

      $('.lilt-text-copy-to-clipboard').on('click', function() {
        const key = $(this).prev().html();
        const original = $(`td[data-translation-key="${key}"][data-source="original"]`).
            text();
        const translated = $(`td[data-translation-key="${key}"][data-source="translated"]`).
            text();

        const textToCopy = `Key: ${key}\n\nContent: ${original}\n\nTranslated: ${translated}`;

        if (!navigator.clipboard) {
          const $temp = $('<div>');
          $('body').append($temp);
          $temp.attr('contenteditable', true).
              text(textToCopy).
              select().
              on('focus', function() {
                document.execCommand('selectAll', false, null);
              }).
              focus();
          document.execCommand('copy');
          Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
          $temp.remove();
        } else {
          navigator.clipboard.writeText(textToCopy).then(function() {
            Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
          }).catch(function() {
            Craft.cp.displayError(Craft.t('app', 'Error occurred.'));
          });
        }
      });

    }).catch(exception => {
      Craft.cp.displayError(
          Craft.t('app', 'Can\'t build preview, unexpected issue occurred'));
      this.$modal.hide();
    });

    const {
      translationIsReviewed, translationIsPublished,
    } = this.getTranslationData(translationId);

    if (translationIsReviewed === 1) {
      this.$modalFooterButtonsSubmit.addClass('disabled');
    } else {
      this.$modalFooterButtonsSubmit.removeClass('disabled');
    }

    if (translationIsPublished === 1) {
      this.$modalFooterButtonsPublish.addClass('disabled');
    } else {
      this.$modalFooterButtonsPublish.removeClass('disabled');
    }
  },
  showMultiModal: function(translationIds) {
    if (translationIds.length === 1) {
      this.next = null;
      this.previous = null;
      this.current = 0;
      this.translationId = null;
      this.isMultiView = false;
      this.selectedValues = [];
      this.modalForPreview = null;
      this.$headerTitle = null;

      this.showModal(translationIds.pop());

      return;
    }

    this.isMultiView = false;
    this.next = null;
    this.previous = null;
    this.current = 0;
    this.selectedValues = translationIds;
    this.isMultiView = translationIds.length > 1;
    this.next = this.current + 1;

    this.showModal(this.selectedValues[this.current]);
  },
  createModal: function() {
    const hideFooter = false;

    this.$modalElement = $('<div id="lilt-preview-modal" />').
        addClass('modal').
        addClass('elementselectormodal').
        css('padding-bottom', 0);

    this.$modalElementBody = $('<div id="lilt-preview-modal-body" />').
        addClass('body');

    if (hideFooter) {
      this.$modalElement.addClass('hidden-footer');
    }

    this.$modalElementSpinner = $(
        '<div id="lilt-preview-modal-body-spinner" style="margin-left: 10px"/>').
        addClass('spinner').
        addClass('big');

    this.$modalFooter = $('<div />').
        addClass('footer');

    this.$modalActionsSpinner = $('<div />').
        addClass('spinner').
        addClass('hidden');

    this.$modalActionButtons = $('<div />').
        addClass('buttons').
        addClass('right');

    this.$modalFooterRightActionButtons = $('<div />').
        addClass('buttons').
        addClass('right');

    this.$modalFooterLeftActionButtons = $('<div />').
        addClass('buttons').
        addClass('left');

    this.$modalFooterNext = $(
        ' <button type="button" class="btn" tabindex="0">Next <span style="margin-left: 10px" data-icon="rarr"></span></button>');

    this.$modalFooterPrevious = $(
        ' <button type="button" class="btn" tabindex="0"><span style="margin-right: 10px" data-icon="larr"></span> Previous</button>');

    this.$modalFooterPrevious.attr('disabled', true);
    this.$modalFooterPrevious.addClass('disabled');

    this.$modalFooterNext.on('click', () => {
      this.$modalElementBody.html('');
      this.$modalElementBody.append(this.$modalElementSpinner);
      this.$modalElementSpinner.show();

      this.loadTranslationData(this.selectedValues[this.next]);

      this.previous = this.current;
      this.current = this.next;
      this.next = this.current + 1;

      let {translationTitle, translationIsReviewed} = this.getTranslationData(
          this.translationId);
      if (translationIsReviewed === 1) {
        this.$modalFooterButtonsSubmit.addClass('disabled');
      } else {
        this.$modalFooterButtonsSubmit.removeClass('disabled');
      }

      this.$headerTitle.html(
          '<h1 style="float: left">Review: ' + translationTitle + '</h1>');

      if (this.selectedValues[this.previous] !== undefined) {
        this.$modalFooterPrevious.attr('disabled', false);
        this.$modalFooterPrevious.removeClass('disabled');
      }

      if (this.selectedValues.length <= this.next) {
        this.$modalFooterNext.attr('disabled', true);
        this.$modalFooterNext.addClass('disabled');
      }

    });
    this.$modalFooterPrevious.on('click', () => {
      this.$modalElementBody.html('');
      this.$modalElementBody.append(this.$modalElementSpinner);
      this.$modalElementSpinner.show();

      this.loadTranslationData(this.selectedValues[this.previous]);
      this.next = this.current;
      this.current = this.previous;
      this.previous = this.current - 1;

      let {translationTitle} = this.getTranslationData(this.translationId);

      this.$headerTitle.html(
          '<h1 style="float: left">Review: ' + translationTitle + '</h1>');

      if (this.selectedValues[this.previous] === undefined) {
        this.$modalFooterPrevious.attr('disabled', true);
        this.$modalFooterPrevious.addClass('disabled');
      }

      if (this.selectedValues[this.next] !== undefined) {
        this.$modalFooterNext.attr('disabled', false);
        this.$modalFooterNext.removeClass('disabled');
      }
    });

    if (!this.isMultiView) {
      this.$modalFooterNext.attr('disabled', true);
      this.$modalFooterNext.addClass('disabled');
      this.$modalFooterPrevious.attr('disabled', true);
      this.$modalFooterPrevious.addClass('disabled');
    }

    this.$modalFooterRightActionButtons.append(this.$modalFooterNext);
    this.$modalFooterLeftActionButtons.append(this.$modalFooterPrevious);

    this.$modalFooterButtonsCancel = $(
        ' <button type="button" class="btn" tabindex="0">Cancel</button>');

    this.$modalFooterButtonsSubmit = $(
        '<button type="button" data-translation-id="' + this.translationId +
        '" class="btn">Mark reviewed</button>\n');

    this.$modalFooterButtonsPublish = $(
        '<button type="button" data-translation-id="' + this.translationId +
        '" class="btn submit">Publish changes</button>\n');

    this.$modalFooterButtonsSubmit.on('click', () => {
      if (this.$modalFooterButtonsSubmit.hasClass('disabled')) {
        return;
      }
      const $spinner = $('<div class="spinner"></div>');
      this.$modalFooterButtonsSubmit.append($spinner);
      this.$modalFooterButtonsSubmit.disable();
      this.$modalFooterButtonsSubmit.addClass('disabled');

      Craft.sendActionRequest('POST',
          'craft-lilt-plugin/translation/post-translation-review/invoke',
          {data: {translationId: this.translationId, reviewed: true}}).
          then(response => {
            $spinner.remove();

            Craft.cp.displayNotice('Review complete');
            $(`#translations-element-index span.translation-status[data-id="${this.translationId}"]`).
                data('is-reviewed', 1);
            if (!this.isMultiView) {
              //location.reload();
              jQuery('#translations-element-index').
                  addClass('busy').
                  addClass('elements');
              CraftliltPlugin.elementIndexTranslation.updateElements();

            }
          }).
          catch(exception => {
            Craft.cp.displayError(Craft.t('app',
                'Can\'t mark reviewed, unexpected issue occurred'));
            this.$modalFooterButtonsSubmit.enable();
            this.$modalActionsSpinner.addClass('hidden');
          });
    });
    this.$modalFooterButtonsPublish.on('click', () => {
      if (this.$modalFooterButtonsPublish.hasClass('disabled')) {
        return;
      }

      const $spinner = $('<div class="spinner"></div>');
      this.$modalFooterButtonsPublish.append($spinner);
      this.$modalFooterButtonsPublish.addClass('disabled');
      this.$modalFooterButtonsSubmit.addClass('disabled');
      this.$modalFooterButtonsPublish.disable();

      Craft.sendActionRequest('POST',
          'craft-lilt-plugin/translation/post-translation-publish/invoke',
          {data: {translationIds: [this.translationId], published: true}}).
          then(response => {
            Craft.cp.displayNotice('Translation published');
            $spinner.remove();

            const $translation = $(`#translations-element-index span.translation-status[data-id="${this.translationId}"]`);

            $translation.
                data('is-reviewed', 1);
            $translation.
                data('is-published', 1);

            if (!this.isMultiView) {
              CraftliltPlugin.elementIndexTranslation.setIndexBusy();

              jQuery('#translations-element-index').
                  addClass('busy').
                  addClass('elements');
              CraftliltPlugin.elementIndexTranslation.updateElements();
            }
          }).
          catch(exception => {
            Craft.cp.displayError(Craft.t('app',
                'Can\'t publish translation, unexpected issue occurred'));
            this.$modalFooterButtonsPublish.enable();
            this.$modalActionsSpinner.addClass('hidden');
          });
    });

    const {translationIsReviewed} = this.getTranslationData(this.translationId);

    if (translationIsReviewed === 1) {
      this.$modalFooterButtonsSubmit.addClass('disabled');
    } else {
      this.$modalFooterButtonsSubmit.removeClass('disabled');
    }

    this.$modalActionButtons.append(this.$modalFooterButtonsSubmit);
    this.$modalActionButtons.append(this.$modalFooterButtonsPublish);
    this.$modalActionButtons.append(
        '<span class="close-modal" data-icon="remove" style="padding: 5px;margin-left: 10px;float: right;cursor: pointer"></span>');

    this.$modalActionButtons.css('margin', 0);

    const {translationTitle} = this.getTranslationData(this.translationId);

    const header = $('<div />').addClass('header');
    this.$headerTitle = $(
        '<h1 style="float: left">Review: ' + translationTitle + '</h1>');
    //<span class="close-modal" data-icon="remove" style="float: right;cursor: pointer"></span>

    header.append(this.$headerTitle);
    header.append(this.$modalActionButtons);
    header.append(this.$modalActionsSpinner);

    this.$modalFooter.append(this.$modalFooterLeftActionButtons);
    this.$modalFooter.append(this.$modalFooterRightActionButtons);

    this.$modalElementBody.append(this.$modalElementSpinner);
    this.$modalElement.append(header);
    this.$modalElement.append(this.$modalElementBody);
    this.$modalElement.append(this.$modalFooter);

    this.$modal = new Garnish.Modal(this.$modalElement, {
      visible: false,
      resizable: true,
      desiredHeight: $(window).height(),
      desiredWidth: $(window).width(),
      onHide: function() {
        CraftliltPlugin.elementIndexTranslation.setIndexBusy();

        jQuery('#translations-element-index').
            addClass('busy').
            addClass('elements');
        CraftliltPlugin.elementIndexTranslation.updateElements();
      },
    });

    this.$modal.desiredHeight = $(window).height();
    this.$modal.desiredWidth = $(window).width();

    this.modalForPreview = this.$modal;
  },
  showModal: function(translationId) {

    this.translationId = translationId;

    this.createModal();

    this.loadTranslationData(translationId);

    $('#lilt-preview-modal .close-modal').on('click', () => {
      CraftliltPlugin.elementIndexTranslation.setIndexBusy();

      jQuery('#translations-element-index').
          addClass('busy').
          addClass('elements');
      CraftliltPlugin.elementIndexTranslation.updateElements();
      this.$modal.hide();
    });

    this.$modalFooterButtonsCancel.on('click', () => {
      CraftliltPlugin.elementIndexTranslation.setIndexBusy();

      jQuery('#translations-element-index').
          addClass('busy').
          addClass('elements');
      CraftliltPlugin.elementIndexTranslation.updateElements();
      this.$modal.hide();
    });
  },
});

/** Init */
$(document).ready(function() {
  CraftliltPlugin.translationReview = new CraftliltPlugin.TranslationReview(
      '#translations', {
        translationsReviewSelector: '#translations-review-action',
        translationReviewSelector: '.lilt-review-translation:not(.disabled)',
      });

  // Get the modal body HTML based on the settings
  var data = {
    context: 'modal',
    elementType: 'lilthq\\craftliltplugin\\elements\\Translation',
    sources: null,
    showStatusMenu: true,
  };

  Craft.postActionRequest('element-selector-modals/body', data,
      (response, textStatus) => {
        if (textStatus === 'success') {
          $('#translations-element-index').html(response.html);

          // Initialize the element index
          CraftliltPlugin.elementIndexTranslation = new CraftliltPlugin.PreviewTranslationsIndex(
              'lilthq\\craftliltplugin\\elements\\Translation',
              $('#translations-element-index'), {
                _ignoreFailedRequest: false,
                context: 'index',
                modal: $('#translations-element-index'),
                storageKey: 'elementindex.lilthq\\craftliltplugin\\elements\\Translation',
                criteria: {
                  jobId: jQuery('#create-job-form').data('job-id'),
                },
                selectable: true,
                multiSelect: true,
                checkboxMode: true,
                onUpdateElements: function() {

                  const elements = CraftliltPlugin.elementIndexTranslation.view.getAllElements().get();

                  console.log(elements)
                  elements.forEach((element) => {

                    const title = jQuery(element).find('th');
                    const titleDiv = jQuery(element).find('th div');

                    const hasNotification = (titleDiv.length > 0 &&
                        titleDiv.data('has-notifications') === 1);

                    if(hasNotification) {

                      const notifications = titleDiv.data('notifications');
                      console.log(notifications)

                        const span = jQuery(
                            '<span class="info warning lilt-warning-span-centred translation-need-attention"></span>');

                      const reasons = notifications.map(notification => notification.reason);
                      const joinedReasons = reasons.join('<hr />');

                        span.on('click', function() {
                          new Garnish.HUD(span,
                              joinedReasons,
                              {
                                orientations: [
                                  'top',
                                  'bottom',
                                  'right',
                                  'left'],
                              });
                        });

                        title.append(span);
                    }
                  });

                  if (CraftliltPlugin.elementIndexTranslation !== undefined &&
                      CraftliltPlugin.elementIndexTranslation.view !==
                      undefined) {
                    const elementsCount = CraftliltPlugin.elementIndexTranslation.view.getAllElements().length;

                    if (elementsCount === 0) {
                      CraftliltPlugin.elementIndexTranslation.notFoundMessage = $('<div class="not-found-message-container"></div>');

                      if($('#create-job-form').data('job-status') === 'in-progress') {
                        CraftliltPlugin.elementIndexTranslation.notFoundMessage.append(
                            '<h2>Translation in progress...</h2>',
                        );
                      } else {
                        let selectedOption = null;
                        CraftliltPlugin.elementIndexTranslation.statusMenu.$options.each(function(index, option) {
                          if(jQuery(option).hasClass('sel')) {
                            selectedOption = jQuery(option);
                          }
                        })

                        const statusHtml = jQuery('<div>' + jQuery(selectedOption).html().toLowerCase() + '</div>');
                        const statusSpan = statusHtml.find('span.status')
                        if(statusSpan !== undefined) {
                          statusSpan.remove()
                        }

                        const boldStatus = jQuery('<b></b>')
                        boldStatus.append(statusHtml.html())

                        const statusHeader = jQuery('<h2>No translations are </h2>');
                        statusHeader.append(boldStatus);

                        CraftliltPlugin.elementIndexTranslation.notFoundMessage.append(
                            statusHeader,
                        );
                      }

                      const refreshButton = jQuery(
                          '<button class="btn" data-icon="refresh">Refresh</button>');
                      refreshButton.on('click', function() {
                        CraftliltPlugin.elementIndexTranslation.updateElements();
                        return false;
                      });

                      CraftliltPlugin.elementIndexTranslation.notFoundMessage.append(
                          refreshButton,
                      );

                      jQuery('#translations-element-index .elements').append(
                          CraftliltPlugin.elementIndexTranslation.notFoundMessage,
                      );
                    } else if (
                        CraftliltPlugin.elementIndexTranslation.notFoundMessage !== undefined &&
                        CraftliltPlugin.elementIndexTranslation.notFoundMessage !== null
                    ) {
                      CraftliltPlugin.elementIndexTranslation.notFoundMessage.remove();
                    }
                  }

                  jQuery('#translations-element-index').
                      removeClass('elements').
                      removeClass('busy');

                  $('.lilt-review-translation').on('click', function() {
                    const parent = jQuery(this).closest('tr');

                    if(parent !== undefined && parent.hasClass('disabled')) {
                      return
                    }

                    CraftliltPlugin.translationReview.showMultiModal(
                        [jQuery(this).data('id')]);
                  });

                  let disabledIds = [];
                  let enabledIds = [];
                  CraftliltPlugin.elementIndexTranslation.view.getAllElements().
                      each(function(i) {
                        const status = $(this).find('span.translation-status').data('status');
                        if (status === 'published' || status === 'failed' ||
                            status === 'new' ||
                            status === 'in-progress') {
                          disabledIds.push($(this).data('id'));
                        } else {
                          enabledIds.push($(this).data('id'))
                        }
                      });
                  CraftliltPlugin.elementIndexTranslation.settings.disabledElementIds = []

                  CraftliltPlugin.elementIndexTranslation.disableElementsById(
                      disabledIds);

                  CraftliltPlugin.elementIndexTranslation.enableElementsById(
                      enabledIds);
                },
                onSelectionChange: function() {
                },
                onReviewTriggered: function() {
                  const selectedElements = CraftliltPlugin.elementIndexTranslation.getSelectedElementIds();
                  if (selectedElements.length === 0) {
                    return;
                  }

                  CraftliltPlugin.translationReview.showMultiModal(
                      selectedElements);
                },
                onPublishTriggered: async function() {
                  const translationIds = CraftliltPlugin.elementIndexTranslation.getSelectedElementIds();
                  if (translationIds.length === 0) {
                    return;
                  }
                  CraftliltPlugin.elementIndexTranslation.setIndexBusy();
                  CraftliltPlugin.elementIndexTranslation.$publishTrigger.addClass(
                      'disabled');
                  CraftliltPlugin.elementIndexTranslation.$reviewTrigger.addClass(
                      'disabled');

                  let success = true;

                  for (let translationId of translationIds) {
                    await Craft.sendActionRequest('POST',
                        'craft-lilt-plugin/translation/post-translation-publish/invoke',
                        {
                          data: {
                            translationIds: [translationId],
                            published: true,
                          },
                        }).
                        catch(exception => {
                          success = false;
                          console.log(exception);
                        });
                  }

                  if (!success) {
                    Craft.cp.displayError(Craft.t('app',
                        'Can\'t publish translation(s), unexpected issue occurred'));
                    CraftliltPlugin.elementIndexTranslation.updateElements();

                    return;
                  }

                  Craft.cp.displayNotice('Translation(s) published');
                  CraftliltPlugin.elementIndexTranslation.updateElements();
                  CraftliltPlugin.elementIndexTranslation.$publishTrigger.removeClass(
                      'disabled');
                  CraftliltPlugin.elementIndexTranslation.$reviewTrigger.removeClass(
                      'disabled');
                },
              });
        }
      });
});
