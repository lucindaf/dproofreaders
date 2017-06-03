<?php
$relPath='./../../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'metarefresh.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'misc.inc'); // attr_safe(), html_safe()
include_once($relPath.'user_is.inc');

require_login();

if ( !user_is_a_sitemanager() )
{
    die("You are not allowed to run this script.");
}

$theme_args['css_data'] = "
table.listing { border-collapse: collapse; width: 80%; margin: auto; }
table.listing td { border: 1px solid #999; padding: 5px; }
table.listing tr.e { background-color: #eee; }
table.listing tr.o { background-color: #ddd; }
table.listing td.enabled { background-color: #9f9; text-align: center; }
table.listing td.disabled { background-color: #ddd; text-align: center; }
table.listing td.center { text-align: center; }
table.listing tr.month > * { border: none; border-bottom: solid 2px black; }
table.listing td.right,th.right { text-align: right; font-weight: normal; border: 1px solid #999; }
table.listing h2 { margin: 1em auto auto auto; text-align: left; }
form { padding-top: 10px; margin: 0; }

table.source { border-collapse: collapse; width: 80%; margin: auto; margin-bottom: 1em; }
table.source td { border: 1px solid black; padding: 5px; background-color: #eeeeee; }
table.source td.pa { width: 30%; font-weight: bold; }
h2.source { margin: 1em auto 1em auto; text-align: center; }";

$page_url = "$code_url/tools/site_admin/manage_special_days.php";

$action = get_enumerated_param($_REQUEST, 'action', 'show_specials',
              array('show_specials','add_special','update_oneshot')
          );

// Action 'update_oneshot' is used as a target for form submit buttons. The
// desired action is based on the submit button's name. Here we do the action
// and set $action to 'show_specials' to display the list all in one page load.
if ($action == 'update_oneshot')
{
    global $page_url;
    if (isset($_POST['edit']))
    {
        $action = 'edit_source';
    }

    elseif (isset($_POST['save_edits']))
    {
        // This handles both edits to existing special days, and the creation of new ones

        $errmsgs = '';

        $spec_code = trim($_POST['spec_code']);

        $source = new SpecialDay($spec_code);

        if (!isset($_POST['editing']) && !$source->new_source )
            $errmsgs .= _('A Special Day with this ID already exists. Please choose a different ID for this event.') . "<br>";

        if (strlen($spec_code) < 1) 
            $errmsgs .= _("A value for Special Day ID is required. Please enter one.") . "<br>";

        // validate the numeric fields
        $numeric_fields = array(
            "open_month"  => _("Open Month"),
            "open_day"    => _("Open Day"),
            "close_month" => _("Close Month"),
            "close_day"   => _("Close Day"),
        );
        foreach($numeric_fields as $field => $string)
        {
            if($_POST[$field] == '' || !ctype_digit($_POST[$field]))
                $errmsgs .= sprintf(_("Field %s does not contain a valid number."),
                                $string) . "<br>";
        }

        // validate the URLs
        if ($_POST['info_url'] && !startswith($_POST['info_url'], "http"))
            $errmsgs .= _("Info URL is not a valid URL -- ensure it starts with http:// or https://.") . "<br>";

        if ($_POST['image_url'] && !startswith($_POST['image_url'], "http"))
            $errmsgs .= _("Image URL is not a valid URL -- ensure it starts with http:// or https://.") . "<br>";

        $source->save_from_post();

        // Redisplay at the entry just added or edited
        metarefresh(0,"$page_url#" . urlencode($spec_code));
    }
}

if ($action == 'show_specials')
{
    output_header(_('Manage Special Days'), NO_STATSBAR, $theme_args);
    $table_summary = _("Special Days Listing");

    show_sd_toolbar($action);

    $result = mysql_query("SELECT spec_code
        FROM special_days
        ORDER BY open_month, open_day");

    echo "<br>\n\n";
    echo "<table class='listing' summary='" . attr_safe($table_summary) . "'>\n";
    $count=0;
    $current_month=-1;
    while ( list($source_name) = mysql_fetch_row($result) )
    {
        $count++;
        $source = new SpecialDay($source_name);
        $current_month=$source->show_listing_row($count,$current_month);
    }
    echo "</table>";
    echo "<br>";
}

elseif ($action == 'edit_source')
{
    $source = new SpecialDay($_POST['source']);
    $headertext = sprintf(_("Editing Special Day: %s"),$source->display_name);
    output_header($headertext, NO_STATSBAR, $theme_args);
    show_sd_toolbar($action);
    echo "<h1 class='source'>" . $headertext . "</h1>\n";
    $source->show_edit_form();
}

elseif ($action == 'add_special')
{
    $headertext=_('Add a new Special Day');
    output_header($headertext, NO_STATSBAR, $theme_args);
    show_sd_toolbar($action);
    echo "<h2 class='source'>" . $headertext . "</h2>\n";
    $blank = new SpecialDay(null);
    $blank->show_edit_form();
}

// ----------------------------------------------------------------------------

class SpecialDay
{

    function SpecialDay($spec_code = null)
    {
        $this->new_source = true;
        $this->enable = 0;

        if( !is_null($spec_code) )
        {
            $result = mysql_query(sprintf("
                SELECT *
                FROM special_days
                WHERE spec_code = '%s'
                ", mysql_real_escape_string($spec_code)));
            $source_fields = mysql_fetch_assoc($result);

            if($source_fields)
            {
                foreach ($source_fields as $field => $value)
                    $this->$field = $value;

                $this->new_source = false;
            }
        }
    }

    function show_listing_row($count, $current_month)
    {
        global $page_url;
        $sid = html_safe($this->spec_code);
        $usid = urlencode($sid);

        if($count%2 == 1)
            $row_class = "o";
        else
            $row_class = "e";

        // Calculate how many rows this listing will have so we can span them
        // for some columns
        $listing_rows = 4;
        if($this->date_changes)
            $listing_rows++;

        // Output a new month header when listing month differs from previous one
        if (( $current_month < 0 ) && ($current_month != $this->open_month ))
        {
            echo "<tr class='month'><td colspan='8'><h2>";
            echo _("Undated Entries") ."</h2></td></tr>";
            output_table_headers();
        }
        else if ( $this->open_month != $current_month )
        {
            echo "<tr class='month'><td colspan='8'><h2>";
            echo strftime("%B", mktime(0, 0, 0, $this->open_month, 10));
            echo "</h2></td></tr>";
            output_table_headers();
        }

        echo "\n\n<tr class='$row_class'>";
        echo "<td rowspan='$listing_rows' valign='middle' align='center'>";
        echo $sid . "\n";
        echo "<form method='post' action='$page_url#$usid '>\n";
        echo "  <input type='hidden' name='action' value='update_oneshot'>\n";
        echo "  <input type='hidden' name='source' value='$sid'>\n";
                $this->show_buttons();
        echo "</form>\n";
        echo "</td>\n";
        echo "<td style='background-color: #". $this->color . ";'>";
        echo "<a name='$usid'></a>" . html_safe($this->display_name) . "</td>";
        echo "<td>" . $this->color . "</td>\n";
        echo $this->_get_status_cell($this->enable,' pb') . "\n";
        echo "<td class='right'>" . $this->open_month . "</td>";
        echo "<td class='right'>" . $this->open_day . "</td>";
        echo "<td class='right'>" . $this->close_month . "</td>";
        echo "<td class='right'>" . $this->close_day . "</td>";
        echo "</tr>\n";

        echo "<tr class='$row_class'>";
        echo "<th class='right'>" . _("Info URL") . ":</th><td colspan='6'>" . make_link($this->info_url, $this->info_url) . "</td>";
        echo "</tr>";
        echo "<tr class='$row_class'>";
        echo "<th class='right'>" . _("Image URL") . ":</th><td colspan='6'>" . make_link($this->image_url, $this->image_url) . "</td>";
        echo "</tr>\n";

        if($this->date_changes)
        {
            echo "<tr class='$row_class'>";
            echo "<th class='right'>" . _("Date Changes") . ":</th><td colspan='6'>" . html_safe($this->date_changes) . "</td>";
            echo "</tr>\n";
        }

        echo "<tr class='$row_class'>";
        echo "<th class='right'>" . _("Comments") . ":</th><td colspan='6'>" . html_safe($this->comment) . "</td>";
        echo "</tr>\n";

        return($this->open_month);
    }

    function show_buttons()
    {
        echo "<input type='submit' name='edit' value='".attr_safe(_('Edit'))."' />\n";
    }

    function show_edit_form()
    {
        global $page_url;
        echo "<table class='source'><form method='post' action='$page_url'>";
        echo "<input type='hidden' name='action' value='update_oneshot'>\n";

        if($this->new_source)
        {
            $this->_show_edit_row('spec_code',_('Special Day ID'),false,20);
        }
        else
        {
            echo "<input type='hidden' name='editing' value='true' />" .
                "<input type='hidden' name='spec_code' value='" . attr_safe($this->spec_code) ."' />";
            $this->_show_summary_row(_('Special Day ID'),$this->spec_code);
        }
        $this->_show_edit_row('display_name',_('Display Name'),false,80);
        echo "  <tr><td class='pa'>Enable</th><td><input type='checkbox' name='enable'";
        if ( $this->enable )
            echo " value='1' checked";
        echo "></td></tr>\n";
        $this->_show_edit_row('comment',_('Comment'),true);
        $this->_show_edit_row('color',_('Color'),false,8);
        $this->_show_edit_row('open_month',_('Open Month'),false,2);
        $this->_show_edit_row('open_day',_('Open Day'),false,2);
        $this->_show_edit_row('close_month',_('Close Month'),false,2);
        $this->_show_edit_row('close_day',_('Close Day'),false,2);
        $this->_show_edit_row('date_changes',_('Date Changes'),false);
        $this->_show_edit_row('info_url',_('Info URL'),false);
        $this->_show_edit_row('image_url',_('Image URL'),false);

        echo "<tr><td colspan='2' style='text-align:center;'>
            <input type='submit' name='save_edits' value='".attr_safe(_('Save'))."' />
            </td> </tr> </form> </table>\n\n";
    }

    function _show_edit_row($field, $label, $textarea = false, $maxlength = null)
    {

        $value = $this->new_source
            ? (empty($_POST[$field]) ? '' : $_POST[$field])
            : $this->$field;

        $value = html_safe($value);

        if ($textarea)
        {
            $editing = "<textarea cols='60' rows='5' name='$field'>$value</textarea>";
        }
        else
        {
            $maxlength_attr = is_null($maxlength) ? '' : "maxlength='$maxlength'";
            $editing = "<input type='text' name='$field' size='60' value='$value' $maxlength_attr />";
        }
        echo "  <tr>" .
            "<td class='pa'>$label</td>" .
            "<td class='pb'>$editing</td>" .
            "</tr>\n";
    }

    function save_from_post()
    {
        global $errmsgs,$theme_args;
        $std_fields = array('display_name','enable','comment',
                    'color','open_day','open_month','close_day',
                    'close_month','date_changes');
        $std_fields_sql = '';
        foreach ($std_fields as $field)
        {
            switch ($field)
            {
                case 'enable':
                {
                    if (!isset($_POST[$field]))
                        $this->$field = 0;
                    else
                        $this->$field = 1;
                    break;
                }
                default:
                {
                    $this->$field = $_POST[$field];
                }
            }
            $std_fields_sql .= sprintf("%s = '%s',\n", $field, mysql_real_escape_string($this->$field));
        }

        if ($this->new_source)
            $this->spec_code = $_POST['spec_code'];

        // Set URLs separately. If the URL has no scheme, prepend http:// (but only if not empty)
        $this->info_url  = $_POST['info_url'];
        if( $this->info_url  != '')
            $this->info_url  = strpos($_POST['info_url'],'://') ? $_POST['info_url'] : 'http://'.$_POST['info_url'];

        $this->image_url = $_POST['image_url'];
        if( $this->image_url != '')
            $this->image_url = strpos($_POST['image_url'],'://') ? $_POST['image_url'] : 'http://'.$_POST['image_url'];

        if ($errmsgs)
        {
            output_header('', NO_STATSBAR, $theme_args);
            echo "<p style='font-weight: bold; color: red;'>" . $errmsgs . "</p>";
            $this->show_edit_form();
            die;
        }

        mysql_query(sprintf("
            REPLACE INTO special_days
            SET
                spec_code = '%s',
                $std_fields_sql
                info_url  = '%s',
                image_url = '%s'
            ", mysql_real_escape_string($this->spec_code),
            mysql_real_escape_string($this->info_url),
            mysql_real_escape_string($this->image_url)))
        or die(_("Couldn't add/edit special day:") . " " . mysql_error());
    }

    function _set_field($field,$value)
    {
        mysql_query(sprintf("
            UPDATE special_days
            SET $field = '%s'
            WHERE spec_code = '%s'
            ", mysql_real_escape_string($value),
            mysql_real_escape_string($this->spec_code)));
        $this->$field = $value;
    }

    function _show_summary_row($label,$value,$htmlspecialchars = true)
    {
        echo "  <tr>" .
            "<td class='pa'>$label</td>" .
            "<td class='pb'>" . ($htmlspecialchars ? html_safe($value) : $value ) . "</td>" .
            "</tr>\n";
    }

    function _get_status_cell($status,$class = '')
    {
        switch ($status)
        {
          case(1):
              $middle = _('Enabled');
              $open = "<td class='enabled{$class}'>";
              break;
          case(0):
              $middle = _('Disabled');
              $open = "<td class='disabled{$class}'>";
              break;
          default:
              $middle = _('Invalid');
              $open = "<td class='disabled{$class}'>";
              break;
        }
        return $open . $middle . '</td>';
    }

}

// ----------------------------------------------------------------------------

function make_link($url,$label)
{
    $start = substr($url,0,3);
    $label = html_safe($label);
    if ($url == '')
        return '';
    if ($start == 'htt')
    {
        return "<a href='$url'>$label</a>";
    }
    else
    {
        return "<a href='http://$url'>$label</a>";
    }
}

function show_sd_toolbar($action)
{
    $pages = array(
        'add_special' => _('Add New Special Day'),
        'show_specials' => _('List All Special Days')
    );

    $toolbar_items = array();
    foreach ($pages as $new_action => $label)
    {
        if($action == $new_action)
            $item = "<b>$label</b>";
        else
            $item = "<a href='?action=$new_action'>$label</a>";

        $toolbar_items[] = $item;
    }

    echo "<p style='text-align: center; margin: 5px 0 5px 0;'>" . implode(" | ", $toolbar_items) . "</p>";
}

function output_table_headers()
{
    echo "<tr><th>&nbsp;</th></tr>";
    echo "<tr>";
    echo "<th>" . _("Special Day Code") . "</th>";
    echo "<th>" . _("Display Name") . "</th>";
    echo "<th>" . _("Color") . "</th>";
    echo "<th>" . _("Enable") . "</th>";
    echo "<th>" . _("Open Month") . "</th>";
    echo "<th>" . _("Open Day") . "</th>";
    echo "<th>" . _("Close Month") . "</th>";
    echo "<th>" . _("Close Day") . "</th>";
    echo "</tr>";
}

// vim: sw=4 ts=4 expandtab
