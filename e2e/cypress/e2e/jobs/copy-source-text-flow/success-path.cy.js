const {generateJobData} = require('../../../support/job/generator.js');

describe(
    'Success copy source text path with copy slug disabled',
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
        })
      });
    });
