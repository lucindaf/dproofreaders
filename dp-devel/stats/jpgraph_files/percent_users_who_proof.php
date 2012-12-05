<?php
$relPath="./../../pinc/";
include_once($relPath.'base.inc');
include_once($relPath.'page_tally.inc');
include_once('common.inc');

// Initialize the graph before anything else.
// This makes use of the jpgraph cache if enabled.
// Last argument to init_simple_bar_graph is the cache timeout in minutes.
$graph = init_simple_bar_graph(640, 400, 900);


///////////////////////////////////////////////////
// For each month in which someone joined,
// get the number who joined,
// and the number of those who have proofread at least one page.
//
$result = mysql_query("
    SELECT
        FROM_UNIXTIME(date_created, '%Y-%m')
          AS month,
        COUNT(*)
          AS num_who_joined,
        SUM($user_ELR_page_tally_column > 0)
          AS num_who_proofed
    FROM users $joined_with_user_ELR_page_tallies
    GROUP BY month
    ORDER BY month
");

// If there was a month when nobody joined,
// then the results will not include a row for that month.
// This may lead to a misleading graph,
// depending on its style.

while ( $row = mysql_fetch_object($result) )
{
        $datax[]  = $row->month;
        $data1y[] = 100 *  $row->num_who_proofed / $row->num_who_joined;
}

$x_text_tick_interval = calculate_text_tick_interval( 'monthly', count($datax) );

draw_simple_bar_graph(
    $graph,
    $datax,
    $data1y,
    $x_text_tick_interval,
    'Percentage of New Users Who Went on to Proofread By Month',
    '% of newly Joined Users who Proofread'
);

// vim: sw=4 ts=4 expandtab
