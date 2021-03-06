<?php
include_once($relPath.'site_vars.php');
include_once($relPath.'user_is.inc');
include_once($relPath.'project_edit.inc');

function abort_if_not_manager()
{
    global $site_manager_email_addr, $code_url;

    if ( !user_is_PM())
    {
        echo "<p>".sprintf(_("You are not listed as a project manager.
            Please contact the <a href='%s'>site manager</a>
            about resolving this problem."),"mailto:$site_manager_email_addr")."
              </p><p>
              ".sprintf(_("Back to <a href='%s'>home page</a>"),"$code_url/default.php")."
              </p>";
        exit();
    }
}

function echo_manager_links()
{
    global $theme, $code_url, $external_catalog_locator, $db_requests_email_addr;

    $create_project_url = (empty($external_catalog_locator)
                           ? "$code_url/tools/project_manager/editproject.php?action=createnew"
                           : "$code_url/tools/project_manager/external_catalog_search.php?action=show_query_form");

    echo "<div id='pm_links' class='sidebar-color'>\n";
    // TRANSLATORS: PM = project manager
    echo "<h2>", _("PM Links"), "</h2>
    <ul>\n";
    if(user_is_PM())
    {
        echo "<li><a href='$code_url/tools/project_manager/remote_file_manager.php'>"._("Manage uploads")."</a></li>\n";
        if (! user_has_project_loads_disabled())
        {
            echo "<li><a href='$create_project_url'>"._("Create Project")."</a></li>\n";
        }
        echo "<li><a href='$code_url/tools/project_manager/project_quick_check.php'>"._("Project Quick Check")."</a></li>\n";
        # SITE-SPECIFIC
        echo "<li><a href='$code_url/../noncvs/send_email.php?to=db-req'>"._("Email db-req")."</a></li>\n";
        echo "<li><a href='$code_url/../noncvs/send_email.php?to=dp-format'>"._("Email dp-format")."</a></li>\n";
        echo "<li><a href='$code_url/tools/project_manager/show_all_good_word_suggestions.php'>"._("Manage proofreaders' Suggestions")."</a></li>\n";
    }
    echo "<li><a href='$code_url/tools/project_manager/show_image_sources.php'>"._("Image Sources Info")."</a></li>\n";
    if (user_is_image_sources_manager())
    {
        echo "<li><a href='$code_url/tools/project_manager/manage_image_sources.php?action=show_sources'>"._("Manage Image Sources")."</a></li>\n";
    }
    echo "<li><a href='$code_url/tools/project_manager/show_specials.php'>"._("Special Days Info")."</a></li>\n";
    if(user_is_a_sitemanager())
    {
        echo "<li><a href='$code_url/tools/site_admin/manage_special_days.php'>"._("Manage Special Days")."</a></li>\n";
    }
    echo "<li><a href='$code_url/stats/release_queue.php'>"._("Show All Release Queues")."</a></li>\n";
    echo "<li><a href='https://www.pgdp.net/wiki/DP_Official_Documentation:CP_and_PM'>"._("PM Official Documentation")."</a></li>\n";
    echo "<li><a href='$code_url/faq/proofreading_guidelines.php'>"._("Proofreading Guidelines")."</a></li>\n";
    echo "<li><a href='$code_url/faq/formatting_guidelines.php'>"._("Formatting Guidelines")."</a></li>\n";

    echo "</ul>\n";
    echo "</div>\n";
}

// vim: sw=4 ts=4 expandtab
?>
