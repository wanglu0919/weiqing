<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');
$founder = explode(',', $_W['config']['setting']['founder']);

$pindex = max(1, intval($_GPC['page']));
$psize = 10;

$type = intval($_GPC['type']);
$params = array();
$condition = empty($type) ? '' : " AND type = '{$type}'";
$condition .= $_W['isfounder'] ? '' : " AND uid = '{$_W['uid']}'";

if (!empty($_GPC['username']) && $_W['isfounder']) {
	$uid = pdo_fetchcolumn("SELECT uid FROM ".tablename('members')." WHERE username = :username", array(':username' => $_GPC['username']));
	$condition = " AND uid = '$uid'";
}

if (!empty($_GPC['wechat'])) {
	$condition = " AND name LIKE :name";
	$params[':name'] = '%'.$_GPC['wechat'].'%';
}
$sql = "SELECT * FROM " . tablename('wechats') . " WHERE 1 $condition ORDER BY `parentid` ASC, `weid` DESC LIMIT ".($pindex - 1) * $psize.','.$psize;
$list = pdo_fetchall($sql, $params, 'weid');
$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wechats') . " WHERE  1 $condition", $params);
$pager = pagination($total, $pindex, $psize);

if (!empty($list)) {
	foreach ($list as $row) {
		$uids[$row['uid']] = $row['uid'];
		if (!empty($row['parentid'])) {
			$list[$row['parentid']]['sub'][$row['weid']] = $row;
			unset($list[$row['weid']]);
		}
	}
	unset($row);
	$users = pdo_fetchall("SELECT uid, username FROM ".tablename('members')." WHERE uid IN (".implode(',', $uids).")", array(), 'uid');
}
template('account/display');