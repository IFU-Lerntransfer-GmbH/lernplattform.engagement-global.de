<?php
require_once "include/ilias_header.inc";

// Template generieren
$tplContent = new Template("content_role.html",true,true);

//PG: deprecated???
$tplContent->setVariable("OBJ_SELF","content_role.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
$tplContent->setVariable("TPOS",$_GET["parent"]);

//show tabs
$o = array();
$o["LINK1"] = "content_role.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"];
$o["LINK2"] = "./object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&cmd=edit";
$o["LINK3"] = "./object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&cmd=perm";
$o["LINK4"] = "./object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&cmd=owner";
$tplContent->setVariable("TABS", TUtil::showTabs(1,$o));

// display path
$path = $tree->showPath($tree->getPathFull(),"content.php");
$tplContent->setVariable("TREEPATH",$path);
$tplContent->setVariable("MESSAGE","<h5>Click on the name of a role to edit the template of that role</h5>");
$tplContent->setVariable("TYPE","role");

// BEGIN ROW
$tplContent->setCurrentBlock("row",true);

// determine sort direction
if(!$_GET["direction"] || $_GET["direction"] == 'ASC')
{
	$tplContent->setVariable("DIR",'DESC');
}
if($_GET["direction"] == 'DESC')
{
	$tplContent->setVariable("DIR",'ASC');
}

// set sort column
if (empty($_GET["order"]))
{
	$_GET["order"] = "title";
}

if ($rbacsystem->checkAccess('read',$_GET["obj_id"],$_GET["parent"]))
{
	if ($role_list = $rbacadmin->getRoleAndTemplateListByObject($obj_id,$_GET["order"],$_GET["direction"]))
	{
		foreach ($role_list as $key => $val)
		{
			// color changing
			$css_row = TUtil::switchColor($key, "tblrow1", "tblrow2");

			$node = "[<a href=\"content.php?obj_id=".$val["id"]."&parent=".$val["parent"]."\">".$val["title"]."</a>]";
			$tplContent->setVariable("LINK_TARGET","object.php?obj_id=".$val["obj_id"].
									 "&parent=$obj_id&parent_parent=$_GET[parent]&cmd=perm&show=rolf");
			$tplContent->setVariable("OBJ_TITLE",$val["title"]);
			$tplContent->setVariable("OBJ_LAST_UPDATE",$val["last_update"]);

			// determine image (role object or role template?)
			$image = $val["type"] == 'rolt' ? 'icon_rtpl_b.gif' : 'icon_role_b.gif';

			$tplContent->setVariable("IMG_TYPE",$image);
			$tplContent->setVariable("ALT_IMG_TYPE",$val["type"]);

			$tplContent->setVariable("CSS_ROW",$css_row);
			$tplContent->setVariable("OBJ",$val["obj_id"]);
			$tplContent->parseCurrentBlock("row");
		}
	}
}
else
{
	$ilias->raiseError("No permission to read role folder",$ilias->error_obj->MESSAGE);
}

if ($_GET["message"])
{
	$tplContent->setCurrentBlock("sys_message");
	$tplContent->setVariable("ERROR_MESSAGE",stripslashes($_GET["message"]));
	$tplContent->parseCurrentBlock();
}

$tplContent->setVariable("TPLPATH", $tplContent->tplPath);

$tplmain->setVariable("PAGECONTENT", $tplContent->get());	
$tplmain->show();

?>