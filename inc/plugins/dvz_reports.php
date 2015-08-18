<?php
// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("modcp_nav", "activate_nav");
$plugins->add_hook("modcp_start", "shoutboxActions");

function dvz_reports_info()
{
    return array(
        "name" => "DVZ Shoutbox moderation system",
        "description" => "Adds a full moderation system in your moderation cp for DVZ Shoutbox.",
        "website" => "http://blazor.nl",
        "author" => "Arne Van Daele",
        "authorsite" => "http://arnevandaele.com",
        "version" => "1.0",
        "guid" => "",
        "compatibility" => "*"
    );
}

/**
 * Create required templates and add a option to the navigation
 */
function dvz_reports_activate()
{

    global $db;
    $insert_array = array(
        'title' => 'dvz_reports_menu',
        'template' => $db->escape_string('<tr>
		<td class="tcat tcat_menu tcat_collapse{$collapsedimg[\'modcpusers\']}">
			<div><span class="smalltext"><strong>{$lang->shoutbox}</strong></span></div>
		</td>
	</tr>
	<tbody style="{$collapsed[\'modcpshouts_e\']}" id="modcpshouts_e">
		<tr><td class="trow1 smalltext"><a href="modcp.php?action=shoutbox_reports" class="modcp_nav_item modcp_nav_reports">{$lang->reports}</a></td></tr>
		<tr><td class="trow1 smalltext"><a href="modcp.php?action=shoutbox_ban" class="modcp_nav_item modcp_nav_banning">{$lang->shoutbox_ban}</a></td></tr>
		<tr><td class="trow1 smalltext"><a href="modcp.php?action=shoutbox_banned" class="modcp_nav_item modcp_nav_modqueue">{$lang->shoutbox_banned}</a></td></tr>
		<tr><td class="trow1 smalltext"><a href="modcp.php?action=shoutbox_private" class="modcp_nav_item modcp_nav_modlogs">{$lang->shoutbox_private}</a></td></tr>
	</tbody>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports_banned',
        'template' => $db->escape_string('<tr>
	<td class="{$trow}" align="center">{$data[\'username\']}</td>
	<td class="{$trow}" align="center">{$data[\'reason\']}</td>
	<td class="{$trow}" align="center">{$data[\'unbantime\']}</td>
	<td class="{$trow}" align="center">{$data[\'banned_by\']}</td>
	<td class="{$trow}" align="center"><a href="modcp.php?action=shoutbox_unban&id={$data[\'id\']}&token={$token}">X</a></td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports_banned_list',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->report_center}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
	<td valign="top">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead" colspan="5"><strong>{$lang->shoutbox_banned}<div class="float_right"><form action="modcp.php?action=shoutbox_banned&search" method="post"><input class="textbox" name="querystring" placeholder="{$lang->username}.." /><input type="submit" class="button" value="Search"></form></div></strong></td>
		</tr>
		<tr>
			<td class="tcat" align="center" width="20%"><span class="smalltext"><strong>{$lang->username}</strong></span></td>
			<td class="tcat" align="center" width="60%"><span class="smalltext"><strong>{$lang->reason}</strong></span></td>
			<td class="tcat" align="center" width="70%"><span class="smalltext"><strong>{$lang->unban_date}</strong></span></td>
			<td class="tcat" align="center" wdith="90%"><span class="smalltext"><strong>{$lang->banned_by}</strong></span></td>
            <td class="tcat" align="center" wdith="90%"><span class="smalltext"><strong></strong></span></td>
		</tr>
		{$bannedList}
		</table>
	</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports_banning',
        'template' => $db->escape_string('<html>
<head>
    <title>{$mybb->settings[\'bbname\']} - {$lang->report_center}</title>
    {$headerinclude}

    <script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/dvz_reports.js"></script>

</head>
<body>
{$header}
<form action="modcp.php?action=shoutbox_ban" method="post">
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}"/>
    <input type="hidden" name="page" value="{$page}"/>
    <table width="100%" border="0" align="center">
        <tr>
            {$modcp_nav}
            <td valign="top">
                <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}"
                       class="tborder">
                    <tr>
                        <td class="thead" colspan="5"><strong>{$lang->report_banning}</strong></td>
                    </tr>
                    <tr>
                        <td class="tcat" align="center" width="15%"><span
                                class="smalltext"><strong>Username</strong></span></td>
                        <td class="tcat" align="center" width="10%"><span
                                class="smalltext"><strong>Reason</strong></span></td>
                        <td class="tcat" align="center" width="10%"><span
                                class="smalltext"><strong>Length</strong></span></td>
                    </tr>
                    <form action="{$mybb->settings[\'bburl\']}">
                        <tr>
                            <td align="center"><input name="username" class="textbox" type="text" id="ban_username"/></td>
                            <td align="center">
                                <select name="reason" id="ban_reason">
                                    {$reasons}
                                </select>
                                <input name="reason_input" placeholder="{$lang->reason}"" class="textbox reason_input" style="display: none;" type="text" id="input_reason"/>
                            </td>
                            <td align="center">
                                <select name="length" id="ban_length">
                                    <option value="1">{$lang->one_day}</option>
                                    <option value="2">{$lang->one_week}</option>
                                    <option value="3">{$lang->two_weeks}</option>
                                    <option value="4">{$lang->three_weeks}</option>
                                    <option value="5">{$lang->four_weeks}</option>
                                    <option value="6">{$lang->permanent}</option>
                                </select>
                            </td>
                        </tr>
                </table>
                <div align="center">
                    <input type="submit" value="{$lang->ban_user}" class="button">
                </div>
</form>
                <br/>
            </td>
        </tr>
    </table>
</form>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports_report',
        'template' => $db->escape_string('<tr>
	<td class="{$trow}" align="center">{$report[\'report_id\']}</td>
	<td class="{$trow}" align="left">{$report[\'report_reason\']}</td>
	<td class="{$trow}" align="center">{$report[\'author_username\']}</td>
    <td class="{$trow}" align="center"><a href="#" onclick=\'javascript:alert("{$report[\'shout_text\']}"); return false;\'>?</a></td>
	<td class="{$trow} smalltext" align="center">{$report[\'report_date\']}</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports_private',
        'template' => $db->escape_string('<tr>
	<td class="{$trow}" align="center">{$usernameSender}</td>
	<td class="{$trow}" align="center">{$usernameReceiver}</td>
  	<td class="{$trow}" align="center">{$data[\'date\']}</td>
	<td class="{$trow} smalltext" align="center">{$data[\'text\']}</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports_private_list',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->report_center}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
	<td valign="top">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead" colspan="5"><strong>{$lang->shoutbox_private}</strong></td>
		</tr>
		<tr>
			<td class="tcat" align="center" width="10%"><span class="smalltext"><strong>{$lang->from}</strong></span></td>
			<td class="tcat" align="center" width="10%"><span class="smalltext"><strong>{$lang->to}</strong></span></td>
			<td class="tcat" align="center" width="20%"><span class="smalltext"><strong>{$lang->date}</strong></span></td>
			<td class="tcat" align="center" width="55%"><span class="smalltext"><strong>{$lang->message}</strong></span></td>
		</tr>
		{$privateMessages}
		</table>
	</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'dvz_reports',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->report_center}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="modcp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="page" value="{$page}" />
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
	<td valign="top">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead" colspan="5"><strong>{$lang->report_center}</strong></td>
		</tr>
		<tr>
			<td class="tcat" align="center" width="10%"><span class="smalltext"><strong>#</strong></span></td>
			<td class="tcat" align="left" width="25%"><span class="smalltext"><strong>{$lang->report_type}</strong></span></td>
			<td class="tcat" align="center" width="15%"><span class="smalltext"><strong>Shout author</strong></span></td>
			<td class="tcat" align="center" width="10%"><span class="smalltext"><strong>Shout message</strong></span></td>
          	<td class="tcat" align="center" width="10%"><span class="smalltext"><strong>Shout date</strong></span></td>
		</tr>
		{$reportData}
		</table>
		{$reportspages}
		<br />
	</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );

    $db->insert_query("templates", $insert_array);

    $db->write_query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX. "dvz_reports_banned` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `reason` varchar(150) NOT NULL DEFAULT '',
  `banned_by` int(11) NOT NULL,
  `unbantime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;");

    $db->insert_query("templates", $insert_array);

    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("modcp_nav", "#" . preg_quote('{$modcp_nav_users}') . "#i", '{$modcp_nav_users}{$modcp_shoutbox}');
}

/**
 * Deactivate the plugin, remove custom templates and remove our template variable
 */
function dvz_reports_deactivate()
{
    global $db;
    $db->delete_query("templates", "title IN('dvz_reports','dvz_reports_menu','dvz_reports_report', 'dvz_reports_banning', 'dvz_reports_banned', 'dvz_reports_banned_list', 'dvz_reports_private_list', 'dvz_reports_private')");
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("modcp_nav", "#" . preg_quote('{$modcp_shoutbox}') . "#i", '', 0);
}

/**
 * Prepare navigation
 */
function activate_nav()
{
    global $mybb, $lang, $templates, $modcp_shoutbox;
    $lang->load('dvz_reports');
    eval("\$modcp_shoutbox = \"" . $templates->get("dvz_reports_menu") . "\";");
}

/**
 * Show reports
 */
function showReports()
{
    global $mybb, $db, $templates, $headerinclude, $header, $lang, $modcp_nav;

    $lang->load('dvz_reports');

    add_breadcrumb($lang->home, "modcp.php");
    add_breadcrumb($lang->shoutbox_reports, "modcp.php?action=shoutbox_reports");

    $reportsQuery = $db->query("SELECT mybb_dvz_reports.id as report_id, mybb_dvz_reports.uid as report_uid, mybb_dvz_reports.reason as report_reason, mybb_dvz_reports.date as report_date,
	mybb_dvz_shoutbox.id as shout_id, mybb_dvz_shoutbox.uid as author_uid, mybb_dvz_shoutbox.text as shout_text, mybb_dvz_shoutbox.date as shout_date, mybb_dvz_shoutbox.ip as author_ip,
	mybb_users.username as author_username
	FROM mybb_dvz_reports
	JOIN mybb_dvz_shoutbox ON mybb_dvz_reports.shid=mybb_dvz_shoutbox.id
	JOIN mybb_users ON mybb_users.uid=mybb_dvz_shoutbox.uid ORDER BY mybb_dvz_shoutbox.date DESC;");


    $reportData = '';

    while ($report = $reportsQuery->fetch_array()) {

        $report['shout_date'] = date('Y-M-D', $report['shout_date']);
        $report['report_date'] = date('d-M-Y H:i:s', $report['report_date']);
        $report['report_reason'] = htmlspecialchars_uni($report['report_reason']);

        eval("\$reportData .= \"" . $templates->get("dvz_reports_report") . "\";");
    }

    eval("\$reportedcontent = \"" . $templates->get("dvz_reports") . "\";");
    output_page($reportedcontent);
}

/**
 * Validate existance of username
 * @param $username
 * @return bool
 */
function usernameExists($username) {
    global $db;

    $username = $db->escape_string($username);

    $query = $db->simple_select('users', 'uid', "username = '" . $username . "'");
    if($query->num_rows === 1) {
        $data = $query->fetch_row();
        return $data[0];
    }

    return false;
}

/**
 * Validate if the given user is already banned
 * @param $uid
 * @return bool
 */
function isBanned($uid) {
    global $db;

    $uid = $db->escape_string($uid);
    $query = $db->simple_select('mybb_dvz_reports_banned', 'uid', "uid='". $uid."'");
    if($query->num_rows === 1) {
        return true;
    }
    return false;
}

/**
 * Calculate unban time based on input
 * @param $length
 * @return int
 */
function getUnban($length) {
    switch($length) {
        case '1':
            $date = strtotime('+1 day', time());
            break;
        case '2':
            $date = strtotime('+1 week', time());
            break;
        case '3':
            $date = strtotime('+2 weeks', time());
            break;
        case '4':
            $date = strtotime('+3 weeks', time());
            break;
        case '5':
            $date = strtotime('+4 weeks', time());
            break;
        case '6':
            $date = strtotime('+999 year', time());
            break;
        default:
            $date = strtotime('+999 year', time());
            break;
    }
    return $date;
}

/**
 * Ban user
 * @param $input
 */
function shoutboxBanUser($input) {

    global $lang, $db, $mybb, $cache;

    $lang->load('dvz_reports');

    //Validate XSRF token
    if(verify_post_check($input['my_post_key'])){
        //Validate if weve got a username
        if(!$input['username']) {
            redirect('modcp.php?action=shoutbox_ban', $lang->invalid_username);
        }

        //Validate existance
        if(!$uid = (int)usernameExists($input['username'])) {
            redirect('modcp.php?action=shoutbox_ban', $lang->invalid_username);
        }
        //User already banned
        if(isBanned($uid)) {
            redirect('modcp.php?action=shoutbox_ban', $lang->already_banned);
        }

        if($input['reason'] == 'different')
        {
        	if(!$input['reason_input'])
        	{
        		redirect('modcp.php?action=shoutbox_ban', $lang->no_reason);
        	} else {
        		$reason =  $input['reason_input'];
        	}
        } else {
        	$reason = $input['reason'];
        }
        
        $data = array(
            'uid'   =>      $db->escape_string($uid),
            'reason'    =>  $db->escape_string($reason),
            'unbantime' =>  getUnban($input['length']),
            'banned_by' =>  $db->escape_string($mybb->user['uid']),
        );

        //Insert new ban
        $db->insert_query('mybb_dvz_reports_banned', $data);
        //Log action
        $logdata = array(
            'uid'       =>  $uid,
            'username'  =>  $mybb->input['username'],
        );
        log_moderator_action($logdata, $lang->banned_user);
        //Redirect
        redirect('modcp.php?action=shoutbox_ban', $lang->ban_succesfull);
    }
}

/**
 * Show the ban user form, render template
 */
function showBanForm() {
    global $mybb, $db, $templates, $headerinclude, $header, $lang, $modcp_nav;

    $lang->load('dvz_reports');

    if($mybb->request_method === 'post') {
        shoutboxBanUser($mybb->input);
    }

    add_breadcrumb($lang->home, "modcp.php");
    add_breadcrumb($lang->shoutbox_banning, "modcp.php?action=shoutbox_ban");

    $reasonQuery = $db->simple_select('warningtypes', 'title');
    if($reasonQuery->num_rows > 0) {
        $reasons = '';
        while($reason = $reasonQuery->fetch_row()) {
            $reasonTitle = htmlspecialchars_uni($reason[0]);
            $reasons .= '<option value="' . $reasonTitle . '">' . $reasonTitle . '</option>';
        }
        $reasons .= '<option value="different">' . $lang->different_reason . '</option>';
    } else {
        $reasons = '<option value="' . $lang->default_reason . '">' . $lang->default_reason . '</option>';
        $reasons .= '<option value="different">' . $lang->different_reason . '</option>';
    }

    eval("\$banning_form = \"" . $templates->get("dvz_reports_banning") . "\";");
    output_page($banning_form);
}

/**
 * Show banned users
 */
function showBanned() {
    global $mybb, $db, $templates, $headerinclude, $header, $lang, $modcp_nav;

    $lang->load('dvz_reports');

    add_breadcrumb($lang->home, "modcp.php");
    add_breadcrumb($lang->shoutbox_banned, "modcp.php?action=shoutbox_ban");

    if(isset($mybb->input['search'])) {
        if(isset($mybb->input['querystring'])) {
            $string = trim($mybb->input['querystring']);
            if(!empty($string)) {
                $string = $db->escape_string($string);
                $query = $db->query("SELECT " .TABLE_PREFIX. "mybb_dvz_reports_banned.*, " .TABLE_PREFIX."users.username FROM " .TABLE_PREFIX. "mybb_dvz_reports_banned JOIN " .TABLE_PREFIX. "users ON " .TABLE_PREFIX. "mybb_dvz_reports_banned.uid=" .TABLE_PREFIX. "users.uid WHERE mybb_users.username LIKE '%". $string . "%';");
            } else {
                $query = $db->query("SELECT " .TABLE_PREFIX. "mybb_dvz_reports_banned.*, " .TABLE_PREFIX."users.username FROM " .TABLE_PREFIX. "mybb_dvz_reports_banned JOIN " .TABLE_PREFIX. "users ON " .TABLE_PREFIX. "mybb_dvz_reports_banned.uid=" .TABLE_PREFIX. "users.uid;");
            }
        } else {
            $query = $db->query("SELECT " .TABLE_PREFIX. "mybb_dvz_reports_banned.*, " .TABLE_PREFIX."users.username FROM " .TABLE_PREFIX. "mybb_dvz_reports_banned JOIN " .TABLE_PREFIX. "users ON " .TABLE_PREFIX. "mybb_dvz_reports_banned.uid=" .TABLE_PREFIX. "users.uid;");
        }
    } else {
        $query = $db->query("SELECT " .TABLE_PREFIX. "mybb_dvz_reports_banned.*, " .TABLE_PREFIX."users.username FROM " .TABLE_PREFIX. "mybb_dvz_reports_banned JOIN " .TABLE_PREFIX. "users ON " .TABLE_PREFIX. "mybb_dvz_reports_banned.uid=" .TABLE_PREFIX. "users.uid;");
    }

    if($query->num_rows >= 1) {
        $token = generate_post_check();
        $bannedList = '';
        while($data = $query->fetch_array()) {
        	// vreemd..
        	$data['banned_by'] = get_user($data['banned_by']);
        	$data['banned_by'] = htmlspecialchars_uni($data['banned_by']['username']);
        	$data['username'] =  htmlspecialchars_uni($data['username']);

            $data['reason'] = htmlspecialchars_uni($data['reason']);
            $data['unbantime'] = date('d-M-Y H:i:s', $data['unbantime']);
            eval("\$bannedList .= \"" . $templates->get("dvz_reports_banned") . "\";");
        }
    }else{
        // Show error: No users found
        eval("\$bannedList .= \"" . $templates->get("dvz_reports_banned") . "\";");
        $bannedList = "<div style=\"background: #D16464; color: #ffffff; border: 1px solid #B50909;padding: 5px;margin: 2px;\">" . $lang->nothing_found . "<i>". htmlspecialchars_uni($string) . "</i>'</div>";
    }

    eval("\$reports_banned_list = \"" . $templates->get("dvz_reports_banned_list") . "\";");
    output_page($reports_banned_list);
}

function getUsername($uid) {
    global $lang;
    $lang->load('dvz_reports');
    $data = get_user($uid);
    if($data) {
        return htmlspecialchars_uni($data['username']);
    }

    return $lang->unknown_user;
}

function getReceiver($message) {
    $parts = explode(' ', $message);
    return array('length'   =>  strlen($parts[1]), 'username'    => getUsername($parts[1]));
}

function showPrivate() {
    global $mybb, $db, $templates, $headerinclude, $header, $lang, $modcp_nav;

    $lang->load('dvz_reports');

    add_breadcrumb($lang->home, "modcp.php");
    add_breadcrumb($lang->shoutbox_banned, "modcp.php?action=shoutbox_private");

    $query = $db->query("SELECT * FROM mybb_dvz_shoutbox WHERE text LIKE '/pvt %' ORDER BY date DESC");
    if($query->num_rows > 0) {
        $privateMessages = '';
        while($data = $query->fetch_array()) {
            $data['uid'] = htmlspecialchars_uni($data['uid']);
            $data['date'] = date('d-M-Y H:i:s', $data['date']);
            $usernameSender = getUsername($data['uid']);
            $usernameReceiver = getReceiver($data['text']);
            $data['text'] = substr(htmlspecialchars_uni($data['text']), 6 + $usernameReceiver['length']);
            $usernameReceiver = $usernameReceiver['username'];
            eval("\$privateMessages .= \"" . $templates->get("dvz_reports_private") . "\";");
        }

        eval("\$privatelist = \"" . $templates->get("dvz_reports_private_list") . "\";");
        output_page($privatelist);
    }
}

/**
 * Delete ban
 */
function shoutboxUnban() {
    global $mybb, $db, $lang;
    if(isset($mybb->input['id']) && isset($mybb->input['token'])) {
        $lang->load('dvz_reports');

        verify_post_check($mybb->input['token']);

        $id = $db->escape_string($mybb->input['id']);

        $data = $db->write_query("select mybb_mybb_dvz_reports_banned.uid, mybb_mybb_dvz_reports_banned.id, mybb_users.username
                from mybb_mybb_dvz_reports_banned
                JOIN mybb_users ON mybb_mybb_dvz_reports_banned.uid = mybb_users.uid
                WHERE mybb_mybb_dvz_reports_banned.id = '$id';");
        //Validate ban existance
        if($data->num_rows === 0) {
            redirect('modcp.php?action=shoutbox_banned');
            die;
        }
        $data = $data->fetch_assoc();

        //Delete ban and log action
        $db->delete_query('mybb_dvz_reports_banned', 'id=' . $id);
        //Log action
        $logdata = array(
            'uid'       =>  htmlspecialchars_uni($data['uid']),
            'username'  =>  htmlspecialchars_uni($data['username']),
        );
        log_moderator_action($logdata, $lang->unban_user);
        redirect('modcp.php?action=shoutbox_banned');
        die;
    }

    //Redirect
    redirect('modcp.php?action=shoutbox_banned');
    die;
}

/**
 * Possible actions
 */
function shoutboxActions()
{

    global $mybb;

    $action = $mybb->input['action'];

    switch ($action) {
        case 'shoutbox_reports':
            showReports();
            break;
        case 'shoutbox_banned':
            showBanned();
            break;
        case 'shoutbox_ban':
            showBanForm();
            break;
        case 'shoutbox_private':
            showPrivate();
            break;
        case 'shoutbox_unban':
            shoutboxUnban();
            break;
        default:
            return;
            break;
    }
    return;

}