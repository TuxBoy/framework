<?php
namespace ITRocks\Framework\Reflection\Tests;

use ITRocks\Framework\Reflection\Annotation\Property\User_Annotation;
use ITRocks\Framework\Reflection\Reflection_Property;
use ITRocks\Framework\Tests\Test;
use ITRocks\Framework\User;

/**
 * Annoted unit tests
 */
class Annoted_Tests extends Test
{

	//----------------------------------------------------------------------------- testSetAnnotation
	public function testSetAnnotation()
	{
		$property1       = new Reflection_Property(User::class, 'login');
		$user_annotation = new User_Annotation(User_Annotation::INVISIBLE);
		$property1->setAnnotation($user_annotation);

		$property2 = new reflection_Property(User::class, 'login');

		$this->assume(
			__METHOD__ . '.modifiedProperty',
			User_Annotation::of($property1)->has(User_Annotation::INVISIBLE),
			true
		);

		$this->assume(
			__METHOD__ . '.newProperty',
			User_Annotation::of($property2)->has(User_Annotation::INVISIBLE),
			true
		);

		// reset for future tests
		$property1->removeAnnotation(User_Annotation::ANNOTATION);
	}

	//------------------------------------------------------------------------ testSetAnnotationLocal
	public function testSetAnnotationLocal()
	{
		$property1       = new Reflection_Property(User::class, 'login');
		$user_annotation = User_Annotation::local($property1);
		$user_annotation->add(User_Annotation::INVISIBLE);

		$property2 = new reflection_Property(User::class, 'login');
		$this->assume(
			__METHOD__ . '.modifiedProperty',
			User_Annotation::of($property1)->has(User_Annotation::INVISIBLE),
			true
		);

		$this->assume(
			__METHOD__ . '.newProperty',
			User_Annotation::of($property2)->has(User_Annotation::INVISIBLE),
			false
		);
	}

}
