const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Verified] Success path for job with multiple target languages with bulk publishing and disabled split send',
    () => {
      const entryLabel = 'The Future of Augmented Reality';
      const entryId = 24;

      it('with copy slug disabled & enable after publish disabled', () => {
        const {jobTitle, slug} = generateJobData();

        cy.verifiedFlow({
          slug,
          entryLabel,
          jobTitle,
          entryId,
          copySlug: false,
          enableAfterPublish: false,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true,
          splitSend: false
        });
      });

    });
