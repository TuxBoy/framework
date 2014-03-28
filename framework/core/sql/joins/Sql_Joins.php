<?php
namespace SAF\Framework;

/**
 * This builds and stores SQL tables joins in order to make easy automatic joins generation
 * knowing only a source business object and property paths.
 */
class Sql_Joins
{

	//-------------------------------------------------------------------------------- $alias_counter
	/**
	 * alias counter for the next aliased table
	 *
	 * @var integer
	 */
	private $alias_counter;

	//-------------------------------------------------------------------------------------- $classes
	/**
	 * link property full path to their class name
	 *
	 * @var string[] key is property full path
	 */
	private $classes = [];

	//---------------------------------------------------------------------------------------- $joins
	/**
	 * link property path to sql join
	 *
	 * @var Sql_Join[] key is property full path
	 */
	private $joins = [];

	//----------------------------------------------------------------------------------- $link_joins
	/**
	 * joins for properties coming from classes having the 'link' annotation
	 *
	 * @var Sql_Join[] key is property full path
	 */
	private $link_joins = [];

	//-------------------------------------------------------------------------------- $linked_tables
	/**
	 * linked tables
	 *
	 * Each key is the linked table name
	 * Each value is a string[] : element 0 is the master column name, 1 is the foreign column name
	 *
	 * @var string[]
	 */
	private $linked_tables = [];

