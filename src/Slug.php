<?php

namespace Nightjar\Slug;

use InvalidArgumentException;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Parsers\URLSegmentFilter;
use UnexpectedValueException;

/**
 * Adds a 'url slug' property to DataObject classes, in order for them to be able to be loaded via a URL
 * through a{@see SilverStripe\Control\Controller}. The typical use case for this is to view records related
 * to a {@see Page}, to be able to have a controller action that doesn't need to reference the ID ofthe record.
 * E.g. /products/nice-jacket - where this is /pageType/relatedObject-notAPage
 *
 * To simplify the controller section of this purpose {@see SlugHandler}
 */
class Slug extends DataExtension
{
    /**
     * Used to set {@see $active} to inactive, or 'link'
     */
    const ACTIVE_NONE = null;

    /**
     * Used to set and check {@see $active} as a section (ancestor of 'current')
     */
    const ACTIVE_SECTION = false;

    /**
     * Used to set and check {@see $active} as the current object active in a request
     */
    const ACTIVE_CURRENT = true;

    private static $db = [
        'URLSlug' => 'Varchar(255)',
    ];

    private static $indexes = [
        'URLSlug' => true,
    ];

    /**
     * The field on the owner that we should take the value from in order to generate a slug
     *
     * @var string
     */
    protected $fieldToSlug;

    /**
     * If we should restrict the slugging uniqueness to a certain subset of the owner class,
     * this will be the name of the relation to filter by to detect uniqueness
     *
     * @var string|null
     */
    protected $relationName;

    /**
     * Whether or not we should update the URLSlug field when the field to slug changes
     *
     * @var boolean
     */
    protected $enforceParity;

    /**
     * The owner has been accessed via a route involving the URLSlug
     * Tri state; current, section, none
     *
     * @var null|boolean {@see setSlugActive}
     */
    protected $active = null;

    /**
     * Apply extension with configurable defaults
     *
     * @param string $fieldToSlug The field on the owner to base the URL Slug from - defaults to 'Title'
     * @param string $relationName Optional name of the has_one relationship to the owner's parent class/Page
     * @param boolean $enforceParity true to alter the URLSlug whenever the $fieldToSlug changes value (default: false)
     */
    public function __construct($fieldToSlug = 'Title', $relationName = null, $enforceParity = false)
    {
        parent::__construct();
        $this->fieldToSlug = $fieldToSlug;
        $this->relationName = $relationName;
        $this->enforceParity = $enforceParity;
    }

    public function setOwner($owner)
    {
        // parent method is set in a try, with a finally to clearOwner - so it is important we set it first,
        // otherwise our InvalidArgumentException will be swallowed by a BadMethodCallException!
        parent::setOwner($owner);

        // throw an exception if the $relationName is invalid or not has_one
        if ($this->relationName) {
            $ownerClass = get_class($owner);
            $valid = DataObject::getSchema()->hasOneComponent($ownerClass, $this->relationName);
            if (!$valid) {
                throw new InvalidArgumentException("$this->relationName is an invalid has_one on $ownerClass");
            }
        }
    }

    /**
     * Set whether this slugged item is being viewed
     * {@see self::ACTIVE_NONE}
     * {@see self::ACTIVE_SECTION}
     * {@see self::ACTIVE_CURRENT}
     *
     * @param null|boolean $active
     * @return DataObject
     * @throws InvalidArgumentException
     */
    public function setSlugActive($active)
    {
        $validParameter = (
            $active === self::ACTIVE_NONE
            || $active === self::ACTIVE_SECTION
            || $active === self::ACTIVE_CURRENT
        );

        if (!$validParameter) {
            throw new InvalidArgumentException(
                "Passed an invalid value to setSlugActive. Please use the constants provided on" . self::class
            );
        }
        $this->active = $active;
        return $this->getOwner();
    }

    /**
     * Check for collisions, if we need to update the slug and
     * update the model with the confirmedsafe value before writing
     */
    public function onBeforeWrite()
    {
        if ($this->slugIsOutdated()) {
            $this->updateSlug();
        } elseif ($this->slugHasChanged()) {
            // enforceParity is set, and the slug has changed... but the
            // field to slug has not. We need to reset to keep parity.
            // Ideally we could do this via the $original property through
            // {@see DataObject::getChangedFields}, however the returned
            // 'before' value for a changed field is unreliable :(
            // https://github.com/silverstripe/silverstripe-framework/issues/8443
            throw new UnexpectedValueException(
                'URLSlug has been updated independently of the tracked field, ' .
                'but this has been disabled via Slug::enforceParity'
            );
        }
    }

