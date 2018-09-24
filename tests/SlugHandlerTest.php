<?php

namespace Nightjar\Slug\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;
use Nightjar\Slug\Tests\Stubs\NewsController;

class SlugHandlerTest extends FunctionalTest
{
    protected static $fixture_file = 'SlugTest.yml';

    protected function setUp()
    {
        parent::setUp();
        Config::modify()->set(
            Journalist::class,
            'extensions',
            [[
                'Slug' => [
                    'constuctor' => ['Name']
                ]
            ]]
        );
        Config::modify()->set(
            Director::class,
            'rules',
            ['news' => NewsController::class]
        );
    }

    public function testHandlingSlugs()
    {
        $output = $this->get('news/article/first-news');
        var_dump($output);
    }

    public function testHandlingSlugs()
    {
        $response = new HTTPRequest('GET', 'news/stories/first-news');
        $this->assertArrayHasKey('Item', $reponse);
        $this->assertInstanceOf(Article::class, $response['Item']);
        $responseArticle = $response['Item']
        $dbArticle = $this->objFromFixture(Article::class, 'one');
        $this->assertSame($dbArticle->ID, $responseArticle->ID);
    }
}
