const mockServer = require('mockserver-client');

describe(
    'Update configuration',
    () => {
      it('Success update of API key and API URL',  () => {
        const appUrl = Cypress.env('APP_URL');
        const apiUrl = Cypress.env('API_URL');

        let mockServerClient = mockServer.mockServerClient(
            Cypress.env('MOCKSERVER_HOST'),
            Cypress.env('MOCKSERVER_PORT')
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
      });
      it('Failed update of API key and API URL',  () => {
        const appUrl = Cypress.env('APP_URL');
        const apiUrl = Cypress.env('API_URL');

        let mockServerClient = mockServer.mockServerClient(
            Cypress.env('MOCKSERVER_HOST'),
            Cypress.env('MOCKSERVER_PORT')
        );

        cy.wrap(mockServerClient.reset());

        cy.wrap(mockServerClient.mockAnyResponse(
            {
              'httpRequest': {
                'method': 'GET',
                'path': '/settings',
              },
              'httpResponse': {
                'statusCode': 500,
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
                'statusCode': 500,
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
            get('#notifications .notification.error').
            invoke('text').
            should(
                'contain',
                'Can\'t update configuration, connection to Lilt is failed. Looks like API Key or API URL is wrong.',
            );
      });
    });
