<?
// Send real HTTP headers to user-agents - at least one of these headers should
// be honored by all clients/proxies/caches.
//
// Date in the past
header("Expires: Mon, 01 Sep 2000 09:00:00 GMT");

// always modified
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");

$relPath="./../../pinc/";
include($relPath.'projectinfo.inc');
include_once($relPath.'bookpages.inc');
include_once($relPath.'echo_project_info.inc');
include_once($relPath.'gettext_setup.inc');

$projectinfo = new projectinfo();
if ($proofstate==PROJ_PROOF_FIRST_AVAILABLE) {
	update_avail_pages($project, " = '".AVAIL_FIRST."'");
	$projectinfo->update_avail($project, PROJ_PROOF_FIRST_AVAILABLE);
} else {
	update_avail_pages($project, " = '".AVAIL_SECOND."'");
	$projectinfo->update_avail($project,PROJ_PROOF_SECOND_AVAILABLE);
}


/* $_GET $project, $proofstate, $proofing */

include($relPath.'doctype.inc');
echo "$docType\r\n<HTML><HEAD><TITLE> "._("Project Comments")."</TITLE>";
?>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="-1" />

<?

if (!isset($proofing) && $userP['i_newwin']==1)
{include($relPath.'js_newwin.inc');}
echo "</HEAD><BODY>\r\n";
if (!isset($proofing)) {
    include('./projects_menu.inc');
?>
<br><i><? echo _("Please scroll down and read the Project Comments for any special instructions <b>before</b> proofreading!"); ?></i><br>
<br>

<?
}
    echo_project_info( $project, $proofstate, !isset($proofing) );
    echo "<BR>";

    if (!isset($proofing)) {
        include('./projects_menu.inc');
    } else {
        echo"<p><p><b> "._("This information has been opened in a separate browser window, feel free to leave it open for reference or close it.")."</b>";
    }
?>
</BODY></HTML>
