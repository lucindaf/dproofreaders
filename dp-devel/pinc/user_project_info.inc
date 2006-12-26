<?PHP
include_once($relPath.'pg.inc'); // get_pg_catalog_url_for_etext
include_once($relPath.'maybe_mail.inc');

$subscribable_project_events = array(
    'posted' => _("Project posted to Project Gutenberg"),
);

function get_label_for_event( $event )
{
    global $subscribable_project_events;
    $label = @$subscribable_project_events[$event];
    if ( is_null($label) )
    {
        die( "Unknown event: '$event'" );
    }
    return $label;
}

// -----------------------------------------------------------------------------

function subscribe_user_to_project_event( $username, $projectid, $event )
{
    set_user_project_event_subscription( $username, $projectid, $event, 1 );
}

function unsubscribe_user_from_project_event( $username, $projectid, $event )
{
    set_user_project_event_subscription( $username, $projectid, $event, 0 );
}

function set_user_project_event_subscription( $username, $projectid, $event, $bit )
{
    assert( $bit === 0 || $bit === 1 );

    $event_label = get_label_for_event( $event );
    // Just to ensure that $event is valid; we don't use $event_label.

    // There may not yet be a row for this ($username,$projectid) pair.
    // Ensure that one exists.
    $res = mysql_query("
        INSERT IGNORE INTO user_project_info
        SET
            username    = '$username',
            projectid   = '$projectid'
    ") or die(mysql_error());
    // If there's already a row for this ($username,$projectid) pair,
    // the insertion doesn't happen; it would raise a duplicate-key error,
    // except that the IGNORE causes the error to be downgraded to a warning.

    // Now that we're sure the row exists, we can update it.
    $res = mysql_query("
        UPDATE user_project_info
        SET iste_$event = $bit
        WHERE username    = '$username'
            AND projectid = '$projectid'
    ") or die(mysql_error());

    /*
        In MySQL 4.1.0 or higher, we could replace those two queries with one:
            INSERT INTO user_project_info
            SET
                username    = '$username',
                projectid   = '$projectid',
                iste_$event = $bit
            ON DUPLICATE KEY UPDATE
                iste_$event = $bit
    */
}

function user_is_subscribed_to_project_event( $username, $projectid, $event )
{
    $event_label = get_label_for_event( $event );
    // Just to ensure that $event is valid; we don't use $event_label.

    $res = mysql_query("
        SELECT iste_$event
        FROM user_project_info
        WHERE username    = '$username'
            AND projectid = '$projectid'
    ") or die(mysql_error());
    if ( mysql_num_rows($res) == 0 )
    {
        // ($username,$projectid) pair does not appear in table.
        return 0;
    }
    else
    {
        return mysql_result($res,0);
    }
}

// -----------------------------------------------------------------------------

function notify_project_event_subscribers( $project, $event )
{
    global $site_name, $code_url, $auto_email_addr, $site_signoff;

    $event_label = get_label_for_event( $event );

    $projectid = $project->projectid;
    $title = $project->nameofwork;

    $msg_subject = "$event_label: \"$title\"";

    $msg_body =
        sprintf( _("This is an automated message from the %s site regarding the following project:"), $site_name )
        . "\n"
        . "    \"$title\"\n"
        . "    ($projectid)\n"
        . "    $code_url/project.php?id=$projectid\n"
        . "\n";

    switch ($event)
    {
        case 'posted':
            $pg_url = get_pg_catalog_url_for_etext( $project->postednum );
            $msg_body .=
                _("This project has been sent to Project Gutenberg and will soon be available for reading.")
                . " "
                . _("Usually, the files will be ready by the time you receive this mail; sometimes there may be a delay of a day or so.")
                . " "
                . sprintf(_("You can download the files via PG's online catalog at <%s>."), $pg_url )
                . "\n";
            break;

        default:
            assert(0);
    }

    $msg_body .= "\n" . $site_signoff;

    $res1 = mysql_query("
        SELECT username
        FROM user_project_info
        WHERE projectid = '$projectid'
            AND iste_$event = 1
    ") or die(mysql_error());
    $n_subscribers = mysql_num_rows($res1);
    while ( list($username) = mysql_fetch_row($res1) )
    {
        $res2 = mysql_query("
            SELECT user_email
            FROM phpbb_users
            WHERE username = '$username'
        ") or die(mysql_error());
        $email = mysql_result($res2, 0, "user_email");
        maybe_mail(
            $email,
            $msg_subject,
            sprintf( _("Hello %s,"), $username ) . "\n" . $msg_body,
            "From: $auto_email_addr\r\nReply-To: $auto_email_addr\r\n"
        );
    }

    if ( $event == 'posted' )
    {
        // Take the number of users subscribed to the event
        // and record that as the project's "interest level".
        $res4 = mysql_query("
            UPDATE projects
            SET int_level = '$n_subscribers'
            WHERE projectid = '$projectid'
        ") or die(mysql_error());
    }
}

// -----------------------------------------------------------------------------

function create_temporary_project_event_subscription_summary_table()
// Create a temp table named 'project_event_subscriptions_grouped_by_project'
// with two columns: 'projectid' and 'nouste_posted', where the latter gives the
// current number of users that are subscribed to the project's 'posted' event.
// ("nouste" = "number of users subscribed to event")
{
    $res = mysql_query("
        CREATE TEMPORARY TABLE project_event_subscriptions_grouped_by_project
        (
            PRIMARY KEY (projectid)
        )
        SELECT
            projectid,
            COUNT(*) AS nouste_posted
        FROM user_project_info
        WHERE iste_posted = 1
        GROUP BY projectid
    ") or die(mysql_error());
}

// vim: sw=4 ts=4 expandtab
?>