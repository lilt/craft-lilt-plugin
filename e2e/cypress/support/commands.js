const mockServer = require('mockserver-client');

const langs = {
  en: 1, uk: 2, de: 3, es: 4,
};

import {
  translateContent,
  originalContent,
  germanContent,
  spanishContent,
  ukrainianContent,
} from './parameters.js';

/**
 * Create new job
 *
 * @memberof cy
 * @method createJob
 * @param {string} title
 * @param {string} flow
 * @param {array} languages
 * @returns undefined
 */
Cypress.Commands.add('createJob', (title, flow, languages = ['de']) => {
  cy.get('#nav-craft-lilt-plugin > a').click();

  cy.
      get('#action-button .btn-create-new-job[data-icon="language"]').
      click();

  cy.get('#title').type(title);

  cy.get('.addAnEntry').click();

  cy.get('div[data-label="The Future of Augmented Reality"]').click();
  cy.get('.elementselectormodal .buttons.right .btn.submit').click();

  for (const language of languages) {
    cy.get(
        `#sites tr[data-language="${language}"][data-name="Happy Lager (${language})"] td`).
        first().
        click();
  }

  cy.get('a[data-target="types-craft-fields-Assets-advanced"]').click();
  cy.get('#translationWorkflow').select(flow);

  cy.get('button.btn.submit[type="submit"][data-icon="language"]').
      click();

  cy.url().should('contain', 'admin/craft-lilt-plugin/job/edit');

  cy.
      get('#notifications .notification.notice').
      invoke('text').
      should('contain', 'Translate job created successfully.');

});

/**
 * Open job page
 *
 * @memberof cy
 * @method openJob
 * @param {string} title
 * @returns undefined
 */
Cypress.Commands.add('openJob', (title) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/craft-lilt-plugin`);
  cy.get('#nav-craft-lilt-plugin > a').click();

  cy.get(`.element[data-label="${title}"]`).click();
});

/**
 * Set configuration option
 *
 * @memberof cy
 * @method setConfigurationOption
 * @param {string} option
 * @param {boolean} enabled
 * @returns undefined
 */
Cypress.Commands.add('setConfigurationOption', (option, enabled) => {
  const apiUrl = Cypress.env('API_URL');
  const appUrl = Cypress.env('APP_URL');

  const options = {
    enableEntries: {
      id: 'enableEntriesForTargetSites',
    }, copySlug: {
      id: 'copyEntriesSlugFromSourceToTarget',
    },
  };

  if (!options[option]) {
    throw new Error(`Option ${option} is not configured`);
  }

  const isMockserverEnabled = Cypress.env('MOCKSERVER_ENABLED');
  if (isMockserverEnabled) {
    let mockServerClient = mockServer.mockServerClient(
        Cypress.env('MOCKSERVER_HOST'), Cypress.env('MOCKSERVER_PORT'));

    cy.wrap(mockServerClient.reset());

    cy.wrap(mockServerClient.mockAnyResponse({
      'httpRequest': {
        'method': 'GET', 'path': '/settings',
      }, 'httpResponse': {
        'statusCode': 200, 'body': JSON.stringify({
          'project_prefix': 'Project Prefix From Response',
          'project_name_template': 'Project Name Template From Response',
          'lilt_translation_workflow': 'INSTANT',
        }),
      }, 'times': {
        'remainingTimes': 10, 'unlimited': false,
      },
    }));

    cy.wrap(mockServerClient.mockAnyResponse({
      'httpRequest': {
        'method': 'PUT', 'path': '/settings', 'headers': [
          {
            'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
          }],
      }, 'httpResponse': {
        'statusCode': 200, 'body': JSON.stringify({
          'project_prefix': 'this-is-connector-project-prefix',
          'project_name_template': 'this-is-connector-project-name-template',
          'lilt_translation_workflow': 'INSTANT',
        }),
      }, 'times': {
        'remainingTimes': 2, 'unlimited': false,
      },
    }));
  }

  cy.visit(`${appUrl}/admin/craft-lilt-plugin/settings`);

  cy.get('#connectorApiUrl').clear().type(apiUrl);
  cy.get('#connectorApiKey').clear().type('this_is_apy_key');

  cy.get('#content .btn.submit').click();

  cy.
      get('#notifications .notification.notice').
      invoke('text').
      should('contain', 'Configuration options saved successfully');

  cy.visit(`${appUrl}/admin/craft-lilt-plugin/settings`);

  cy.get(`#${options[option].id}`).
      invoke('prop', 'checked').
      then((checked) => {
        if (checked !== enabled) {
          cy.get(`label[for="${options[option].id}"]`).click();
          cy.get('#content .btn.submit').click();

          cy.
              get('#notifications .notification.notice').
              invoke('text').
              should('contain', 'Configuration options saved successfully');
        }
      });

  cy.log('Enabled: ', enabled);

  cy.get(`#${options[option].id}`).
      invoke('prop', 'checked').
      should('equal', enabled);
});

