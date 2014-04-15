<?php
namespace SAF\Framework\Reflection\Annotation;

use SAF\Framework\Reflection\Annotation;
use SAF\Framework\Reflection\Annotation\Template\List_Annotation;
use SAF\Framework\Reflection\Has_Doc_Comment;

/**
 * An annoted class contains annotations.
 *
 * Common annoted classes are Reflection_Class, Reflection_Property, Reflection_Method.
 * Classes that use this trait must implement Has_Doc_Comment !
 */
trait Annoted
{

	//---------------------------------------------------------------------------------- $annotations
	/**
	 * Local annotations cache
	 *
	 * @var array
	 */
	private $annotations = [];

	//---------------------------------------------------------------------------- $annotations_cache
	/**
	 * Global annotations cache
	 *
	 * Annotation['Class_Name']['@']['annotation']
	 * Annotation['Class_Name']['property']['annotation']
	 * Annotation['Class_Name']['methodName()']['annotation']
	 *
	 * @var array
	 */
	private static $annotations_cache = [];

	//--------------------------------------------------------------------------------- getAnnotation
	/**
	 * Gets an single annotation of the reflected property
	 *
	 * @param $annotation_name string
	 * @return Annotation
	 */
	public function getAnnotation($annotation_name)
	{
		return $this->getCachedAnnotation($annotation_name, false);
	}

	//------------------------------------------------------------------------ getAnnotationCachePath
	/**
	 * @return string[]
	 */
	protected abstract function getAnnotationCachePath();

	//-------------------------------------------------------------------------------- getAnnotations
	/**
	 * Gets multiple annotations of the reflected property
	 *
	 * If the annotation name is given, will return the Annotation[]
	 * If no annotation name is given, all annotations will be read for the reflected property
	 *
	 * @param $annotation_name string
	 * @return Annotation[]|array
	 */
	public function getAnnotations($annotation_name = null)
	{
		if (isset($annotation_name)) {
			return $this->getCachedAnnotation($annotation_name, true);
		}
		else {
			/** @var $this Annoted|Has_Doc_Comment */
			return Parser::allAnnotations($this);
		}
	}

	//--------------------------------------------------------------------------- getCachedAnnotation
	/**
	 * @param $annotation_name string
	 * @param $multiple        boolean
	 * @return Annotation|Annotation[] depending on $multiple value
	 */
	private function getCachedAnnotation($annotation_name, $multiple)
	{
		if (isset($this->annotations[$annotation_name])) {
			return $this->annotations[$annotation_name];
		}
		$path = $this->getAnnotationCachePath();
		if (
			!isset(self::$annotations_cache[$path[0]][$path[1]][$annotation_name][$multiple])
			&& ($this instanceof Has_Doc_Comment)
		) {
			/** @var $this Annoted|Has_Doc_Comment */
			self::$annotations_cache[$path[0]][$path[1]][$annotation_name][$multiple]
				= Parser::byName($this, $annotation_name, $multiple);
		}
		return self::$annotations_cache[$path[0]][$path[1]][$annotation_name][$multiple];
	}

	//----------------------------------------------------------------------------- getListAnnotation
	/**
	 * Gets an List_Annotation for the reflected property
	 *
	 * @param $annotation_name string
	 * @return List_Annotation
	 */
	public function getListAnnotation($annotation_name)
	{
		$annotation = $this->getCachedAnnotation($annotation_name, false);
		if (!($annotation instanceof List_Annotation)) {
			trigger_error(
				'Bad annotation type getListAnnotation(' . $annotation_name . ')', E_USER_ERROR
			);
		}
		return $annotation;
	}

	//---------------------------------------------------------------------------- getListAnnotations
	/**
	 * Gets multiple List_Annotation for the reflected property
	 *
	 * @param $annotation_name string
	 * @return List_Annotation[]
	 */
	public function getListAnnotations($annotation_name)
	{
		$annotations = $this->getCachedAnnotation($annotation_name, true);
		if ($annotations && !(reset($annotations) instanceof List_Annotation)) {
			trigger_error(
				'Bad annotation type getListAnnotations(' . $annotation_name . ')', E_USER_ERROR
			);
		}
		return $annotations;
	}

	//--------------------------------------------------------------------------------- setAnnotation
	/**
	 * Sets an annotation value for the reflected object (use it when no annotation found)
	 *
	 * The annotation value will be set for all equivalent reflection objects.
	 * If you want to change the annotation for a local reflection object only, please consider
	 * using setAnnotationLocal($annotation_name) and modifying the local annotation instead.
	 *
	 * @param $annotation_name string
	 * @param $annotation      Annotation
	 */
	public function setAnnotation($annotation_name, Annotation $annotation)
	{
		$path = $this->getAnnotationCachePath();
		self::$annotations_cache[$path[0]][$path[1]][$annotation_name][false] = $annotation;
	}

	//---------------------------------------------------------------------------- setAnnotationLocal
	/**
	 * Sets an annotation to local and return the local annotation object.
	 * This enable to get a copy of the notation visible into this reflection object only,
	 * that you can change without affecting others equivalent reflection objects.
	 *
	 * If the annotation was already set to local, this local annotation is returned without reset.
	 *
	 * @param $annotation_name
	 * @return Annotation
	 */
	public function setAnnotationLocal($annotation_name)
	{
		return isset($this->annotations[$annotation_name])
			? $this->annotations[$annotation_name]
			: ($this->annotations[$annotation_name] = clone $this->getAnnotation($annotation_name));
	}

	//-------------------------------------------------------------------------------- setAnnotations
	/**
	 * Sets a multiple annotations value for the reflected object (use it when no annotation found)
	 *
	 * The annotation values will be set for all equivalent reflection objects.
	 * If you want to change the annotations for a local reflection object only, please consider
	 * using setAnnotationLocal($annotation_name) and modifying the local annotations instead.
	 *
	 * @param $annotation_name string
	 * @param $annotations     Annotation[]
	 */
	public function setAnnotations($annotation_name, $annotations)
	{
		$path = $this->getAnnotationCachePath();
		self::$annotations_cache[$path[0]][$path[1]][$annotation_name][true] = $annotations;
	}

}