<?php

namespace Nightjar\Slug\Tests;

use Nightjar\Slug\Slug;
use InvalidArgumentException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use Nightjar\Slug\Tests\Stubs\Article;
use Nightjar\Slug\Tests\Stubs\NewsPage;
use SilverStripe\Core\Injector\Injector;
use Nightjar\Slug\Tests\Stubs\Journalist;
use Nightjar\Slug\Tests\Stubs\NewsController;

class SlugHandlerTest extends FunctionalTest
{
    protected static $fixture_file = 'SlugTest.yml';

    protected static $extra_dataobjects = [
        Article::class,
        NewsPage::class,
        Journalist::class,
    ];

    /**
     * By all accounts this should be setUpBeforeClass... but it is too coarse a call to achieve getting
     * Journalist.URLSlug into the database (modifying Conifg before calling parent::setUpBeforeClass is
     * too soon, and calling after parent;:setUpBeforeClass is too late!). So we must hijack a call from
     * within setUpBeforeClass to allow for this. This is the only viable option!
     *
     * The reason for this is because we cannot define TestOnly yaml configuration like we can with php
     * fixture classes, and here we are also testing defining a service to apply the extension works.
     * This is important for e.g. backwards compatiblity aliases
     */
    public static function getExtraDataObjects()
    {
        $config = Config::modify();
        $config->set(Injector::class, 'JournalistSlug', [
            'class' => Slug::class,
            'constructor' => ['Name', 'NewsPages'],
        ]);
        $config->merge(Journalist::class, 'extensions', ['JournalistSlug']);

        // Do what we actually came here for
        return parent::getExtraDataObjects();
    }

    protected function setUp()
    {
        parent::setUp();
        $config = Config::modify();
        $data = $this->objFromFixture(NewsPage::class, 'holder');
        Injector::inst()->registerService($data, 'NewsPage');
        $spec = [
            'properties' => [
                'Failover' => '%$NewsPage',
            ],
        ];
        $config->merge(Injector::class, NewsController::class, $spec);

        // This could be done via $extra_controllers - but this would not allow us
        // to register the injection service above, which is critical for this test.
        // This is due to SapphireTest::getExtraRoutes instantiating each extra
        // controller as a singleton - which makes the above property injection irrelevant.
        $config->set(Director::class, 'rules', ['news' => NewsController::class]);
    }

    public function testRequestingSlugs()
    {
        $jim = $this->objFromFixture(Journalist::class, 'jim');
        $news = $this->objFromFixture(NewsPage::class, 'holder');
        $news->Journalists()->add($jim);

        $output = $this->get('news/');
        $this->assertEquals('Index ok', $output->getBody());
        $output = $this->get('news/nonsense/first-news');
        $this->assertEquals(404, $output->getStatusCode());

        $output = $this->get('news/stories/');
        $this->assertEquals(404, $output->getStatusCode());
        $output = $this->get('news/stories/first-news');
        $this->assertEquals('First News', $output->getBody());
        $output = $this->get('news/first-news');
        $this->assertEquals(404, $output->getStatusCode());

        $output = $this->get('news/contributors/');
        $this->assertEquals(404, $output->getStatusCode());
        $output = $this->get('news/contributors/jimbo-the-journo');
        $this->assertEquals('Jimbo the Journo', $output->getBody());
        $output = $this->get('news/jimbo-the-journo');
        $this->assertEquals(404, $output->getStatusCode());

        $output = $this->get('news/stories/jimbo-the-journo');
        $this->assertEquals(404, $output->getStatusCode());
        $output = $this->get('news/contributors/first-news');
        $this->assertEquals(404, $output->getStatusCode());
    }
}
