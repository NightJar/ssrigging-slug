<?php 


/* eg.
 
DO:
	class Item extends DataObject {
		private static $extensions = array(
			'Slug("Title", "ParentID", true)'
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

Page:
	class ItemsPage extends Page {
		private static $has_many = array(
			'Items' => 'Item'
		);
	}
	class ItemsPage_Controller extends Page_Controller {
		private static $url_handlers = array(
			'$Item!' => 'viewItem'
		);
		private static $allowed_actions = array(
			'viewItem'
		);
		public function getItem() {
			return $this->Items()->filter('URLSlug', $this->request->param('Item'))->first();
		}
		public function viewItem() {
			$item = $this->getItem();
			if(!$item) $this->httpError(404);
			return array('Item' => $item);
		}
		public function activeItem() {
			return $this->request->param('Item');
		}
	}


*/