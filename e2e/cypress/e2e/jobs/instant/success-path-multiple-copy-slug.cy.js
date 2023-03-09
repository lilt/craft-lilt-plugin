const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Instant] Success path for job with multiple target languages',
    () => {
      const entryLabel = 'The Future of Augmented Reality';
      const entryId = 24;

      it('with copy slug enabled & enable after publish disabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.instantFlow({
          slug,
          entryLabel,
          jobTitle,
          entryId,
          copySlug: true,
          enableAfterPublish: false,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true,
        });
      });

    });