/**
 * Set entry slug
 *
 * @memberof cy
 * @method setEntrySlug
 * @param {string} slug
 * @param {string} entryId
 * @returns undefined
 */
Cypress.Commands.add('setEntrySlug', (slug, entryId, language = 'en') => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`#context-btn`).click();
  cy.get(`a[data-site-id="${langs[language]}"][role="option"]`).click();

  cy.get(`.elements .element[data-id="${entryId}"]`).click();

  cy.get('#slug').clear().type(slug);

  cy.get('#save-btn-container .btn.submit[type="submit"]').click();
});

/**
 * Set entry slug
 *
 * @memberof cy
 * @method resetEntryTitle
 * @param {int} entryId
 * @param {string} entryLabel
 * @returns undefined
 */
Cypress.Commands.add('resetEntryTitle', (entryId, title) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  for (const [targetLanguage, targetLanguageId] of Object.entries(langs)) {
    cy.get(`#context-btn`).click();
    cy.get(`a[data-site-id="${targetLanguageId}"][role="option"]`).click();

    cy.get(`.elements .element[data-id="${entryId}"]`).click();

    cy.get('#title').clear().type(title);

    cy.get('#save-btn-container .btn.submit[type="submit"]').click();
  }
});

/**
 * Disable entry for all sites
 *
 * @memberof cy
 * @method disableEntry
 * @param {string} slug
 * @param {string} entryId
 * @returns undefined
 */
Cypress.Commands.add('disableEntry', (slug, entryId) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`.elements .element[data-id="${entryId}"]`).click();

  cy.get('#expand-status-btn').click();

  for (const [targetLanguage, targetLanguageId] of Object.entries(langs)) {
    cy.get(`#enabledForSite-${targetLanguageId}`).
        invoke('attr', 'aria-checked').
        then((value) => {
          if (value === 'true') {
            cy.get(`#enabledForSite-${targetLanguageId}`).click();
          }
        });
  }

  cy.get('#enabled').
      invoke('attr', 'aria-checked').
      should('equal', 'false');

  cy.get('#save-btn-container .btn.submit[type="submit"]').click();
});

/**
 * Reset entry content for site
 *
 * @memberof cy
 * @method resetEntryContent
 * @param {string} entryId
 * @param {array} languages
 * @returns undefined
 */
Cypress.Commands.add('resetEntryContent', (entryId, languages) => {
  const appUrl = Cypress.env('APP_URL');

  for (const language of languages) {
    cy.visit(`${appUrl}/admin/entries/news`);

    cy.get(`#context-btn`).click();
    cy.get(`a[data-site-id="${langs[language]}"][role="option"]`).click();

    cy.get(`.elements .element[data-id="${entryId}"]`).click();

    cy.get('.redactor-in').then(els => {
      [...els].forEach(el => {
        cy.wrap(el).clear();
        cy.wrap(el).type('This content should be changed');
      });
    });

    cy.get('#fields .input input[type="text"]').then(els => {
      [...els].forEach(el => {
        cy.wrap(el).clear();
        cy.wrap(el).type('This content should be changed');
      });
    });

    cy.get('#save-btn-container .btn.submit[type="submit"]').click();

    cy.
        get('#notifications .notification.notice').
        invoke('text').
        should('contain', 'Entry saved');
  }
});

/**
 * Enable entry for all sites
 *
 * @memberof cy
 * @method enableEntry
 * @param {string} slug
 * @param {string} entryId
 * @returns undefined
 */
Cypress.Commands.add('enableEntry', (slug, entryId) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`.elements .element[data-label="${entryId}"]`).click();

  cy.get('#expand-status-btn').click();

  for (const [targetLanguage, targetLanguageId] of Object.entries(langs)) {
    cy.get(`#enabledForSite-${targetLanguageId}`).
        invoke('attr', 'aria-checked').
        then((value) => {
          if (value === 'false') {
            cy.get(`#enabledForSite-${targetLanguageId}`).click();
          }
        });
  }

  cy.get('#enabled').
      invoke('attr', 'aria-checked').
      should('equal', 'true');

  cy.get('#save-btn-container .btn.submit[type="submit"]').click();
});

