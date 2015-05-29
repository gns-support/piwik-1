<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Access;
use Piwik\Widget\WidgetConfig;
use Piwik\Plugins\Goals\API;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Translate;
use Piwik\Widget\WidgetsList;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 */
class WidgetsListTest extends IntegrationTestCase
{
    public function testGet()
    {
        // setup the access layer
        $pseudoMockAccess = new FakeAccess;
        FakeAccess::$superUser = true;
        Access::setSingletonInstance($pseudoMockAccess);

        Fixture::createWebsite('2009-01-04 00:11:42');

        $_GET['idSite'] = 1;

        $widgets = WidgetsList::get();

        $widgetsPerCategory = $this->getWidgetsPerCategory($widgets);

        // check if each category has the right number of widgets
        $numberOfWidgets = array(
            'Dashboard_Dashboard' => 1,
            'General_Actions' => 15,
            'General_Visitors' => 34,
            'Example Widgets' => 5,
            'SEO' => 2,
            'Goals_Goals' => 5,
            'Live!' => 2,
            'Insights_WidgetCategory' => 2,
            'ExampleUI_UiFramework' => 8,
            'Referrers_Referrers' => 9,
        );

        // number of main categories
        $this->assertEquals(count($numberOfWidgets), count($widgetsPerCategory));

        foreach ($numberOfWidgets as $category => $widgetCount) {
            $this->assertEquals($widgetCount, count($widgetsPerCategory[$category]), sprintf("Widget: %s", $category));
        }
    }

    private function getWidgetsPerCategory(WidgetsList $list)
    {
        $widgetsPerCategory = array();
        foreach ($list->getWidgets() as $widgetConfig) {
            $category = $widgetConfig->getCategory();
            if (!isset($widgetsPerCategory[$category])) {
                $widgetsPerCategory[$category] = array();
            }

            $widgetsPerCategory[$category][] = $widgetConfig;
        }

        return $widgetsPerCategory;
    }

    public function testGetWithGoals()
    {
        // setup the access layer
        $pseudoMockAccess = new FakeAccess;
        FakeAccess::$superUser = true;
        Access::setSingletonInstance($pseudoMockAccess);

        Fixture::createWebsite('2009-01-04 00:11:42');

        $initialGoalsWidgets = 5;

        $_GET['idSite'] = 1;

        $perCategory = $this->getWidgetsPerCategory(WidgetsList::get());
        $this->assertEquals($initialGoalsWidgets, count($perCategory['Goals_Goals']));


        API::getInstance()->addGoal(1, 'Goal 1 - Thank you', 'title', 'Thank you', 'contains', $caseSensitive = false, $revenue = 10, $allowMultipleConversions = 1);

        $perCategory = $this->getWidgetsPerCategory(WidgetsList::get());

        // number of main categories
        $this->assertEquals(10, count($perCategory));
        $this->assertEquals($initialGoalsWidgets + 5, count($perCategory['Goals_Goals'])); // make sure widgets for that goal were added
    }

    public function testGetWithGoalsAndEcommerce()
    {
        // setup the access layer
        $pseudoMockAccess = new FakeAccess;
        FakeAccess::$superUser = true;
        Access::setSingletonInstance($pseudoMockAccess);

        Fixture::createWebsite('2009-01-04 00:11:42', true);
        API::getInstance()->addGoal(1, 'Goal 1 - Thank you', 'title', 'Thank you', 'contains', $caseSensitive = false, $revenue = 10, $allowMultipleConversions = 1);

        $_GET['idSite'] = 1;

        $perCategory = $this->getWidgetsPerCategory(WidgetsList::get());

        // number of main categories
        $this->assertEquals(11, count($perCategory));

        // check if each category has the right number of widgets
        $numberOfWidgets = array(
            'Goals_Goals'     => 10,
            'Goals_Ecommerce' => 6,
        );

        foreach ($numberOfWidgets as $category => $widgetCount) {
            $this->assertEquals($widgetCount, count($perCategory[$category]));
        }
    }

    public function testRemove()
    {
        // setup the access layer
        $pseudoMockAccess = new FakeAccess;
        FakeAccess::$superUser = true;
        Access::setSingletonInstance($pseudoMockAccess);

        Fixture::createWebsite('2009-01-04 00:11:42', true);
        API::getInstance()->addGoal(1, 'Goal 1 - Thank you', 'title', 'Thank you', 'contains', $caseSensitive = false, $revenue = 10, $allowMultipleConversions = 1);

        $_GET['idSite'] = 1;

        $list = WidgetsList::get();

        $this->assertCount(11, $this->getWidgetsPerCategory($list));

        $list->remove('SEO', 'NoTeXiStInG');

        $perCategory = $this->getWidgetsPerCategory($list);
        $this->assertCount(11, $perCategory);

        $this->assertArrayHasKey('SEO', $perCategory);
        $this->assertCount(2, $perCategory['SEO']);

        $list->remove('SEO', 'SEO_SeoRankings');

        $perCategory = $this->getWidgetsPerCategory($list);
        $this->assertCount(1, $perCategory['SEO']);

        $list->remove('SEO');

        $perCategory = $this->getWidgetsPerCategory($list);
        $this->assertArrayNotHasKey('SEO', $perCategory);
    }

    public function testIsDefined()
    {
        // setup the access layer
        $pseudoMockAccess = new FakeAccess;
        FakeAccess::$superUser = true;
        Access::setSingletonInstance($pseudoMockAccess);

        Translate::loadAllTranslations();

        Fixture::createWebsite('2009-01-04 00:11:42', true);

        $_GET['idSite'] = 1;

        $config = new WidgetConfig();
        $config->setCategory('Actions');
        $config->setName('Pages');
        $config->setModule('Actions');
        $config->setAction('getPageUrls');
        $list = WidgetsList::get();
        $list->addWidget($config);

        $this->assertTrue($list->isDefined('Actions', 'getPageUrls'));
        $this->assertFalse($list->isDefined('Actions', 'inValiD'));

        Translate::reset();
    }
}
