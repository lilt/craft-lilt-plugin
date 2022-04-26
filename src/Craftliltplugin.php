<?php
/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

namespace lilthq\craftliltplugin;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use GuzzleHttp\Client;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\assets\CraftLiltPluginAsset;
use lilthq\craftliltplugin\services\order\CreateOrderHandler;
use lilthq\craftliltplugin\services\provider\LiltConfigurationProvider;
use lilthq\craftliltplugin\services\repository\external\LiltJobRepository;
use lilthq\craftliltplugin\services\repository\external\LiltFileRepository;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Lilt Devs
 * @package   Craftliltplugin
 * @since     1.0.0
 *
 * @property LiltJobRepository $liltJobRepository
 * @property LiltConfigurationProvider $liltConfigurationProvider
 * @property LiltFileRepository $liltJobsFileRepository
 * @property Configuration $liltApiConfig
 * @property JobsApi $liltJobsApi
 */
class Craftliltplugin extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Craftliltplugin::$plugin
     *
     * @var Craftliltplugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string|null
     */
    private $connectorKey = null;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Craftliltplugin::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->connectorKey = getenv('CRAFT_LILT_PLUGIN_CONNECTOR_API_KEY');

        Craft::$app->getView()->registerAssetBundle(CraftLiltPluginAsset::class);
        $this->loadComponents();

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['lilt/orders/invoke'] = 'craft-lilt-plugin/order/invoke';
            }
        );

        Craft::info(
            Craft::t(
                'craft-lilt-plugin',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $navItem = parent::getCpNavItem();
        $navItem['subnav'] = [
            [
                'label' => 'Dashboard',
                'url' => 'craft-lilt-plugin',
            ],
            [
                'label' => 'Orders',
                'url' => 'craft-lilt-plugin/orders',
            ],
            [
                'label' => 'Translators',
                'url' => 'craft-lilt-plugin/translators',
            ],
            [
                'label' => 'Static Translations',
                'url' => 'craft-lilt-plugin/static-translations',
            ],
            [
                'label' => 'Settings',
                'url' => 'craft-lilt-plugin/settings',
            ]
        ];

        return $navItem;
    }

    /**
     * @return string|null
     */
    public function getConnectorKey(): ?string
    {
        return $this->connectorKey;
    }

    /**
     * @throws InvalidConfigException
     */
    private function loadComponents(): void
    {
        $this->setComponents([
            'createOrderHandler' => CreateOrderHandler::class,
            'liltConfigurationProvider' => LiltConfigurationProvider::class,
        ]);

        $this->set(
            'liltApiConfig',
            $this->liltConfigurationProvider->provide()
        );

        $this->set(
            'liltJobsApi',
            function() {
                return new JobsApi(
                    new Client(),
                    $this->liltApiConfig
                );
            }
        );

        $this->set(
            'liltJobRepository', [
                'class' => LiltJobRepository::class,
                'apiInstance' => $this->liltJobsApi,
            ]
        );

        $this->set(
            'liltJobsFileRepository', [
                'class' => LiltFileRepository::class,
                'apiInstance' => $this->liltJobsApi,
            ]
        );
    }
}
