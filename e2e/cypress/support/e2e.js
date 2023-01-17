// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

beforeEach(() => {
  cy.wrap(Cypress.session.clearAllSavedSessions())

  const appUrl = Cypress.env('APP_URL');
  const userName = Cypress.env('USER_NAME');
  const userPassword = Cypress.env('USER_PASSWORD');

  cy.visit(`${appUrl}/admin/login`)

  cy.get('#loginName')
  .type(userName)

  cy.get('#password')
  .type(userPassword)

  cy.get('#submit')
  .click()

  cy.url().should('contain', '/admin/dashboard')
})