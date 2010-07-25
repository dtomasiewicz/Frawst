<?php
	namespace Corelativ;
	use \Corelativ\Factory,
		\Corelativ\Validator,
		\DataPane;
	
	abstract class Model implements \Serializable {
		private static $nextUniqueId = 1;
		private $uniqueId;
		public static $defaultMapper = null;
		
		/**
		 * The name of the model (the base part of the class name). Set
		 * in constructor.
		 * @var string
		 */
		private $modelName;
		private static $modelNames = array();
		
		/**
		 * The datasource to use for database operations by this model. Set
		 * in constructor.
		 * @var mixed
		 */
		protected $Mapper;
		protected $Data;
		protected $Cache;
		
		/**
		 * The datasource where this model is stored.
		 */
		protected $dataSource = 'default';
		private static $dataSources = array();
		
		/**
		 * The field to be used as the primary key. May be overridden in subclasses.
		 * @var string
		 */
		protected $primaryKeyField = 'id';
		private static $primaryKeyFields = array();
		
		/**
		 * The field to be used by the toString method. May be overridden in subclasses.
		 * @var string
		 */
		protected $displayField = 'name';
		private static $displayFields = array();
		
		/**
		 * The name of the database table associated with this model. May be overridden
		 * in subclasses. Set in constructor.
		 * @var string
		 */
		protected $tableName;
		private static $tableNames = array();
		
		/**
		 * An associative array describing the relations between this model and other models.
		 * @var array
		 */
		protected $related = array();
		
		/**
		 * Associative array of validation instructions for this model.
		 * @var array
		 */
		protected $validation = array();
		
		/**
		 * Associative array of field names and information from this model's
		 * datasource. Set in constructor.
		 * @var array
		 */
		private $schema;
		
		/**
		 * Stores the saved properties of this model. Set in constructor.
		 * @var array
		 */
		private $properties;
		
		/**
		 * Stores the unsaved properties of this model. Set in constructor.
		 * @var array
		 */
		private $changes;
		
		/**
		 * Whether or not this model has been saved to the datasource since the
		 * most recent change. Set in constructor.
		 * @var boolean
		 */
		private $saved;
		
		/**
		 * Relation models used for getting/setting associated models.
		 * @var array
		 */
		private $relations = array();
		
		/**
		 * An associative array of validation errors from the most recent validate()
		 * call on this model. Set in constructor.
		 * @var array
		 */
		private $errors;
		
		/**
		 * When this model is acting as a proxy for a relationship or factory, this stores
		 * the factory objec that defines how this relationship behaves. Set in constructor.
		 * @var Factory
		 */
		protected $Factory;
		
		/**
		 * Constructor. This is the only place a primary key can be set from outside
		 * of the model.
		 */
		public function __construct($properties = array(), $mapper = null) {
			$this->Mapper = $mapper;
			$this->Data = $mapper->getDataController();
			$this->Cache = $mapper->getCacheController();
			
			$this->modelName = static::modelName();
			$this->tableName = static::tableName();
			$this->schema = isset($this->schema)
				? $this->schema
				: $this->Data->schema($this->tableName, $this->dataSource);
			$this->primaryKeyField = static::primaryKeyField();
			$this->displayField = static::displayField();
			
			if($properties instanceof Factory) {
				// empty model for use by a factory
				$this->Factory = $properties;
			} else {
				$this->uniqueId = self::$nextUniqueId++;
				$this->properties = array();
				$this->changes = array();
				$this->errors = array();
				
				$this->saved = isset($properties[$this->primaryKeyField]);
				
				foreach($this->schema as $field => $info) {
					if(isset($properties[$field])) {
						if($this->saved) {
							$this->properties[$field] = $properties[$field];
						} else {
							$this->properties[$field] = $this->Data->defaultValue($info, $this->dataSource);
							$this->changes[$field] = $properties[$field];
						}
					} else {
						$this->properties[$field] = $this->Data->defaultValue($info, $this->dataSource);
					}
				}
			}
		}
		
		public function __get($property) {
			return $this->get($property);
		}
		
		public function __set($property, $value) {
			$this->set($property, $value);
		}
		
		public function __toString() {
			return $this->get($this->displayField);
		}
		
		public function serialize() {
			return serialize($this->properties);
		}
		
		public function unserialize($properties) {
			$this->__construct(unserialize($properties), self::$defaultMapper);
		}
		
		/**
		 * Getter override. Will first check to see if the requested member name is a
		 * model property. If it is, it'll return the (unsaved) value of that property.
		 * Otherwise will attempt to return a relationship object.
		 * 
		 * @param string $property The name of the property/relation
		 * @return mixed The value of the property, or a relation object, if exists.
		 */
		public function get($property = null) {
			if(is_null($property)) {
				return $this->changes + $this->properties;
			} elseif($this->propertyExists($property)) {
				return isset($this->changes[$property]) ? $this->changes[$property] : $this->properties[$property];
			} elseif($rel = $this->relate($property)) {
				if($rel instanceof Factory\Singular) {
					return $rel->find();
				} else {
					return $rel;
				}
			} else {
				//@todo throw exception
				exit('unfound property: '.$property);
			}
		}
		
		/**
		 * Sets the value of a model property or relation. You can flag autoRelate
		 * to false to avoid setting any relations, if your data is compromisable
		 * (for example, if you are simply set()ing form data, users may be able to
		 * cross-site-script to change the relations).
		 * 
		 * @return true if everything set correctly, false if any property failed to
		 *         set. since primary keys are ignored (they should be treated as
		 *         immutable) a primary key may fail to set but still return true.
		 * 
		 * @signature[1] (string $name, mixed $value[, boolean $autoRelate = true])
		 * @param string $name The name of the property or relation to set
		 * @param mixed $value The value to set the property or relation to
		 * @param boolean $autoRelate Whether or not to allow relation setting
		 * 
		 * @signature[2] (array $values[, boolean $autoRelate = true])
		 * @param array $values An array of property => value pairs to be set
		 * @param boolean $autoRelate Whether or not to allow relation setting
		 */
		public function set($property, $value = '', $autoRelate = true) {
			$success = true;
			if(is_array($property)) {
				// signature[2]
				$autoRelate = $value;
				if($autoRelate !== false) {
					$autoRelate = true;
				}
				
				foreach($property as $p => $v) {
					$this->set($p, $v, $autoRelate);
				}
			} else {
				if($this->propertyExists($property)) {
					if($property != $this->primaryKeyField) {
						$setMethod = 'set'.ucfirst($property);
						
						if(method_exists($this, $setMethod)) {
							$this->changes[$property] = $this->$setMethod($value);
						} else {
							$this->changes[$property] = $value;
						}
					} elseif($value != $this->primaryKey()) {
						//@todo exception
						exit('trying to set a primary key different from the saved primary key.');
					}
				} elseif($autoRelate && ($rel = $this->relate($property))) {
					$rel->set($value);
				} else {
					//@todo exception
					exit('trying to set invalid model property or relation: '.$property);
				}
			}
		}
		
		/**
		 * Revert changes to a model and its relations (or to specified properties)
		 * 
		 * @interface model
		 * 
		 * @signature[1] ([string $property = null])
		 * @param string $property The property/relation to be reverted. If null, all
		 *                         properties and relations will be reverted.
		 * 
		 * @signature[2] (array $properties)
		 * @param array $properties An array of properties/relations to be reverted
		 */
		public function revert($property = null) {
			if(is_array($property)) {
				// signature[2]
				foreach($property as $p) {
					$this->revert($p);
				}
			} elseif(is_null($property)) {
				$this->changes = array();
				$this->relations = array();
			} else {
				if($this->propertyExists($property) && isset($this->changes[$property])) {
					unset($this->changes[$property]);
				} elseif(isset($this->relations[$property])) {
					unset($this->relations[$property]);
				}
			}
		}
		
		/**
		 * Saves models.
		 */
		public function save($relationships = true) {
			if(!$this->callHook('beforeValidate')) {
				return false;
			}
			
			if($this->validate($relationships) !== true) {
				return false;
			}
			
			if(!$this->callHook('beforeSave')) {
				return false;
			}
			
			$success = true;
			// only go through with the save if the model is unsaved or has changes
			if(count($this->changes) || !$this->saved) {
				if($this->saved) {
					$q = new DataPane\Query('update', $this->tableName, array(
						'values' => $this->changes,
						'where' => new DataPane\ConditionSet(array($this->primaryKeyField => $this->primaryKey())),
						'limit' => 1
					));
				} else {
					$q = new DataPane\Query('insert', $this->tableName, array('values' => $this->changes));
				}
				
				if($success = $this->Data->query($q, $this->dataSource)) {
					$this->properties = $this->changes + $this->properties;
					$this->changes = array();
							
					if($q->type == 'insert') {
						$this->properties[$this->primaryKeyField] = $this->Data->insertId($this->dataSource);
						$this->saved = true;
					}
				}
			}
			
			// save model relations
			if($success) {
				if($relationships) {
					$this->saveRelationships();
				}
				$this->callHook('afterSave');
				return $success;
			} else {
				//@todo exception
				exit('Could not save model: '.$this->Data->error());
			}
		}
		
		/**
		 * Saves relationships.
		 */
		private function saveRelationships() {
			foreach($this->relations as $relation) {
				$relation->save();
			}
		}
		
		/**
		 * Returns true if validation passes
		 */
		public function validate($relationships = true) {
			$validate = Validator::check($this, $this->validation);
			if(is_array($validate)) {
				$this->errors = $validate;
			} else {
				$this->errors = array();
			}
			
			if($relationships) {
				// validate relations too
				foreach($this->relations as $alias => $relation) {
					$rValidate = $relation->validate();
					if(is_array($rValidate)) {
						$this->errors[$alias] = $rValidate;
					}
				}
			}
			
			return (count($this->errors()) == 0) ? true : $this->errors();
		}
				
		/**
		 * Deletes this model.
		 */
		public function delete() {
			$q = new DataPane\Query('delete', $this->tableName, array(
				'limit' => 1,
				'where' => new DataPane\ConditionSet(array($this->primaryKeyField => $this->primaryKey()))
			));
			
			if($result = $this->Data->query($q, $this->dataSource)) {
				//@TODO delete relations?
				$this->properties[$this->primaryKeyField] = '';
				$this->saved = false;
				return $result;
			} else {
				throw new Exception\Model('Could not delete model: '.$this->Data->error($this->dataSource));
			}
		}
		
		/**
		 * Methods to return validation errors from the previous validation attempt.
		 */
		public function errors($field = null) {
			if(is_null($field)) {
				return $this->errors;
			} else {
				return $this->errors[$field];
			}
		}
		
		public function setErrors($field, $errors = array()) {
			$this->errors[$field] = $errors;
		}
		
		public function addError($field, $error) {
			if($old = $this->errors[$field]) {
				$old[] = $error;
				$this->errors[$field] = $old;
			} else {
				$this->errors[$field] = array($error);
			}
		}
		
		public function valid($fields = null) {
			if(!is_array($fields)) {
				$fields = array($fields);
			}
			foreach($fields as $field) {
				if(isset($this->errors[$field]) && count($this->errors[$field]) > 0) {
					return false;
				}
			}
			return true;
		}
		
		/**
		 * Calls a hook (callback) on this model
		 */
		public function callHook($method, $args = array()) {
			return call_user_func_array(array($this, $method), $args);
		}
		
		public function propertyExists($property) {
			return array_key_exists($property, $this->schema);
		}
		
		public function relate($alias) {
			if(isset($this->relations[$alias])) {
				return $this->relations[$alias];
			} elseif(isset($this->related[$alias])) {
				// create the relation object
				$related = $this->related[$alias];
				if(is_string($related)) {
					$related = array('type' => $related);
				}
				$related['objectAlias'] = $alias;
				if(!isset($related['model'])) {
					$related['model'] = $alias;
				}
				$related['subject'] = $this;
				
				$class = '\\Corelativ\\Factory\\'.$related['type'];
				return $this->relations[$alias] = new $class($related, $this->Mapper);
			} else {
				return false;
			}
		}
		
		public function factory($model) {
			return $this->Mapper->factory($model);
		}
		
		/**
		 * Hooks
		 */
		public function beforeFind($params) {
			return $params;
		}
		
		public function beforeSave() {
			return true;
		}
		
		public function beforeValidate() {
			return true;
		}
		
		public function afterSave() {
			
		}
		
		/**
		 * Determines whether or not the modle has been saved. If a field is specified,
		 * will instead return the saved value of that field.
		 */
		public function saved($field = null) {
			if(!is_null($field)) {
				if($this->propertyExists($field)) {
					return $this->properties[$field];
				} else {
					//@todo exception
					exit('Attempting to access saved value of invalid property: '.$field);
				}
			}
			
			return $this->saved;
		}
		
		public function uniqueId() {
			return $this->uniqueId;
		}
		
		public function primaryKey() {
			return $this->properties[$this->primaryKeyField];
		}
		
		public static function dataSource() {
			if(isset($this)) {
				return $this->dataSource;
			} else {
				$c = get_called_class();
				if(!isset(self::$dataSources[$c])) {
					$v = get_class_vars($c);
					self::$dataSources[$c] = $v['dataSource'];
				}
				return self::$dataSources[$c];
			}
		}
		
		public static function modelName() {
			if(isset($this)) {
				return $this->modelName;
			} else {
				$c = get_called_class();
				if(!isset(self::$modelNames[$c])) {
					$cParts = explode('\\', $c);
					self::$modelNames[$c] = array_pop($cParts);
				}
				return self::$modelNames[$c];
			}
		}
		
		public static function tableName() {
			if(isset($this)) {
				return $this->tableName;
			} else {
				$c = get_called_class();
				if(!isset(self::$tableNames[$c])) {
					$v = get_class_vars($c);
					self::$tableNames[$c] = isset($v['tableName'])
						? $v['tableName']
						: static::modelName();
				}
				return self::$tableNames[$c];
			}
		}
		
		public static function primaryKeyField() {
			if(isset($this)) {
				return $this->primaryKeyField;
			} else {
				$c = get_called_class();
				if(!isset(self::$primaryKeyFields[$c])) {
					$v = get_class_vars($c);
					self::$primaryKeyFields[$c] = $v['primaryKeyField'];
				}
				return self::$primaryKeyFields[$c];
			}
		}
		
		public static function displayField() {
			if(isset($this)) {
				return $this->displayField;
			} else {
				$c = get_called_class();
				if(!isset(self::$displayFields[$c])) {
					$v = get_class_vars($c);
					self::$displayFields[$c] = $v['displayField'];
				}
				return self::$displayFields[$c];
			}
		}
	}