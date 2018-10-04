<?php

namespace Nightjar\Slug\Tests\Stubs;

use ReflectionProperty;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;

class Article extends DataObject implements TestOnly
{
    private static $extensions = [
        'Nightjar\Slug\Slug("Title", "Parent", true)'
    ];

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'Parent' => NewsPage::class
    ];

    protected $reflectedChanges = null;

    /**
     * Speeds up testing by negating the need for a particular test to be database backed.
     */
    public function fakeWrite()
    {
        if (!$this->reflectedChanges) {
            $this->reflectedChanges = new ReflectionProperty(DataObject::class, 'changed');
            $this->reflectedChanges->setAccessible(true);
        }
        $this->onBeforeWrite();
        $this->reflectedChanges->setValue($this, []);
        return $this->record['ID'];
    }
}
