<?php
namespace SAF\Framework;

/**
 * Classes that use this trait must implement Has_Doc_Comment !
 */
trait Annoted
{

	//---------------------------------------------------------------------------------- $annotations
	/**
	 * Annotations values
	 *
	 * @var Annotation[]
	 */
	private $annotations = array();

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

	//-------------------------------------------------------------------------------- getAnnotations
	/**
	 * Gets multiple annotations of the reflected property
	 *
	 * @param $annotation_name string
	 * @return Annotation[]
	 */
	public function getAnnotations($annotation_name)
	{
		return $this->getCachedAnnotation($annotation_name, true);
	}

	//--------------------------------------------------------------------------- getCachedAnnotation
	/**
	 * @param $annotation_name string
	 * @param $multiple        boolean
	 * @return Annotation|Annotation[] depending on $multiple value
	 */
	private function getCachedAnnotation($annotation_name, $multiple)
	{
		if (!isset($this->annotations[$annotation_name][$multiple])) {
			$this->annotations[$annotation_name][$multiple] = Annotation_Parser::byName(
				$this, $annotation_name, $multiple
			);
		}
		return $this->annotations[$annotation_name][$multiple];
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
			user_error("Bad annotation type getListAnnotation('$annotation_name')");
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
			user_error("Bad annotation type getListAnnotations('$annotation_name')");
		}
		return $annotations;
	}

	//--------------------------------------------------------------------------------- setAnnotation
	/**
	 * Sets an annotation value for the reflected property (use it when no annotation found)
	 *
	 * @param $annotation_name string
	 * @param $annotation      Annotation
	 */
	protected function setAnnotation($annotation_name, Annotation $annotation)
	{
		$this->annotations[$annotation_name][false] = $annotation;
	}

	//-------------------------------------------------------------------------------- setAnnotations
	/**
	 * Sets a multiple annotations value for the reflected property (use it when no annotation found)
	 *
	 * @param $annotation_name string
	 * @param $annotations     Annotation[]
	 */
	protected function setAnnotations($annotation_name, $annotations)
	{
		$this->annotations[$annotation_name][true] = $annotations;
	}

}
