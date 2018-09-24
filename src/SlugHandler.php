<?php

namespace Nightjar\Slug;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;

/**
 * This is for handling a request for a slugged DataObject, and should be applied to a Controller.
 *
 * One can either supply the method name (to get slugs from) as a string to the constructor,
 * or define a slug_trails {@see SilverStripe\Core\Config} property on the Controller.
 * The latter allows support for multiple slugged relationships to reside on the same
 * {@see SilverStripe\ORM\DataObject}, and the `slug_trails` property is an array map
 * in the format of
 * [ $Trail => $RelationshipName, ... ]
 * where $Trail is the URL segment {@see $url_handlers} it is loaded over,
 * and $RelationshipName is the internal method to call to resolve the $Slug parameter
 * of the url_handler.
 * a.k.a.
 * MyController:
 *   slug_trails:
 *     'news' => 'NewsArticles'
 */
class SlugHandler extends Extension
{
    /**
     * We must define these statically up front, so we cannot tell if the model we'd otherwise be handling has only
     * one slugged relationship, or many of them. In the former case the simple $Slug is sufficient, but if there are
     * many releationships we require $Trail in order to follow to the correct slug. So both routes are defiend.
     *
     * @var array
     */
    private static $url_handlers = [
        '$Trail/$Slug!' => 'handleSlug',
        '$Slug!' => 'handleSlug'
    ];

    private static $allowed_actions = [
        'handleSlug'
    ];

    /**
     * Holds relationship info for single slugged relationship objects, if passed to the constructor
     *
     * @var null|array
     */
    protected $slugs;

    /**
     * Which function on the Controller will get us the initial DataObject?
     *
     * @var array
     */
    protected $data;

    /**
     * Apply extension to {@see SilverStripe\Control\Controller}.
     * Supply a parameter as a shorthand if handling multiple slugs on the same object is unneeded.
     * Also takes the name of the initial getter function used to move from Controller to Model,
     * the default is set to 'getFailover' as this is nicely generic - so if this property is not set
     * then a failover should be ensured. The data parameter could even be `Me` if the controller is 
     * 'bare' and getter methods exist directly on it for each slugged object type (with no parent relation).
     *
     * @param string $relationship Relationship name (optional)
     * @param string $data getter function name
     */
    public function __construct($relationship = null, $data = 'getFailover')
    {
        parent::__construct();
        $this->slugs = $relationship ? ['' => $relationship] : null;
        $this->data = $data;
    }

    protected function findSlug()
    {
        // You're probably wondering about these variable names...
        /** @var SilverStripe\Control\Controller */
        $owner = $this->getOwner();
        $request = $owner->getRequest();
        $plot = $this->data;
        $garden = $owner->$plot();
        $slime = $this->slugs;
        if (!$slime) {
            $slime = $garden->config()->get('slug_trails') ?: $owner->config()->get('slug_trails');
        }
        $trail = $request->param('Trail') ?: '';
        $slimeTrail = array_key_exists($trail, $slime) ? $slime[$trail] : $owner->httpError(404);
        $slug = $request->param('Slug');
        // ...I follow slime trails in the garden to find slugs :)
        return $garden->$slimeTrail()->find('URLSlug', $slug);
    }

    /**
     * Process a request for a slugged item.
     *
     * @param HTTPRequest $request
     * @return array customisation of owner's otherwise normal output
     */
    public function handleSlug(HTTPRequest $request)
    {
        $owner = $this->getOwner();
        $item = $this->findSlug();
        if (!$item) {
            $owner->httpError(404);
        }
        $item->setSlugActive(Slug::ACTIVE_CURRENT);
        return ['ActiveSlug' => $item];
    }
}
