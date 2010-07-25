<?php
	namespace Corelativ;
	use \Corelativ\Model,
		\Frawst\Library\Collection;
	
	/**
	 * Model collection class
	 *
	 * Model::find() will return results for 'all' operations as an instance
	 * of ModelSet instead of an array. This object can still be iterated like an
	 * array, but also provides some additional information not available using an
	 * array.
	 */
	class ModelSet extends Collection {
		public $page;
		public $totalRecords;
		public $totalPages;
		private $modelType;
		
		public function __construct($type, $items = array()) {
			$this->modelType = $type;
			parent::__construct('\\Corelativ\\Model\\'.$type, $items);
		}
		
		public function indexByPrimaryKey() {
			$class = $this->getType();
			parent::indexBy($class::primaryKeyField());
		}
		
		public function getModelType() {
			return $this->modelType;
		}
	}