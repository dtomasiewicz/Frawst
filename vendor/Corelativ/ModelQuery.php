<?php
	namespace Corelativ;
	use \DataPane;
	
	class ModelQuery extends DataPane\Query {
		protected $page;
		
		public function __construct($type, $tables, $params = array(), $data = null) {
			parent::__construct($type, $tables, $params, $data);
			
			$this->page = isset($params['page']) ? $params['page'] : null;
		}
		
		public function paginated() {
			return (bool) (!is_null($this->page) && !is_null($this->limit));
		}
		
		/**
		 * If a page number is defined, increase the offset by the appropriate amount.
		 * Default offset is 0.
		 * @param string $name Name of the property
		 * @return string If name is offset, will return the adjusted offset. Otherwise, see parent.
		 */
		public function __get($name) {
			if($name == 'offset' && $this->paginated()) {
				return $this->offset + ($this->page-1)*$this->limit;
			} else {
				return parent::__get($name);
			}
		}
	}