# ssrigging-slug

## Requirements

* silverstripe/framework ^4 || ^5
* silverstripe/cms (optional)

**Please note:** For a SilverStripe 3 compatible version, please see [the 1.0.0 release](https://github.com/nightjar/ssrigging-slug/tree/1.0.0).

## Installation

1. `composer require nightjar/ss-slug`
2. Apply the extensions as desired
3. Optionally add configuration
4. `dev/build`

## Usage

It is best to supply parameters with the extension (though not necessary if the defaults are sufficient, see **About:Properties** below), so the easiest method of applying it is by definition in the class itself

```php
namespace MyVendor\MyNamespace;

use SilverStripe\ORM\DataObject;
use Nightjar\Slug\Slug;

class Item extends DataObject {
    private static $extensions = [
        Slug::class . '("Title", "Parent", true)' // Apply Extension!
    ];
    private static $db = [
        'Title' => 'Varchar'
    ];
    private static $has_one = [
        'Parent' => 'ItemsPage'
    ];
    public function Link() { // Will override extension's Link() method
        return $this->Parent()->Link($this->URLSlug);
    }
}
```

Or this could happen via _config yaml files

```yaml
# Generate URL 'slugs' for Items
MyVendor\MyNamespace\Item
  - extensions:
    - Nightjar\Slug\Slug('Title','Parent',true)
```

This part is optional, but is a common pattern (and part of this example as a whole). One does not necessarily have to use `Page` for this, or any kind of 'parent' type model object (as the slug extension at base simply adds a property to a model).

```php
namespace MyVendor\MyNamespace;

use Page;

class ItemsPage extends Page {
    private static $has_many = [
        'Items' => 'Item'
    ];
}
```

Then comes the much more necessary part where one adds a Controller method to access the items decorated items via a request (provided example here is for a PageController). The controller extension works by providing new `url_handlers`, directing to an action called `handleSlug`. One could define a custom handler to deal with this logic, should the need arise.

```php
namespace MyVendor\MyNamespace;

use PageController;
use Nightjar\Slug\SlugHandler;

class ItemsPageController extends PageController {
    private static $extensions = [
        SlugHandler::class
    ];
}
```

Note: if your relation is not named `Items`, but e.g. `MyItems`, you have to configure `SlugHandler` in your controller class like:

```php
namespace MyVendor\MyNamespace;

use PageController;
use Nightjar\Slug\SlugHandler;

class ItemsPageController extends PageController {
    private static $extensions = [
        SlugHandler::class . '("MyItems")'
    ];
}
```


One can then define a template such as MyVendor/MyNamespace/ItemsPage_handleSlug.ss

```html
<% with $ActiveSlug %>
    $Title
    ...
<% end_with %>
```

## About
The holder/child page type pattern is often... potentially unwieldy in large numbers (more than 10). Other modules don't particularly give the flexibility required. ModelAdmin is for managing a type of Model, not for exposing them as pages via a site's front end. Slug provides a much needed a more generic solution, and can be applied easily to multiple objects on a site with minimal fuss. It should even work when they're accessed directly through the a controller, provided one takes care in setting up the configuration.

### Properties
The Slug Extension takes three constructor parameters, all of which are optional:

1. The name of the field it should use to create a slug. (defaults to 'Title')
2. The name of a relation on the 'parent' object (Page, in the example above), allowing for nested URLs. By default a slug must of course be unique, and this is usually globally to the extended class. However defining a 'parent' allows for a slug to be unique under that parent only. Eg. With the page setup above if `ItemsPage`s were set up to list primary colours, one can have both `primary-colours/red` AND `primary-light-colours/red`, rather than `primary-light-colours/red1`. (defaults to null)
3. A parity bit. If set to true, whenever the field the module is bound to (Title by default, see 1.) is updated, the slug will automatically update also (keeping parity). Setting this to true also prevents the slug from being manually altered via the CMS by _not_ applying the field to the EditForm. (defaults to false)

The SlugHandler extension takes two constructor parameters, both of which are optional:

1. The name of the method to get the list of slugged items on. Usually a relationshp name. If set no other relationships will be looked at for slugs (see **About:Configuration** below), and the slugged items can all be loaded as if they were the action - e.g. `some-page/slugged-item`. (defaults to `null`)
2. The source of data to call methods to fetch slugged item lists from. Controllers do not have relationships to objects, so we must first get one to be able to fetch related items via a slug. e.g. a PageController must first get a Page to be able to source it's data, so too must the SlugHandler find data with this method before dereferencing it. (defaults to 'getFailover')

### Configuration
The SlugHandler by default will look for routes by loading the configuration value of `slug_trails` from either the Controller it is associated with, or the DataObject (or other object - refer to the second constructor parameter explanation above) it leverages to access the list of slugged items. The format of this is `['route-name' => 'RelationshipName', ...]` in php, or `route-name: RelationshipName` in yaml.

With this configuration a route will be valid in the form `some-page/route-name/slugged-item`, where `route-name` is always static. This allows one to define routes to multiple relationships on a page instead of just one. To omit this configuration will necessitate the use of the first parameter to the SlugHandler constructor in order to be able to view any items from a request.

## Notes
- Very simple in code, however an often sought after concept implemented as a great (and flexible) time saver. Concepts also good as a tutorial for learners... uuh, learning. Covers extensions, actions, routing, & request parameters. Code is heavily commented with documentation blocks for this reason.
- If a DataObject named Lettuce exists, it's data consistency will be compromised. Apply the silverstripe-blitzem extension to protect against this.
- This extension will probably cease to work if the DataObject it is applied to is named [BLITZEM](http://www.yates.co.nz/brand/blitzem/). Untested.
- _The previous two notes are jokes._ :)
