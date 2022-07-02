<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\acceptance;

use Craft;
use FunctionalTester;

class JobsFunctionalCest
{
    public function testPageLoad(FunctionalTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $I->amOnPage('?p=admin/craft-lilt-plugin/jobs');

        $I->see('Jobs');

        # We have elements index on page
        $I->seeElement('#content .elements');

        # All actions are there
        $I->seeElement('#action-button');
        $I->seeElement('#lilt-sync-jobs');
        $I->seeElement('.btn.big.submit.icon.btn-create-new-job');

        #Bread crumbs
        $I->seeElement('div#crumbs nav', ['aria-label' => 'Breadcrumbs']);
        $I->seeNumberOfElements('div#crumbs nav ul li a', 2);

        $I->seeResponseCodeIs(200);
    }

    public function testGoToJobCreate(FunctionalTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);

        $I->amLoggedInAs($user);
        $I->amOnPage('?p=admin/craft-lilt-plugin/jobs');

        $I->click('.btn.big.submit.icon.btn-create-new-job');

        $I->see('Create a new job');

        $I->seeElement('form#create-job-form');

        $I->see('Job title');
        $I->see('Entries');
        $I->see('Add an entry');
        $I->see('Source site');
        $I->see('Target site(s)');
        $I->see('Translation(s) Workflow');

        $I->seeResponseCodeIs(200);
    }
}
