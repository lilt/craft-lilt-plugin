import { defineConfig } from "cypress";

export default defineConfig({
  viewportWidth: 1920,
  viewportHeight: 1080,
  defaultCommandTimeout: 360 * 1000,
  // video: false,
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
  retries: {
    // Configure retry attempts for `cypress run`
    runMode: 5,
    // Configure retry attempts for `cypress open`
    openMode: 0
  }
});
