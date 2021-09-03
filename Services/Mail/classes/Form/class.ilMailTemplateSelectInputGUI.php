<?php declare(strict_types=1);
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Form/classes/class.ilSelectInputGUI.php';

/**
 * Class ilMailTemplateSelectInputGUI
 */
class ilMailTemplateSelectInputGUI extends ilSelectInputGUI
{
    protected array $fields = [];
    protected string $url;

    /**
     * @param string $a_title
     * @param string $a_postvar
     * @param string $url
     * @param array $fields
     */
    public function __construct(string $a_title = '', string $a_postvar = '', string $url = '', array $fields = [])
    {
        parent::__construct($a_title, $a_postvar);

        $this->url = $url;
        $this->fields = $fields;
    }

    /**
     * @inheritDoc
     */
    public function render($a_mode = '') : string
    {
        $html = parent::render($a_mode);

        $tpl = new ilTemplate('tpl.prop_template_select_container.html', true, true, 'Services/Mail');
        $tpl->setVariable('CONTENT', $html);
        $tpl->setVariable('FIELDS', json_encode($this->fields));
        $tpl->setVariable('URL', $this->url);
        $tpl->setVariable('ID', $this->getFieldId());

        return $tpl->get();
    }
}
