const mockServer = require('mockserver-client');

const langs = {
  en: 1,
  uk: 2,
  de: 3,
  es: 4,
};

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
      should(
          'contain',
          'Translate job created successfully.',
      );

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
    },
    copySlug: {
      id: 'copyEntriesSlugFromSourceToTarget',
    },
  };

  if (!options[option]) {
    throw new Error(`Option ${option} is not configured`);
  }

  let mockServerClient = mockServer.mockServerClient(
      Cypress.env('MOCKSERVER_HOST'),
      Cypress.env('MOCKSERVER_PORT'),
  );

  cy.wrap(mockServerClient.reset());

  cy.wrap(mockServerClient.mockAnyResponse(
      {
        'httpRequest': {
          'method': 'GET',
          'path': '/settings',
        },
        'httpResponse': {
          'statusCode': 200,
          'body': JSON.stringify(
              {
                'project_prefix': 'Project Prefix From Response',
                'project_name_template': 'Project Name Template From Response',
                'lilt_translation_workflow': 'INSTANT',
              },
          ),
        },
        'times': {
          'remainingTimes': 10,
          'unlimited': false,
        },
      },
  ));

  cy.visit(`${appUrl}/admin/craft-lilt-plugin/settings`);

  cy.wrap(mockServerClient.mockAnyResponse(
      {
        'httpRequest': {
          'method': 'PUT',
          'path': '/settings',
          'headers': [
            {
              'name': 'Authorization',
              'values': ['Bearer this_is_apy_key'],
            },
          ],
        },
        'httpResponse': {
          'statusCode': 200,
          'body': JSON.stringify(
              {
                'project_prefix': 'this-is-connector-project-prefix',
                'project_name_template': 'this-is-connector-project-name-template',
                'lilt_translation_workflow': 'INSTANT',
              },
          ),
        },
        'times': {
          'remainingTimes': 2,
          'unlimited': false,
        },
      },
  ));

  cy.get('#connectorApiUrl').clear().type(apiUrl);
  cy.get('#connectorApiKey').clear().type('this_is_apy_key');

  cy.get('#content .btn.submit').click();

  cy.
      get('#notifications .notification.notice').
      invoke('text').
      should(
          'contain',
          'Configuration options saved successfully',
      );

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
              should(
                  'contain',
                  'Configuration options saved successfully',
              );
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
 * @param {string} entryLabel
 * @returns undefined
 */
Cypress.Commands.add('setEntrySlug', (slug, entryLabel) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`.elements .element[data-label="${entryLabel}"]`).click();

  cy.get('#slug').clear().type(slug);

  cy.get('#save-btn-container .btn.submit[type="submit"]').click();
});

/**
 * Disable entry for all sites
 *
 * @memberof cy
 * @method disableEntry
 * @param {string} slug
 * @param {string} entryLabel
 * @returns undefined
 */
Cypress.Commands.add('disableEntry', (slug, entryLabel) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`.elements .element[data-label="${entryLabel}"]`).click();

  cy.get('#expand-status-btn').click();

  const enableLanguage = (langId) => {
    cy.get(`#enabledForSite-${langId}`).
        invoke('attr', 'aria-checked').
        then((value) => {
          if (value === 'true') {
            cy.get(`#enabledForSite-${langId}`).click();
          }
        });
  };

  enableLanguage(1);
  enableLanguage(2);
  enableLanguage(3);
  enableLanguage(4);

  cy.get('#enabled').
      invoke('attr', 'aria-checked').
      should('equal', 'false');

  cy.get('#save-btn-container .btn.submit[type="submit"]').click();
});

/**
 * Enable entry for all sites
 *
 * @memberof cy
 * @method enableEntry
 * @param {string} slug
 * @param {string} entryLabel
 * @returns undefined
 */
