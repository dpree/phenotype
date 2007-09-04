<?php
// -------------------------------------------------------
// Phenotype Content Application Framework
// -------------------------------------------------------
// Copyright (c) 2003-2006 Nils Hagemann, Paul Sellinger,
// Peter Sellinger.
// -------------------------------------------------------
// Thanks for your support: Markus Griesbach, Michael 
// Kr�mer, Annemarie Komor, Jochen Rieger, Alexander
// Wehrum, Martin Ochs.
// -------------------------------------------------------
// Kontakt:
// www.phenotype.de - offical product homepage
// www.phenotype-cms.de - documentation & support
// www.sellinger-server.de - inventors of phenotype
// -------------------------------------------------------
// Version ##!PT_VERSION!## vom ##!BUILD_DATE!##
// -------------------------------------------------------
?>
<?php
require("_config.inc.php");
require("_session.inc.php");
if (PT_CONFIGMODE!=1){exit();}
?>
<?php
if (!$mySUser->checkRight("superuser"))
{
	$url = "noaccess.php";
	Header ("Location:" . $url."?".SID);
	exit();
}
?>
<?php
$myAdm = new PhenotypeAdmin();
$id = $_REQUEST["id"];

if (isset($_REQUEST["delete"]))
{
	$myAdm->cfg_removeContent($id);

	$url = "config_content.php?r=" . $myRequest->get("r");

	Header ("Location:" . $url."&".SID);
	exit();

}


$mySQL = new SQLBuilder();

// Konfiguration
$rubrik = $myRequest->get("rubrik");
//if ($rubrik==""){$rubrik="Neue Rubrik";}

if ($_REQUEST["b"]==0)
{

	$mySQL->addField("con_bez",$myRequest->get("bez"));
	$mySQL->addField("con_description",$myRequest->get("description"));
	$mySQL->addField("con_rubrik",$rubrik);

	// OPTIONEN
	if (isset($_REQUEST["anlegen"]))
	{
		$mySQL->addField("con_anlegen",1,DB_NUMBER);
	}else{
		$mySQL->addField("con_anlegen",0,DB_NUMBER);
	}

	if (isset($_REQUEST["bearbeiten"]))
	{
		$mySQL->addField("con_bearbeiten",1,DB_NUMBER);
	}else{
		$mySQL->addField("con_bearbeiten",0,DB_NUMBER);
	}

	if (isset($_REQUEST["loeschen"]))
	{
		$mySQL->addField("con_loeschen",1,DB_NUMBER);
	}else{
		$mySQL->addField("con_loeschen",0,DB_NUMBER);
	}

	/*
	if (isset($_REQUEST["statistik"]))
	{
	$mySQL->addField("con_statistik",1,DB_NUMBER);
	}else{
	$mySQL->addField("con_statistik",0,DB_NUMBER);
	}
	*/

	$sql = $mySQL->update("content","con_id =" . $id);
	$myDB->query($sql);
}

// SKRIPT
if ($_REQUEST["b"]==1)
{
	if ($myAdm->browserOK_HTMLArea())
	{$skript = $myAdm->decodeRequest_HTMLArea($myRequest->get("skript"));}
	else
	{$skript = $myAdm->decodeRequest_TextArea($myRequest->get("skript"));}

	$dateiname = APPPATH . "content/PhenotypeContent_"  .$id . ".class.php";

	$fp = fopen ($dateiname,"w");
	fputs ($fp,$skript);
	fclose ($fp);
	@chmod ($dateiname,UMASK);
}

// TEMPLATES
if ($_REQUEST["b"]==0 OR $_REQUEST["b"]==2)
{
	$sql = "SELECT * FROM content_template WHERE con_id = " . $id . " ORDER BY tpl_id";
	$rs = $myDB->query($sql);
	$c= mysql_num_rows($rs);
	$plus = "";$minus = "";
	$anzahl_templates=0;
	while ($row_ttp=mysql_fetch_array($rs))
	{
		$anzahl_templates++;
		$identifier = "ttp_". $row_ttp["tpl_id"]."_";
		$mySQL = new SQLBuilder();
		$mySQL->addField("tpl_bez",$_REQUEST[$identifier . "bez"]);
		$sql = $mySQL->update("content_template","tpl_id =" . $row_ttp["tpl_id"]);
		$myDB->query($sql);

		// Templates nur im Block 3
		if ($_REQUEST["b"]==2)
		{
			if ($myAdm->browserOK_HTMLArea())
			{$html = $myAdm->decodeRequest_HTMLArea($myRequest->get($identifier . "template"));}
			else
			{$html = $myAdm->decodeRequest_TextArea($myRequest->get($identifier . "template"));}

			$dateiname = $myPT->getTemplateFileName(PT_CFG_CONTENTCLASS, $id, $row_ttp["tpl_id"]);
			$fp = fopen ($dateiname,"w");
			fputs ($fp,$html);
			fclose ($fp);
			@chmod ($dateiname,UMASK);
		}

		if (isset($_REQUEST[$identifier . "minus_x"])){$minus = $row_ttp["tpl_id"];}
		if (isset($_REQUEST[$identifier . "plus_x"])){$plus = $row_ttp["tpl_id"];}
	}

	if ($minus !="")
	{
		$sql = "DELETE FROM content_template WHERE tpl_id = " . $minus;
		$myDB->query($sql);
		$anzahl_templates--;
	}

	if ($plus !="")
	{
		$mySQL = new SQLBuilder();
		$mySQL->addField("con_id",$id,DB_NUMBER);
		$mySQL->addField("tpl_bez","TPL_".($c+1));
		$sql = $mySQL->insert("content_template");
		$myDB->query($sql);
	}

	if (isset($_REQUEST["ttp_plus_x"]))
	{
		// 1. Template
		$mySQL = new SQLBuilder();
		$mySQL->addField("con_id",$id,DB_NUMBER);
		$mySQL->addField("tpl_bez","TPL_".($c+1));
		$sql = $mySQL->insert("content_template");
		$myDB->query($sql);
	}
}

$b = $_REQUEST["b"];

if ($b==2)
{
	if ($anzahl_templates == 0){$b=0;}
}

$fname = "PhenotypeContent_".$id;
$myCO = new $fname;
$myCO->snapshot($mySUser->id,"config");

$url = "contentobject_edit.php?id=" . $id . "&b=" . $b. "&r=" . urlencode($rubrik);
Header ("Location:" . $url."&".SID);


?>
