
const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Verified] Success path for job with multiple target languages with bulk publishing',
    () => {
      const entryLabel = 'The Future of Augmented Reality';
      const entryId = 24;

      it('with copy slug enabled & enable after publish enabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.log(`Job title: ${jobTitle}`);
        cy.log(`Slug: ${slug}`);

        cy.verifiedFlow({
          slug,
          entryLabel,
          jobTitle,
          entryId,
          copySlug: true,
          enableAfterPublish: true,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true,
        });
      });
    });