/**
 * Wait for job status to be changed
 *
 * @memberof cy
 * @method waitForJobStatus
 * @param {string} status
 * @param {int} maxAttempts
 * @param {int} attempts
 * @param {int} waitPerIteration
 * @returns undefined
 */
Cypress.Commands.add('waitForJobStatus',
    (status = 'ready-for-review', maxAttempts = 15, attempts = 0,
        waitPerIteration = 3000) => {
      if (attempts > maxAttempts) {
        throw new Error('Timed out waiting for jpb status to be ' + status);
      }
      cy.get('#create-job-form').
          invoke('attr', 'data-job-status').
          then(async $jobStatus => {
            if ($jobStatus !== status) {
              cy.wait(waitPerIteration);
              cy.reload();
              cy.waitForJobStatus(status, maxAttempts, attempts + 1,
                  waitPerIteration);
            }
          });
    });

/**
 * Wait for draft to be created
 *
 * @memberof cy
 * @method waitForTranslationDrafts
 * @param {int} maxAttempts
 * @param {int} attempts
 * @param {int} waitPerIteration
 * @returns undefined
 */
Cypress.Commands.add('waitForTranslationDrafts',
    (maxAttempts = 100, attempts = 0, waitPerIteration = 1000) => {
      if (attempts > maxAttempts) {
        throw new Error('Timed out waiting for report to be generated');
      }
      cy.get('#translations-list th[data-title="Title"] div.element').
          invoke('attr', 'data-translated-draft-id').
          then(async translatedDraftId => {
            cy.log('TransladtedDraftId');
            cy.log(translatedDraftId);

            if (translatedDraftId === '' || translatedDraftId === undefined) {
              cy.wait(waitPerIteration);
              cy.reload();
              cy.waitForTranslationDrafts(maxAttempts, attempts + 1,
                  waitPerIteration);
            }
          });
    });

/**
 * Publish single translation for job
 *
 * @memberof cy
 * @method publishTranslation
 * @param {string} jobTitle
 * @param {string} language
 * @returns undefined
 */
Cypress.Commands.add('publishTranslation', (jobTitle, language) => {
  // going to job
  cy.openJob(jobTitle);

  // select checkbox of taraget language and click publish button
  cy.get(
      `#translations-list th[data-title="Title"] div.element[data-target-site-language="${language}"]`).
      invoke('attr', 'data-id').
      then((dataId) => {
        cy.get(`tbody tr[data-id="${dataId}"] .checkbox-cell`).click();
        cy.get('#translations-publish-action').click();
      });

  cy.
      get('#notifications .notification.notice').
      invoke('text').
      should('contain', 'Translation(s) published');
});

/**
 * Publish translations for job by languages
 *
 * @memberof cy
 * @method publishTranslations
 * @param {string} jobTitle
 * @param {string} language
 * @returns undefined
 */
Cypress.Commands.add('publishTranslations', (jobTitle, languages) => {
  // going to job
  cy.openJob(jobTitle);

  for (const language of languages) {
    // select checkbox of taraget language and click publish button
    cy.get(
        `#translations-list th[data-title="Title"] div.element[data-target-site-language="${language}"]`).
        invoke('attr', 'data-id').
        then((dataId) => {
          cy.get(`tbody tr[data-id="${dataId}"] .checkbox-cell`).click();
        });
  }

  cy.get('#translations-publish-action').click();

  cy.
      get('#notifications .notification.notice').
      invoke('text').
      should('contain', 'Translation(s) published');

  cy.waitForJobStatus('complete');
});

/**
 * Publish job translations in many iterations
 *
 * @memberof cy
 * @method publishJob
 * @param {array} options
 * @returns undefined
 */
Cypress.Commands.add('publishJob',
    ({languages, jobTitle, copySlug, slug, entryId, enableAfterPublish}) => {
      //assert copy slug functionality
      for (const language of languages) {
        // open job page
        cy.openJob(jobTitle);

        cy.assertDraftSlugValue(copySlug, slug, language);

        cy.publishTranslation(jobTitle, language);
      }

      cy.waitForJobStatus('complete');

      for (const language of languages) {
        cy.assertAfterPublish(copySlug, slug, entryId, language,
            enableAfterPublish);
      }
    });

/**
 * Publish job translations in one iteration
 *
 * @memberof cy
 * @method publishJobBatch
 * @param {array} options
 * @returns undefined
 */
