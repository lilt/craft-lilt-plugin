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
use lilthq\craftliltplugin\assets\CraftLiltPluginAsset;
use lilthq\craftliltplugin\services\order\CreateOrderHandler;
use yii\base\Event;

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
    public $hasCpSection = false;

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
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->getView()->registerAssetBundle(CraftLiltPluginAsset::class);

        $this->setComponents([
            'createOrderHandler' => CreateOrderHandler::class,
        ]);

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

        /*
        Craft::$app->view->hook('cp.entries.edit.settings', function(&$context) {
            $entry = $context['entry'];
            if ($entry->sectionId == 1) {
                $url = UrlHelper::url('some/path/'.$entry->id);
                return '<a href="'.$url.'" class="btn">My Button!</a>';
            }
        }); */
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

    // Protected Methods
    // =========================================================================
}
