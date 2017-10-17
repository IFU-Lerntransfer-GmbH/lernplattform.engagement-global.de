<?php

/* Copyright (c) 2017 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see
docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Component as C;
use ILIAS\Validation\Factory as ValidationFactory;
use ILIAS\Transformation\Factory as TransformationFactory;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Component\JavaScriptBindable as IJavaScriptBindable;

/**
 * This implements the checkbox input.
 */
class Checkbox extends Input implements C\Input\Field\Checkbox, IJavaScriptBindable {
	use JavaScriptBindable;

	/**
	 * @var SubSection
	 */
	protected $sub_section;


	/**
	 * Numeric constructor.
	 * @param DataFactory $data_factory
	 * @param $label
	 * @param $byline
	 */
	public function __construct(DataFactory $data_factory, ValidationFactory $validation_factory, TransformationFactory $transformation_factory, $label, $byline) {

		parent::__construct($data_factory, $validation_factory, $transformation_factory, $label, $byline);

		//TODO: IsBoolean or similar here
		//$this->setAdditionalConstraint($this->validation_factory->isNumeric());
	}

	/**
	 * @inheritdoc
	 */
	protected function isClientSideValueOk($value) {
		//TODO: Implement this
		return true;
	}


	/**
	 * @inheritdoc
	 */
	protected function getConstraintForRequirement() {
		throw new \LogicException("NYI: What could 'required' mean here?");
	}

	/**
	 * @inheritdoc
	 */
	public function withSubsection(C\Input\Field\SubSection $sub_section){
		$clone = clone $this;
		$clone->sub_section = $sub_section;
		return $clone;
	}


	/**
	 * @inheritdoc
	 */
	public function getSubSection(){
		return $this->sub_section;
	}
}
