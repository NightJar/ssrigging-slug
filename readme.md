# ssrigging-slug

##Requirements
* Silverstripe 3.1
* cms module (optional)

##Installation
* Simply drop into silverstripe root (using whatever method)
* `dev/build`

##Usage
It is best to supply parameters with the extension, so the easiest method of applying it is by definition in the class itself
```php
class Item extends DataObject {
	private static $extensions = array(
		'Slug("Title", "ParentID", true)' //Apply Extension!
	);
	private static $db = array(
		'Title' => 'Varchar'
	);
	private static $has_one = array(
		'Parent' => 'ItemsPage'
	);
	public function Link() {
		return $this->Parent()->Link().$this->URLSlug,'/';
	}
}
```
This part is optional, but is a common pattern (and part of this example as a whole). One does not necessarily have to use Page at all, or any kind of 'parent' type model object.
```php
class ItemsPage extends Page {
	private static $has_many = array(
		'Items' => 'Item'
	);
}
```
Then comes the much more necessary part where one adds a Controller method to access the items decorated items via a request (provided example here is for a Page_Controller). The method/action names are of course exemplary; So long as the applicable configuration elements are all consistent, the names can of course change.
```php
class ItemsPage_Controller extends Page_Controller {
	private static $url_handlers = array(
		'$Item!' => 'viewItem'
	);
	private static $allowed_actions = array(
		'viewItem'
	);
	public function viewItem() {
		$item = $this->getItem();
		if(!$item) $this->httpError(404);
		return array('Item' => $this->Items()->filter('URLSlug', $this->request->param('Item'))->first());
	}
	//One can use something like this to <% if $Top.activeItem == $Slug %> in a menu
	public function activeItem() {
		return $this->request->param('Item');
	}
}
```
One can then define a template such as ItemsPage_viewItem.ss
```html
<% with $Item %>
	$Title
	...
<% end_with %>
```

##About
The holder/child page type pattern is often... potentially unwieldy in undefined numbers. Other modules don't particularly give the flexibility required. ModelAdmin is for managing a type of Model, not for exposing them as pages via a site's front end. Slug provides a much needed a more generic solution, and can be applied easily to multiple objects on a site with minimal fuss. It should even work when they're accessed through the same controller provided one takes care in setting up the access methods.

###Properties
The Extension takes three parameters, all of which are optional (although defaults should not be relied upon):
1. The name of the field it should use to create a slug. (defaults to 'Title')
2. The name of a relation on the 'parent' object (Page, in the example above), allowing for nested URLs. By default a slug must of course be unique, and this is usually globally to the extended class. However defining a 'parent ID' allows for a slug to be unique under that parent only. Eg. With the page setup above if `ItemsPage`s were set up to list primary colours, one can have both `primary-colours/red` AND `primary-light-colours/red`, rather than `primary-light-colours/red1`. (defaults to null)
3. A parity bit. If set to true, whenever the field the module is bound to (Title by default, see 1.) is updated, the slug will automatically update also (keeping parity). Setting this to true also prevents the slug from being manually altered via the CMS by _not_ applying the field to the EditForm. (defaults to false)

##Notes
- Very simple in code, however an often sought after concept implemented as a great (and flexible) time saver. Concepts also good as a tutorial for learners... uuh, learning. Covers extensions, actions, routing, & request parameters.
- If a DataObject named Lettuce exists, it's data consistency will be compromised. Apply the silverstripe-blitzem extension to protect against this.
- This extension will probably cease to work if the DataObject it is applied to is named [BLITZEM](http://www.yates.co.nz/brand/blitzem/). Untested.
- _The previous two notes are jokes._ :)