	//----------------------------------------------------------------------------------- $properties
	/**
	 * link class names to their properties
	 *
	 * @var array The 2 keys are class and property name, value is Reflection_Property
	 */
	private $properties = [];

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct Sql_Joins object and prepare joins for a list of property paths
	 *
	 * @param $starting_class_name string the class name for the root of property paths
	 * @param $paths array a property paths list to add at construction
	 */
	public function __construct($starting_class_name, $paths = [])
	{
		$this->alias_counter = 1;
		$this->classes[''] = $starting_class_name;
		$this->addProperties('', $starting_class_name);
		foreach ($paths as $path) {
			$this->add($path);
		}
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Adds a property path to the joins list
	 *
	 * @param $path string  full path to desired property, starting from starting class
	 * @param $depth integer for internal use : please do not use this
	 * @return Sql_Join the added join, or null if $path does not generate any join
	 */
	public function add($path, $depth = 0)
	{
		if (isset($this->joins[$path]) || array_key_exists($path, $this->joins)) {
			return $this->joins[$path];
		}
		list($master_path, $master_property_name) = Sql_Builder::splitPropertyPath($path);
		if ($master_path && !isset($this->joins[$master_path])) {
			$this->add($master_path, $depth + 1);
		}
		if (isset($this->link_joins[$path])) {
			$property_type = $this->getProperties($master_path)[$master_property_name]->getType();
			if ($property_type->isClass()) {
				$linked_master_alias = $this->link_joins[$path]->foreign_alias;
			}
			else {
				return $this->link_joins[$path];
			}
		}
		$join = new Sql_Join();
		$foreign_class_name = (strpos($master_property_name, '->'))
			? $this->addReverseJoin($join, $master_path, $master_property_name, $path)
			: $this->addSimpleJoin($join, $master_path, $master_property_name, $path);
		$this->joins[$path] = $join->mode
			? $this->addFinalize($join, $master_path, $foreign_class_name, $path, $depth)
			: null;
		if (isset($linked_master_alias)) {
			$join->master_alias = $linked_master_alias;
		}
		return $this->joins[$path];
	}

	//----------------------------------------------------------------------------------- addFinalize
	/**
	 * @param $join Sql_Join
	 * @param $master_path string
	 * @param $foreign_class_name string
	 * @param $foreign_path string
	 * @param $depth integer
	 * @return Sql_Join
	 */
	private function addFinalize(
		Sql_Join $join, $master_path, $foreign_class_name, $foreign_path, $depth
	) {
		if (!$depth) {
			$join->type = Sql_Join::OBJECT;
		}
		$join->foreign_alias = 't' . $this->alias_counter++;
		if (!isset($join->foreign_table)) {
			$join->foreign_class = Builder::className($foreign_class_name);
			$join->foreign_table = Dao::storeNameOf($join->foreign_class);
		}
		if (!isset($join->master_alias)) {
			$join->master_alias = $master_path ? $this->getAlias($master_path) : 't0';
		}
		$this->classes[$foreign_path] = $foreign_class_name;
		$this->addProperties($foreign_path, $foreign_class_name, $join->mode);
		return $join;
	}

	//--------------------------------------------------------------------------------------- addJoin
	/**
	 * Adds a join and automatically set its foreign alias to the next one (if not already set)
	 *
	 * @param Sql_Join $join
	 */
	public function addJoin(Sql_Join $join)
	{
		if (!isset($join->foreign_alias)) {
			$join->foreign_alias = 't' . $this->alias_counter++;
		}
		$this->joins[] = $join;
	}

	//-------------------------------------------------------------------------------- addLinkedClass
	/**
	 * Add a link class (using the 'link' class annotation) to joins
	 *
	 * @param $path               string     the property path
	 * @param $class              Link_Class the link class itself (which contains the @link)
	 * @param $linked_class_name  string     the linked class name (the value of @link)
	 * @param $join_mode          string
	 * @return Reflection_Property[] the properties that come from the linked class,
	 * for further exclusion
	 */
	private function addLinkedClass($path, $class, $linked_class_name, $join_mode)
	{
		$linked_class = new Reflection_Class($linked_class_name);
		$join = new Sql_Join();
		$join->master_alias   = 't' . ($this->alias_counter - 1);
		$join->master_column  = 'id_' . $class->getCompositeProperty()->getAnnotation('storage')->value;
		$join->foreign_alias  = 't' . $this->alias_counter++;
		$join->foreign_column = 'id';
		$join->foreign_class  = Builder::className($linked_class_name);
		$join->foreign_table  = Dao::storeNameOf($join->foreign_class);
		$join->mode           = ($join_mode == Sql_Join::LEFT) ? Sql_Join::LEFT : Sql_Join::INNER;
		$join->type           = Sql_Join::LINK;
		if (!isset($this->joins[$path])) {
			// this ensures that the main path is set before the linked path
			$this->joins[$path] = null;
		}
		$this->joins[($path ? ($path . '-') : '') . $join->foreign_table . '-@link'] = $join;
		$more_linked_class_name = $linked_class->getAnnotation('link')->value;
		$exclude_properties = $more_linked_class_name
			? $this->addLinkedClass($path, $class, $more_linked_class_name, $join_mode)
			: [];
		foreach ($linked_class->getAllProperties() as $property) if (!$property->isStatic()) {
			if (!isset($exclude_properties[$property->name])) {
				$this->properties[$linked_class_name][$property->name] = $property;
				$property_path = ($path ? $path . DOT : '') . $property->name;
				$type = $property->getType();
				if ($type->isClass()) {
					$this->classes[$property_path] = $property->getType()->getElementTypeAsString();
				}
				$this->link_joins[$property_path] = $join;
				$exclude_properties[$property->name] = true;
			}
		}
		return $exclude_properties;
	}

	//--------------------------------------------------------------------------------- addLinkedJoin
	/**
	 * @param $join               Sql_Join
	 * @param $master_path        string
	 * @param $foreign_path       string
	 * @param $foreign_class_name string
	 * @param $property           Reflection_Property
	 * @param $reverse            boolean
	 */
	private function addLinkedJoin(
		Sql_Join $join, $master_path, $foreign_path, $foreign_class_name,
		Reflection_Property $property, $reverse = false
	) {
		$link_table = new Sql_Link_Table($property);
		$linked_join = new Sql_Join();
		$linked_join->foreign_column = $reverse ? $link_table->foreignColumn() : $link_table->masterColumn();
		$linked_join->foreign_table = $link_table->table();
		$linked_join->master_column = 'id';
		$linked_join->mode = $join->mode;
		$this->joins[$foreign_path . '-link'] = $this->addFinalize(
			$linked_join, $master_path ? $master_path : 'id', $foreign_class_name, $foreign_path, 1
		);
		$join->foreign_column = 'id';
		$join->master_column = $reverse ? $link_table->masterColumn() : $link_table->foreignColumn();
		$join->master_alias = $linked_join->foreign_alias;
		$this->linked_tables[$linked_join->foreign_table] = [
			$join->master_column, $linked_join->foreign_column
		];
	}

	//----------------------------------------------------------------------------------- addMultiple
	/**
	 * Adds multiple properties paths to the joins list
	 *
	 * @param $paths_array string[]
	 * @return Sql_Joins
	 */
	public function addMultiple($paths_array)
	{
		foreach ($paths_array as $path) {
			$this->add($path);
		}
		return $this;
	}

	//--------------------------------------------------------------------------------- addProperties
	/**
	 * Adds properties of the class name into $properties
	 *
	 * Please always call this instead of adding properties manually : it manages 'link'
	 * class annotations.
	 *
	 * @param $path       string
	 * @param $class_name string
	 * @param $join_mode  string
	 */
	private function addProperties($path, $class_name, $join_mode = null)
	{
		$class = new Link_Class($class_name);
		$this->properties[$class_name] = $class->getAllProperties();
		$linked_class_name = $class->getAnnotation('link')->value;
		if ($linked_class_name) {
			$this->addLinkedClass($path, $class, $linked_class_name, $join_mode);
		}
	}

	//-------------------------------------------------------------------------------- addReverseJoin
	/**
	 * @param $join                 Sql_Join
	 * @param $master_path          string
	 * @param $master_property_name string
	 * @param $foreign_path         string
	 * @return string the foreign class name
	 * @todo use @storage to get correct master and foreign columns name
	 */
	private function addReverseJoin(
		Sql_Join $join, $master_path, $master_property_name, $foreign_path
	) {
		list($foreign_class_name, $foreign_property_name) = explode('->', $master_property_name);
		$foreign_class_name = Namespaces::fullClassName($foreign_class_name);
		if (strpos($foreign_property_name, '=')) {
			list($foreign_property_name, $master_property_name) = explode('=', $foreign_property_name);
			$join->master_column  = 'id_' . $master_property_name;
		}
		else {
			$join->master_column = 'id';
		}
		$join->foreign_column = 'id_' . $foreign_property_name;
		$join->mode = Sql_Join::LEFT;
		$foreign_property = new Reflection_Property($foreign_class_name, $foreign_property_name);
		if ($foreign_property->getType()->isMultiple()) {
			$this->addLinkedJoin(
				$join, $master_path, $foreign_path, $foreign_class_name, $foreign_property, true
			);
		}
		return $foreign_class_name;
	}

	//--------------------------------------------------------------------------------- addSimpleJoin
	/**
	 * @param $join                 Sql_Join
	 * @param $master_path          string
	 * @param $master_property_name string
	 * @param $foreign_path         string
	 * @return string the foreign class name
	 */
	private function addSimpleJoin(Sql_Join $join, $master_path, $master_property_name, $foreign_path)
	{
		$foreign_class_name = null;
		$master_property = $this->getProperty($master_path, $master_property_name);
		if ($master_property) {
			$foreign_type = $master_property->getType();
			if ($foreign_type->isMultiple() && ($foreign_type->getElementTypeAsString() == 'string')) {
				// TODO : string[] can have multiple implementations, depending on database engine
				// linked strings table, mysql set.. should find a way to make this common without
				// knowing anything about the specific
				$foreign_class_name = $foreign_type->asString();
			}
			elseif (!$foreign_type->isBasic()) {
				$join->mode = $master_property->getAnnotation('mandatory')->value
					? Sql_Join::INNER
					: Sql_Join::LEFT;
				if ($foreign_type->isMultiple()) {
					$foreign_class_name = $foreign_type->getElementTypeAsString();
					$foreign_property_name = $master_property->getAnnotation('foreign')->value;
					if (
						property_exists($foreign_class_name, $foreign_property_name)
						&& ($master_property->getAnnotation('link')->value != 'Map')
					) {
						$foreign_property = new Reflection_Property(
							$foreign_class_name, $foreign_property_name
						);
						$join->foreign_column = 'id_' . $foreign_property->getAnnotation('storage')->value;
						$join->master_column  = 'id';
					}
					else {
						$this->addLinkedJoin(
							$join, $master_path, $foreign_path, $foreign_class_name, $master_property
						);
					}
				}
				else {
					$foreign_class_name = $foreign_type->asString();
					$join->master_column  = 'id_' . $master_property->getAnnotation('storage')->value;
					$join->foreign_column = 'id';
				}
			}
		}
		return $foreign_class_name;
	}

	//-------------------------------------------------------------------------------------- getAlias
	/**
	 * Gets foreign table alias for a given property path
	 *
	 * @param $path string
	 * @return string
	 */
	public function getAlias($path)
	{
		return isset($this->joins[$path]) ? $this->joins[$path]->foreign_alias : 't0';
	}

	//------------------------------------------------------------------------------------ getClasses
	/**
	 * Gets an array of used classes names
	 *
	 * Classes can be returned twice if they are used by several property paths
	 *
	 * @return string[] key is the path of the matching property
	 */
	public function getClasses()
	{
		return $this->classes;
	}

	//--------------------------------------------------------------------------------- getClassNames
	/**
	 * Gets an array of used classes names
	 *
	 * Classes are always returned once
	 *
	 * @return string[] key is an arbitrary counter
	 */
	public function getClassNames()
	{
		return array_values($this->classes);
	}

	//---------------------------------------------------------------------------- getClassProperties
	/**
	 * @param $class_name
	 * @return Reflection_Property[]
	 */
	public function getClassProperties($class_name)
	{
		return $this->properties[$class_name];
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * Gets Sql_Join object for a given property path
	 *
	 * @param $path string full property path
	 * @return Sql_Join|null may be null if no join have been generated with $path
	 */
	public function getJoin($path)
	{
		return isset($this->joins[$path]) ? $this->joins[$path] : null;
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * Gets all joins object
	 *
	 * @return Sql_Join[] indiced by properties paths
	 */
	public function getJoins()
	{
		return $this->joins;
	}

	//-------------------------------------------------------------------------------- getLinkedJoins
	/**
	 * Gets the list of joins that come from a 'link' class annotation
	 *
	 * @return Sql_Join[]
	 */
	public function getLinkedJoins()
	{
		$joins = [];
		foreach ($this->joins as $key => $join) {
			if (is_object($join) && (($key === 'id') || (substr($key, -6) === '-@link'))) {
				$joins[$key] = $join;
			}
		}
		return $joins;
	}

	//------------------------------------------------------------------------------- getLinkedTables
	/**
	 * Gets the list of linked tables.
	 * There are tables which do not have any matching class
	 *
	 * @return string[] main key is the table name, contained arrays contains two fields names[]
	 */
	public function getLinkedTables()
	{
		return $this->linked_tables;
	}

	//--------------------------------------------------------------------------------- getProperties
	/**
	 * Gets the list of Reflection_Property objects for a given property path
	 *
	 * @param $master_path string
	 * @return Reflection_Property[]
	 */
	public function getProperties($master_path)
	{
		$class_name = isset($this->classes[$master_path]) ? $this->classes[$master_path] : null;
		return isset($this->properties[$class_name]) ? $this->properties[$class_name] : [];
	}

	//----------------------------------------------------------------------------------- getProperty
	/**
	 * Gets a Reflection_Property object for a given property path
	 *
	 * @param $master_path string
	 * @param $property_name string
	 * @return Reflection_Property
	 */
	private function getProperty($master_path, $property_name)
	{
		$properties = $this->getProperties($master_path);
		return isset($properties[$property_name]) ? $properties[$property_name] : null;
	}

	//-------------------------------------------------------------------------- getStartingClassName
	/**
	 * Gets starting class name as defined in constructor
	 *
	 * @return string
	 */
	public function getStartingClassName()
	{
		return $this->classes[''];
	}

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * A new instance of Sql_Joins, for those who don't lake the new Sql_Joins() syntax
	 *
	 * Construct Sql_Joins object and prepare joins for a list of property paths.
	 *
	 * @param $starting_class_name string the class name for the root of property paths
	 * @param $paths array a property paths list to add at construction
	 * @return Sql_Joins
	 */
	public static function newInstance($starting_class_name, $paths = [])
	{
		return new Sql_Joins($starting_class_name, $paths);
	}

}