Cypress.Commands.add('enableEntry', (slug, entryLabel) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`.elements .element[data-label="${entryLabel}"]`).click();

  cy.get('#expand-status-btn').click();

  const enableLanguage = (langId) => {
    cy.get(`#enabledForSite-${langId}`).
        invoke('attr', 'aria-checked').
        then((value) => {
          if (value === 'false') {
            cy.get(`#enabledForSite-${langId}`).click();
          }
        });
  };

  enableLanguage(1);
  enableLanguage(2);
  enableLanguage(3);
  enableLanguage(4);

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
Cypress.Commands.add('waitForJobStatus', (
    status = 'ready-for-review',
    maxAttempts = 10,
    attempts = 0,
    waitPerIteration = 3000) => {
  if (attempts > maxAttempts) {
    throw new Error('Timed out waiting for report to be generated');
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

  cy.wait(5000); //delay for publishing
  cy.waitForJobStatus('complete');
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

  cy.wait(5000); //delay for publishing
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
    ({languages, jobTitle, copySlug, slug, entryLabel, enableAfterPublish}) => {
      //assert copy slug functionality
      for (const language of languages) {
        // open job page
        cy.openJob(jobTitle);

        cy.assertDraftSlugValue(copySlug, slug, language);

        cy.publishTranslation(jobTitle, language);

        cy.assertAfterPublish(copySlug, slug, entryLabel, language,
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
    ({languages, jobTitle, copySlug, slug, entryLabel, enableAfterPublish}) => {
      cy.assertBeforePublishBatch(jobTitle, languages, copySlug, slug);
      cy.publishTranslations(jobTitle, languages);
      cy.assertAfterPublishBatch(languages, copySlug, slug, entryLabel,
          enableAfterPublish);
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
}) => {

  cy.setConfigurationOption('enableEntries', enableAfterPublish);
  cy.setConfigurationOption('copySlug', copySlug);

  if (copySlug) {
    // update slug on entry and enable slug copy option
    cy.setEntrySlug(slug, entryLabel);
  }

  cy.disableEntry(slug, entryLabel);

  // create job
  cy.createJob(jobTitle, 'copy_source_text', languages);

  // send job for translation
  cy.get('#lilt-btn-create-new-job').click();

  cy.
      get('#status-value').
      invoke('text').
      should(
          'contain',
          'In Progress',
      );

  //wait for job to be in status ready-for-review
  cy.waitForJobStatus('ready-for-review');

  //assert all the values
  cy.
      get('#status-value').
      invoke('text').
      should(
          'contain',
          'Ready for review',
      );

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
      should('equal',
          'The Future of Augmented Reality – Happy Lager (en)');

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
        cy.url().should(
            'equal',
            `${appUrl}/admin/craft-lilt-plugin/job/edit/${createdJobId}`,
        );
      });

  if (batchPublishing) {
    cy.publishJobBatch({
      languages,
      jobTitle,
      copySlug,
      slug,
      entryLabel,
      enableAfterPublish,
    });

    return;
  }

  cy.publishJob({
    languages,
    jobTitle,
    copySlug,
    slug,
    entryLabel,
    enableAfterPublish,
  });
});

/**
 * @memberof cy
 * @method assertEntrySlug
 * @param {string} chainer
 * @param {string} slug
 * @param {string} entryLabel
 * @param {string} language
 * @returns undefined
 */
Cypress.Commands.add('assertEntrySlug',
    (chainer, slug, entryLabel, language = 'en') => {
      const appUrl = Cypress.env('APP_URL');
      cy.visit(`${appUrl}/admin/entries/news`);

      cy.get(`#context-btn`).click();
      cy.get(`a[data-site-id="${langs[language]}"][role="option"]`).click();

      cy.get(`.elements .element[data-label="${entryLabel}"]`).click();

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
 * @param {string} entryLabel
 * @param {string} language
 * @param {boolean} enableAfterPublish
 * @returns undefined
 */
Cypress.Commands.add('assertAfterPublish',
    (copySlug, slug, entryLabel, language, enableAfterPublish) => {
      if (copySlug) {
        cy.assertEntrySlug('equal', slug, entryLabel, 'en');
        cy.assertEntrySlug('equal', slug, entryLabel, language);
      } else {
        cy.assertEntrySlug('not.equal', slug, entryLabel, 'en');
        cy.assertEntrySlug('not.equal', slug, entryLabel, language);
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
    (languages, copySlug, slug, entryLabel, enableAfterPublish) => {
      for (const language of languages) {
        cy.assertAfterPublish(copySlug, slug, entryLabel, language,
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
