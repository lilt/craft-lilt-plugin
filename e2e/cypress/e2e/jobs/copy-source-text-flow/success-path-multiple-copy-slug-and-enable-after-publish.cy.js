const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Copy Source Text] Success path for job with multiple target languages',
    () => {
      const entryLabel = 'The Future of Augmented Reality';

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
