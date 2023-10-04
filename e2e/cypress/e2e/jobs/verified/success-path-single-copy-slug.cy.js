const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Verified] Success path for job with single target language',
    () => {
      const entryLabel  = 'The Future of Augmented Reality';
      const entryId     = 24;

      it('with copy slug enabled & enable after publish disabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.verifiedFlow({
          slug,
          entryLabel,
          jobTitle,
          entryId,
          copySlug: true,
          enableAfterPublish: false,
          languages: ["de"]
        })
      });
    });
