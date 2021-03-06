<?php
$relPath = '../../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'misc.inc'); // html_safe()
include_once("authors.inc");
include_once("menu.inc");

require_login();

abort_if_not_authors_db_editor(true);

// load posted values or defaults
if (isset($_GET['author_id'])) {
    // init creation of new bio
    $author_id = get_integer_param($_GET, 'author_id', null, null, null, TRUE);
    $bio = '';
}
elseif (isset($_GET['bio_id'])) {
    // init edit of existing bio
    $bio_id = get_integer_param($_GET, 'bio_id', null, null, null, TRUE);
    $result = mysqli_query(DPDatabase::get_connection(), "SELECT * FROM biographies WHERE bio_id = $bio_id;");
    $row = mysqli_fetch_assoc($result);
    $author_id = $row["author_id"];
    $bio       = $row["bio"];
}
elseif (isset($_POST['author_id'])) {
    // preview / save
    $author_id = get_integer_param($_POST, 'author_id', null, null, null, TRUE);
    $bio = $_POST['bio'];

    if (isset($_POST['SaveAndExit']) || isset($_POST['SaveAndView']) || isset($_POST['SaveAndNew'])) {

        // save

        if (isset($_POST['bio_id'])) {
            // edit existing bio
            $bio_id = get_integer_param($_POST, 'bio_id', null, null, null, TRUE);
            $result = mysqli_query(DPDatabase::get_connection(), sprintf("
                UPDATE biographies
                SET bio = '%s'
                WHERE bio_id = %s
            ", mysqli_real_escape_string(DPDatabase::get_connection(), $bio), $bio_id));
            $msg = _('The biography was successfully updated in the database!');
        }
        else {
            // add to database
            $result = mysqli_query(DPDatabase::get_connection(), sprintf("
                INSERT INTO biographies
                    (author_id, bio)
                VALUES(%s, '%s')
            ", $author_id, mysqli_real_escape_string(DPDatabase::get_connection(), $bio)));
            $bio_id = mysqli_insert_id(DPDatabase::get_connection());
            $msg = _('The biography was successfully entered into the database!');
        }
        if ($result) {
            // success
            if (isset($_POST['SaveAndExit']))
                header('Location: listing.php?message=' . urlencode($msg));
            else if (isset($_POST['SaveAndView']))
                header("Location: bio.php?bio_id=$bio_id&message=" . urlencode($msg));
            else if (isset($_POST['SaveAndNew']))
                header('Location: add.php?message=' . urlencode($msg));
        }
        else {
            // failure!
            output_header(_('An error occurred'));
            echo _('It was not possible to save the biography.') . _('The following error-message was received:') . ' ' .
                         mysqli_error(DPDatabase::get_connection());
        }
        exit;
    }
    else {
        // Preview
    }
}
else {
    // someone's trying to display this page outside of the workflow.
    output_header(_('An error occurred'));
    echo _('Some information is missing and this page can not be displayed. This has most likely occurred ' .
                 'because you have entered the URL manually. Please enter this page by following links from other pages.');
    exit;
}

if (isset($_POST['bio_id']))
    $bio_id = $_POST['bio_id'];

// from here on to end of file:
// produce form (with blank values
// or those to be edited)

output_header(_('Add biography'));

echo "<h1>" . _("Add Biography") . "</h1>";
echo_menu();


if (isset($msg))
    echo html_safe($msg);

$message = @$_GET['message'];
if (isset($message))
    echo html_safe($message) . '<br>';
elseif (isset($_POST['Preview'])) {
    echo '<table border="1"><td>' . html_safe($bio) . '</td></table>';
    echo '<br>';
}
?>
<form name="addform" action="addbio.php" method="POST">
<input type="hidden" name="author_id" value="<?php echo $author_id; ?>">
<?php
if (isset($bio_id))
    echo '<input type="hidden" name="bio_id" value="' . $bio_id . '">';
?>
<table border="1">
<tr><th><?php echo _('Biography') . ' (' . _('HTML') . ')'; ?></th></tr>
<tr><td><textarea cols="70" rows="20" name="bio">
<?php echo html_safe($bio); ?></textarea></td></tr>
<tr><td>
<table><tr>
<td><input type="submit" name="Preview" value="<?php echo _('Preview'); ?>"></td>
<td><input type="submit" name="SaveAndExit" value="<?php echo _('Save and Exit'); ?>"></td>
<td><input type="submit" name="SaveAndView" value="<?php echo _('Save and View'); ?>"></td>
<td><input type="submit" name="SaveAndNew" value="<?php echo _('Save and add Author'); ?>"></td>
<td><input type="button" value="<?php echo _('Exit without saving'); ?>" onClick="location='listing.php';"></td>
</tr></table>
</td></tr>
</table>
</form>

<?php
echo_menu();

// vim: sw=4 ts=4 expandtab
