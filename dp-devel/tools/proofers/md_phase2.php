<?
$relPath="./../../pinc/";
include($relPath.'v_site.inc');
include($relPath.'dp_main.inc');
include_once($relPath.'c_pages.inc');
include($relPath."doctype.inc");
include_once($relPath.'theme.inc');

// get cookie
//$tpage=new processpage();
//$npage=$tpage->getPageCookie();



if(!isset($_GET['imagename']))
{
//find next available page for this project
$result = mysql_query("SELECT image FROM $projectid WHERE state = 'avail_md_second' ORDER BY image ASC LIMIT 1");
//if no more images
$numrows = mysql_num_rows($result);
 if($numrows == '0')
{
$body=_("No more files available for proofing for this round of the project.<br> You will be taken back to the project listing page in 4 seconds.");
//////////this will be changed to pre-processing state
$result = mysql_query("UPDATE $projectid SET state = 'avail_first'");
$result = mysql_query("UPDATE projects SET state = 'avail_1' WHERE projectid = '$projectid'");
//////////

metarefresh(5,"md_available.php","Image Metadata Collection",$body);
exit;
}
else
{
$imagename = mysql_result($result, 0, "image");
//set the image as checked out
$result = mysql_query("UPDATE $projectid SET state = 'out_md_second' WHERE image = '$imagename'");
metarefresh(0,"md_phase2.php?imagename=$imagename&projectid=$projectid","Image Metadata Collection","");
}
}


if (isset($_POST['done']))
{
//process the page metadata
    //get existing metadata
      $result = mysql_query("SELECT metadata FROM $projectid WHERE image = '$imagename'");
      $old_md = mysql_result($result, 0, "metadata");

    //concat new metadata
    $i=0;
    foreach($HTTP_POST_VARS as $key => $val)
    {
     if ($val =='on')
    {
     $new_md = $new_md.','.$key;
     $i++;
    }
     $all_md = $old_md.$new_md;
     $result = mysql_query("UPDATE $projectid SET metadata = '$all_md' WHERE image = '$imagename'");
    }

    //change page status and back to md_available.php
    $result = mysql_query("UPDATE $projectid SET state = 'save_md_second' WHERE image = '$image'");
    metarefresh(0,'md_available.php',"Image Metadata Collection","");
}

if (isset($_POST['quit']))
{
//they don't want to save so set page to avail return them to md_available
$result = mysql_query("UPDATE $projectid SET state = 'avail_md_second' WHERE image = '$image'");
metarefresh(0,'md_available.php',"Image Metadata Collection","");
}

if (isset($_POST['continue']))
{
  //process the page metadata
    //get existing metadata
      $result = mysql_query("SELECT metadata FROM $projectid WHERE image = '$imagename'");
      $old_md = mysql_result($result, 0, "metadata");

    //concat new metadata
    $i=0;
    foreach($HTTP_POST_VARS as $key => $val)
    {
     if ($val =='on')
    {
     $new_md = $new_md.','.$key;
     $i++;
    }
     $all_md = $old_md.$new_md;
     $result = mysql_query("UPDATE $projectid SET metadata = '$all_md' WHERE image = '$imagename'");  
    }

   //change page status and keep going
    $result = mysql_query("UPDATE $projectid SET state = 'save_md_second' WHERE image = '$imagename'");
    metarefresh(0,"md_phase2.php?projectid=$projectid","Image Metadata Collection","");

}


echo "<html><head><title>Image Frame</title></head><body bgcolor=#e0e8dd>";

//Start the outside table
echo "<table cols =\"2\" border = \"1\">";

//Display image
  if ($userP['i_layout']==1)
    {$iWidth=$userP['v_zoom'];}
  else {$iWidth=$userP['h_zoom'];}
    $iWidth=round((1000*$iWidth)/100);


echo "<td><img name=\"scanimage\" id=\"scanimage\" title=\"\" alt=\"\" src=\"$projects_url/$projectid/$imagename\" width = \"$iWidth\"></td>";


//start the metadata table
echo "<form method = 'post'><td valign = \"top\"><table cols =\"2\" border = \"1\">";
echo "<td colspan =\"2\" align = \"center\"><b>This Image Contains:</b></td><tr>
      <td>Front Matter</td><td><input type='checkbox' name='frontmatter'></td><tr>
      <td>Back Matter</td><td><input type='checkbox' name='backmatter'></td></td><tr>
      <td>Chapter/Part/Book Heading</td><td><input type='checkbox' name='division'></td></td><tr>
      <td>Verse</td><td><input type='checkbox' name='verse'></td><tr>
      <td>Poetry</td><td><input type='checkbox' name='poetry'></td><tr>
      <td>Letter</td><td><input type='checkbox' name='letter'></td><tr>
      <td>Table of Contents</td><td><input type='checkbox' name='toc'></td><tr>
      <td>Footnote</td><td><input type='checkbox' name='footnote'></td><tr>
      <td>Sidenote</td><td><input type='checkbox' name='sidenote'></td><tr>
      <td>Epigraph</td><td><input type='checkbox' name='epigraph'></td><tr>
      <td>Table</td><td><input type='checkbox' name='table'></td><tr>
      <td>List</td><td><input type='checkbox' name='list'></td><tr>
      <td>Math Notation</td><td><input type='checkbox' name='math'></td><tr>
      <td>Illustration or Drawing</td><td><input type='checkbox' name='drawing'></td><tr>
      <td></td><td></td><tr>
      <td></td><td></td><tr>
      <td></td><td></td><tr>
      <td colspan =\"2\" align = \"center\"><INPUT TYPE=SUBMIT VALUE=\"Save and Quit\" NAME = \"done\"></td><tr>
      <td colspan =\"2\" align = \"center\"><INPUT TYPE=SUBMIT VALUE=\"Quit Without Saving\" NAME = \"quit\"></td><tr>
      <td colspan =\"2\" align = \"center\"><INPUT TYPE=SUBMIT VALUE=\"Save and Do Another\"NAME = \"continue\"></td><tr>";
echo "</form></table>";
echo "</table></body></html>";

?>