    /**
     * Helper to decide if a slug is outdated
     *
     * @return bool
     */
    public function slugIsOutdated(): bool
    {
        $owner = $this->getOwner();
        $slugHasChanged = $this->slugHasChanged();
        $fieldToSlugHasChanged = $owner->isChanged($this->fieldToSlug);
        $relationHasChanged = $this->relationName && $owner->isChanged($this->relationName . 'ID');

        return (
            empty($owner->URLSlug)
            || ($this->enforceParity && $fieldToSlugHasChanged)
            || (!$this->enforceParity && $slugHasChanged)
            || $relationHasChanged
        );
    }

    /**
     * the actual logic for updating the slug and fix collisions with other slugs that have the same parent
     */
    public function updateSlug(): void
    {
        $owner = $this->getOwner();
        $owner->URLSlug = $this->getSlug($this->enforceParity);

        $collisionList = DataObject::get(get_class($owner))->exclude('ID', $owner->ID);
        $filter = ['URLSlug' => $owner->URLSlug];

        if ($this->relationName) {
            $parentIDField = $this->relationName . 'ID';
            $filter[$parentIDField] = $owner->$parentIDField;
            // Also handle polymorphic relationships
            $parentClassName = DataObject::getSchema()->hasOneComponent(get_class($owner), $this->relationName);
            if ($parentClassName === DataObject::class) {
                $parentClassField = $this->relationName . 'Class';
                $filter[$parentClassField] = $owner->$parentClassField;
            }
        }

        $count = 1;
        $origSlug = $owner->URLSlug;
        while ($collisionList->filter($filter)->exists()) {
            $owner->URLSlug = implode('-', [$origSlug, $count++]);
            $filter['URLSlug'] = $owner->URLSlug;
        }
    }

    /**
     * Generate a url slug segment
     *
     * @param boolean $forceRegeneration
     * @return string
     */
    public function getSlug($forceRegeneration = false)
    {
        $owner = $this->getOwner();
        $field = $this->fieldToSlug;
        $unfilteredSlug = $owner->URLSlug;
        if (!$unfilteredSlug || $forceRegeneration) {
            $unfilteredSlug = $owner->$field;
        }
        return URLSegmentFilter::create()->filter($unfilteredSlug);
    }

    /**
     * Helper to decide if the slug field has changed
     *
     * @return bool
     */
    public function slugHasChanged(): bool
    {
        return $this->owner->isChanged('URLSlug');
    }

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->enforceParity) {
            $fields->removeByName('URLSlug');
        }
    }

    /**
     * Get the link to be able to request this (owner) object from a URL
     * The nature of a DataExtension is that this can be overridden at any
     * time by defining the `Link($action = null)` method on the owner class.
     *
     * @param string|array $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = null;
        $owner = $this->getOwner();
        $action = ($action) ? Controller::join_links($owner->URLSlug, $action) : $owner->URLSlug;

        $relationName = $this->relationName;
        if ($relationName && ($parent = $owner->$relationName()) && $parent->hasMethod('Link')) {
            $link = $parent->Link($action);
        } elseif (Controller::has_curr()) {
            // Quite the assumption, but sufficient in most cases.
            $link = Controller::curr()->Link($action);
        }
        return $link;
    }

    /**
     * Return "link" or "section" depending on if this is the current viewing object.
     * {@see isCurrent}
     *
     * @return string
     */
    public function LinkOrCurrent()
    {
        return $this->isCurrent() ? 'current' : 'link';
    }

    /**
     * Returns true if this is the slugged object being used to handle this request.
     *
     * @return boolean
     */
    public function isCurrent()
    {
        return $this->active === self::ACTIVE_CURRENT;
    }

    /**
     * Return "link" or "section" depending on if this is the current section.
     * {@see isSection}
     *
     * @return string
     */
    public function LinkOrSection()
    {
        return $this->isSection() ? 'section' : 'link';
    }

    /**
     * Check if this slugged object is in the currently active section
     * (i.e. it, or one of its children is currently being viewed).
     *
     * @return boolean
     */
    public function isSection()
    {
        return $this->isCurrent() || ($this->active === self::ACTIVE_SECTION);
    }

    /**
     * Return "current", "section", or "link" depending on if this page is the slugged item
     * currently being viewed, in the section (an ancestor of the current), or simply exists - respectively.
     *
     * @return string
     */
    public function LinkingMode()
    {
        if ($this->isCurrent()) {
            return 'current';
        } elseif ($this->isSection()) {
            return 'section';
        } else {
            return 'link';
        }
    }
}
