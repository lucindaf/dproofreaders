<?php
$relPath="./../../pinc/";
include_once($relPath.'Stopwatch.inc');
include_once($relPath.'site_vars.php');
include_once($relPath.'dp_main.inc');
include_once($relPath.'project_states.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'Project.inc');
include_once('./post_files.inc');
include_once($relPath.'wordcheck_engine.inc');
include_once('./word_freq_table.inc');

$watch = new Stopwatch;
$watch->start();

set_time_limit(0); // no time limit

$projectid = $_GET["projectid"];

// if format is 'text', all words and frequencies will be printed
// if format is not 'text', an HTML page is displayed
$format = @$_GET["format"];

// anything that appears in the list less than this number
// won't show up in the list
$minFreq = array_get($_GET, 'minFreq', 5);

$t_before = $watch->read();

// get the latest project text of all pages up to last possible round
$last_possible_round = get_Round_for_round_number(MAX_NUM_PAGE_EDITING_ROUNDS);
$pages_res = page_info_query($projectid,$last_possible_round->id,'LE');
$all_pages_text = join_page_texts($pages_res);

// now run it through the spell-checker
list($bad_words_w_freq,$languages,$messages) =
    get_bad_words_for_text($all_pages_text,$projectid,'all','',array(),'FREQS');

$t_after = $watch->read();
$t_to_generate_data = $t_after - $t_before;

// sort the list by frequency, then by word
array_multisort(array_values($bad_words_w_freq), SORT_DESC, array_keys($bad_words_w_freq), SORT_ASC, $bad_words_w_freq);


// if the user wants the list in text-only mode
if($format == "text") {
    # The following is a pure hack for evil IE not accepting filenames
    $filename="${projectid}_WordCheck_flag_words.txt";
    header("Content-type: text/plain");
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // freq side
    foreach( $bad_words_w_freq as $word => $freq) {
        echo "$word - $freq\r\n";
    }
    exit;
}

?>
<html>
<head>
<title>Candidate words from project</title>
</head>
<body>
<h1>Candidate words from project</h1>
<p>The list below contains words from this project that WordCheck would flag for the proofer. The list was generated by accessing the most recent text of each page and running it through the WordCheck engine. The WordCheck engine uses the current dictionaries/wordlists at the external, site, and project levels.</p>
<p>If you find words in this list that should not be flagged in WordCheck, add them to the project's Good Words List by copying them into the Good Words box when editing the project. (Take care not to overwrite any words that are already in the box.) You can run this tool again after saving the changes made to the Edit Project page (which include the Good Words List) and see that the words added to the Good Words List are no longer Flagged. See also the <a href="<?=$code_url;?>/faq/wordcheck-faq.php">WordCheck FAQ</a> for more information on the new WordCheck system.</p>

<p>You can <a href="show_current_flagged_words.php?projectid=<?PHP echo $projectid; ?>&amp;format=text">download</a> a copy of the full word list with frequencies for offline analysis. When adding the final list to the input box on the Edit Project page, the frequencies can be left in and the system will remove them.</p>

<p>Time to generate this data: <? echo sprintf('%.2f', $t_to_generate_data); ?> seconds</p>

<?
if ( count($messages) > 0 )
{
    echo "<p>\n";
    echo "The following warnings/errors were raised:<br>\n";
    foreach ( $messages as $message )
    {
        echo "$message<br>\n";
    }
    echo "</p>\n";
}

// how many instances (ie: frequency sections) are there?
$instances=1;
// what is the intial cutoff frequecny?
$initialFreq=5;
// what are the cutoff options?
$cutoffOptions = array(1,2,3,4,5,10,25,50);

echo_cutoff_script($cutoffOptions,$instances);
$cutoffString = get_cutoff_string($cutoffOptions);
?>
<p>Words that appear fewer than <b><span id="current_cutoff"><?=$initialFreq;?></span></b> times are not shown. Other cutoff options are available: <?=$cutoffString;?>.</p>

<?
printTableFrequencies($initialFreq,$cutoffOptions,$bad_words_w_freq,$instances--);

// vim: sw=4 ts=4 expandtab
?>
</body>
</html>
