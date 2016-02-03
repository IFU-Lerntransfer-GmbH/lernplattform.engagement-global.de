<?php

require_once 'Services/ReportsRepository/classes/class.ilObjReportBase.php';
require_once 'Services/GEV/Utils/classes/class.gevCourseUtils.php';
/*
ini_set("memory_limit","2048M"); 
ini_set('max_execution_time', 0);
set_time_limit(0);
*/
class ilObjReportEmplAtt extends ilObjReportBase {
	protected $relevant_parameters = array();

	public function __construct($ref_id = 0) {
		parent::__construct($ref_id);
	}

	public function initType() {
		 $this->setType("xrea");
	}

	protected function buildQuery($query) {
		$query
			->select("usr.user_id")
			->select("usr.lastname")
			->select("usr.firstname")
			->select("usr.email")
			->select("usr.adp_number")
			->select("usr.job_number")
			->select("orgu.org_unit_above1")
			->select("orgu.org_unit_above2")
			->select_raw("GROUP_CONCAT(DISTINCT orgu.orgu_title SEPARATOR ', ') AS org_unit")
			->select_raw("GROUP_CONCAT(DISTINCT role.rol_title ORDER BY role.rol_title SEPARATOR ', ') AS roles")
			->select("usr.position_key")
			->select("crs.custom_id")
			->select("crs.title")
			->select("crs.venue")
			->select("crs.type")
			->select("usrcrs.credit_points")
			->select("usrcrs.booking_status")
			->select("usrcrs.participation_status")
			->select("usrcrs.usr_id")
			->select("usrcrs.crs_id")
			->select("crs.begin_date")
			->select("crs.end_date")
			->select("crs.edu_program")
			->from("hist_user usr")
			->left_join("hist_usercoursestatus usrcrs")
				->on("usr.user_id = usrcrs.usr_id AND usrcrs.hist_historic = 0")
			->left_join("hist_course crs")
				->on("crs.crs_id = usrcrs.crs_id AND crs.hist_historic = 0")
			->left_join("hist_userorgu orgu")
				->on("orgu.usr_id = usr.user_id")
			->left_join("hist_userrole role")
				->on("role.usr_id = usr.user_id")
			->group_by("usr.user_id")
			->group_by("usrcrs.crs_id")
			->compile()
			;
		return $query;
	}

	protected function buildOrder($order) {
		$order->mapping("date", "crs.begin_date")
				->mapping("od_bd", array("org_unit_above1", "org_unit_above2"))
				->defaultOrder("lastname", "ASC")
				;
		return $order;
	}

	protected function buildTable($table) {
		$table
			->column("lastname", $this->plugin->txt("lastname"), true)
			->column("firstname", $this->plugin->txt("firstname"), true)
			->column("email", $this->plugin->txt("email"), true)
			->column("adp_number", $this->plugin->txt("adp_number"), true)
			->column("job_number", $this->plugin->txt("job_number"), true)
			->column("od_bd", $this->plugin->txt("od_bd"), true)
			->column("org_unit",  $this->plugin->txt("org_unit_short"), true)
			->column("roles", $this->plugin->txt("roles"), true)
			->column("custom_id",  $this->plugin->txt("training_id"), true)
			->column("title",  $this->plugin->txt("title"), true)
			->column("venue",  $this->plugin->txt("location"), true)
			->column("type", $this->plugin->txt("learning_type"), true)
			->column("date", $this->plugin->txt("date"), true)
			->column("credit_points", $this->plugin->txt("credit_points"), true)
			->column("booking_status", $this->plugin->txt("booking_status"), true)
			->column("participation_status", $this->plugin->txt("participation_status"), true)
			;
		return parent::buildTable($table);
	}

	protected function buildFilter($filter) {
		$orgu_filter = new recursiveOrguFilter("org_unit","orgu.orgu_id",true,true);
		$orgu_filter->setFilterOptionsByUser($this->user_utils);

		$filter	->dateperiod( "period"
									, $this->plugin->txt("period")
									, $this->plugin->txt("until")
									, "usrcrs.begin_date"
									, "usrcrs.end_date"
									, date("Y")."-01-01"
									, date("Y")."-12-31"
									, false
									, " OR usrcrs.hist_historic IS NULL"
									);
		$orgu_filter->addToFilter($filter);
		$filter	->multiselect("template_title"
									 , $this->plugin->txt("title")
									 , "template_title"
									 , gevCourseUtils::getTemplateTitleFromHisto()
									 , array()
									 , ""
									 , 300
									 , 160
									 )
				->multiselect("participation_status"
									 , $this->plugin->txt("participation_status")
									 , "participation_status"
									 , array(	"teilgenommen"=>"teilgenommen"
									 			,"fehlt ohne Absage"=>"fehlt ohne Absage"
									 			,"fehlt entschuldigt"=>"fehlt entschuldigt"
									 			,"nicht gesetzt"=>"gebucht, noch nicht abgeschlossen")
									 , array()
									 , ""
									 , 220
									 , 160
									 , "text"
									 , "asc"
									 , true
									 )
				->static_condition($this->gIldb->in("usr.user_id", $this->user_utils->getEmployees(), false, "integer"))
				->static_condition(" usr.hist_historic = 0")
				->static_condition("( usrcrs.booking_status != '-empty-'"
								  ." OR usrcrs.hist_historic IS NULL )")
				->static_condition("(   usrcrs.participation_status != '-empty-'"
								  ." OR usrcrs.hist_historic IS NULL )")
				->static_condition("(   usrcrs.booking_status != 'kostenfrei storniert'"
								  ." OR usrcrs.hist_historic IS NULL )")
				->static_condition("(   usrcrs.booking_status != ".$this->gIldb->quote('-empty-','text')
								  ." OR usrcrs.hist_historic IS NULL )" )
				->static_condition("orgu.action >= 0")
				->static_condition("orgu.hist_historic = 0")
				->static_condition("orgu.rol_title = 'Mitarbeiter'")
				->static_condition("role.action = 1")
				->static_condition("role.hist_historic = 0")
				->action($this->filter_action)
				->compile();
				;
		return $filter;
	}

	protected function getRowTemplateTitle() {
		return "tpl.gev_attendance_by_employee_row.html";
	}

	public function getRelevantParameters() {
		return $this->relevant_parameters;
	}

	public function doCreate() {
		$this->gIldb->manipulate("INSERT INTO rep_robj_rea ".
			"(id, is_online) VALUES (".
			$this->gIldb->quote($this->getId(), "integer")
			.",".$this->gIldb->quote(0, "integer")
			.")");
	}

	public function doRead() {
		$set = $this->gIldb->query("SELECT * FROM rep_robj_rea ".
			" WHERE id = ".$this->gIldb->quote($this->getId(), "integer")
			);
		while ($rec = $this->gIldb->fetchAssoc($set)) {
			$this->setOnline($rec["is_online"]);
		}
	}

	public function doUpdate() {
		$this->gIldb->manipulate("UPDATE rep_robj_rea SET "
			." is_online = ".$this->gIldb->quote($this->getOnline(), "integer")
			." WHERE id = ".$this->gIldb->quote($this->getId(), "integer")
			);
	}

	public function doDelete() {
		$this->gIldb->manipulate("DELETE FROM rep_robj_rea WHERE ".
			" id = ".$this->gIldb->quote($this->getId(), "integer")
		); 
	}

	public function doClone($a_target_id,$a_copy_id,$new_obj) {
		$new_obj->setOnline($this->getOnline());
		$new_obj->update();
	}

	public function setOnline($a_val) {
		$this->online = (int)$a_val;
	}

	public function getOnline() {
		return $this->online;
	}
}