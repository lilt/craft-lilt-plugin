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

    this._disableReviewed();

    this._initActions();
    this._initCheckBoxes();
  },

  _disableReviewed: function() {
    $('#translations-review-action').
        addClass('disabled').
        attr('disabled', true);

    $('#lilt-translations-table tr').each(function() {
      const status = $(this).data('status');
      if (status === 'published' || status === 'failed' || status === 'new' ||
          status === 'in-progress') {
        $(this).find('.checkbox-cell').addClass('disabled').disable();
        $(this).
            find('.checkbox-cell').
            on('click', function() {return false;});
      }
    });
  },
  _initActions: function() {
    this.addListener($(this.translationsReviewSelector), 'click', () => {
      const selectedElements = this.getSelectedElements();
      if (selectedElements.length === 0) {
        return;
      }
      this.showMultiModal(selectedElements);
    });

    this.addListener($(this.translationReviewSelector), 'click', (e) => {
      let translationId = $(e.target).data('id');
      CraftliltPlugin.translationReview.showModal(translationId);
    });
  },
  _getEnabledCheckBoxes: function() {
    const checkBoxSelector = '#lilt-translations-table tr td:not(.disabled) input.checkbox';
    return $(checkBoxSelector);
  },
  _initCheckBoxes: function() {
    const $elements = this._getEnabledCheckBoxes();
    const selector = '#lilt-translations-table th.checkbox-cell.selectallcontainer .checkbox';

    if ($elements.length === 0) {
      $(selector).attr('disabled', true);
      return;
    }

    $(selector, this.$container).on('click', function() {
      const allChecked = $(this).prop('checked');

      const checkBoxSelector = '#lilt-translations-table tr td:not(.disabled) input.checkbox';
      const $elements = $(checkBoxSelector);

      if ($elements.length === 0) {
        return;
      }

      $elements.each(function() {
        $(this).prop('checked', allChecked);
      });
    });

    $('#lilt-translations-table .checkbox').on('click', () => {
      const selectedElements = this.getSelectedElements();
      if (selectedElements.length > 0) {
        $(this.translationsReviewSelector).
            removeClass('disabled').
            attr('disabled', false);

        return;
      }

      $(this.translationsReviewSelector).
          addClass('disabled').
          attr('disabled', true);
    });
  },

  getSelectedElements: function() {
    let selectedValues = [];
    $('#lilt-translations-table tr td input.checkbox').each(function() {
      if ($(this).prop('checked') === true) {
        selectedValues.push($(this).val());
      }
    });

    return selectedValues;
  },
  getTranslationData: function(translationId) {
    const selector = `#lilt-translations-table tr[data-id="${translationId}"]`;

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
      this.$modalElementBody.append($(response.data.html));

      $('#lilt-modal-preview-tabs nav ul li a').on('click', function() {
        const idToShow = $(this).data('id');
        $('#lilt-modal-preview-tabs nav ul li a').each(function() {
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

      let {translationTitle} = this.getTranslationData(this.translationId);
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
        '" class="btn">Submit review</button>\n');

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
            $(`#lilt-translations-table tr[data-id="${this.translationId}"]`).
                data('is-reviewed', 1);
            if (!this.isMultiView) {
              location.reload();
            }
          }).
          catch(exception => {
            Craft.cp.displayError(Craft.t('app',
                'Can\'t submit review, unexpected issue occurred'));
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
          {data: {translationId: this.translationId, published: true}}).
          then(response => {
            Craft.cp.displayNotice('Translation published');
            $spinner.remove();

            const $translation = $(`#lilt-translations-table tr[data-id="${this.translationId}"]`);

            $translation.
                data('is-reviewed', 1);
            $translation.
                data('is-published', 1);

            if (!this.isMultiView) {
              location.reload();
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
      this.$modalFooterButtonsSubmit.hide();
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
    });

    this.$modal.desiredHeight = $(window).height();
    this.$modal.desiredWidth = $(window).width();

    this.modalForPreview = this.$modal;
  },
  showModal: function(translationId) {

    this.translationId = translationId;

    if (this.$modal === null) {
      this.createModal();
    }

    this.loadTranslationData(translationId);

    $('#lilt-preview-modal .close-modal').on('click', () => {
      $('#content-container #content #lilt-translations-table').
          addClass('disabled');
      $('#content-container #content').
          append(
              $('<div style="position: absolute; top: 50%; left: 50%; margin: -24px 0 0 -24px;"/>').
                  addClass('spinner').
                  addClass('big').show());

      location.reload();
      this.$modal.hide();
    });

    this.$modalFooterButtonsCancel.on('click', () => {
      location.reload();
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
});
