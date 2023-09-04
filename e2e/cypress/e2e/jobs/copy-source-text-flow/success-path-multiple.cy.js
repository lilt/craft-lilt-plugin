const {generateJobData} = require('../../../support/job/generator.js');

describe(
    '[Copy Source Text] Success path for job with multiple target languages',
    () => {
      const entryLabel = 'The Future of Augmented Reality';

      it('with copy slug disabled & enable after publish disabled', () => {
        const {jobTitle, slug} = generateJobData();

        // cy.assertEntryContent(
        //     ['en'],
        //     'copy_source_text',
        //     24,
        // );
        //
        // return;
        cy.copySourceTextFlow({
          slug,
          entryLabel,
          jobTitle,
          copySlug: false,
          enableAfterPublish: false,
          languages: ['de', 'es', 'uk'],
          batchPublishing: true,
        });
      });
    });
