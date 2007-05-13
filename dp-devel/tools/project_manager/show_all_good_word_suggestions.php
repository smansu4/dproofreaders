<?
$relPath="./../../pinc/";
include_once($relPath.'site_vars.php');
include_once($relPath.'dp_main.inc');
include_once($relPath.'wordcheck_engine.inc');
include_once($relPath.'slim_header.inc');
include_once($relPath.'Stopwatch.inc');
include_once('./post_files.inc');
include_once("./word_freq_table.inc");

$datetime_format = _("%A, %B %e, %Y at %X");

$watch = new Stopwatch;
$watch->start();

set_time_limit(0); // no time limit

$freqCutoff = array_get($_REQUEST,"freqCutoff",5);
$timeCutoff = array_get($_REQUEST,"timeCutoff",-1);

// load the PM
$pm = array_get($_REQUEST,"pm",$pguser);
if ( !user_is_a_sitemanager() && !user_is_proj_facilitator() ) {
    $pm = $pguser;
}

// $frame determines which frame we're operating from
//     none - we're the master frame
//   'left' - we're the left frame with the text
//  'right' - we're the right frame for the context info
// 'update' - not a frame at all - process the incoming data
$frame = array_get($_REQUEST,"frame","master");

if($frame=="update") {
    $newProjectWords=array();
    foreach($_POST as $key => $val) {
        if(preg_match("/cb_(projectID.*)_(\d+)/",$key,$matches)) {
            $projectid=$matches[1];
            $word=decode_word($val);
            if(!is_array($newProjectWords[$projectid]))
                $newProjectWords[$projectid]=array();
            array_push($newProjectWords[$projectid],$word);
        }
    }

    foreach($newProjectWords as $projectid => $projectWords) {
        $words = load_project_good_words($projectid);
        $words = array_merge($words,$projectWords);
        save_project_good_words($projectid,$words);
    }

    $frame="left";
}

if($frame=="master") {
    slim_header(_("Manage Suggestions"),TRUE,FALSE);
    $frameSpec='cols="40%,60%"';
    if(@$_REQUEST["timecutoff"])
        $timeCutoffSpec="timeCutoff=$timeCutoff&amp;";
    else $timeCutoffSpec="";
?>
</head>
<frameset <?=$frameSpec;?>>
<frame src="<?=$_SERVER["PHP_SELF"];?>?pm=<?=$pm;?>&amp;freqCutoff=<?=$freqCutoff;?>&amp;<?=$timeCutoffSpec;?>frame=left">
<frame name="detailframe" src="<?=$_SERVER["PHP_SELF"];?>?frame=right">
</frameset>
<noframes>
<? _("Your browser currently does not display frames!"); ?>
</noframes>
</html>
<?
    exit;
}

// now load data in the left frame
if($frame=="left") {
    // get all projects for this PM
    $projects = _get_projects_for_pm($pm);

    $submitLabel = _("Add selected words to Good Words List");

    slim_header(_("Manage Suggestions"),TRUE,TRUE);

    // how many instances (ie: frequency sections) are there?
    $instances=count( $projects ) + 1;
    // what are the cutoff options?
    $cutoffOptions = array(1,2,3,4,5,10,25,50);
    // what is the intial cutoff frequecny?
    $initialFreq=getInitialCutoff($freqCutoff,$cutoffOptions);

    // echo page support text, like JS and stylesheets
    echo_cutoff_script($cutoffOptions,$instances);

    echo_word_freq_style();

    echo "<h1>" . _("Manage Suggestions") . "</h1>";

    echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='get'>";
    echo "<input type='hidden' name='frame' value='left'>";
    echo "<p>";
    if ( user_is_a_sitemanager() || user_is_proj_facilitator() ) {
        echo _("View projects for user:") . " <input type='text' name='pm' value='$pm' size='10'><br>";
    }

echo _("Show:") . " ";
echo "<select name='timeCutoff'>";
echo "<option value='0'"; if($timeCutoff==0) echo "selected"; echo ">" . _("All suggestions") . "</option>";
echo "<option value='-1'"; if($timeCutoff==-1) echo "selected"; echo ">" . _("Suggestions since Good Words List was saved") . "</option>";
$timeCutoffOptions=array(1,2,3,4,5,6,7,14,21);
foreach($timeCutoffOptions as $timeCutoffOption) {
    $timeCutoffValue = ceil((time() - 24*60*60*$timeCutoffOption)/100)*100;
    echo "<option value='$timeCutoffValue'";
    if($timeCutoff==$timeCutoffValue) echo "selected";
    echo ">" . sprintf(_("Suggestions made in the past %d days"),$timeCutoffOption) . "</option>";
}
echo "</select>";
echo "<br>";


    echo "<input type='submit' value='Submit'></p>";
    echo "</form>";

    if($timeCutoff==-1)
        $time_cutoff_text = _("Suggestions since the project's Good Words List was last modified are included.");
    elseif($timeCutoff==0)
        $time_cutoff_text = _("<b>All proofer suggestions</b> are included in the results.");
    else
        $time_cutoff_text = sprintf(_("Only proofer suggestions made <b>after %s</b> are included in the results."),strftime($datetime_format,$timeCutoff));

    echo "<p>" . $time_cutoff_text . "</p>";

    echo "<p>" . sprintf(_("Selecting any '%s' button will add all selected words to their corresponding project word list, not just the words in the section for the button itself."),$submitLabel) . "</p>";
    
    echo_cutoff_text( $initialFreq,$cutoffOptions );

    $t_before = $watch->read();

    echo "<form action='" . $_SERVER["PHP_SELF"] . "' method='post'>";
    echo "<input type='hidden' name='frame' value='update'>";
    echo "<input type='hidden' name='pm' value='$pm'>";
    echo "<input type='hidden' name='timeCutoff' value='$timeCutoff'>";

    $projectsNeedingAttention=0;
    // loop through the projects
    foreach($projects as $projectid=>$projectname) {
        $goodFileObject = get_project_word_file($projectid,"good");
        $goodSuggsFileObject = get_project_word_file($projectid,"good_suggs");

        // set the timeCutoff
        if($timeCutoff==-1) $timeCutoffActual=$goodFileObject->mod_time;
        else $timeCutoffActual=$timeCutoff;

        // if the suggestion file hasn't been modified since the
        // good file list, or the suggestion file doesn't exist, skip it
        if(($timeCutoffActual > $goodSuggsFileObject->mod_time)
            || !$goodSuggsFileObject->exists) continue;

        // get the data
        list($suggestions_w_freq,$suggestions_w_occurances,$messages) =
            _get_word_list($projectid,$timeCutoffActual);

        // if no words are returned (probably because something was
        // suggested but is no longer in the text) skip this project
        if(count($suggestions_w_freq)==0) continue;

        $projectsNeedingAttention++;

        echo "<hr>";
        echo "<h3>$projectname</h3>";

        echo "<p>";
        echo "<a href='#' onClick=\"return checkAll('$projectid'," . count($suggestions_w_freq) . ",true)\">";
        echo    _("Check All");
        echo "</a>";
        echo " | ";
        echo "<a href='#' onClick=\"return checkAll('$projectid'," . count($suggestions_w_freq) . ",false)\">";
        echo    _("Uncheck All");
        echo "</a>";
        echo "</p>";

        echo_any_warnings_errors( $messages );

        $count=0;
        foreach($suggestions_w_freq as $word => $freq) {
            $encWord = encode_word($word);
            $context_array[$word]="<a href='show_good_word_suggestions_detail.php?projectid=$projectid&amp;word=$encWord&amp;timeCutoff=$timeCutoff' target='detailframe'>" . _("Context") . "</a>";
            $word_checkbox[$word]="<input type='checkbox' id='cb_{$projectid}_{$count}' name='cb_{$projectid}_{$count}' value='$encWord'>";
            $count++;
        }
        $suggestions_w_occurances['[[TITLE]]']=_("Sugg");
        $suggestions_w_occurances['[[STYLE]]']="text-align: right;";
        $context_array['[[TITLE]]']=_("Show Context");

        printTableFrequencies($initialFreq,$cutoffOptions,$suggestions_w_freq,$instances--,array($suggestions_w_occurances,$context_array),$word_checkbox);

        echo "<p><input type='submit' value='$submitLabel'></p>";
    }

    if($projectsNeedingAttention==0) {
        echo "<p>" . _("No projects have proofer suggestions for the given timeframe.") . "</p>";
    } else {
        echo "<hr>";
    }

    echo "</form>";

    $t_after = $watch->read();
    $t_to_generate_data = $t_after - $t_before;

    echo_page_footer($t_to_generate_data);
}


