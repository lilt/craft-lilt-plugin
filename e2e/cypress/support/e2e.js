import './commands'
import './flow/instant'
import './flow/verified'

beforeEach(() => {
  cy.wrap(Cypress.session.clearAllSavedSessions())

  // Disable fail on JS errors
  Cypress.on('uncaught:exception', (err, runnable) => {
    return false
  })

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
