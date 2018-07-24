<?php

namespace NightJar\Slug\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Parsers\URLSegmentFilter;

class Slug extends DataExtension {

    private static $db = [
        'URLSlug' => 'Varchar(255)'
    ];
    
    protected $parentRel = null;
    protected $name = 'Title';
    protected $enforceParity = false;
    
    public function __construct($name='Title', $parentRel=null, $enforceParity=false) {
        parent::__construct();
        
        if(!empty($parentRel))
            $this->parentRel = $parentRel;
        if(!empty($name))
            $this->name = $name;
        $this->enforceParity = $enforceParity;
    }

    public function Slug($regen=false) {
        $name = $this->name;
        $existing = $this->owner->URLSlug;
        return $existing && !$regen ? $existing : URLSegmentFilter::create()->filter($this->owner->$name);
    }
    
    public function onBeforeWrite() {
        if($this->owner->isChanged('URLSlug') || !$this->owner->URLSlug || $this->owner->isChanged($this->name)) {
            $this->owner->URLSlug = $this->Slug($this->enforceParity);
            $class = $this->owner->getClassName();
            $filter = [
                'URLSlug' => $this->owner->URLSlug
            ];
            if($parent = $this->parentRel)
                $filter[$parent] = $this->owner->$parent;
            $count = 1;
            while($exists = $class::get()->filter($filter)->exclude('ID', $this->owner->ID)->exists()) {
                $this->owner->URLSlug = $this->owner->URLSlug.$count++;
                $filter['URLSlug'] = $this->owner->URLSlug;
            }
        }
    }
    
    public function updateCMSFields(FieldList $fields) {
        if($this->enforceParity)
            $fields->removeByName('URLSlug');
    }
    
}
