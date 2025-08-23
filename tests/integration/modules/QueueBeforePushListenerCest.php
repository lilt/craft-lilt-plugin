<?php

declare(strict_types=1);

use craft\helpers\Queue;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\modules\SendTranslationToConnector;
use PHPUnit\Framework\Assert;

class QueueBeforePushListenerCest
{
    public function testNoSameJobInQueue_SendJobToConnector(IntegrationTester $I): void
    {
        $I->clearQueue();

        $jobClass = 'lilthq\craftliltplugin\modules\SendJobToConnector';

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 345
            ]),
            SendJobToConnector::PRIORITY,
            SendJobToConnector::DELAY_IN_SECONDS
        );

        $jobInfos = Craft::$app->queue->getJobInfo();
        $jobs = [];

        foreach ($jobInfos as $jobInfo) {
            $actual = Craft::$app->queue->getJobDetails((string) $jobInfo['id']);

            if (get_class($actual['job']) !== $jobClass) {
                continue;
            }

            /**
             * @var SendJobToConnector $actual
             */
            $jobs[$actual['job']->jobId] = ['attempt' => $actual['job']->attempt];
        }

        Assert::assertCount(1, $jobs);
        Assert::assertArrayHasKey(123, $jobs);
        Assert::assertEquals(['attempt' => 345], $jobs['123']);

        $I->assertJobInQueue(
            new SendJobToConnector([
                'jobId' => 123,
                'attempt' => 345
            ])
        );
    }

    public function testSameJobInQueue_SendJobToConnector(IntegrationTester $I): void
    {
        $I->clearQueue();

        $jobClass = 'lilthq\craftliltplugin\modules\SendJobToConnector';

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 678
            ]),
            SendJobToConnector::PRIORITY,
            SendJobToConnector::DELAY_IN_SECONDS
        );

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 345
            ]),
            SendJobToConnector::PRIORITY,
            SendJobToConnector::DELAY_IN_SECONDS
        );

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 91011
            ]),
            SendJobToConnector::PRIORITY,
            SendJobToConnector::DELAY_IN_SECONDS
        );

        $jobInfos = Craft::$app->queue->getJobInfo();
        $jobs = [];

        foreach ($jobInfos as $jobInfo) {
            $actual = Craft::$app->queue->getJobDetails((string) $jobInfo['id']);

            if (get_class($actual['job']) !== $jobClass) {
                continue;
            }

            /**
             * @var SendJobToConnector $actual
             */
            $jobs[$actual['job']->jobId] = ['attempt' => $actual['job']->attempt];
        }

        Assert::assertCount(1, $jobs);
        Assert::assertArrayHasKey(123, $jobs);
        Assert::assertEquals(['attempt' => 678], $jobs['123']);

        $I->assertJobInQueue(
            new SendJobToConnector([
                'jobId' => 123,
                'attempt' => 678
            ])
        );
    }

    public function testNoSameJobInQueue_SendTranslationToConnector(IntegrationTester $I): void
    {
        $I->clearQueue();

        $jobClass = 'lilthq\craftliltplugin\modules\SendTranslationToConnector';

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'translationId' => 112233,
                'attempt' => 345,
            ]),
            SendTranslationToConnector::PRIORITY,
            SendTranslationToConnector::DELAY_IN_SECONDS
        );

        $jobInfos = Craft::$app->queue->getJobInfo();
        $jobs = [];

        foreach ($jobInfos as $jobInfo) {
            $actual = Craft::$app->queue->getJobDetails((string) $jobInfo['id']);

            if (get_class($actual['job']) !== $jobClass) {
                continue;
            }

            /**
             * @var SendTranslationToConnector $actualJob
             */
            $actualJob = $actual['job'];

            $jobs[$actual['job']->jobId] = [
                'attempt' => $actualJob->attempt,
                'translationId' => $actualJob->translationId
            ];
        }

        Assert::assertCount(1, $jobs);
        Assert::assertArrayHasKey(123, $jobs);
        Assert::assertEquals(['attempt' => 345, 'translationId' => 112233], $jobs['123']);

        $I->assertJobInQueue(
            new SendTranslationToConnector([
                'jobId' => 123,
                'attempt' => 345,
                'translationId' => 112233
            ])
        );
    }

    public function testSameJobInQueue_SendTranslationToConnector(IntegrationTester $I): void
    {
        $I->clearQueue();

        $jobClass = 'lilthq\craftliltplugin\modules\SendTranslationToConnector';

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 678,
                'translationId' => 112233
            ]),
            SendTranslationToConnector::PRIORITY,
            SendTranslationToConnector::DELAY_IN_SECONDS
        );

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 345,
                'translationId' => 112233
            ]),
            SendTranslationToConnector::PRIORITY,
            SendTranslationToConnector::DELAY_IN_SECONDS
        );

        Queue::push(
            new $jobClass([
                'jobId' => 123,
                'attempt' => 91011,
                'translationId' => 112233
            ]),
            SendTranslationToConnector::PRIORITY,
            SendTranslationToConnector::DELAY_IN_SECONDS
        );

        $jobInfos = Craft::$app->queue->getJobInfo();
        $jobs = [];

        foreach ($jobInfos as $jobInfo) {
            $actual = Craft::$app->queue->getJobDetails((string) $jobInfo['id']);

            if (get_class($actual['job']) !== $jobClass) {
                continue;
            }

            /**
             * @var SendTranslationToConnector $actual
             */
            $jobs[$actual['job']->jobId] = [
                'attempt' => $actual['job']->attempt,
                'translationId' => 112233,
            ];
        }

        Assert::assertCount(1, $jobs);
        Assert::assertArrayHasKey(123, $jobs);
        Assert::assertEquals([
            'attempt' => 678,
            'translationId' => 112233
        ], $jobs['123']);

        $I->assertJobInQueue(
            new SendTranslationToConnector([
                'jobId' => 123,
                'attempt' => 678,
                'translationId' => 112233
            ])
        );
    }
}
