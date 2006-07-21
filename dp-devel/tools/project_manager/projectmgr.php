<?
$relPath="./../../pinc/";
include_once($relPath.'misc.inc');
include_once($relPath.'v_site.inc');
include_once($relPath.'dp_main.inc');
include_once($relPath.'user_is.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'Project.inc');
include_once($relPath.'projectinfo.inc');
include_once($relPath.'project_edit.inc');
include_once($relPath.'metarefresh.inc');
include_once($relPath.'iso_lang_list.inc');
include_once($relPath.'SettingsClass.inc');
include_once($relPath.'special_colors.inc');
include_once($relPath.'ProjectTransition.inc');
include_once('projectmgr.inc');



if (user_is_PM() && empty($_GET['show']) && empty($_GET['up_projectid'])) {
    if ($userP['i_pmdefault'] == 0) {
        metarefresh(0,"projectmgr.php?show=user_all","","");
        exit();
    } elseif ($userP['i_pmdefault'] == 1) {
        metarefresh(0,"projectmgr.php?show=user_active","", "");
        exit();
    }
}

theme(_("Project Search"), "header");

define( 'DEFAULT_N_RESULTS_PER_PAGE', 100 );

$PROJECT_IS_ACTIVE_sql = "(state NOT IN ('".PROJ_SUBMIT_PG_POSTED."','".PROJ_DELETE."'))";

if ((!isset($_GET['show']) && (!isset($_GET['up_projectid']))) ||
    $_GET['show'] == 'search_form' ||
    ($_GET['show'] == '' && $_GET['up_projectid'] == '' )) {

    echo_manager_header('project_search_page');
    
    $special_day_res = mysql_query("        
        SELECT
            spec_code,
            display_name,
            DATE_FORMAT(concat('2000-',open_month,'-',open_day),'%e %b') as 'Start Date'
        FROM special_days
        WHERE enable = 1
        ORDER BY open_month, open_day");

    $special_days = array();
    while ( $s_row = mysql_fetch_assoc($special_day_res) )
    {
        $show = $s_row['display_name']." (".$s_row['Start Date'].")";
        $code = $s_row['spec_code'];
        $special_days["$code"] = $show;
    }

    echo "
        <center>
        <h1>Search for Projects</h1>
        "._("Search for projects matching the following criteria:")."<br>
        <form method=get action='projectmgr.php'>
        <input type='hidden' name='show' value='search'>
        <table>
        <tr>
            <td>"._("Title")."</td>
            <td><input type='text' name='title'></td>
        </tr>
        <tr>
            <td>"._("Author")."</td>
            <td><input type='text' name='author'></td>
        </tr>
        <tr>
            <td>"._("Genre")."</td>
            <td><input type='text' name='genre'></td>
        </tr>
        <tr>
            <td>"._("Special day")."</td>
            <td><select name='special_day'>
                <option value='' selected>Any day</option>";
    foreach ($special_days as $s_code => $s_day)
    {
        echo "<option value='$s_code'>$s_day</option>";
    }
    echo "
                </select>
            </td>
        </tr>
        <tr>
            <td>"._("Language")."</td>
            <td>
            <select name='language'>
                <option value='' selected>"._("Any")."</option>
    ";
    foreach($lang_list as $k=>$v)
        echo "<option value='{$v['lang_name']}'>{$v['lang_name']}</option>\n";
    echo "
            </select>
            </td>
        </tr>
    ";

    {
        $project_manager = @$_GET['project_manager'];
        echo "
        <tr>
            <td>"._("Project Manager")."</td>
            <td><input type='text' name='project_manager' value='$project_manager'></td>
        </tr>
        ";
    }
    echo "
        <tr>
            <td>"._("Checked out by")."</td>
            <td><input type='text' name='checkedoutby'></td>
        </tr>
        ";
    echo "
        <tr>
            <td>"._("Project ID")."</td>
            <td><input type='text' name='projectid'> ("._("list ok").")</td>
        </tr>
    ";
    // In the <select> tag, we set the name attribute to 'state[]'.
    // I'm pretty sure this doesn't mean anything to HTML/HTTP,
    // but PHP takes it as a cue to make the multiple values of
    // the select control available as an array.
    // That is, $_GET['state'] will be an array containing
    // all selected values.
    echo "
        <tr>
            <td>"._("State")."</td>
            <td>
            <select name='state[]' multiple>
            <option value=''>any state</option>
    ";
    foreach ($PROJECT_STATES_IN_ORDER as $proj_state_in_order)
    {
        echo "<option value='$proj_state_in_order'>";
        echo project_states_text($proj_state_in_order);
        echo "</option>\n";
    }
    echo "
            </select>
            </td>
        </tr>
        <tr>
            <td>"._("Number of results per page")."</td>
            <td>
                <select name='n_results_per_page'>
    ";
    foreach ( array(30,100,300) as $n )
    {
        $selected = ( $n == DEFAULT_N_RESULTS_PER_PAGE ? 'SELECTED' : '' );
        echo "<option value='$n' $selected>$n</option>\n";
    }
    echo "
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <table width='100%'>
                <tr>
                    <td align='left'><input type='submit' value='"._("Search")."'></td>
                    <td align='right'><input type='reset' value='"._("Clear form")."'></td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
        </form>
        "._("Matching [except for State and multiple projectIDs] is case-insensitive and unanchored; so, for instance, 'jim' matches both 'Jimmy Olsen' and 'piggyjimjams'.")."
        <br><br>
        "._('"(list ok)": You can search by multiple ProjectIDs at once: enter the list of ProjectIDs, separated by commas, semicolons, or spaces.')."
        <br><br>
        "._("If desired, you should be able to select multiple values for State (e.g., by holding down Ctrl).")."
        </center>
    ";
} else {
    echo_manager_header('project_listings_page');

    // Construct and submit the search query.

    if ($_GET['show'] == 'search') {
        $condition = '1';
        if ( $_GET['title'] != '' )
        {
            $condition .= " AND nameofwork LIKE '%{$_GET['title']}%'";
        }
        if ( $_GET['author'] != '' )
        {
            $condition .= " AND authorsname LIKE '%{$_GET['author']}%'";
        }
        if ( $_GET['genre'] != '' )
        {
            $condition .= " AND genre LIKE '%{$_GET['genre']}%'";
        }
        if ( $_GET['special_day'] != '' )
        {
            $condition .= " AND special_code = '{$_GET['special_day']}'";
        }
        if ( $_GET['language'] != '' )
        {
            $condition .= " AND language LIKE '%{$_GET['language']}%'";
        }
        if ( $_GET['checkedoutby'] != '' )
        {
            $condition .= " AND checkedoutby LIKE '%{$_GET['checkedoutby']}%'";
        }
        {
            if ( $_GET['project_manager' ] != '' )
            {
                $condition .= " AND username LIKE '%{$_GET['project_manager']}%'";
            }
        }
        if ( $_GET['projectid'] != '' )
        {
            if ( preg_match('/[\s,;]/', $_GET['projectid']) )
            {
                $pid_list = mysql_real_escape_string($_GET['projectid']);
                $pid_list = preg_split('/[\s,;]/',$pid_list);
                $sql_list = '';
                foreach ($pid_list as $proj) { $sql_list .= "'$proj',"; }
                $sql_list = substr($sql_list,0,(strlen($sql_list) - 1));
                $condition .= " AND projectid IN ($sql_list)";
            }
            else
            {
                $condition .= " AND projectid LIKE '%{$_GET['projectid']}%'";
            }
        }
        if ( isset($_GET['state']) && count($_GET['state']) > 0 )
        {
            $condition .= " AND (0";
            foreach( $_GET['state'] as $state )
            {
                if ( $state == '' )
                {
                    $condition .= " OR 1";
                }
                else
                {
                    $condition .= " OR state='$state'";
                }
            }
            $condition .= ")";
        }
    } elseif ($_GET['show'] == "site_active") {
        $condition = $PROJECT_IS_ACTIVE_sql;
    } elseif ($_GET['show'] == "allfor" && isset($_GET['up_projectid'])) {
        $condition = " 1 ";
    } elseif ($_GET['show'] == "user_all") {
        $condition = "username = '$pguser'";
    } else {
        // ($_GET['show'] == "user_active")
        // plus some corner cases
        $condition = "$PROJECT_IS_ACTIVE_sql AND username = '$pguser'";
    }


    if (isset($_GET['up_projectid'])) {
        $up_projectid = $_GET['up_projectid'];
        $can_see_this_uber = TRUE;
        if (!$can_see_this_uber) {
            $UP_ok_qry = mysql_query("
            SELECT * FROM uber_projects up, usersettings us
            WHERE us.username = '$pguser' AND
                us.setting  = 'up_manager' AND
                us.value = up.up_projectid AND
                up.up_projectid > 0
            ");
            $can_see_this_uber = mysql_num_rows($UP_ok_qry);
        }
        if ($can_see_this_uber) {
            $condition .= " AND up_projectid = '$up_projectid' ";
        }
    }

    $n_results_per_page = @$_GET['n_results_per_page'];
    if ( $n_results_per_page == 0 ) $n_results_per_page = DEFAULT_N_RESULTS_PER_PAGE;

    $results_offset = intval(@$_GET['results_offset']);

    $state_collater = sql_collater_for_project_state('state');
    $result = mysql_query("
        SELECT SQL_CALC_FOUND_ROWS *
        FROM projects
        WHERE $condition
        ORDER BY $state_collater, nameofwork asc
        LIMIT $n_results_per_page OFFSET $results_offset
    ") or die(mysql_error());

    $numrows = mysql_num_rows($result);

    $res_found = mysql_query("SELECT FOUND_ROWS()");
    $num_found_rows = mysql_result($res_found,0);

    echo "<h1>Search Results</h1>\n";

    if ( $numrows == 0 )
    {
        echo _("<b>No projects matched the search criteria.</b>");
        theme("","footer");
        return;
    }

    // -------------------------------------------------------------

    function option_to_move( $curr_state, $new_state )
    {
        global $result;

        $projectids = array();
        while ( $project_assoc = mysql_fetch_assoc($result) )
        {
            if ( $project_assoc['state'] == $curr_state )
            {
                $projectids[] = $project_assoc['projectid'];
            }
        }
        mysql_data_seek($result, 0);

        if ( count($projectids) > 0 )
        {
            $curr_blurb = project_states_text($curr_state);
            $new_blurb  = project_states_text($new_state);
            $projectids_str = implode( ',', $projectids );

            echo "<a href='move_projects.php?curr_state=$curr_state&new_state=$new_state&projects=$projectids_str'>";
            echo _("Move all")." <b>$curr_blurb</b> "._("projects on this page to")." <b>$new_blurb</b>";
            echo "</a>";
            echo "<br>";
            echo "<br>";
            echo "\n";
        }
    }

    // Formerly, a user's search results could only contain projects
    // that the user could manage. Now that we've opened up the search page,
    // this is no longer true. E.g., the results may contain New projects
    // that the user does not have the authority to push to P1.unavail.
    // Thus, these links would be confusing/misleading. So comment them out.
    //
    // option_to_move( PROJ_NEW, PROJ_P1_UNAVAILABLE );
    // option_to_move( PROJ_P1_UNAVAILABLE, PROJ_P1_WAITING_FOR_RELEASE );

    // -------------------------------------------------------------

    // Present the results of the search query.

    function results_navigator()
    {
        global $n_results_per_page, $results_offset, $numrows, $num_found_rows;

        // The REQUEST_URI must have at least one query-string parameter,
        // otherwise the response would have been just the search form,
        // and this function wouldn't have been called.
        $url_base = $_SERVER['REQUEST_URI'] . '&';
        $url_base = preg_replace('/results_offset=[^&]*&/', '', $url_base);

        if ( $results_offset > 0 )
        {
            $t = _('Previous');
            $prev_offset = max(0, $results_offset - $n_results_per_page );
            $url = $url_base . "results_offset=$prev_offset";
            echo "<a href='$url'>$t</a> | ";
        }

        echo sprintf(
            _("Projects %d to %d of %d"),
            $results_offset + 1,
            $results_offset + $numrows,
            $num_found_rows
        );
        echo "\n";

        if ( $results_offset + $numrows < $num_found_rows )
        {
            $t = _('Next');
            $next_offset = $results_offset + $n_results_per_page;
            $url = $url_base . "results_offset=$next_offset";
            echo " | <a href='$url'>$t</a>";
        }
    }

    results_navigator();

    $show_pages_total = 1;

    $user_can_see_download_links = user_can_work_in_stage($pguser, 'PP');
    $show_options_column = $user_can_see_download_links || user_is_PM();

    echo "<center><table border=1 width=630 cellpadding=0 cellspacing=0 style='border-collapse: collapse' bordercolor=#111111>";

    function echo_header_cell( $width, $text )
    {
        global $theme;
        echo "<td width='$width' align='center' bgcolor='{$theme['color_headerbar_bg']}'>";
        echo "<font color='{$theme['color_headerbar_font']}'>";
        echo "<b>$text</b>";
        echo "</font>";
        echo "</td>";
        echo "\n";
    }

    echo "<tr>";
    echo_header_cell( 175, _("Title") );
    echo_header_cell( 100, _("Author") );
    echo_header_cell( 25, _("Diff.") );
    if ( $show_pages_total )
    {
        echo_header_cell( 50, _("Total") );
    }
    echo_header_cell(  75, _("PM") );
    echo_header_cell(  75, _("Checked Out By") );
    echo_header_cell( 180, _("Project Status") );
    if ( $show_options_column )
    {
        echo_header_cell(  30, _("Options") );
    }
    echo "</tr>";

    // Determine whether to use special colors or not
    // (this does not affect the alternating between two
    // background colors) in the project listing.
    $userSettings = Settings::get_Settings($pguser);
    $show_special_colors = !$userSettings->get_boolean('hide_special_colors');

    $tr_num = 0;
    while ($project_assoc = mysql_fetch_assoc($result)) {
        $project = new Project($project_assoc);
        $projectid = $project->projectid;

        if ($tr_num % 2 ) {
            $bgcolor = $theme['color_mainbody_bg'];
        } else {
            $bgcolor = $theme['color_navbar_bg'];
        }

        // Special colours for special books of various types
        if ($show_special_colors)
        {
            $special_color = get_special_color_for_project($project_assoc);
            if (!is_null($special_color)) {
                $bgcolor = "'$special_color'";
            }
        }

        echo "<tr bgcolor=$bgcolor>\n";

        // Title
        echo "<td><a href='$code_url/project.php?id=$projectid&amp;detail_level=3'>{$project->nameofwork}</a></td>\n";

        // Author
        echo "<td>{$project->authorsname}</td>\n";

        // Difficulty
        $diff = strtoupper(substr($project->difficulty,0,1));
        echo "<td align=\"center\">$diff</td>\n";


        // Total
        if ( $show_pages_total )
        {
            $totpag = $project->n_pages;

            echo "<td align=\"center\">$totpag</td>\n";
        }


        // PM
        echo "<td align=\"center\">";
            print $project->username;
        echo "</td>\n";

        // Checked Out By
        echo "<td align=\"center\">";
        if ($project->checkedoutby != "") {
            // Maybe we should get this info via a
            // left outer join in the big select query.
            $tempsql = mysql_query("SELECT user_id FROM phpbb_users WHERE username = '{$project->checkedoutby}'");
            $outby_user_id = mysql_result($tempsql, 0);
            $contact_url = "$forums_url/privmsg.php?mode=post&amp;u=$outby_user_id";
            print "<a href='$contact_url'>{$project->checkedoutby}</a>";
        }
        echo "</td>\n";

        // Project Status

        echo "<td valign=center>\n";
        echo_project_state_changer($project);
        echo "</td>\n";

        // Options
        if ( $show_options_column )
        {
            echo "<td align=center>";
            if ( user_is_a_sitemanager() || user_is_proj_facilitator() || $project->username == $pguser )
            {
                print "<a href=\"editproject.php?action=edit&project=$projectid\">Edit</a>";
            }
            if ( $user_can_see_download_links )
            {
                if ($project->state == PROJ_POST_FIRST_UNAVAILABLE ||
                    $project->state == PROJ_POST_FIRST_AVAILABLE ||
                    $project->state == PROJ_POST_FIRST_CHECKED_OUT)
                {
                    print " <a href=\"$projects_url/$projectid/$projectid.zip\">D/L</A>";
                }
                if ($project->state == PROJ_POST_SECOND_CHECKED_OUT ||
                    $project->state == PROJ_POST_COMPLETE)
                {
                    print " <a href=\"$projects_url/$projectid/".$projectid."_second.zip\">D/L</A>";
                }
            }
            echo "</td>\n";
        }

        echo "</tr>\n";

        $tr_num++;
    }

    echo "<tr><td colspan=8 bgcolor='".$theme['color_headerbar_bg']."'>&nbsp;</td></tr></table></center>";
    echo "\n";

    results_navigator();

    // special colours legend
    // Don't display if the user has selected the
    // setting "Show Special Colors: No".
    // The legend has been put at the bottom of the page
    // because the use of colors is presumably mostly
    // useful as a check that no typo was made. The
    // exact color probably doesn't matter and,
    // furthermore, the PM 'knows' the project and
    // what's so special about it.
    if (!$userSettings->get_boolean('hide_special_colors')) {
        echo "<p><font face='{$theme['font_mainbody']}'>\n";
        echo_special_legend(" 1 = 1");
        echo "</font></p><br>\n";
    }

    // Commented out until it's working.
    // list_uber_projects( TRUE );
}
echo "<br>";
theme("","footer");

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_project_state_changer($project)
{
    global $pguser, $code_url;

    $transitions = get_valid_transitions( $project, $pguser );

    if ( count($transitions) > 0 )
    {
        $here = $_SERVER['REQUEST_URI'];
        echo "
            <form
                name='$project->projectid'
                method='POST'
                action='$code_url/tools/changestate.php'>
            <input
                type='hidden'
                name='projectid'
                value='$project->projectid'>
            <input
                type='hidden'
                name='curr_state'
                value='$project->state'>
            <input
                type='hidden'
                name='return_uri'
                value='$here'>
            <select
                name='next_state'
                onchange='this.form.submit()'>
        ";

        echo_project_state_option( $project->state, 1 );

        foreach ( $transitions as $transition )
        {
            echo_project_state_option( $transition->to_state, 0 );
        }

        echo "
            </select>
            </form>
        ";
    }
    else
    {
        echo project_states_text($project->state), "\n";
    }
}

function echo_project_state_option($project_state,$selected)
{
    echo "<option value='$project_state'";
    if ($selected) echo " SELECTED";
    echo ">";
    if ($project_state == 'automodify')
    {
        echo 'automodify';
    }
    else
    {
        echo project_states_text($project_state);
    }
    echo "</option>\n";
}

// -----------------------------------------------------------------------------

function list_uber_projects( $can_see_all )
{
    global $pguser, $theme, $PROJECT_IS_ACTIVE_sql;

    // site managers and project facilitors can see all uber projects

    if ($can_see_all) {

        $UPs = mysql_query("
            SELECT * FROM uber_projects WHERE up_enabled = 1
        ");

    } else {

        // if the user is currently the UP_manager of any Uber Projects, display them

        // note that the Settings class can't handle lists of values, nor joins to other tables,
        // so we go directly to the user_settings table instead

        $UPs = mysql_query("
            SELECT * FROM uber_projects up, usersettings us
            WHERE us.username = '$pguser' AND
                us.setting  = 'up_manager' AND
                us.value = up.up_projectid
        ");

    }

    if (mysql_num_rows($UPs)) {

        $tr_num = 0;

        echo "<br><center><h3>"._("Uber Projects to which you have access")."</h3></center><br>";

        echo "<center><table border=1 width=630 cellpadding=0 cellspacing=0 style='border-collapse: collapse' bordercolor=#111111>";

        echo "<tr>";
        echo_header_cell( 300, _("Overall Name of Uber Project") );
        echo_header_cell( 75,  _("Your Active Related Projects") );
        echo_header_cell( 55,  _("All Your Related Projects") );
        echo_header_cell( 75,  _("All Active Related Projects") );
        echo_header_cell( 55,  _("All Related Projects") );
        echo_header_cell( 55,  _("Project Managers") );
        echo_header_cell( 30,  _("Forum Thread") );
        echo_header_cell( 30,  _("Options") );
        echo "</tr>";


        if (!$can_see_all) {
            $limit_to_user = " ";
        } else {
            $limit_to_user = " ";
        }

        while ($UPinfo = mysql_fetch_assoc($UPs)) {

            $up_projid = $UPinfo['up_projectid'];
            $up_name = $UPinfo['up_nameofwork'];
            $up_topicid = $UPinfo['up_topic_id'];

            // no one will have specific access to a large number of UPs,
            // and SA/PFs only see the list when they request it,
            // so these next few queries shouldn't be too expensive
            // in absolute terms, even though they are in a loop

            $num_active_proj_res = mysql_fetch_assoc(mysql_query("
                SELECT count(*) as num
                FROM projects WHERE up_projectid = '$up_projid'
                AND $PROJECT_IS_ACTIVE_sql
                AND username = '".$pguser."'
            "));
            $num_active_proj = $num_active_proj_res['num'];

            $num_all_active_proj_res = mysql_fetch_assoc(mysql_query("
                SELECT count(*) as num
                FROM projects WHERE up_projectid = '$up_projid'
                AND $PROJECT_IS_ACTIVE_sql
            "));
            $num_all_active_proj = $num_all_active_proj_res['num'];

            $num_proj_res = mysql_fetch_assoc(mysql_query("
                SELECT count(*) as num
                FROM projects WHERE up_projectid = '$up_projid'
                AND username = '".$pguser."'
            "));
            $num_proj = $num_proj_res['num'];

            $num_all_proj_res = mysql_fetch_assoc(mysql_query("
                SELECT count(*) as num
                FROM projects WHERE up_projectid = '$up_projid'
            "));
            $num_all_proj = $num_all_proj_res['num'];

            $num_PM_res = mysql_fetch_assoc(mysql_query("
                SELECT count(*) as num
                FROM usersettings WHERE setting = 'up_manager' and value = '$up_projid'
            "));
            $num_PM = $num_PM_res['num'];

            if ($tr_num % 2 ) {
                $bgcolor = $theme['color_mainbody_bg'];
            } else {
                $bgcolor = $theme['color_navbar_bg'];
            }

            echo "<tr bgcolor=$bgcolor>\n";

            // Name
            echo "<td>$up_name</td>\n";

            // Number of THIS USER'S active related projects  (NB SA/PFs are users too!)
            echo "<td align=\"center\"><a href='projectmgr.php?up_projectid=$up_projid'>$num_active_proj</a></td>\n";

            // Number of all of THIS USER'S related projects
            echo "<td align=\"center\"><a href='projectmgr.php?show=user_all&up_projectid=$up_projid'>$num_proj</a></td>\n";

            // Number of ALL active related projects
            // For SA/PFs this is a link to them, others just see the total
            if ($can_see_all) {
                $link_top = "<a href='projectmgr.php?show=site_active&up_projectid=".$up_projid."'>";
                $link_tail = "</a>";
            } else {
                $link_top = "";
                $link_tail = "";
            }
            echo "<td align=\"center\">".$link_top.$num_all_active_proj.$link_tail."</td>\n";

            // Number of ALL related projects
            // For SA/PFs this is a link to them, others just see the total
            if ($can_see_all) {
                $link_top = "<a href='projectmgr.php?show=allfor&up_projectid=".$up_projid."'>";
                $link_tail = "</a>";
            } else {
                $link_top = "";
                $link_tail = "";
            }
            echo "<td align=\"center\">".$link_top.$num_all_proj.$link_tail."</td>\n";

            // Number of project managers
            // could in a fancy future show SA/PFs a drop down list of PMs with
            // projects related to this UP, and let selection show the list
            // filtered by PM...
            echo "<td align=\"center\">$num_PM</td>\n";

            // link to Forum thread
            echo "<td>Click here</td>\n";

            // Options
            echo "<td>Edit / Create New</td>\n";
            echo "</tr>\n";

            $tr_num++;
        }

        echo "<tr><td colspan=8 bgcolor='".$theme['color_headerbar_bg']."'>&nbsp;</td></tr></table></center>";
    }
}

// vim: sw=4 ts=4 expandtab
?>
