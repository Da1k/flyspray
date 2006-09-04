<?php

  /********************************************************\
  | Show various reports on tasks                          |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                               |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->perms('view_reports')) {
    Flyspray::Redirect($baseurl);
}

require_once(BASEDIR . '/includes/events.inc.php');
$page->setTitle($fs->prefs['page_title'] . L('reports'));

/**********************\
*  Event reports       *
\**********************/

$events = array(1 => L('opened'),
                13 => L('reopened'),
                2 => L('closed'),
                3 => L('edited'),
                14 => L('assignmentchanged'),
                29 => L('events.useraddedtoassignees'),
                4 => L('commentadded'),
                5 => L('commentedited'),
                6 => L('commentdeleted'),
                7 => L('attachmentadded'),
                8 => L('attachmentdeleted'),
                11 => L('relatedadded'),
                11 => L('relateddeleted'),
                9 => L('notificationadded'),
                10 => L('notificationdeleted'),
                17 => L('reminderadded'),
                18 => L('reminderdeleted'));

$user_events = array(30 => L('created'),
                     31 => L('deleted'));
                     
$page->assign('events', $events);
$page->assign('user_events', $user_events);

$sort = strtoupper(Req::enum('sort', array('desc', 'asc')));

switch (Req::val('order')) {
    case 'type':
        $orderby = "h.event_type {$sort}, h.event_date {$sort}";
        break;
    case 'user':
        $orderby = "u.real_name {$sort}, h.event_date {$sort}";
        break;
    case 'date': default:
        $orderby = "h.event_date {$sort}, h.event_type {$sort}";
}

$where = array();
foreach (Req::val('events', array()) as $eventtype) {
    $where[] = 'h.event_type = ' . intval($eventtype);
}
$where = '(' . implode(' OR ', $where) . ')';

if ($proj->id) {
    $where = ' (t.project_id = ' . $db->qstr($proj->id) . ' OR h.event_type > 29) AND ' . $where;
}

if ( ($fromdate = Req::val('fromdate')) || Req::val('todate')) {
        $where .= ' AND ';
        $ufromdate = strtotime($fromdate) + 0;
        $todate = Req::val('todate');
        $utodate   = strtotime($todate) + 86400;
        
        if ($fromdate) {
            $where .= " h.event_date > {$ufromdate} ";
        }
        if ($todate && $fromdate) {
            $where .= " AND h.event_date < {$utodate} ";
        } else if ($todate) {
            $where .= " h.event_date < {$utodate} ";
        }
}

if (Req::val('event_number')) {
    $orderby .= ' LIMIT ' . Req::num('event_number');
}

if (count(Req::val('events'))) { 
    $histories = $db->Query("SELECT h.*
                        FROM  {history} h
                   LEFT JOIN {tasks} t ON h.task_id = t.task_id
                        WHERE $where
                     ORDER BY $orderby");
             
    $histories = $db->FetchAllArray($histories);
}

$page->uses('histories', 'sort');

$page->pushTpl('reports.tpl');
?>