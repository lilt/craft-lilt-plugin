import './commands'

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
