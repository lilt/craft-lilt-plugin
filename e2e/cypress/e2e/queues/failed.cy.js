const {generateJobData} = require('../../support/job/generator.js');
import mockServer from 'mockserver-client';

describe(
    'Create `copy source text` job in status `ready for review` and check filters',
    () => {
      const {jobTitle} = generateJobData();
      const isMockserverEnabled = Cypress.env('MOCKSERVER_ENABLED');

      it('create and send for translation', () => {
        cy.setConfigurationOption('copySlug', false);

        // create job
        cy.createJob(jobTitle, 'instant', ['de', 'es', 'uk']);

        if (!isMockserverEnabled) {
          cy.task('log', { message: "Can't perform scenario run on environment without MockServer" })
          Cypress.currentTest.tags.push('risk')

          return;
        }

        let mockServerClient = mockServer.mockServerClient(
            Cypress.env('MOCKSERVER_HOST'), Cypress.env('MOCKSERVER_PORT'));

        cy.wrap(mockServerClient.reset());
        cy.wrap(mockServerClient.mockAnyResponse({
          'httpRequest': {
            'method': 'POST', 'path': '/jobs', 'headers': [
              {
                'name': 'Authorization', 'values': ['Bearer this_is_apy_key'],
              }], 'body': {
              'project_prefix': jobTitle,
              'lilt_translation_workflow': 'INSTANT',
            },
          }, 'httpResponse': {
            'statusCode': 500,
          }, 'times': {
            'remainingTimes': 4, 'unlimited': false,
          },
        }));

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
        cy.waitForJobStatus('failed');

        //assert all the values
        cy.
            get('#status-value').
            invoke('text').
            should(
                'contain',
                'Failed',
            );

        cy.get('#translations-list th[data-title="Translation"] div.element').
            invoke('attr', 'data-type').
            should('equal', 'lilthq\\craftliltplugin\\elements\\Translation');

        cy.get('#translations-list th[data-title="Translation"] div.element').
            invoke('attr', 'data-status').
            should('equal', 'failed');

        cy.get('.lilt-job-activity-log').invoke('text').should('contain', 'Unexpected error: [500] Server error: `POST http://mockserver:1080/jobs` resulted in a `500 Internal Server Error` response')
        cy.get('.lilt-job-activity-log').invoke('text').should('contain', 'Job failed after 3 attempt(s)')
      });
    });