Cypress.Commands.add('publishJobBatch',
    ({languages, jobTitle, copySlug, slug, entryId, enableAfterPublish}) => {
      cy.assertBeforePublishBatch(jobTitle, languages, copySlug, slug);
      cy.publishTranslations(jobTitle, languages);
      cy.assertAfterPublishBatch(languages, copySlug, slug, entryId,
          enableAfterPublish);
    });

/**
 * @memberof cy
 * @method assertEntryContent
 * @param {array} languages
 * @param {string} flow
 * @param {int} entryId
 * @returns undefined
 */
Cypress.Commands.add('assertEntryContent',
    (languages, flow, entryId = 24) => {
      const expected = (flow === 'copy_source_text') ? {
        'de': originalContent,
        'es': originalContent,
        'uk': originalContent,
      } : {
        'de': germanContent,
        'es': spanishContent,
        'uk': ukrainianContent,
      };

      const appUrl = Cypress.env('APP_URL');

      for (const language of languages) {
        cy.visit(`${appUrl}/admin/entries/news`);

        cy.get(`#context-btn`).click();
        cy.get(`a[data-site-id="${langs[language]}"][role="option"]`).click();

        cy.get(`.elements .element[data-id="${entryId}"]`).click();

        cy.screenshot(
            `${flow}_${entryId}_${language}`,
            {
              capture: 'fullPage',
            });
        cy.get('.redactor-toolbar-wrapper').should('be.visible');

        for (let expectedValue of expected[language]) {
          cy.get(expectedValue.id, {timeout: 1000}).
              invoke(expectedValue.functionName).
              should('equal', expectedValue.value);
        }
      }
    });

/**
 *
 * Run E2E for copy source text flow with options
 *
 * @memberof cy
 * @method copySourceTextFlow
 * @param {object} options
 * @returns undefined
 */
Cypress.Commands.add('copySourceTextFlow', ({
  slug,
  entryLabel,
  jobTitle,
  copySlug = false,
  enableAfterPublish = false,
  languages = ['de'],
  batchPublishing = false, //publish all translations at once with publish button
  entryId = 24,
}) => {
  cy.setConfigurationOption('enableEntries', enableAfterPublish);
  cy.setConfigurationOption('copySlug', copySlug);

  if (copySlug) {
    // update slug on entry and enable slug copy option
    cy.setEntrySlug(slug, entryId);
  }

  cy.disableEntry(slug, entryId);
  cy.resetEntryContent(entryId, languages);

  return;
  // create job
  cy.createJob(jobTitle, 'copy_source_text', languages);

  // send job for translation
  cy.get('#lilt-btn-create-new-job').click();

  cy.
      get('#status-value').
      invoke('text').
      should('contain', 'In Progress');

  //wait for job to be in status ready-for-review
  cy.waitForJobStatus('ready-for-review');

  //assert all the values
  cy.
      get('#status-value').
      invoke('text').
      should('contain', 'Ready for review');

  cy.get('#translations-list th[data-title="Title"] div.element').
      invoke('attr', 'data-type').
      should('equal', 'lilthq\\craftliltplugin\\elements\\Translation');

  cy.get('#translations-list th[data-title="Title"] div.element').
      invoke('attr', 'data-status').
      should('equal', 'ready-for-review');

  cy.get('#translations-list th[data-title="Title"] div.element').
      invoke('attr', 'data-label').
      should('equal', 'The Future of Augmented Reality');

  cy.get('#translations-list th[data-title="Title"] div.element').
      invoke('attr', 'title').
      should('equal', 'The Future of Augmented Reality – Happy Lager (en)');

  cy.get('#author-label').invoke('text').should('equal', 'Author');

  cy.get('#meta-settings-source-site').
      invoke('text').
      should('equal', 'en');

  for (const language of languages) {
    cy.get(
        `#meta-settings-target-sites .target-languages-list span[data-language="${language}"]`).
        should('be.visible');
  }

  cy.get('#meta-settings-translation-workflow').
      invoke('text').
      should('equal', 'Copy source text');

  cy.get('#meta-settings-job-id').
      invoke('text').
      then((createdJobId) => {
        const appUrl = Cypress.env('APP_URL');
        cy.url().
            should('equal',
                `${appUrl}/admin/craft-lilt-plugin/job/edit/${createdJobId}`);
      });

  if (batchPublishing) {
    cy.publishJobBatch({
      languages, jobTitle, copySlug, slug, entryId, enableAfterPublish,
    });

    cy.assertEntryContent(languages, 'copy_source_text', entryId);

    return;
  }

  cy.publishJob({
    languages, jobTitle, copySlug, slug, entryId, enableAfterPublish,
  });

  cy.assertEntryContent(languages, 'copy_source_text', entryId);
});

