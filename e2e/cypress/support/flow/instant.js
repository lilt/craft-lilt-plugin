import {siteLanguages} from '../parameters';
import mockServer from 'mockserver-client';

/**
 * @memberof cy
 * @method instantFlow
 * @param {object} options
 * @returns undefined
 */
Cypress.Commands.add('instantFlow', ({
  slug,
  entryLabel,
  jobTitle,
  copySlug = false,
  enableAfterPublish = false,
  languages = ['de'],
  batchPublishing = false, //publish all translations at once with publish button
  entryId = 24,
}) => {
  const isMockserverEnabled = Cypress.env('MOCKSERVER_ENABLED');

  cy.releaseQueueManager();
  cy.resetEntryTitle(entryId, entryLabel);

  cy.setConfigurationOption('enableEntries', enableAfterPublish);
  cy.setConfigurationOption('copySlug', copySlug);

  if (copySlug) {
    // update slug on entry and enable slug copy option
    cy.setEntrySlug(slug, entryId);
  }

  cy.disableEntry(slug, entryId);

  // create job
  cy.createJob(jobTitle, 'instant', languages);

  // send job for translation
  cy.get('#lilt-btn-create-new-job').click();
  cy.get('#translations-list').should('be.visible');

  let mockServerClient = mockServer.mockServerClient(
      Cypress.env('MOCKSERVER_HOST'), Cypress.env('MOCKSERVER_PORT'));

  if (isMockserverEnabled) {
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
        'unlimited': true,
      },
    }));

    cy.wrap(mockServerClient.mockAnyResponse({
      'httpRequest': {
        'method': 'POST', 'path': '/jobs', 'headers': [
          {
            'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
          }], 'body': {
          'project_prefix': jobTitle, 'lilt_translation_workflow': 'INSTANT',
        },
      }, 'httpResponse': {
        'statusCode': 200, 'body': JSON.stringify({
          'id': 777,
          'status': 'draft',
          'errorMsg': '',
          'createdAt': '2019-08-24T14:15:22Z',
          'updatedAt': '2019-08-24T14:15:22Z',
        }),
      }, 'times': {
        'remainingTimes': 1, 'unlimited': false,
      },
    }));

    cy.wrap(mockServerClient.mockAnyResponse({
      'httpRequest': {
        'method': 'POST', 'path': '/jobs/777/start', 'headers': [
          {
            'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
          }],
      }, 'httpResponse': {
        'statusCode': 200,
      }, 'times': {
        'remainingTimes': 1, 'unlimited': false,
      },
    }));

    for (const language of languages) {
      cy.wrap(mockServerClient.mockAnyResponse({
        'httpRequest': {
          'method': 'POST',
          'path': '/jobs/777/files',
          'queryStringParameters': [
            {
              'name': 'trglang', 'values': [language],
            }, {
              'name': 'srclang', 'values': ['en'],
            }, {
              'name': 'name', 'values': ['element_[\\d]+_.*\\.json\\+html'],
            }, {
              'name': 'due', 'values': [''],
            }],
          'headers': [
            {
              'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
            }], // TODO: expectation request body
          // 'body': translationBody,
        }, 'httpResponse': {
          'statusCode': 200,
        }, 'times': {
          'remainingTimes': 1, 'unlimited': false,
        },
      }));
    }
  }

  cy.
      get('#status-value').
      invoke('text').
      should('contain', 'In Progress');

  cy.waitForTranslationDrafts();

  if (isMockserverEnabled) {
    cy.wrap(mockServerClient.mockAnyResponse({
      'httpRequest': {
        'method': 'GET', 'path': '/jobs/777', 'headers': [
          {
            'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
          }],
      }, 'httpResponse': {
        'statusCode': 200, 'body': JSON.stringify({
          'id': 777,
          'status': 'complete',
          'errorMsg': '',
          'createdAt': '2019-08-24T14:15:22Z',
          'updatedAt': '2019-08-24T14:15:22Z',
        }),
      }, 'times': {
        'remainingTimes': 1, 'unlimited': false,
      },
    }));

    let translationsResult = [];

    for (const language of languages) {
      const siteId = siteLanguages[language];
      const translationId = 777000 + siteId;

      const translationResult = {
        'createdAt': '2022-05-29T11:31:58',
        'errorMsg': null,
        'id': translationId,
        'name': '777_element_' + 24 + '_slug_for_' + language + '.json+html',
        'status': 'mt_complete',
        'trgLang': language,
        'trgLocale': '',
        'updatedAt': '2022-06-02T23:01:42',
      };
      translationsResult.push(translationResult);

      cy.wrap(mockServerClient.mockAnyResponse({
        'httpRequest': {
          'method': 'GET',
          'path': `/translations/${translationId}`,
          'headers': [
            {
              'name': 'Authorization',
              'values': ['Bearer this_is_apy_key'],
            }],
        }, 'httpResponse': {
          'statusCode': 200, 'body': JSON.stringify(translationResult),
        }, 'times': {
          'remainingTimes': 1, 'unlimited': false,
        },
      }));
    }

    cy.wrap(mockServerClient.mockAnyResponse({
      'httpRequest': {
        'method': 'GET', 'path': '/translations', 'headers': [
          {
            'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
          }], 'queryStringParameters': [
          {
            'name': 'limit', 'values': ['100'],
          }, {
            'name': 'start', 'values': ['00'],
          }, {
            'name': 'job_id', 'values': ['777'],
          }],
      }, 'httpResponse': {
        'statusCode': 200, 'body': JSON.stringify({
          'limit': 100, 'start': 0, 'results': translationsResult,
        }),
      }, 'times': {
        'remainingTimes': 1, 'unlimited': false,
      },
    }));

    for (const language of languages) {
      cy.get(
          `#translations-list th[data-title="Title"] div.element[data-target-site-language="${language}"]`).
          invoke('attr', 'data-translated-draft-id').
          then(async translatedDraftId => {
            const siteId = siteLanguages[language];
            const translationId = 777000 + siteId;

            let expectedSourceContent = {};
            expectedSourceContent[translatedDraftId] = {'title': `Translated ${language}: The Future of Augmented Reality`};

            cy.wrap(mockServerClient.mockAnyResponse({
              'httpRequest': {
                'method': 'GET',
                'path': `/translations/${translationId}/download`,
                'headers': [
                  {
                    'name': 'Authorization',
                    'values': ['Bearer this_is_apy_key'],
                  }],
              }, 'httpResponse': {
                'statusCode': 200,
                'body': JSON.stringify(expectedSourceContent),
              }, 'times': {
                'remainingTimes': 1, 'unlimited': false,
              },
            }));
          });
    }
  }

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
      should('equal', 'Instant');

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

    return;
  }

  cy.publishJob({
    languages, jobTitle, copySlug, slug, entryId, enableAfterPublish,
  });
});
