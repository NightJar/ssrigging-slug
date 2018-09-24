<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Control\Controller;
use Nightjar\Slug\Slug;

class NewsController extends Controller implements TestOnly
{
    private static $extensions = [
        'SlugHandler'
    ];

    private static $slug_trails = [
        'stories' => 'Articles',
        'contributors' => 'Journalists'
    ];
}
