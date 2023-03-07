import './commands';
import './flow/instant';
import './flow/verified';

beforeEach(() => {
  cy.wrap(Cypress.session.clearAllSavedSessions());

  Cypress.on('fail', (error, runnable) => {
    // we now have access to the err instance
    // and the mocha runnable this failed on

    const testName = Cypress.spec.name;
    const screenshotFileName = `${testName} -- ${runnable.parent.title} -- ${runnable.title} (failed).png`;

    // take a screenshot and save it as a file
    cy.screenshot(screenshotFileName, {
      capture: 'fullPage',
    });

    // add the screenshot to the error stack trace
    error.stack += `\n\nScreenshot: ${Cypress.config(
        'screenshotsFolder')}/${screenshotFileName}\n`;

    // throw the error again so that it fails the test
    throw error;
  });

  // Disable fail on JS errors
  Cypress.on('uncaught:exception', (err, runnable) => {
    return false;
  });

  const appUrl = Cypress.env('APP_URL');
  const userName = Cypress.env('USER_NAME');
  const userPassword = Cypress.env('USER_PASSWORD');

  cy.visit(`${appUrl}/admin/login`);

  cy.get('#loginName').type(userName);

  cy.get('#password').type(userPassword);

  cy.get('#submit').click();

  cy.url().should('contain', '/admin/dashboard');
});
