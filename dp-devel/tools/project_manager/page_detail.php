<?
$relPath="./../../pinc/";
include_once($relPath.'v_site.inc');
include_once($relPath.'dp_main.inc');
include_once('page_table.inc');

$myresult = mysql_query("
                SELECT nameofwork, state FROM projects WHERE projectid = '$project'
        ");

$row = mysql_fetch_assoc($myresult);
$state = $row['state'];
$title = $row['nameofwork'];

$label = _("Return to Project Comments page for");

echo "<br>\n";
echo "<a href='$code_url/tools/proofers/projects.php?project=$project&amp;proofstate=$state'>$label $title</a>";
echo "<br>\n";

include_once('detail_legend.inc');

echo_page_table( $project, $show_image);

?>

