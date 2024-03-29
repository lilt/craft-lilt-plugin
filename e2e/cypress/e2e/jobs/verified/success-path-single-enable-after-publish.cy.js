const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Verified] Success path for job with single target language',
    () => {
      const entryLabel  = 'The Future of Augmented Reality';
      const entryId     = 24;

      it('with copy slug disabled & enable after publish enabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.verifiedFlow({
          slug,
          entryLabel,
          jobTitle,
          entryId,
          copySlug: false,
          enableAfterPublish: true,
          languages: ["de"]
        })
      });
    });
