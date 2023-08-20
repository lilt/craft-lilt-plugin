const {generateJobData} = require('../../../support/job/generator.js');

describe(
    'Create `copy source text` job in status `ready for review` and check filters',
    () => {
      const {jobHash, jobTitle} = generateJobData();

      it('create and send for translation', () => {
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
        cy.waitForJobStatus( 'ready-for-review');

        //assert all the values
        cy.
            get('#status-value').
            invoke('text').
            should(
                'contain',
                'Ready for review',
            );

        cy.get('#translations-list th[data-title="Translation"] div.element').
            invoke('attr', 'data-type').
            should('equal', 'lilthq\\craftliltplugin\\elements\\Translation');

        cy.get('#translations-list th[data-title="Translation"] div.element').
            invoke('attr', 'data-status').
            should('equal', 'ready-for-review');

        cy.get('#translations-list th[data-title="Translation"] div.element').
            invoke('attr', 'data-label').
            should('equal', 'The Future of Augmented Reality');

        cy.get('#translations-list th[data-title="Translation"] div.element').
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
                  'contain',
                  `${appUrl}/admin/craft-lilt-plugin/job/edit/${createdJobId}`,
              );
            });
      });

      it('check that job exists on jobs page', () => {
        const appUrl = Cypress.env('APP_URL');
        cy.visit(`${appUrl}/admin/craft-lilt-plugin/jobs`)

        cy.
            get(`div[data-label="${jobTitle}"]`).
            invoke('attr','data-status').
            should('equal', 'ready-for-review')
      })

      it('can search job by title on jobs page', () => {
        const appUrl = Cypress.env('APP_URL');
        cy.visit(`${appUrl}/admin/craft-lilt-plugin/jobs`)

        cy.get('.search .text').type(jobTitle)

        cy.
            get(`div[data-label="${jobTitle}"]`).
            invoke('attr','data-status').
            should('equal', 'ready-for-review')

        cy.get('.data').find('tr td[data-attr="status"]').should('have.length', 1)
      })

      it('can search job by hash on jobs page', () => {
        const appUrl = Cypress.env('APP_URL');
        cy.visit(`${appUrl}/admin/craft-lilt-plugin/jobs`)

        cy.get('.search .text').type(jobHash)

        cy.
            get(`div[data-label="${jobTitle}"]`).
            invoke('attr','data-status').
            should('equal', 'ready-for-review')

        cy.get('.data').find('tr td[data-attr="status"]').should('have.length', 1)
      })

      it(
          'can see job in the list with status filter', async () => {
        const appUrl = Cypress.env('APP_URL');
        cy.visit(`${appUrl}/admin/craft-lilt-plugin/jobs`)

        cy.get('#sidebar a[data-key="ready-for-review"]').click()

        cy.
            get(`div[data-label="${jobTitle}"]`).
            invoke('attr','data-status').
            should('equal', 'ready-for-review')
      })
    });
