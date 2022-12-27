const mockServer = require('mockserver-client');

const langs = {
  en: 1,
  uk: 2,
  de: 3,
  es: 4,
};

/**
 * @memberof cy
 * @method createJob
 * @param {string} title
 * @param {string} flow
 * @returns undefined
 */
Cypress.Commands.add('createJob', (title, flow) => {
  cy.get('#nav-craft-lilt-plugin > a').click();

  cy.
      get('#action-button .btn-create-new-job[data-icon="language"]').
      click();

  cy.get('#title').type(title);

  cy.get('.addAnEntry').click();

  cy.get('div[data-label="The Future of Augmented Reality"]').click();
  cy.get('.elementselectormodal .buttons.right .btn.submit').click();

  cy.get('#sites tr[data-language="de"][data-name="Happy Lager (de)"] td').
      first().
      click();

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
 * @memberof cy
 * @method assertEntrySlug
 * @param {string} chainer
 * @param {string} slug
 * @param {string} entryLabel
 * @returns undefined
 */
Cypress.Commands.add('assertEntrySlug', (chainer, slug, entryLabel) => {
  const appUrl = Cypress.env('APP_URL');
  cy.visit(`${appUrl}/admin/entries/news`);

  cy.get(`.elements .element[data-label="${entryLabel}"]`).click();

  cy.get('#slug-field input#slug').invoke('val').should(chainer, slug);
});

/**
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
 * @memberof cy
 * @method copySourceTextFlow
 * @param {object} options
 * @returns undefined
 */
Cypress.Commands.add('copySourceTextFlow', ({
  slug,
  entryLabel,
  jobTitle,
  copySlug,
  enableAfterPublish,
}) => {

  cy.setConfigurationOption('enableEntries', enableAfterPublish);
  cy.setConfigurationOption('copySlug', copySlug);

  if (copySlug) {
    // update slug on entry and enable slug copy option
    cy.setEntrySlug(slug, entryLabel);
  }

  cy.disableEntry(slug, entryLabel);

  // create job
  cy.createJob(jobTitle, 'copy_source_text');

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

  cy.get('#meta-settings-target-sites').
      invoke('text').
      should('equal', 'de');

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

  //assert copy slug functionality
  cy.get('#translations-list th[data-title="Title"] div.element a').
      click();
  cy.url().should('contain', 'site=de&draftId=');

  if (copySlug) {
    // assert slug to be equal to updated one on draft
    cy.get('#slug-field #slug-status.status-badge.modified').
        should('be.visible');
    cy.get('#slug-field input#slug').invoke('val').should('equal', slug);
  } else {
    // assert slug to be equal to be updated
    cy.get('#slug-field #slug-status.status-badge.modified').
        should('not.exist');
    cy.get('#slug-field input#slug').invoke('val').should('not.equal', slug);
  }

  // going back to job
  cy.openJob(jobTitle);

  // cy.get('.lilt-review-translation ').click()
  // cy.get('#lilt-preview-modal')

  cy.get('tbody .checkbox-cell').click();
  cy.get('#translations-publish-action').click();

  if (copySlug) {
    cy.assertEntrySlug('equal', slug, entryLabel);
  } else {
    cy.assertEntrySlug('not.equal', slug, entryLabel);
  }

  if (enableAfterPublish) {
    cy.get('#expand-status-btn').click();

    cy.get(`#enabledForSite-${langs['de']}`).
        invoke('attr', 'aria-checked').
        should('equal', 'true');
  } else {
    cy.get('#expand-status-btn').click();

    cy.get(`#enabledForSite-${langs['de']}`).
        invoke('attr', 'aria-checked').
        should('equal', 'false');
  }
});