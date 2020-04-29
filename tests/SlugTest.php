<?php

namespace Nightjar\Slug\Tests;

use Nightjar\Slug\Slug;
use InvalidArgumentException;
use UnexpectedValueException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use Nightjar\Slug\Tests\Stubs\Article;
use Nightjar\Slug\Tests\Stubs\Blitzem;
use Nightjar\Slug\Tests\Stubs\NewsPage;
use SilverStripe\Core\Injector\Injector;
use Nightjar\Slug\Tests\Stubs\Journalist;

class SlugTest extends SapphireTest
{
    protected $usesTransactions = false;

    protected static $fixture_file = 'SlugTest.yml';

    protected static $extra_dataobjects = [
        Article::class,
        Blitzem::class,
        NewsPage::class,
        Journalist::class,
    ];

    protected static $required_extensions = [
        Journalist::class => [
            Slug::class
        ]
    ];

    public function testGettingSlug()
    {
        $someone = Journalist::create(['Name' => 'Dave']);
        $this->assertEquals('dave', $someone->getSlug());
        $someone->update(['Name' => 'Jim']);
        $this->assertEquals('jim', $someone->getSlug());
        $someone->URLSlug = 'bob';
        $this->assertEquals('bob', $someone->getSlug());
        $this->assertEquals('jim', $someone->getSlug(true));
        $this->assertEquals('bob', $someone->getSlug());
        $someone->update(['Name' => 'Dave']);
        $this->assertEquals('bob', $someone->getSlug());
        $this->assertEquals('dave', $someone->getSlug(true));
    }

    public function testCannotAssociateToInvalidRelationshipType()
    {
        Config::modify()->merge(Blitzem::class, 'extensions', [Slug::class . '("Title", "Protects")']);

        $this->expectException(InvalidArgumentException::class);
        // Try to go through the Blitzem to get to the tasty Lettuce!
        Blitzem::create();
    }

    public function testSlugsWillSetAndSanitiseOnSave()
    {
        $journo = Journalist::create();
        $journo->update(['Name' => 'Ash Katchum!'])->extend('onBeforeWrite');
        $this->assertEquals('ash-katchum', $journo->URLSlug, 'initial write should sanitise');
        $journo->update(['URLSlug' => '5* Stories'])->extend('onBeforeWrite');
        $this->assertEquals('Ash Katchum!', $journo->Name, 'Name remains unaffected');
        $this->assertEquals('5-stories', $journo->URLSlug, 'onBeforeWrite should always sanitise');
    }

    public function testSlugKeepsParity()
    {
        $newArticle = Article::create();

        $newArticle->update(['Title' => 'Second News'])->fakeWrite();
        $this->assertEquals('second-news', $newArticle->URLSlug);

        $newArticle->update(['Title' => 'Sports News'])->fakeWrite();
        $this->assertEquals('sports-news', $newArticle->URLSlug);

        $newArticle->URLSlug = 'rugby-news';
        $this->expectException(UnexpectedValueException::class);
        $newArticle->fakeWrite();
        $this->assertEquals('sports-news', $newArticle->URLSlug);
    }

    /**
     * Uses the database.
     */
    public function testSlugCollisionsCorrectThemselves()
    {
        $newsPage = $this->objFromFixture(NewsPage::class, 'holder');
        $newArticle = Article::create()->update([
            'Title' => 'First news',
            'ParentID' => $newsPage->ID,
        ]);
        $newArticle->extend('onBeforeWrite');
        $this->assertEquals('first-news1', $newArticle->URLSlug);
        $this->assertEquals('First news', $newArticle->Title);
    }

    /**
     * Uses the database.
     */
    public function testCollisionDetectionIsLocalised()
    {
        $article = $this->objFromFixture(Article::class, 'one');
        $this->assertEquals('first-news', $article->URLSlug);
        $articleID = $article->ID;

        $competitionNews = NewsPage::create();
        $competitionNews->write();
        $newArticle = Article::create()->update([
            'Title' => 'First news',
            'ParentID' => $competitionNews->ID,
        ]);
        $newArticle->write();
        $this->assertEquals('first-news', $newArticle->URLSlug);
        $oldArticle = Article::get()->byID($articleID);
        $this->assertEquals('first-news', $oldArticle->URLSlug);
    }

    /**
     * Uses the database.
     */
    public function testLink()
    {
        $article = $this->objFromFixture(Article::class, 'one');
        $this->assertEquals('news/first-news', $article->Link());
        $iSpy = '?tracking=you#some-ad';
        $this->assertEquals("news/first-news${iSpy}", $article->Link($iSpy));
    }

    public function testCMSFieldsAreUpdated()
    {
        $fields = Article::create()->getCMSFields();
        $this->assertNull($fields->fieldByName('URLSlug'));

        $fields = Journalist::create()->getCMSFields();
        $this->assertNotNull($fields->dataFieldByName('URLSlug'));
    }

    public function testActiveUnsetByDefault()
    {
        $slug = new Slug;
        $this->assertSlugActiveUnset($slug);
    }

    private function assertSlugActiveUnset($slug)
    {
        $this->assertFalse($slug->isCurrent());
        $this->assertFalse($slug->isSection());
        $this->assertSame('link', $slug->LinkOrCurrent());
        $this->assertSame('link', $slug->LinkOrSection());
        $this->assertSame('link', $slug->LinkingMode());
    }

    public function testSettingSlugActive()
    {
        $slug = new Slug;
        $slug->setSlugActive(Slug::ACTIVE_CURRENT);

        // current is also a section head - this mirrors SiteTree behaviour
        $this->assertTrue($slug->isCurrent());
        $this->assertTrue($slug->isSection());
        $this->assertSame('current', $slug->LinkOrCurrent());
        $this->assertSame('section', $slug->LinkOrSection());
        $this->assertSame('current', $slug->LinkingMode());

        $slug->setSlugActive(Slug::ACTIVE_NONE);
        $this->assertSlugActiveUnset($slug);
    }

    public function testSettingSlugSection()
    {
        $slug = new Slug;
        $slug->setSlugActive(Slug::ACTIVE_SECTION);

        $this->assertFalse($slug->isCurrent());
        $this->assertTrue($slug->isSection());
        $this->assertSame('link', $slug->LinkOrCurrent());
        $this->assertSame('section', $slug->LinkOrSection());
        $this->assertSame('section', $slug->LinkingMode());

        $slug->setSlugActive(Slug::ACTIVE_NONE);
        $this->assertSlugActiveUnset($slug);
    }
}