/**
 * @memberof cy
 * @method assertEntrySlug
 * @param {string} chainer
 * @param {string} slug
 * @param {string} entryId
 * @param {string} language
 * @returns undefined
 */
Cypress.Commands.add('assertEntrySlug',
    (chainer, slug, entryId, language = 'en') => {
      const appUrl = Cypress.env('APP_URL');
      cy.visit(`${appUrl}/admin/entries/news`);

      cy.get(`#context-btn`).click();
      cy.get(`a[data-site-id="${langs[language]}"][role="option"]`).click();

      cy.get(`.elements .element[data-id="${entryId}"]`).click();

      cy.get('#slug-field input#slug').invoke('val').should(chainer, slug);
    });

/**
 * @memberof cy
 * @method assertDraftSlugValue
 * @param {boolean} copySlug
 * @param {string} slug
 * @param {string} language
 * @returns undefined
 */
Cypress.Commands.add('assertDraftSlugValue', (copySlug, slug, language) => {
  // going to draft page
  cy.get(
      `#translations-list th[data-title="Title"] div.element[data-target-site-language="${language}"] a`).
      click();
  cy.url().should('contain', `site=${language}&draftId=`);

  if (copySlug) {
    // assert slug to be equal to updated one on draft
    cy.get('#slug-field #slug-status.status-badge.modified').
        should('be.visible');
    cy.get('#slug-field input#slug').invoke('val').should('equal', slug);
  } else {
    // assert slug to be equal to be updated
    cy.get('#slug-field #slug-status.status-badge.modified').
        should('not.exist');
    cy.get('#slug-field input#slug').
        invoke('val').
        should('not.equal', slug);
  }
});

/**
 * @memberof cy
 * @method assertAfterPublish
 * @param {boolean} copySlug
 * @param {string} slug
 * @param {string} entryId
 * @param {string} language
 * @param {boolean} enableAfterPublish
 * @returns undefined
 */
Cypress.Commands.add('assertAfterPublish',
    (copySlug, slug, entryId, language, enableAfterPublish) => {
      if (copySlug) {
        cy.assertEntrySlug('equal', slug, entryId, 'en');
        cy.assertEntrySlug('equal', slug, entryId, language);
      } else {
        cy.assertEntrySlug('not.equal', slug, entryId, 'en');
        cy.assertEntrySlug('not.equal', slug, entryId, language);
      }

      //assert copy slug functionality
      if (enableAfterPublish) {
        cy.get('#expand-status-btn').click();

        cy.get(`#enabledForSite-${langs[language]}`).
            invoke('attr', 'aria-checked').
            should('equal', 'true');
      } else {
        cy.get('#expand-status-btn').click();

        cy.get(`#enabledForSite-${langs[language]}`).
            invoke('attr', 'aria-checked').
            should('equal', 'false');
      }
    });

/**
 * @memberof cy
 * @method assertAfterPublishBatch
 * @param {array} options
 * @returns undefined
 */
Cypress.Commands.add('assertAfterPublishBatch',
    (languages, copySlug, slug, entryId, enableAfterPublish) => {
      for (const language of languages) {
        cy.assertAfterPublish(copySlug, slug, entryId, language,
            enableAfterPublish);
      }
    });

/**
 * @memberof cy
 * @method assertBeforePublishBatch
 * @param {string} jobTitle
 * @param {array} languages
 * @param {boolean} copySlug
 * @param {string} slug
 * @returns undefined
 */
Cypress.Commands.add('assertBeforePublishBatch',
    (jobTitle, languages, copySlug, slug) => {
      //assert copy slug functionality
      for (const language of languages) {
        // open job page
        cy.openJob(jobTitle);
        cy.assertDraftSlugValue(copySlug, slug, language);
      }
    });

/**
 * @memberof cy
 * @method releaseQueueManager
 * @returns undefined
 */
Cypress.Commands.add('releaseQueueManager', () => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/utilities/queue-manager`);

  cy.get('body').then($body => {
    if ($body.find(
            '#header #toolbar button[type="button"][data-icon="remove"]').length >
        0) {
      cy.get('#header #toolbar button[type="button"][data-icon="remove"]').
          click();
      cy.get('#header #toolbar button[type="button"][data-icon="remove"]').
          should('not.exist');
    }
  });
});
