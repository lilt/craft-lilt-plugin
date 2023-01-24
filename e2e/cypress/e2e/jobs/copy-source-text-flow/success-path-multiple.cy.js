const {generateJobData} = require('../../../support/job/generator.js');

describe(
    'Success path for job with multiple target languages',
    () => {
      const entryLabel = 'The Future of Augmented Reality';

      it('with copy slug disabled & enable after publish disabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.copySourceTextFlow({
          slug,
          entryLabel,
          jobTitle,
          copySlug: false,
          enableAfterPublish: false,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true
        })
      });

      it('with copy slug disabled & enable after publish enabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.copySourceTextFlow({
          slug,
          entryLabel,
          jobTitle,
          copySlug: false,
          enableAfterPublish: true,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true
        })
      });

      it('with copy slug enabled & enable after publish disabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.copySourceTextFlow({
          slug,
          entryLabel,
          jobTitle,
          copySlug: true,
          enableAfterPublish: false,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true
        })
      });

      it('with copy slug enabled & enable after publish enabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.copySourceTextFlow({
          slug,
          entryLabel,
          jobTitle,
          copySlug: true,
          enableAfterPublish: true,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true
        })
      });
    });
