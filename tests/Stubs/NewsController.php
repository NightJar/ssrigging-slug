<?php

namespace Nightjar\Slug\Tests\Stubs;

use Nightjar\Slug\SlugHandler;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Controller;

class NewsController extends Controller implements TestOnly
{
    private static $extensions = [
        SlugHandler::class
    ];

    private static $url_segment = 'news';

    private static $slug_trails = [
        'stories' => 'Articles',
        'contributors' => 'Journalists',
    ];

    public function getViewer($action)
    {
        $testTemplate = '' .
            '<% if $ActiveSlug %>' .
                // using Name as Title is a default fallback in framework
                '$ActiveSlug.Title' .
            '<% else %>' .
                'Index ok' .
            '<% end_if %>';
        return SSViewer::fromString($testTemplate);
    }

    public function Link($action = null)
    {
        return 'news/';
    }
}
