<?php
$relPath='../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'dpsql.inc');
include_once($relPath.'project_states.inc');
include_once($relPath.'theme.inc');

require_login();

$title = _("Post-Processing Verification Statistics");
theme($title,'header');

echo "<br><br><h2>$title</h2>\n";

echo "<br>\n";

echo "<h3>" . _("Post-Processing Verifiers") . "</h3>\n";
echo "<h4>" . _("(Number of Projects Posted to PG)") . "</h4>\n";

$psd = get_project_status_descriptor('posted');
dpsql_dump_themed_query("
    SELECT checkedoutby as '" . mysql_real_escape_string(_("PPVer")) . "', 
        count(*) as '" . mysql_real_escape_string(_("Projects PPVd")) . "'
    FROM  `projects` , usersettings
    WHERE 1  AND checkedoutby != postproofer AND $psd->state_selector
        and checkedoutby = usersettings.username 
        and setting = 'PPV.access' and value = 'yes' 
    GROUP  BY 1 
    ORDER  BY 2  DESC ", 1, DPSQL_SHOW_RANK);

echo "<br>\n";

echo _("Note that the above figures are as accurate as possible within the bounds of the current database structure");

echo "<br>\n";

theme("","footer");

// vim: sw=4 ts=4 expandtab
