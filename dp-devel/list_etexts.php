<?php
$relPath="./pinc/";
include_once($relPath.'base.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'project_states.inc');
include_once($relPath.'list_projects.inc');
include_once($relPath.'misc.inc'); // undo_all_magic_quotes()

undo_all_magic_quotes();

$x        = get_enumerated_param($_GET, 'x', 'g', array('g', 's', 'b'));
$sort     = get_integer_param($_GET, 'sort',      0, 0, 5);
$per_page = get_integer_param($_GET, 'per_page', 20, 1, NULL);
$offset   = get_integer_param($_GET, 'offset',    0, 0, NULL);

$boilerplate = _("These e-texts are the product of hundreds of hours of labor donated by all of our volunteers. The list is sorted with the most recently submitted e-texts at the top. You can sort them based upon your own preferences by clicking below. Enjoy!!");

if($x == "g") {
    $type = "Gold";
    $title = _("Completed Gold E-Texts");
    $state = SQL_CONDITION_GOLD;
    $info = _("Below is the list of Gold e-texts that have been produced on this site. Gold e-texts are books that have passed through all phases of proofreading, formatting, and post-processing. They have been submitted to Project Gutenberg and are now available for your enjoyment and download.");
} elseif ($x == "s") {
    $type = "Silver";
    $title = _("In Progress Silver E-Texts");
    $state = SQL_CONDITION_SILVER;
    $info = _("Below is the list of Silver e-texts that have almost completed processing on our site. Silver e-texts are books that have passed through all phases of proofreading and formatting and are now in the post-processing phase. Post-processing is the final assembly stage in which one volunteer performs a series of checks for consistency and correctness before the e-book is submitted to Project Gutenberg for your enjoyment and download.");
} elseif ($x == "b") {
    $type = "Bronze";
    $title = _("Now Proofreading Bronze E-Texts");
    $state = SQL_CONDITION_BRONZE;
    $info = _("Below is the list of Bronze e-texts that are currently available for proofreading on this site. Bronze e-texts are what our newest volunteers see and what you can work on now by logging in. These e-texts are in the initial stages of proofreading where everyone has a chance to correct any OCR errors which may be found. After going through a number of other phases, the e-text then goes to an experienced volunteer for final assembly (post-processing), after which the e-text is submitted to Project Gutenberg for your enjoyment and download.");
} else {
    die("x parameter must be 'g', 's', or 'b'. ('$x')");
}

output_header($title);
?>

<center>
<font face="Verdana" size="6" color="<?php echo $type; ?>"><b><?php echo $title; ?></b></font><br>
<?php
$listsuffix = "&amp;sort=$sort&amp;per_page=$per_page";
if ($type == "Gold")
    echo _("Gold");
else
    echo "<a href='list_etexts.php?x=g$listsuffix'>" . _("Gold") . "</a>";

echo " | ";

if ($type == "Silver")
    echo _("Silver");
else
    echo "<a href='list_etexts.php?x=s$listsuffix'>" . _("Silver") . "</a>";

echo " | ";

if ($type == "Bronze")
    echo _("Bronze");
else
    echo "<a href='list_etexts.php?x=b$listsuffix'>" . _("Bronze") ."</a>";

?>
</center><br>

<?php
echo "<p>$info</p>";
echo "<p>$boilerplate</p>";
?>
<center>
<?php
$listurl = "list_etexts.php?x=$x&amp;per_page=$per_page&amp;offset=$offset";
echo sprintf( _("<i>Title:</i> <a href='%1\$s'>asc</a> or <a href='%2\$s'>desc</a> | "), "$listurl&amp;sort=0", "$listurl&amp;sort=1");
echo sprintf( _("<i>Author:</i> <a href='%1\$s'>asc</a> or <a href='%2\$s'>desc</a> | "), "$listurl&amp;sort=2", "$listurl&amp;sort=3");
echo sprintf( _("<i>Submitted Date:</i> <a href='%1\$s'>asc</a> or <a href='%2\$s'>desc</a>"), "$listurl&amp;sort=4", "$listurl&amp;sort=5");
?>
<br>
</center>
<hr width="75%" align="center">

<?php
$sortlist = array(
    "ORDER BY nameofwork asc",
    "ORDER BY nameofwork desc",
    "ORDER BY authorsname asc",
    "ORDER BY authorsname desc",
    "ORDER BY modifieddate asc",
    "ORDER BY modifieddate desc"
);

list_projects($state, $sortlist[$sort], "list_etexts.php?x=$x&amp;sort=$sort&amp;", $per_page, $offset);

// vim: sw=4 ts=4 expandtab
