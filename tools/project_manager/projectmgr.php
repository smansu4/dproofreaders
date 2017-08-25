<?php
$relPath="./../../pinc/";
include_once($relPath.'base.inc');
include_once($relPath.'user_is.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'metarefresh.inc');
include_once($relPath.'SettingsClass.inc');
include_once($relPath.'special_colors.inc');
include_once($relPath.'gradual.inc');
include_once($relPath.'ProjectSearchForm.inc');
include_once($relPath.'ProjectSearchResults.inc');
include_once('projectmgr.inc');

require_login();

if (user_is_PM() && empty($_GET['show'])) {
    if ($userP['i_pmdefault'] == 0) {
        metarefresh(0,"projectmgr.php?show=user_all","","");
        exit();
    } elseif ($userP['i_pmdefault'] == 1) {
        metarefresh(0,"projectmgr.php?show=user_active","", "");
        exit();
    }
}

output_header(_("Project Search"), NO_STATSBAR);

$search_form = new ProjectSearchForm();

$PROJECT_IS_ACTIVE_sql = "(state NOT IN ('".PROJ_SUBMIT_PG_POSTED."','".PROJ_DELETE."'))";

if (!isset($_GET['show']) || $_GET['show'] == 'search_form') {

    echo_manager_header();

    // New proofreaders are having a hard time finding stuff because they
    // end up on the Project Search page instead of the starting round page.
    // See if we can't help them out by pointing them to the starting
    // round page.
    $pagesproofed = get_pages_proofed_maybe_simulated();
    if($pagesproofed < 100)
    {
        echo "<div class='callout'>";
        echo "<div class='calloutheader'>";
        echo _("Looking for projects to proofread?");
        echo "</div>";

        echo "<p>" . sprintf(_("If you're looking for projects to proofread, consider using the list on the <a href='%1\$s'>%2\$s</a> round page instead of this search form."), "$code_url/{$ELR_round->relative_url}#{$ELR_round->id}", $ELR_round->id) . "</p>";
        echo "</p>";

        echo "<p><small>";
        echo _("After a period of time, this message will no longer appear.");
        echo "</small></p>";
        echo "</div>";
    }

    echo "
        <center>
        <h1>", _("Search for Projects"), "</h1>
        "._("Search for projects matching the following criteria:")."<br>";

    $search_form->render('projectmgr.php');

    echo "</center>";
} else {
    echo_manager_header();

    // Construct and submit the search query.

    if ($_GET['show'] == 'search') {
        $condition = $search_form->get_widget_contribution();
    } elseif ($_GET['show'] == "site_active") {
        $condition = $PROJECT_IS_ACTIVE_sql;
    } elseif ($_GET['show'] == "user_all") {
        $condition = "username = '$pguser'";
    } else {
        // ($_GET['show'] == "user_active")
        // plus some corner cases
        $condition = "$PROJECT_IS_ACTIVE_sql AND username = '$pguser'";
    }

    // Determine whether to use special colors or not
    // (this does not affect the alternating between two
    // background colors) in the project listing.
    $userSettings =& Settings::get_Settings($pguser);
    $show_special_colors = !$userSettings->get_boolean('hide_special_colors');

    echo "<h1>", _("Search Results"), "</h1>\n";

    $search_results = new ProjectSearchResults($search_form->get_page_size());
    $results_offset = intval(@$_GET['results_offset']);
    $search_results->render($condition, $results_offset, $show_special_colors);

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
        echo_special_legend(" 1 = 1");
    }
}
echo "<br>";

// vim: sw=4 ts=4 expandtab
