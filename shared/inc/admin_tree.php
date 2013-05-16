<?php

// Returns an array composed of:
// ["adm_login"]
// ["ob_head"]
// ["ob_tail"]
// ["ob_next"]
// or 1 if error
function getAdminOb($adm_login){
	global $pro_mysql_admin_table;
	$q = "SELECT adm_login,ob_head,ob_next,ob_tail FROM $pro_mysql_admin_table WHERE adm_login='$adm_login';";
	$r = mysql_query($q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$n = mysql_num_rows($r);
	if($n != 1){
		return 1;
	}
	return mysql_fetch_array($r);
}

// Writes a (modified) getAdminOb() into the db.
function setAdminOb($ob){
	global $pro_mysql_admin_table;
	$q = "UPDATE $pro_mysql_admin_table SET
		ob_head='".$ob["ob_head"]."',
		ob_tail='".$ob["ob_tail"]."',
		ob_next='".$ob["ob_next"]."'
		WHERE adm_login='".$ob["adm_login"]."';";
	$r = mysql_query($q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
}

// Returns a getAdminOb() array, 1 if error
function getAdminParent($me){
	$next = $me["ob_next"];
	$cur = getAdminOb($next);
	if($cur == 1){
		return 1;
	}
	while($cur["ob_tail"] != $next){
		if($next == ''){
			return 1;
		}
		$next = $cur["ob_next"];
		$cur = getAdminOb($next);
		if($cur == 1){
			return 1;
		}
	}
	return $cur;
}

// Returns a getAdminOb() array, 1 if error
// Predicat: there is a previous sibling, $adm_login isn't first child
function getPreviousSibling($parent,$adm_login){
	// Get the first child
	$cur = getAdminOb($parent["ob_head"]);
	$next = $cur["ob_next"];
	while($next != $adm_login){	// If next object isn't $adm_login, continue to search
		$cur = getAdminOb($cur["ob_next"]);
		$next = $cur["ob_next"];
	}
	return $cur;
}

// This function deletes an admin after removing it
// from the tree of admin. If $adm_login has children,
// then they are deleted as well.
function recursiveDeleteAdmin($adm_login){
	$ob = getAdminOb($adm_login);
	// While there's still some children, recursively delete first child
	while($ob["ob_head"] != ''){
		recursiveDeleteAdmin($ob["ob_head"]);
		$ob = getAdminOb($adm_login);
	}

	// remove myself from list
	if($ob["ob_next"] != ''){
		$parent = getAdminParent($ob);
		// Can't find parent and there's an ob_next: structure error.
		if($parent == 1){
			return 1;
		}
		// Parent doesn't have a ob_head: structure error;
		if($parent["ob_head"] == '' || $parent["ob_tail"] == ''){
			return 1;
		}

		// Only child case (eg: no siblings)
		if($parent["ob_head"] == $parent["ob_tail"]){
			// TODO: 1/ Remove ob_head / ob_next from parent 2/ kill myself
			$parent["ob_head"] = '';
			$parent["ob_tail"] = '';
			setAdminOb($parent);
			DTCdeleteAdmin($adm_login);
			return 0;
		}
		// First child case (more than one sibling)
		if($parent["ob_head"] == $adm_login && $parent["ob_tail"] != $adm_login){
			// TODO: 1/ remove first child from tree 2/ kill myself
			$parent["ob_head"] = $ob["ob_next"];
			setAdminOb($parent);
			DTCdeleteAdmin($adm_login);
			return 0;
		}
		$previous_sibling = getPreviousSibling($parent,$adm_login);
		if($previous_sibling == 1){
			return 1;
		}
		// Last child (more than one sibling)
		if($ob["ob_next"] == $parent["adm_login"] && $parent["ob_tail"] == $adm_login){
			// TODO: 1/ Move $ob["ob_next"] in $previous_sibling
			// 2/ move $previous_sibling["adm_login"] in parent ob_tail
			// 3/ Kill myself
			$previous_sibling["ob_next"] = $ob["ob_next"];
			setAdminOb($previous_sibling);
			$parent["ob_tail"] = $previous_sibling["adm_login"];
			setAdminOb($parent);
			DTCdeleteAdmin($adm_login);
			return 0;
		}
		// One of the child, not first or last
		// TODO: 1/ put $ob["ob_next"] in previous child 2/ Kill myself
		$previous_sibling["ob_next"] = $ob["ob_next"];
		setAdminOb($previous_sibling);
		DTCdeleteAdmin($adm_login);
	}else{
		// Object isn't par of a tree (anymore?)
		// TODO: kill myself
		DTCdeleteAdmin($adm_login);
		return 0;
	}
}

function addSubAccount($adm_login,$parent_login){
	$parent = getAdminOb($parent_login);
	$ob = getAdminOb($adm_login);
	if($parent == 1 || $ob == 1){
		return 1;
	}
	// Parent has no child yet, let's add one.
	if($parent["ob_head"] == '' && $parent["ob_tail"] == ''){
		$parent["ob_head"] = $adm_login;
		$parent["ob_tail"] = $adm_login;
		setAdminOb($parent);
		$ob["ob_next"] = $parent_login;
		setAdminOb($ob);
		return 0;
	}else{
		if($parent["ob_head"] == '' || $parent["ob_tail"] == ''){
			return 1;
		}
		// Insert as first child, before existing first child.
		$ob["ob_next"] = $parent["ob_head"];
		$parent["ob_head"] = $adm_login;
		setAdminOb($parent);
		setAdminOb($ob);
	}
}

?>
