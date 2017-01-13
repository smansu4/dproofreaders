<?php
$relPath = '../../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'theme.inc');
include_once('authors.inc');
include_once('menu.inc');

require_login();

$author_id = get_integer_param($_GET, 'author_id', null, null, null, TRUE);

$result = mysql_query("SELECT * FROM authors WHERE author_id=$author_id");
$row = mysql_fetch_assoc($result);
$last_name = $row["last_name"];
$other_names = $row["other_names"];
$birth = format_date_from_sqlset($row, 'b');
$decease = format_date_from_sqlset($row, 'd');

// Start outputting
output_header(_('Author') . ': ' . $last_name . ($other_names!=''?", $other_names":''));

echo_menu($author_id);

echo '<h1 align="center">' . _('Author') . '</h1>';

echo_author($last_name, $other_names, $birth, $decease, $author_id);

echo_menu($author_id);

// vim: sw=4 ts=4 expandtab
