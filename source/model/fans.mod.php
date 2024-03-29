<?php
function fans_update($user, $fields) {
	global $_W;
	$_W['weid'] && $fields['weid'] = $_W['weid'];
	$struct = cache_load('fansfields');
	if (empty($fields)) {
		return false;
	}
	if (empty($struct)) {
		$struct = cache_build_fans_struct();
	}
	foreach ($fields as $field => $value) {
		if (!in_array($field, $struct)) {
			unset($fields[$field]);
		} 
	}
	
	if (empty($fields['avatar']) && !empty($_FILES['avatar']['tmp_name'])) {
		$_W['uploadsetting'] = array();
		$_W['uploadsetting']['avatar']['folder'] = 'avatar';
		$_W['uploadsetting']['avatar']['extentions'] = $_W['config']['upload']['image']['extentions'];
		$_W['uploadsetting']['avatar']['limit'] = $_W['config']['upload']['image']['limit'];
		$upload = file_upload($_FILES['avatar'], 'avatar', $user);
		if (is_error($upload)) {
			message($upload['message']);
		}
		$fields['avatar'] = $upload['path'];
	} elseif (!empty($fields['avatar'])) {
		$pathinfo = pathinfo($fields['avatar']);
		$fields['avatar'] = $pathinfo['basename'];
	}
	$isexists = pdo_fetchcolumn("SELECT id FROM ".tablename('fans')." WHERE from_user = :user", array(':user' => $user));
	if (empty($isexists)) {
		$fields['from_user'] = $user;
		$fields['createtime'] = TIMESTAMP;
		foreach ($struct as $field) {
			if ($field != 'id' && $field != 'follow' && !isset($fields[$field])) {
				$fields[$field] = '';
			}
		}
		return pdo_insert('fans', $fields);
	} else {
		return pdo_update('fans', $fields, array('from_user' => $user));
	}
}

function fans_search($user, $fields = array()) {
	global $_W;
	$struct = cache_load('fansfields');
	if (empty($fields)) {
		$select = '*';
	} else {
		foreach ($fields as $field) {
			if (!in_array($field, $struct)) {
				unset($fields[$field]);
			}
		}
		$select = '`from_user`, `'.implode('`,`', $fields).'`';
	}
	$result = pdo_fetchall("SELECT $select FROM ".tablename('fans')." WHERE from_user IN ('".implode("','", is_array($user) ? $user : array($user))."')", array(), 'from_user');
	if (!empty($result)) {
		foreach ($result as &$row) {
			if (!empty($row['avatar'])) {
				if (strexists($row['avatar'], 'avatar_')) {
					$row['avatar'] = $_W['siteroot'] . 'resource/image/avatar/' . $row['avatar'];
				} else {
					$row['avatar'] = $_W['attachurl'] . $row['avatar'];
				}
			}
		}
		if (is_array($user)) {
			return $result;
		} else {
			return $result[$user];
		}
	} else {
		return array();
	}
}

function fans_fields() {
	$result = array();
	$fields = pdo_fetchall("SHOW FULL FIELDS FROM ".tablename('fans'));
	foreach ($fields as $field) {
		if (in_array($field['Field'], array('id', 'weid', 'from_user', 'follow', 'createtime', 'salt', 'fakeid'))) {
			continue;
		}
		$result[$field['Field']] = $field['Comment'];
	}
	return $result;
}

function fans_require($user, $fields, $pre = '') {
	global $_W;
	if(empty($fields) || !is_array($fields)) {
		return false;
	}
	if(!in_array('weid', $fields)) {
		$fields[] = 'weid';
	}
	if(!empty($pre)) {
		$pre .= '<br/>';
	}
	$profile = fans_search($user, $fields);
	$weid = $profile['weid'];
	$titles = fans_fields();
	$message = '';
	$ks = array();
	foreach($profile as $k => $v) {
		if(empty($v)) {
			$ks[] = $k;
			$message .= $titles[$k] . ', ';
		}
	}
	if(!empty($message)) {
		$redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'] . '#qq.com#wechat_redirect';
		$site = WeUtility::createModuleSite('fans');
		$site->module = $_W['account']['modules']['fans'];
		$site->weid = $_W['weid'];
		$site->inMobile = true;
		$site->doMobileRequire($fields, $redirect);
	}
	return $profile;
}
