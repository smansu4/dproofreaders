<?php
$relPath='../../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'theme.inc');

output_header(_('Moderate Proofreading Tutorial'));

echo "<h2>" . sprintf(_("Moderate Proofreading Tutorial, Page %d"), 3) . "</h2>\n";
echo "<h3>" . _("Accented/Non-ASCII Characters") . "</h3>\n";
if(!$utf8_site)
{
    echo "<p>" . _("Please proofread these using the proper symbols or accented characters to match the image, where possible, including the use or non-use of accents. ") . "</p>\n";
} else {
    echo "<p>" . _("Please proofread these using the proper UTF-8 characters. For characters which are not in Unicode, see the Project Manager's instructions in the Project Comments.") . "</p>\n";
}

echo "<h3>" . _("Inserting Special Characters") . "</h3>\n";
echo "<p>" . _("If they are not on your keyboard, there are several ways to input special characters:") . "</p>\n";
echo "<ul compact>\n";
echo "<li>" . _("The pull-down menus in the proofreading interface.") . "</li>\n";
echo "<li>" . _("Keyboard shortcuts.") . "<br>\n";
echo sprintf(_("Tables for <a href='%1\$s' target='_blank'>Windows</a> and <a href='%2\$s' target='_blank'>Macintosh</a> which list these shortcuts are in the Proofreading Guidelines."), "../../faq/proofreading_guidelines.php#a_chars_win", "../../faq/proofreading_guidelines.php#a_chars_mac") . "</li>\n";
echo "<li>" . sprintf(_("Other methods described in the <a href='%s' target='_blank'>guidelines</a>."), "../../faq/proofreading_guidelines.php#insert_char") . "</li>\n";
echo "</ul>\n";

echo "<h3>" . _("Fractions") . "</h3>\n";
echo "<p>" . _("Proofread fractions as follows: <tt>&frac14;</tt> becomes <tt>1/4</tt>, and <tt>2&frac12;</tt> becomes <tt>2-1/2</tt>. The hyphen prevents the whole and fractional part from becoming separated when the lines are rewrapped during post-processing.") . "</p>\n";

echo "<p><a href='../generic/main.php?quiz_page_id=p_mod1_3'>" . _("Continue to quiz") . "</a></p>\n";

// vim: sw=4 ts=4 expandtab