//---------------------------------------------------------------------------
// supporting page functions

function _get_word_list($projectid,$timeCutoff) {
    $messages = array();

    // load the suggestions
    $suggestions = load_project_good_word_suggestions($projectid,$timeCutoff);
    if(!is_array($suggestions)) {
        $messages[] = sprintf(_("Unable to load suggestions: %s"),$suggestions);
        return array( array(), array(), $messages);
    }

    if(count($suggestions)==0) {
        return array( array(), array(), $messages);
    }

    // load project good words
    $project_good_words = load_project_good_words($projectid);

    // load project bad words
    $project_bad_words = load_project_bad_words($projectid);

    // get the latest project text of all pages up to last possible round
    $last_possible_round = get_Round_for_round_number(MAX_NUM_PAGE_EDITING_ROUNDS);
    $pages_res = page_info_query($projectid,$last_possible_round->id,'LE');
    $all_words_w_freq = get_distinct_words_in_text( get_page_texts( $pages_res ));

    // array to hold all words
    $all_suggestions = array();

    // parse the suggestions complex array
    // it is in the format: $suggestions[$round][$pagenum]=$wordsArray
    foreach( $suggestions as $round => $pageArray ) {
        $round_suggestions = array();
        foreach( $pageArray as $page => $words) {
            // add the words to the combined array too
            $all_suggestions = array_merge($all_suggestions,$words);
        }
    }

    // now, remove any words that are already on the project's good or bad words lists
    $all_suggestions = array_diff( $all_suggestions, array_merge($project_good_words,$project_bad_words) );

    // get the number of suggestion occurances
    $all_suggestions_w_occurances = generate_frequencies( $all_suggestions );

    // $all_suggestions doesn't have frequency info,
    // so start with the info in $all_words_w_freq,
    // and extract the items where the key matches a key in $all_suggestions.
    $all_suggestions_w_freq = array_intersect_key( $all_words_w_freq, array_flip( $all_suggestions ) );

    // multisort screws up all-numeric words so we need to preprocess first
    prep_numeric_keys_for_multisort( $all_suggestions_w_freq );

    // sort the list by frequency, then by word
    array_multisort( array_values( $all_suggestions_w_freq ), SORT_DESC, array_map( 'strtolower', array_keys( $all_suggestions_w_freq ) ), SORT_ASC, $all_suggestions_w_freq );

    return array($all_suggestions_w_freq, $all_suggestions_w_occurances, $messages);
}

function _get_projects_for_pm($pm) {
    $returnArray=array();
    $query = "select projectid, nameofwork from projects where username='$pm' order by nameofwork";
    $res = mysql_query($query);
    while($ar = mysql_fetch_array($res)) {
        $returnArray[$ar["projectid"]]=$ar["nameofwork"];
    }
    return $returnArray;

}

// vim: sw=4 ts=4 expandtab
?>