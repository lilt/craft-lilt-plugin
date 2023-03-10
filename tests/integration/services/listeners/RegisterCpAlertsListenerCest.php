<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\services\listeners;

use Craft;
use craft\events\RegisterCpAlertsEvent;
use craft\helpers\Cp;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\services\repositories\external\PackagistRepository;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use PHPUnit\Framework\Assert;
use yii\base\Event;
use IntegrationTester;

class RegisterCpAlertsListenerCest extends AbstractIntegrationCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ],
        ];
    }

    public function testInvokeMinorVersion(IntegrationTester $I): void
    {
        $response = json_decode(
            '{
                  "package": {
                    "versions": {
                      "dev-3.x-development": {
                        "version": "dev-3.x-development"
                      },
                      "dev-3.x-cover-content": {
                        "version": "dev-3.x-cover-content"
                      },
                      "dev-cover-fixed-issues": {
                        "version": "dev-cover-fixed-issues"
                      },
                      "3.x-dev": {
                        "version": "3.x-dev"
                      },
                      "dev-90-failed-jobs-arent-able-to-be-deleted": {
                        "version": "dev-90-failed-jobs-arent-able-to-be-deleted"
                      },
                      "dev-4.x-development": {
                        "version": "dev-4.x-development"
                      },
                      "4.x-dev": {
                        "version": "4.x-dev"
                      },
                      "1.x-dev": {
                        "version": "1.x-dev"
                      },
                      "3.4.3": {
                        "version": "3.4.3"
                      },
                      "3.4.2": {
                        "version": "3.4.2"
                      },
                      "3.4.1": {
                        "version": "3.4.1"
                      },
                      "3.4.0": {
                        "version": "3.4.0"
                      },
                      "3.3.0": {
                        "version": "3.3.0"
                      },
                      "3.2.1": {
                        "version": "3.2.1"
                      },
                      "4.1.0": {
                        "version": "4.1.0"
                      },
                      "4.0.0": {
                        "version": "4.0.0"
                      },
                      "3.0.0": {
                        "version": "3.0.0"
                      },
                      "0.8.1": {
                        "version": "0.8.1"
                      },
                      "0.8.0": {
                        "version": "0.8.0"
                      },
                      "0.3.1": {
                        "version": "0.3.1"
                      },
                      "0.3.0": {
                        "version": "0.3.0"
                      },
                      "0.2.1": {
                        "version": "0.2.1"
                      },
                      "0.1.1": {
                        "version": "0.1.1"
                      }
                    }
                  }
                }',
            true
        );

        Craft::$app->cache->delete('craftliltplugin-latest-version');
        Craft::$app->request->setUrl('admin/craft-lilt-plugin/settings/lilt-system-report');
        Craftliltplugin::getInstance()->setVersion('3.4.0');
        Craftliltplugin::getInstance()->set('packagistRepository', new PackagistRepository('http://wiremock'));

        $I->expectPackagistRequest('/packages/lilt/craft-lilt-plugin.json', $response, 200);

        $event = new RegisterCpAlertsEvent();
        Event::trigger(Cp::class, Cp::EVENT_REGISTER_ALERTS, $event);

        Assert::assertSame([
            0 => 'The Lilt plugin is outdated. Please update to version 3.4.3'
        ], $event->alerts);

        Assert::assertSame('3.4.3',  Craft::$app->cache->get('craftliltplugin-latest-version'));
    }

    public function testInvokeSuccessMaxVersion(IntegrationTester $I): void
    {
        $response = json_decode(
            '{
                  "package": {
                    "versions": {
                      "3.999.999": {
                        "version": "3.999.999"
                      },
                      "dev-3.x-development": {
                        "version": "dev-3.x-development"
                      },
                      "dev-3.x-cover-content": {
                        "version": "dev-3.x-cover-content"
                      },
                      "dev-cover-fixed-issues": {
                        "version": "dev-cover-fixed-issues"
                      },
                      "3.x-dev": {
                        "version": "3.x-dev"
                      },
                      "dev-90-failed-jobs-arent-able-to-be-deleted": {
                        "version": "dev-90-failed-jobs-arent-able-to-be-deleted"
                      },
                      "dev-4.x-development": {
                        "version": "dev-4.x-development"
                      },
                      "4.x-dev": {
                        "version": "4.x-dev"
                      },
                      "1.x-dev": {
                        "version": "1.x-dev"
                      },
                      "3.4.3": {
                        "version": "3.4.3"
                      },
                      "3.4.2": {
                        "version": "3.4.2"
                      },
                      "3.4.1": {
                        "version": "3.4.1"
                      },
                      "3.4.0": {
                        "version": "3.4.0"
                      },
                      "3.3.0": {
                        "version": "3.3.0"
                      },
                      "3.2.1": {
                        "version": "3.2.1"
                      },
                      "4.1.0": {
                        "version": "4.1.0"
                      },
                      "4.0.0": {
                        "version": "4.0.0"
                      },
                      "3.0.0": {
                        "version": "3.0.0"
                      },
                      "0.8.1": {
                        "version": "0.8.1"
                      },
                      "0.8.0": {
                        "version": "0.8.0"
                      },
                      "0.3.1": {
                        "version": "0.3.1"
                      },
                      "0.3.0": {
                        "version": "0.3.0"
                      },
                      "0.2.1": {
                        "version": "0.2.1"
                      },
                      "0.1.1": {
                        "version": "0.1.1"
                      }
                    }
                  }
                }',
            true
        );

        Craft::$app->cache->delete('craftliltplugin-latest-version');
        Craft::$app->request->setUrl('admin/craft-lilt-plugin/settings/lilt-system-report');
        Craftliltplugin::getInstance()->setVersion('3.4.0');
        Craftliltplugin::getInstance()->set('packagistRepository', new PackagistRepository('http://wiremock'));

        $I->expectPackagistRequest('/packages/lilt/craft-lilt-plugin.json', $response, 200);

        $event = new RegisterCpAlertsEvent();
        Event::trigger(Cp::class, Cp::EVENT_REGISTER_ALERTS, $event);

        Assert::assertSame([
            0 => 'The Lilt plugin is outdated. Please update to version 3.999.999'
        ], $event->alerts);

        Assert::assertSame('3.999.999',  Craft::$app->cache->get('craftliltplugin-latest-version'));
    }

    public function testInvokeSuccessZeroVersion(IntegrationTester $I): void
    {
        $response = json_decode(
            '{
                  "package": {
                    "versions": {
                      "dev-3.x-development": {
                        "version": "dev-3.x-development"
                      },
                      "dev-3.x-cover-content": {
                        "version": "dev-3.x-cover-content"
                      },
                      "dev-cover-fixed-issues": {
                        "version": "dev-cover-fixed-issues"
                      },
                      "3.x-dev": {
                        "version": "3.x-dev"
                      },
                      "dev-90-failed-jobs-arent-able-to-be-deleted": {
                        "version": "dev-90-failed-jobs-arent-able-to-be-deleted"
                      },
                      "dev-4.x-development": {
                        "version": "dev-4.x-development"
                      },
                      "4.x-dev": {
                        "version": "4.x-dev"
                      },
                      "1.x-dev": {
                        "version": "1.x-dev"
                      },
                      "3.4.3": {
                        "version": "3.4.3"
                      },
                      "3.4.2": {
                        "version": "3.4.2"
                      },
                      "3.4.1": {
                        "version": "3.4.1"
                      },
                      "3.4.0": {
                        "version": "3.4.0"
                      },
                      "3.3.0": {
                        "version": "3.3.0"
                      },
                      "3.2.1": {
                        "version": "3.2.1"
                      },
                      "4.1.0": {
                        "version": "4.1.0"
                      },
                      "4.0.0": {
                        "version": "4.0.0"
                      },
                      "3.0.0": {
                        "version": "3.0.0"
                      },
                      "0.8.1": {
                        "version": "0.8.1"
                      },
                      "0.8.0": {
                        "version": "0.8.0"
                      },
                      "0.3.1": {
                        "version": "0.3.1"
                      },
                      "0.3.0": {
                        "version": "0.3.0"
                      },
                      "0.2.1": {
                        "version": "0.2.1"
                      },
                      "0.1.1": {
                        "version": "0.1.1"
                      }
                    }
                  }
                }',
            true
        );

        Craft::$app->cache->delete('craftliltplugin-latest-version');
        Craft::$app->request->setUrl('admin/craft-lilt-plugin/settings/lilt-system-report');
        Craftliltplugin::getInstance()->setVersion('3.0.0');
        Craftliltplugin::getInstance()->set('packagistRepository', new PackagistRepository('http://wiremock'));

        $I->expectPackagistRequest('/packages/lilt/craft-lilt-plugin.json', $response, 200);

        $event = new RegisterCpAlertsEvent();
        Event::trigger(Cp::class, Cp::EVENT_REGISTER_ALERTS, $event);

        Assert::assertSame([
            0 => 'The Lilt plugin is outdated. Please update to version 3.4.3'
        ], $event->alerts);

        Assert::assertSame('3.4.3',  Craft::$app->cache->get('craftliltplugin-latest-version'));
    }

    public function testInvokeVersionMatched(IntegrationTester $I): void
    {
        $response = json_decode(
            '{
                  "package": {
                    "versions": {
                      "3.999.999": {
                        "version": "3.999.999"
                      }
                    }
                  }
                }',
            true
        );

        Craft::$app->cache->delete('craftliltplugin-latest-version');
        Craft::$app->request->setUrl('admin/craft-lilt-plugin/settings/lilt-system-report');
        Craftliltplugin::getInstance()->setVersion('3.999.999');
        Craftliltplugin::getInstance()->set('packagistRepository', new PackagistRepository('http://wiremock'));

        $I->expectPackagistRequest('/packages/lilt/craft-lilt-plugin.json', $response, 200);

        $event = new RegisterCpAlertsEvent();
        Event::trigger(Cp::class, Cp::EVENT_REGISTER_ALERTS, $event);

        Assert::assertSame([], $event->alerts);
        Assert::assertSame('3.999.999',  Craft::$app->cache->get('craftliltplugin-latest-version'));
    }

    public function testInvokeUrlWrong(IntegrationTester $I): void
    {
        Craft::$app->cache->delete('craftliltplugin-latest-version');
        Craft::$app->request->setUrl('admin/settings');
        Craftliltplugin::getInstance()->setVersion('3.999.999');
        Craftliltplugin::getInstance()->set('packagistRepository', new PackagistRepository('http://wiremock'));

        $event = new RegisterCpAlertsEvent();
        Event::trigger(Cp::class, Cp::EVENT_REGISTER_ALERTS, $event);

        Assert::assertSame([], $event->alerts);
        Assert::assertFalse(Craft::$app->cache->get('craftliltplugin-latest-version'));
    }
}
