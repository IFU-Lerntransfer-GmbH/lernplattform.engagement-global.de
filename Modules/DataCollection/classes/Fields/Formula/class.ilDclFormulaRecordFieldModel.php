<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Modules/DataCollection/classes/Fields/Base/class.ilDclBaseRecordFieldModel.php');
require_once('class.ilDclStack.php');
require_once('class.ilDclExpressionParser.php');
require_once('class.ilDclTokenizer.php');

/**
 * Class ilDclBaseFieldModel
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 *
 */
class ilDclFormulaRecordFieldModel extends ilDclBaseRecordFieldModel {

	/**
	 * @var string
	 */
	protected $expression = '';

	/**
	 * @var string
	 */
	protected $parsed_value = '';


	/**
	 * @param ilDclBaseRecordModel $record
	 * @param ilDclBaseFieldModel  $field
	 */
	public function __construct(ilDclBaseRecordModel $record, ilDclBaseFieldModel $field) {
		parent::__construct($record, $field);

		$this->expression = $this->getField()->getProperty(ilDclBaseFieldModel::PROP_FORMULA_EXPRESSION);
	}


	/**
	 * Do nothing, value is runtime only and not stored in DB
	 */
	protected function loadValue() {
		return NULL;
	}


	/**
	 * Set value for record field
	 *
	 * @param mixed $value
	 * @param bool  $omit_parsing If true, does not parse the value and stores it in the given format
	 */
	public function setValue($value, $omit_parsing = false) {
		unset($value);
	}


	/**
	 * Do nothing, value is runtime only and not stored in DB
	 */
	public function doUpdate() {
		return NULL;
	}


	/**
	 * Do nothing, value is runtime only and not stored in DB
	 */
	public function doRead() {
		return NULL;
	}


	/**
	 * Do nothing, value is runtime only and not stored in DB
	 */
	public function delete() {
		return NULL;
	}


	/**
	 *
	 * @return mixed|string
	 */
	public function getFormInput() {
		return $this->parse();
	}


	/**
	 * @return string
	 */
	public function getHTML() {
		return $this->parse();
	}


	/**
	 * @return string
	 */
	public function getExportValue() {
		return $this->parse();
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->parse();
	}


	/**
	 * Parse expression
	 *
	 * @return string
	 */
	protected function parse() {
		if (!$this->parsed_value && $this->expression) {
			$parser = new ilDclExpressionParser($this->expression, $this->getRecord(), $this->getField());
			try {
				$this->parsed_value = $parser->parse();
			} catch (ilException $e) {
				return $this->lng->txt('dcl_error_parsing_expression') . ' (' . $e->getMessage() . ')';
			}
		}

		return $this->parsed_value;
	}
}