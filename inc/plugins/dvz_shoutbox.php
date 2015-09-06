<?php
/* by Tomasz 'Devilshakerz' Mlynski [devilshakerz.com]; Copyright (C) 2014
 released under Creative Commons BY-NC-SA 3.0 license: http://creativecommons.org/licenses/by-nc-sa/3.0/ */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />
         Please make sure IN_MYBB is defined.");
}
$plugins->add_hook('global_end', 'dvz_shoutbox_global_end'); // load language file, catch archive page
$plugins->add_hook('xmlhttp', 'dvz_shoutbox_xmlhttp');      // xmlhttp.php listening
$plugins->add_hook('index_end', 'dvz_shoutbox');           // load Shoutbox window to {$dvz_shoutbox} variable

// MyBB handling
function dvz_shoutbox_info () {
    return array(
        'name'           => 'DVZ Shoutbox CUSTOMISED',
        'description'    => 'Lightweight AJAX chat, customised by Arne Van Daele, now with private messages, report system and more.',
        'website'        => 'http://devilshakerz.com/',
        'author'         => 'Originally by Tomasz \'Devilshakerz\' Mlynski',
        'authorsite'     => 'http://devilshakerz.com/',
        'version'        => '1.0',
        'guid'           => 'a54d9c66ae174f090b6345ce19e7a063',
        'compatibility'  => '16*,18*',
        'codename'       => 'DVZPRIV',
    );
}
function dvz_shoutbox_install () {
    global $db;

    // table
    $db->write_query("
        CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."dvz_shoutbox` (
            `id` int(11) NOT NULL auto_increment,
            `uid` int(11) NOT NULL,
            `text` text NOT NULL,
            `date` int(11) NOT NULL,
            `ip` varchar(15) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM ".$db->build_create_table_collation()."
    ");

    // example shout
    $db->write_query("INSERT INTO ".TABLE_PREFIX."dvz_shoutbox VALUES (NULL, 1, 'Welcome to our shoutbox!', ".time().", '127.0.0.1')");

    // settings
    $db->write_query("INSERT INTO `".TABLE_PREFIX."settinggroups` VALUES (NULL, 'dvz_shoutbox', 'DVZ Shoutbox', 'Settings for DVZ Shoutbox.', 1, 0)");
    $sgID = $db->insert_id();

    $db->write_query("INSERT INTO `".TABLE_PREFIX."settings` VALUES
        (NULL, 'dvz_sb_num', 'Shouts to display', 'Number of shouts displayed in the Shoutbox window.', 'text', '20', 1, $sgID, 0),
        (NULL, 'dvz_sb_num_archive', 'Shouts to display on archive', 'Number of shouts to display per page on archive view.', 'text', '15', 2, $sgID, 0),
        (NULL, 'dvz_sb_reversed', 'Reversed order', 'Reverse the order of displaying shouts in the Shoutbox window so that new ones appear on the bottom. You might also want to move the <b>{\$panel}</b> variable below window in the <i>dvz_shoutbox</i> template.', 'yesno', '0', 3, $sgID, 0),
        (NULL, 'dvz_sb_height', 'Shoutbox height', 'Height of the Shoutbox window in pixels.', 'text', '160', 4, $sgID, 0),
        (NULL, 'dvz_sb_dateformat', 'Date format', 'Format of the date displayed. This format uses the PHP <a href=\"http://php.net/manual/en/function.date.php\">date()</a> function.', 'text', 'd M H:i', 5, $sgID, 0),

        (NULL, 'dvz_sb_mycode', 'Parse MyCode', '', 'yesno', '1', 6, $sgID, 0),
        (NULL, 'dvz_sb_smilies', 'Parse smilies', '', 'yesno', '1', 7, $sgID, 0),
        (NULL, 'dvz_sb_interval', 'Refresh interval', 'Number of seconds before new posted shouts are displayed in the window (lower values provide better synchronization but cause higher server load). Set 0 to disable the auto-refreshing feature.', 'text', '5', 8, $sgID, 0),
        (NULL, 'dvz_sb_away', 'Away mode', 'Number of seconds after last user action (e.g. click) after which shoutbox will be minimized to prevent unnecessary usage of server resources. Set 0 to disable this feature.', 'text', '600', 9, $sgID, 0),
        (NULL, 'dvz_sb_antiflood', 'Anti-flood interval', 'Minimum number of seconds before user can post next shout (this does not apply to Shoutbox moderators).', 'text', '5', 10, $sgID, 0),
        (NULL, 'dvz_sb_lazyload', 'Lazy load', 'Start loading data only when the Shoutbox window is actually being displayed on the screen (the page is scrolled to the Shoutbox position).', 'select
off=Disabled
start=Check if on display to start
always=Always check if on display to refresh', 'off', 11, $sgID, 0),
        (NULL, 'dvz_sb_status', 'Shoutbox default status', 'Choose whether Shoutbox window should be expanded or collapsed by default.', 'onoff', '1', 12, $sgID, 0),

        (NULL, 'dvz_sb_minposts', 'Minimum posts required to shout', 'Set 0 to allow everyone.', 'text', '0', 13, $sgID, 0),

        (NULL, 'dvz_sb_groups_view', 'Group permissions: View', 'Comma-separated list of user groups that can view Shoutbox. Leave empty to let everyone view (including guests).', 'text', '', 14, $sgID, 0),
        (NULL, 'dvz_sb_groups_shout', 'Group permissions: Shout', 'Comma-separated list of user groups that can post shouts in Shoutbox. Leave empty to let everyone post (that does not include guests).', 'text', '', 15, $sgID, 0),
        (NULL, 'dvz_sb_groups_refresh', 'Group permissions: Auto-refresh', 'Comma-separated list of user groups that shoutbox will be refreshing for. Leave empty to let Shoutbox refresh for everyone.', 'text', '', 16, $sgID, 0),
        (NULL, 'dvz_sb_groups_mod', 'Group permissions: Moderate', 'Comma-separated list of users groups that can moderate the Shoutbox (edit and delete shouts).', 'text', '', 17, $sgID, 0),
        (NULL, 'dvz_sb_groups_mod_own', 'Group permissions: Moderate own shouts', 'Comma-separated list of users groups that can edit and delete own shouts.', 'text', '', 18, $sgID, 0),

        (NULL, 'dvz_sb_supermods', 'Super moderators are Shoutbox moderators', 'Automatically allow forum super moderators to moderate Shoutbox as well.', 'yesno', '1', 19, $sgID, 0),


        (NULL, 'dvz_sb_blocked_users', 'Banned users', 'Comma-separated list of user IDs that are banned from posting messages.', 'textarea', '', 20, $sgID, 0)
    ");

    $db->write_query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."dvz_reports` (
`id` int(11) NOT NULL,
  `shid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `reason` varchar(150) NOT NULL,
  `date` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");
    $db->write_query('ALTER TABLE `'.TABLE_PREFIX.'dvz_reports`
 ADD PRIMARY KEY (`id`);');
    $db->write_query('ALTER TABLE `'.TABLE_PREFIX.'dvz_reports`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

    rebuild_settings();

    // templates
    $template_panel = '
<div class="panel">
<form>
<input type="text" class="text" placeholder="{$lang->dvz_sb_default}" autocomplete="off" maxlength="120" />
<input type="submit" style="display:none" />
</form>
</div>';

    $template_shoutbox = '
<div id="shoutbox" class="front{$classes}">

<div class="thead">
{$lang->dvz_sb_shoutbox}
<span style="float:right;"><a href="{$mybb->settings[\'bburl\']}/index.php?action=shoutbox_archive">&laquo; {$lang->dvz_sb_archivelink}</a></span>
</div>

<div class="body">

{$panel}

<div class="window" style="height:{$mybb->settings[\'dvz_sb_height\']}px">
<div class="data"></div>
</div>

</div>

<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/dvz_shoutbox.js"></script>
{$javascript}

</div>';

    $template_archive = '<html>
<head>
<title>{$lang->dvz_sb_archive}</title>
{$headerinclude}
</head>
<body>
{$header}

<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/dvz_shoutbox.js"></script>
{$javascript}

{$multipage}

<br />

<div id="shoutbox">

{$modoptions}

<div class="thead">{$lang->dvz_sb_archive}</div>

<div class="data">
{$archive}
</div>
</div>

<br />

{$multipage}

{$footer}
</body>
</html>';

    $template_archive_modoptions = '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr><td class="thead" colspan="2"><strong>{$lang->dvz_sb_mod}</strong></td></tr>
<tr><td class="tcat">{$lang->dvz_sb_mod_banlist}</td><td class="tcat">{$lang->dvz_sb_mod_clear}</td></tr>
<tr>
<td class="trow1">
<form action="" method="post">
<input type="text" class="textbox" style="width:80%" name="banlist" value="{$blocked_users}"></textarea>
<input type="hidden" name="postkey" value="{$mybb->post_code}" />
<input type="submit" class="button" value="{$lang->dvz_sb_mod_banlist_button}" />
</form>
</td>
<td class="trow1">
<form action="" method="post">
<select name="days">
<option value="2">2 {$lang->days}</option>
<option value="7">7 {$lang->days}</option>
<option value="30">30 {$lang->days}</option>
<option value="90">90 {$lang->days}</option>
<option value="all">* {$lang->dvz_sb_mod_clear_all} *</option>
</select>
<input type="hidden" name="postkey" value="{$mybb->post_code}" />
<input type="submit" class="button" value="{$lang->dvz_sb_mod_clear_button}" />
</form>
</td>
</tr>
</table>
<br />';

    $db->write_query("INSERT INTO `".TABLE_PREFIX."templates` VALUES (NULL, 'dvz_shoutbox_panel', '".$db->escape_string($template_panel)."', '-1', '1', '', '".time()."')");
    $db->write_query("INSERT INTO `".TABLE_PREFIX."templates` VALUES (NULL, 'dvz_shoutbox', '".$db->escape_string($template_shoutbox)."', '-1', '1', '', '".time()."')");
    $db->write_query("INSERT INTO `".TABLE_PREFIX."templates` VALUES (NULL, 'dvz_shoutbox_archive', '".$db->escape_string($template_archive)."', '-1', '1', '', '".time()."')");
    $db->write_query("INSERT INTO `".TABLE_PREFIX."templates` VALUES (NULL, 'dvz_shoutbox_archive_modoptions', '".$db->escape_string($template_archive_modoptions)."', '-1', '1', '', '".time()."')");

}
function dvz_shoutbox_uninstall () {
    global $db;

    $groupID = $db->fetch_field(
        $db->simple_select('settinggroups', 'gid', "name='dvz_shoutbox'"),
        'gid'
    );

    // delete settings
    $db->delete_query('settinggroups', "name='dvz_shoutbox'");
    $db->delete_query('settings', 'gid='.$groupID);

    // delete templates
    $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title IN('dvz_shoutbox', 'dvz_shoutbox_panel', 'dvz_shoutbox_archive', 'dvz_shoutbox_archive_modoptions')");

    // delete data
    $db->query("DROP TABLE ".TABLE_PREFIX.'dvz_shoutbox');

    //delete reports
    $db->query("DROP TABLE ".TABLE_PREFIX. "dvz_reports");
}
function dvz_shoutbox_is_installed () {
		global $db;
		$query = $db->simple_select('settinggroups', '*', "name='dvz_shoutbox'");
        return $db->num_rows($query);
}
function dvz_shoutbox_activate () {
}
function dvz_shoutbox_deactivate () {
}

// hooks
function dvz_shoutbox_global_end () {
    global $mybb, $lang;

    $lang->load('dvz_shoutbox');

    if ($mybb->input['action'] == 'shoutbox_archive') {
        return dvz_shoutbox::show_archive();
    }
}
function dvz_shoutbox_xmlhttp () {
    global $mybb, $db, $lang, $charset;

    switch ($mybb->input['action']) {

        case 'dvz_sb_get_shouts':

            $permissions = (
                (dvz_shoutbox::access_view() && !isset($mybb->input['from']) ) ||
                dvz_shoutbox::access_refresh()
            );

            $handler = function() use ($mybb, $db) {
                $data = dvz_shoutbox::get_multiple("WHERE s.id > " . (int)$mybb->input['from'] . " ORDER BY s.id DESC LIMIT " . (int)$mybb->settings['dvz_sb_num']);

                $html = null; // JS-handled empty response
                $lastId = 0;

                while ($row = $db->fetch_array($data)) {
                    if ($lastId == 0) {
                        $lastId = $row['id'];
                    }
                    $shout = dvz_shoutbox::render_shout($row);
                    $html  = $mybb->settings['dvz_sb_reversed']
                        ? $shout . $html
                        : $html  . $shout
                    ;
                }

                if ($html != null) {
                    echo json_encode(array(
                        'html' => $html,
                        'last' => $lastId,
                    )); 
                }
            };

        break;
        case 'dvz_sb_shout':

            $permissions = (
                dvz_shoutbox::access_shout() &&
                verify_post_check($mybb->input['key'], true)
            );

            $handler = function() use ($mybb) {
                if (!dvz_shoutbox::antiflood_pass() && !dvz_shoutbox::access_mod()) die('A'); // JS-handled error (Anti-flood)

                dvz_shoutbox::shout(array(
                    'uid'  => $mybb->user['uid'],
                    'text' => $mybb->input['text'],
                    'ip'   => get_ip(),
                ));
            };

        break;
        case 'dvz_sb_get':

            $data = dvz_shoutbox::get($mybb->input['id']);

            $permissions = (
                (
                    dvz_shoutbox::access_mod() ||
                    (dvz_shoutbox::access_mod_own() && $data['uid'] == $mybb->user['uid'] && dvz_shoutbox::access_shout())
                ) &&
                verify_post_check($mybb->input['key'], true)
            );

            $handler = function() use ($data) {
                echo json_encode(array(
                    'text' => $data['text'],
                ));
            };

        break;
        case 'dvz_sb_update':

            $permissions = (
                dvz_shoutbox::can_mod($mybb->input['id']) &&
                verify_post_check($mybb->input['key'], true)
            );

            $handler = function() use ($mybb) {
                dvz_shoutbox::update($mybb->input['id'], $mybb->input['text']);
                echo dvz_shoutbox::parse($mybb->input['text'], dvz_shoutbox::get_username($mybb->input['id']));

            };

        break;
        case 'dvz_sb_delete':

            $permissions = (
                dvz_shoutbox::can_mod($mybb->input['id']) &&
                verify_post_check($mybb->input['key'], true)
            );

            $handler = function() use ($mybb) {
                dvz_shoutbox::delete($mybb->input['id']);
            };

        break;
        case 'dvz_sb_report':
            echo dvz_shoutbox::reportShout($mybb->input);
            break;

    }

    if (isset($permissions)) {

        if ($permissions == false) {
            echo 'P'; // JS-handled error (Permissions)
        } else {

            $lang->load("dvz_shoutbox");
            header('Content-type: text/plain; charset='.$charset);
            header('Cache-Control: no-store'); // Chrome request caching issue
            $handler();

        }
    }

}
function dvz_shoutbox () {
    return dvz_shoutbox::load_window();
}

class dvz_shoutbox {

    // internal cache
    static $userGroups = false;

    // immediate output
    static function load_window () {
        global $templates, $dvz_shoutbox, $lang, $mybb, $theme;

        // MyBB template
        $dvz_shoutbox = null;

        // dvz_shoutbox template
        $javascript   = null;
        $panel        = null;
        $classes      = null;

        if (dvz_shoutbox::access_view()) {

            if (dvz_shoutbox::is_user()) {

                // message: blocked
                if ($reason = dvz_shoutbox::is_blocked()) {
                    $panel = '<div class="panel blocked"><p>' . $lang->dvz_sb_user_blocked . ' ' . $reason . '</p></div>';
                }
                // message: minimum posts
                else if (!dvz_shoutbox::access_minposts() && !dvz_shoutbox::access_mod()) {
                    $panel = '<div class="panel minposts"><p>' . str_replace('{MINPOSTS}', $mybb->settings['dvz_sb_minposts'], $lang->dvz_sb_minposts) . '</p></div>';
                }
                // shout form
                else if (dvz_shoutbox::access_shout()) {
                    eval('$panel = "' . $templates->get('dvz_shoutbox_panel') . '";');
                }

            }

            $js = null;

            // configuration
            $js .= 'dvz_shoutbox.interval   = ' . (dvz_shoutbox::access_refresh() ? (float)$mybb->settings['dvz_sb_interval'] : 0) . ';' . PHP_EOL;
            $js .= 'dvz_shoutbox.antiflood  = ' . (dvz_shoutbox::access_mod() ? 0 : (float)$mybb->settings['dvz_sb_antiflood']) . ';' . PHP_EOL;
            $js .= 'dvz_shoutbox.maxShouts  = ' . (int)$mybb->settings['dvz_sb_num'] . ';' . PHP_EOL;
            $js .= 'dvz_shoutbox.awayTime   = ' . (float)$mybb->settings['dvz_sb_away'] . '*1000;' . PHP_EOL;
            $js .= 'dvz_shoutbox.lang       = [\'' . $lang->dvz_sb_delete_confirm . '\', \'' . str_replace('{ANTIFLOOD}', $mybb->settings['dvz_sb_antiflood'], $lang->dvz_sb_antiflood) . '\', \''.$lang->dvz_sb_permissions.'\'];' . PHP_EOL;

            // reversed order
            if ($mybb->settings['dvz_sb_reversed']) {
                $js .= 'dvz_shoutbox.reversed   = true;' . PHP_EOL;
            }

            // lazyload
            if ($mybb->settings['dvz_sb_lazyload']) {
                $js .= 'dvz_shoutbox.lazyMode   = \'' . $mybb->settings['dvz_sb_lazyload'] . '\';' . PHP_EOL;
                $js .= 'jQuery(window).bind(\'scroll resize\', dvz_shoutbox.checkVisibility);' . PHP_EOL;
            }

            // away mode
            if ($mybb->settings['dvz_sb_away']) {
                $js .= 'jQuery(window).on(\'mousemove click dblclick keydown scroll\', dvz_shoutbox.updateActivity);' . PHP_EOL;
            }

            // shoutbox status
            $status = isset($_COOKIE['dvz_sb_status'])
                ? (bool)$_COOKIE['dvz_sb_status']
                : (bool)$mybb->settings['dvz_sb_status']
            ;
            $js .= 'dvz_shoutbox.status     = ' . (int)$status . ';' . PHP_EOL;

            if ($status == false) {
                $classes .= ' collapsed';
            }

            $javascript = '
<script>
' . $js . '
dvz_shoutbox.updateActivity();
dvz_shoutbox.loop();
</script>';

            eval('$dvz_shoutbox = "' . $templates->get('dvz_shoutbox') . '";');

        }

    }
    static function show_archive () {
        global $db, $mybb, $templates, $lang, $theme, $footer, $headerinclude, $header, $charset;

        if (!dvz_shoutbox::access_view()) return false;

        header('Content-type: text/html; charset='.$charset);

        add_breadcrumb($lang->dvz_sb_shoutbox, "index.php?action=shoutbox_archive");

        // moderation panel
        if (dvz_shoutbox::access_mod()) {

            if (isset($mybb->input['banlist']) && verify_post_check($mybb->input['postkey'])) {
                dvz_shoutbox::banlist_update($mybb->input['banlist']);
            }

            if (isset($mybb->input['days']) && verify_post_check($mybb->input['postkey'])) {
                if ($mybb->input['days'] == 'all') {
                    dvz_shoutbox::clear();
                } else {
                    $allowed = array(2, 7, 30, 90);
                    if (in_array($mybb->input['days'], $allowed)) {
                        dvz_shoutbox::clear($mybb->input['days']);
                    }
                }
            }

            $blocked_users = htmlspecialchars($mybb->settings['dvz_sb_blocked_users']);
            eval('$modoptions = "'.$templates->get("dvz_shoutbox_archive_modoptions").'";');

        } else {
            $modoptions = null;
        }

        // pagination
        $shoutsTotal = dvz_shoutbox::count();
        $pageNum     = (int)$mybb->input['page'];
        $perPage     = (int)$mybb->settings['dvz_sb_num_archive'];
        $pages       = ceil($shoutsTotal / $perPage);

        if (!$pageNum || $pageNum < 1 || $pageNum > $pages) $pageNum = 1;

        $start = ($pageNum - 1) * $perPage;

        if ($shoutsTotal > $perPage) {
            $multipage = multipage($shoutsTotal, $perPage, $pageNum, 'index.php?action=shoutbox_archive');
        }

        $data = dvz_shoutbox::get_multiple("ORDER by s.id DESC LIMIT $start,$perPage");

        $archive = null;

        while ($row = $db->fetch_array($data)) {
            $archive .= dvz_shoutbox::render_shout($row, true);
        }

        $javascript = '
<script>
dvz_shoutbox.lang = [\'' . $lang->dvz_sb_delete_confirm . '\', \'' . str_replace('{ANTIFLOOD}', $mybb->settings['dvz_sb_antiflood'], $lang->dvz_sb_antiflood) . '\', \''.$lang->dvz_sb_permissions.'\'];
</script>';

        eval('$content = "'.$templates->get("dvz_shoutbox_archive").'";');

        output_page($content);

        exit;

    }

    static function isPvt($data) {
        $string = trim($data);
        $part = substr($string, 0,4);
        if($part === '/pvt') {
            //Get UID
            $data = explode(" ", $data);
            return $data[1];
        }

        return false;
    }

    static function getUsername($uid, $data) {
        global $mybb, $lang;
        $lang->load('custom');
        //UID is ontvanger
        if($uid == $mybb->user['uid']) {
            return '<span class="private-message">' . $lang->sprintf($lang->private_message, $lang->from, $data['username']) . '</span>';
        }

        if($data['username'] == $mybb->user['username']) {
            $userdata = get_user($uid);
            return '<span class="private-message">' . $lang->sprintf($lang->private_message, $lang->to, $userdata['username']) . '</span>';
        }

        return $lang->default_private;
    }

    static function render_shout ($data, $static = false) {
        global $mybb, $lang;

        $id     = $data['id'];
        $text   = $data['text'];
        $date   = my_date($mybb->settings['dvz_sb_dateformat'], $data['date']);

        if($uid = self::isPvt($text)) {
            if($uid != $mybb->user['uid'] && $data['username'] != $mybb->user['username']) {
                return;
            }

            $replace = array("/pvt", $uid);
            $lang->load('custom');

            $usernameString = self::getUsername($uid, $data);

            $text = str_replace($replace, "", $text);
        }

        $text   = dvz_shoutbox::parse($text, $data['username']);
        if($usernameString) {
            $replace = array('<p>', '</p>');
            $text = $usernameString . str_replace($replace, "",$text);
        }

        $avatar = '<a href="User-' . $data['username'] . '"><img src="' . (empty($data['avatar']) ? 'images/default_avatar.png' : $data['avatar']) . '" alt="avatar" /></a>';
        $user   = '<span class="username" data-id="'. (int)$data['uid'] .'"><a>' . format_name($data['username'], $data['usergroup'], $data['displaygroup']) . '</a></span>';


        $notes = null;
        $attributes = null;

        $own = $data['uid'] == $mybb->user['uid'];

        if ($static) {
            if (dvz_shoutbox::access_mod()) {
                $notes .= '<span class="ip">'.$data['ip'].'</span>';
            }

            if (
                dvz_shoutbox::access_mod() ||
                (dvz_shoutbox::access_mod_own() && $own)
            ) {
                $notes .= '<a href="" class="mod edit">E</a><a href="" class="mod del">X</a>';
            }
        }

        if (
            dvz_shoutbox::access_mod() ||
            (dvz_shoutbox::access_mod_own() && $own)
        ) {
            $attributes .= ' data-mod';
        }

        if ($own) {
            $attributes .= ' data-own';
        }

        $notes .= '<a href="" class="mod report">REPORT</a>';

        return '
<div class="entry" data-id="'.$id.'" data-username="'.$data['username'].'"'.$attributes.'>
    <div class="avatar">'.$avatar.'</div>
    <div class="user">'.$user.':</div>
    <div class="text">'.$text.'</div>
    <div class="info"><span class="date">'.$date.'</span>'.$notes.'</div>
</div>';

    }

    // data manipulation
    static function get ($id) {
        global $db;
        return $db->fetch_array( $db->simple_select('dvz_shoutbox', '*', 'id=' . (int)$id) );
    }
    static function get_multiple ($clauses) {
        global $db;
        return $db->query("
            SELECT
                s.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                ".TABLE_PREFIX."dvz_shoutbox s
                LEFT JOIN ".TABLE_PREFIX."users u ON u.uid = s.uid 
            ".$clauses."
        ");
    }
    static function get_username ($id) {
        global $db;
        return $db->fetch_field( $db->query("SELECT username FROM ".TABLE_PREFIX."users u, ".TABLE_PREFIX."dvz_shoutbox s WHERE u.uid=s.uid AND s.id=" . (int)$id), 'username');
    }
    static function user_last_shout_time ($uid) {
        global $db;
        return $db->fetch_field(
            $db->simple_select('dvz_shoutbox', 'date', 'uid=' . (int)$uid, array(
                'order_by'  => 'date',
                'order_dir' => 'desc',
                'limit'     => 1
        )), 'date');
    }
    static function count () {
        global $db;
        return $db->fetch_field(
            $db->simple_select('dvz_shoutbox', 'COUNT(*) as n'),
            'n'
        );
    }
    static function shout ($data) {
        global $db;

        foreach ($data as &$item) {
            $item = $db->escape_string($item);
            if(strlen($item) > 120) {
                die;
            }
        }

        $data['date'] = time();

        return $db->insert_query('dvz_shoutbox', $data);
    }
    static function update ($id, $text) {
        global $db;
        return $db->update_query('dvz_shoutbox', array('text' => $db->escape_string($text)), 'id=' . (int)$id);
    }
    static function banlist_update ($new) {
        global $db;
        $db->update_query('settings', array('value' => $db->escape_string($new)), "name='dvz_sb_blocked_users'");
        rebuild_settings();
    }
    static function delete ($id) {
        global $db;
        return $db->delete_query('dvz_shoutbox', 'id=' . (int)$id);
    }
    static function clear ($days = false) {
        global $db;
        if ($days) {
            $where = 'date < '.( time()-((int)$days*86400) );
        } else {
            $where = false;
        }
        return $db->delete_query('dvz_shoutbox', $where);
    }

    // permissions
    static function is_user () {
        global $mybb;
        return !($mybb->user['usergroup'] == 1 && $mybb->user['uid'] < 1);
    }
    static function is_blocked () {
        global $db, $mybb, $lang;

        $uid = $mybb->user['uid'];
        $query = $db->simple_select("dvz_reports_banned", 'id, reason, unbantime', "uid='" . $uid . "'");
        if($query->num_rows === 1) {
            $data = $query->fetch_row();
            if($data[2] < strtotime('+1 hour', time())) {
                $uid = $db->escape_string($data[0]);
                $db->delete_query("dvz_reports_banned", "id ='" . $uid . "'");
                return false;
            }

            $lang->load('custom');

            return '<br />' . $lang->sprintf($lang->shoutbox_reason, htmlspecialchars_uni($data[1])) . '<br />' . $lang->sprintf($lang->unbanat, date('d-M-Y H:i:s', $data[2]));
        }
        return false;
    }
    static function access_view () {
        global $mybb;

        $array = dvz_shoutbox::settings_get_csv('groups_view');

        return (
            empty($array) ||
            dvz_shoutbox::member_of($array)
        );
    }
    static function access_refresh () {
        global $mybb;

        $array = dvz_shoutbox::settings_get_csv('groups_refresh');

        return (
            empty($array) ||
            dvz_shoutbox::member_of($array)
        );
    }
    static function access_shout () {
        global $mybb;

        $array = dvz_shoutbox::settings_get_csv('groups_shout');

        return (
            dvz_shoutbox::is_user() &&
            !dvz_shoutbox::is_blocked() &&
            (
                dvz_shoutbox::access_mod() ||
                (
                    dvz_shoutbox::access_view() &&
                    dvz_shoutbox::access_minposts() &&
                    (
                        empty($array) ||
                        dvz_shoutbox::member_of($array)
                    )
                )
            )
        );
    }
    static function access_mod () {
        global $mybb;

        $array = dvz_shoutbox::settings_get_csv('groups_mod');
        return (
            dvz_shoutbox::member_of($array) ||
            ($mybb->settings['dvz_sb_supermods'] && $mybb->usergroup['issupermod'])
        );
    }
    static function access_mod_own () {
        global $mybb;

        if ($mybb->settings['dvz_sb_groups_mod_own']) {
            $array = dvz_shoutbox::settings_get_csv('groups_mod_own');
            return dvz_shoutbox::member_of($array);
        } else {
            return false;
        }
    }
    static function access_minposts () {
        global $mybb;
        return $mybb->user['postnum'] >= $mybb->settings['dvz_sb_minposts'];
    }
    static function can_mod ($shoutId) {
        global $mybb;

        if (dvz_shoutbox::access_mod()) {
            return true;
        } else if (dvz_shoutbox::access_mod_own() && dvz_shoutbox::access_shout()) {

            $data = dvz_shoutbox::get($shoutId);

            if ($data['uid'] == $mybb->user['uid']) {
                return true;
            }

        }

        return false;

    }

    // core
    static function parse ($message, $me_username) {
        global $mybb;

        require_once MYBB_ROOT.'inc/class_parser.php';

        $parser = new postParser;
        $options = array(
            'allow_mycode'      =>  0,
            'allow_smilies'     => $mybb->settings['dvz_sb_smilies'],
            'allow_imgcode'     =>  0,
            'filter_badwords'   =>  1,
        );

        $message = $parser->parse_message($message, $options);
        $message = $parser->mycode_auto_url($message);
        $message = $post = preg_replace('/\[url](.+?)\[\/url\]/', '<a href="\1" target="_blank">\1</a>', $message);
        return $message;

    }
    static function antiflood_pass () {
        global $mybb;

        return (
            !$mybb->settings['dvz_sb_antiflood'] ||
            ( time() - dvz_shoutbox::user_last_shout_time($mybb->user['uid']) ) > $mybb->settings['dvz_sb_antiflood']
        );

    }
    static function member_of ($groupsArray) {
        global $mybb;

        if (dvz_shoutbox::$userGroups == false) {
            dvz_shoutbox::$userGroups = explode(',', $mybb->user['additionalgroups']);
            dvz_shoutbox::$userGroups[] = $mybb->user['usergroup'];
        }

        return array_intersect(dvz_shoutbox::$userGroups, $groupsArray);
    }
    static function settings_get_csv ($name) {
        global $mybb;

        $items = explode(',', $mybb->settings['dvz_sb_'.$name]);

        if (count($items) == 1 && $items[0] == '') {
            return array();
        } else 

        return $items;
    }

    static function reportShout($postdata)
    {
        global $mybb, $db;
        if (verify_post_check($postdata['key'])) {
            if (self::access_shout()) {
                $id = $db->escape_string($postdata['id']);
                $getPost = $db->write_query("SELECT id FROM " .TABLE_PREFIX. "dvz_shoutbox WHERE id = '$id'");
                if ($getPost->num_rows === 1) {
                    //Store report
                    $data = array(
                        'shid' => $db->escape_string($postdata['id']),
                        'uid' => $db->escape_string($mybb->user['uid']),
                        'reason' => $db->escape_string($postdata['reason']),
                        'date' => time(),
                        'ip' => $db->escape_string(get_ip()),
                    );

                    $insert = $db->insert_query('dvz_reports', $data);
                    if ($insert) {
                        return true;
                    }
                    return false;
                }
                return false;
            }

            return false;
        }
    }

}

?>
