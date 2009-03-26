<?php
//============================================================+
// File name   : tce_show_result_user.php
// Begin       : 2004-06-10
// Last Update : 2009-02-20
// 
// Description : Display test results for specified user.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com S.r.l.
//               Via della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//
// License: 
//    Copyright (C) 2004-2009  Nicola Asuni - Tecnick.com S.r.l.
//    
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//    
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//    
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//     
//    Additionally, you can't remove the original TCExam logo, copyrights statements
//    and links to Tecnick.com and TCExam websites.
//    
//    See LICENSE.TXT file for more information.
//============================================================+

/**
 * Display test results for specified user.
 * @package com.tecnick.tcexam.admin
 * @author Nicola Asuni
 * @copyright Copyright &copy; 2004-2009, Nicola Asuni - Tecnick.com S.r.l. - ITALY - www.tecnick.com - info@tecnick.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link www.tecnick.com
 * @since 2004-06-10
 */

/**
 */

require_once('../config/tce_config.php');

$pagelevel = K_AUTH_ADMIN_RESULTS;
require_once('../../shared/code/tce_authorization.php');

$thispage_title = $l['t_result_user'];
require_once('../code/tce_page_header.php');
require_once('../../shared/code/tce_functions_form.php');
require_once('../../shared/code/tce_functions_tcecode.php');
require_once('../../shared/code/tce_functions_test.php');
require_once('../../shared/code/tce_functions_test_stats.php');
require_once('../code/tce_functions_auth_sql.php');

if (isset($_REQUEST['test_id']) AND ($_REQUEST['test_id'] > 0)) {
	$test_id = intval($_REQUEST['test_id']);
	// check user's authorization
	if (!F_isAuthorizedUser(K_TABLE_TESTS, 'test_id', $test_id, 'test_user_id')) {
		F_print_error('ERROR', $l['m_authorization_denied']);
		exit;
	}
}
if (isset($testuser_id)) {
	$testuser_id = intval($testuser_id);
}
if (isset($user_id)) {
	$user_id = intval($user_id);
}

if (isset($selectcategory)) {
	$changecategory = 1;
}

if(isset($_POST['lock'])) {
	$menu_mode = 'lock';
} elseif(isset($_POST['unlock'])) {
	$menu_mode = 'unlock';
} elseif(isset($_POST['extendtime'])) {
	$menu_mode = 'extendtime';
}

switch($menu_mode) {
	
	case 'delete':{
		F_stripslashes_formfields(); 
		// ask confirmation
		F_print_error('WARNING', $l['m_delete_confirm']);
		?>
		<div class="confirmbox">
		<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post" enctype="multipart/form-data" id="form_delete">
		<div>
		<input type="hidden" name="testuser_id" id="testuser_id" value="<?php echo $testuser_id; ?>" />
		<?php 
		F_submit_button('forcedelete', $l['w_delete'], $l['h_delete']);
		F_submit_button('cancel', $l['w_cancel'], $l['h_cancel']);
		?>
		</div>
		</form>
		</div>
		<?php
		break;
	}
	
	case 'forcedelete':{
		F_stripslashes_formfields(); // Delete
		if($forcedelete == $l['w_delete']) { //check if delete button has been pushed (redundant check)
				$sql = 'DELETE FROM '.K_TABLE_TEST_USER.' 
					WHERE testuser_id='.$testuser_id.'';
				if(!$r = F_db_query($sql, $db)) {
					F_display_db_error();
				} else {
					$testuser_id = false;
					F_print_error('MESSAGE', $l['m_deleted']);
				}
		}
		break;
	}
	
	case 'extendtime':{
		// extend the test time by 5 minutes
		// this time extension is obtained moving forward the test starting time
		$sqlu = 'UPDATE '.K_TABLE_TEST_USER.'
			SET testuser_creation_time=\''.date(K_TIMESTAMP_FORMAT, F_getTestStartTime($testuser_id) + (K_EXTEND_TIME_MINUTES * K_SECONDS_IN_MINUTE)).'\'
			WHERE testuser_id='.$testuser_id.'';
		if(!$ru = F_db_query($sqlu, $db)) {
			F_display_db_error();
		} else {
			F_print_error('MESSAGE', $l['m_updated']);
		}
		break;
	}
	
	case 'lock':{
		// update test mode to 4 = test locked
		$sqlu = 'UPDATE '.K_TABLE_TEST_USER.'
			SET testuser_status=4 
			WHERE testuser_id='.$testuser_id.'';
		if(!$ru = F_db_query($sqlu, $db)) {
			F_display_db_error();
		} else {
			F_print_error('MESSAGE', $l['m_updated']);
		}
		break;
	}
	
	case 'unlock':{
		// update test mode to 1 = test unlocked
		$sqlu = 'UPDATE '.K_TABLE_TEST_USER.'
			SET testuser_status=1 
			WHERE testuser_id='.$testuser_id.'';
		if(!$ru = F_db_query($sqlu, $db)) {
			F_display_db_error();
		} else {
			F_print_error('MESSAGE', $l['m_updated']);
		}
		break;
	}
	
	default: { 
		break;
	}

} //end of switch

// --- Initialize variables

if(!isset($test_id) OR empty($test_id)) {
	// select default test ID
	$sql = F_select_executed_tests_sql().' LIMIT 1';
	if($r = F_db_query($sql, $db)) {
		if($m = F_db_fetch_array($r)) {
			$test_id = $m['test_id'];
		} else {
			$test_id = 0;
		}
	} else {
		F_display_db_error();
	}
}

if($formstatus) {
	if ((isset($changecategory) AND ($changecategory > 0)) OR (!isset($user_id)) OR empty($user_id)) {
			$sql = 'SELECT testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_status, user_lastname, user_firstname, user_name, SUM(testlog_score) AS test_score, MAX(testlog_change_time) AS test_end_time
				FROM '.K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_USERS.' 
				WHERE testlog_testuser_id=testuser_id
					AND testuser_user_id=user_id
					AND testuser_test_id='.$test_id.'
					AND testuser_status>0
				GROUP BY testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_status, user_lastname, user_firstname, user_name
				ORDER BY testuser_test_id, user_lastname, user_firstname
				LIMIT 1';
	} else {
		$sql = 'SELECT testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_status, user_lastname, user_firstname, user_name, SUM(testlog_score) AS test_score, MAX(testlog_change_time) AS test_end_time
			FROM '.K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_USERS.' 
			WHERE testlog_testuser_id=testuser_id
				AND testuser_user_id=user_id
				AND testuser_test_id='.$test_id.'
				AND testuser_user_id='.$user_id.'
				AND testuser_status>0
			GROUP BY testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_status, user_lastname, user_firstname, user_name
			LIMIT 1';
	}
	if($r = F_db_query($sql, $db)) {
		if($m = F_db_fetch_array($r)) {
			$testuser_id = $m['testuser_id'];
			$test_id = $m['testuser_test_id'];
			$user_id = $m['testuser_user_id'];
			$user_lastname = $m['user_lastname'];
			$user_firstname = $m['user_firstname'];
			$user_name = $m['user_name'];
			$test_start_time = $m['testuser_creation_time'];
			$test_score = $m['test_score'];
			$testuser_status = $m['testuser_status'];
			$usrtestdata = F_getUserTestStat($test_id, $user_id);
			$test_end_time = $m['test_end_time'];
		} else {
			$testuser_id = '';
			$testlog_test_id = '';
			$testlog_user_id = '';
			$user_lastname = '';
			$user_firstname = '';
			$user_name = '';
			$test_start_time = '';
			$test_end_time = '';
			$test_score = '';
			$testuser_status = 0;
		}
	} else {
		F_display_db_error();
	}
}
?>

<div class="container">

<div class="tceformbox">
<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post" enctype="multipart/form-data" id="form_resultuser">

<div class="row">
<span class="label">
<label for="test_id"><?php echo $l['w_test']; ?></label>
</span>
<span class="formw">
<input type="hidden" name="testuser_id" id="testuser_id" value="<?php echo $testuser_id; ?>" />
<input type="hidden" name="changecategory" id="changecategory" value="" />
<select name="test_id" id="test_id" size="0" onchange="document.getElementById('form_resultuser').changecategory.value=1;document.getElementById('form_resultuser').submit()" title="<?php echo $l['h_test']; ?>">
<?php
$sql = F_select_executed_tests_sql();
if($r = F_db_query($sql, $db)) {
	while($m = F_db_fetch_array($r)) {
		echo '<option value="'.$m['test_id'].'"';
		if($m['test_id'] == $test_id) {
			echo ' selected="selected"';
		}
		echo '>'.substr($m['test_begin_time'], 0, 10).' '.htmlspecialchars($m['test_name'], ENT_NOQUOTES, $l['a_meta_charset']).'</option>'.K_NEWLINE;
	}
}
else {
	F_display_db_error();
}
?>
</select>
</span>
</div>

<noscript>
<div class="row">
<span class="label">&nbsp;</span>
<span class="formw">
<input type="submit" name="selectcategory" id="selectcategory" value="<?php echo $l['w_select']; ?>" />
</span>
</div>
</noscript>

<div class="row">
<span class="label">
<label for="user_id"><?php echo $l['w_user']; ?></label>
</span>
<span class="formw">
<select name="user_id" id="user_id" size="0" onchange="document.getElementById('form_resultuser').submit()" title="<?php echo $l['h_select_user']; ?>">
<?php
$sql = 'SELECT user_id, user_lastname, user_firstname, user_name
	FROM '.K_TABLE_TEST_USER.', '.K_TABLE_USERS.' 
	WHERE testuser_user_id=user_id
	AND testuser_test_id='.$test_id.'
	ORDER BY user_lastname, user_firstname, user_name';
if($r = F_db_query($sql, $db)) {
	$usrcount = 1;
	while($m = F_db_fetch_array($r)) {
		echo '<option value="'.$m['user_id'].'"';
		if($m['user_id'] == $user_id) {
			echo ' selected="selected"';
		}
		echo '>';
		echo ''.$usrcount.'. ';
		echo ''.htmlspecialchars($m['user_lastname'].' '.$m['user_firstname'].' - '.$m['user_name'].'', ENT_NOQUOTES, $l['a_meta_charset']).'';
		echo '</option>'.K_NEWLINE;
		$usrcount++;
	}
}
else {
	F_display_db_error();
}
?>
</select>
</span>
</div>

<noscript>
<div class="row">
<span class="label">&nbsp;</span>
<span class="formw">
<input type="submit" name="selectrecord" id="selectrecord" value="<?php echo $l['w_select']; ?>" />
</span>
</div>
</noscript>

<div class="row"><hr /></div>

<?php
if (isset($usrtestdata)) {
?>

<div class="row">
<span class="label">
<span title="<?php echo $l['h_time_begin']; ?>"><?php echo $l['w_time_begin']; ?>:</span>
</span>
<span class="formw">
<?php
echo ''.$test_start_time.' ';
if (isset($test_id) AND ($test_id > 0) AND isset($user_id) AND ($user_id > 0)) {
	F_submit_button('extendtime', '+'.K_EXTEND_TIME_MINUTES.' min', $l['h_add_five_minutes']);
}
?>
&nbsp;
</span>
</div>

<div class="row">
<span class="label">
<span title="<?php echo $l['h_time_end']; ?>"><?php echo $l['w_time_end']; ?>:</span>
</span>
<span class="formw">
<?php
echo ''.$test_end_time.' ';
?>
&nbsp;
</span>
</div>

<div class="row">
<span class="label">
<span title="<?php echo $l['w_test_time']; ?>"><?php echo $l['w_test_time']; ?>:</span>
</span>
<span class="formw">
<?php
$time_diff = strtotime($test_end_time) - strtotime($test_start_time); //sec
$time_diff = gmdate('H:i:s', $time_diff);
echo ''.$time_diff.'';
?>
&nbsp;
</span>
</div>

<div class="row">
<span class="label">
<span title="<?php echo $l['h_score_total']; ?>"><?php echo $l['w_score']; ?>:</span>
</span>
<span class="formw">
<?php
$passmsg = '';
if ($usrtestdata['score_threshold'] > 0) {
	if ($usrtestdata['score'] >= $usrtestdata['score_threshold']) {
		$passmsg = ' - '.$l['w_passed'];
	} else {
		$passmsg = ' - '.$l['w_not_passed'];
	}
}
echo ''.$test_score.' ('.round(100 * $usrtestdata['score'] / $usrtestdata['max_score']).'%)'.$passmsg.'';
?>
&nbsp;
</span>
</div>

<div class="row">
<span class="label">
<span title="<?php echo $l['h_answers_right']; ?>"><?php echo $l['w_answers_right']; ?>:</span>
</span>
<span class="formw">
<?php 
echo "".$usrtestdata['right'].' ('.round(100 * $usrtestdata['right'] / $usrtestdata['all']).'%)';
?>
&nbsp;
</span>
</div>

<div class="row">
<span class="label">
<span title="<?php echo $l['h_testcomment']; ?>"><?php echo $l['w_comment']; ?>:</span>
</span>
<span class="formw">
<?php 
echo ''.F_decode_tcecode($usrtestdata['comment']).'';
?>
&nbsp;
</span>
</div>

<div class="rowl">
<?php
if (isset($testuser_id) AND (!empty($testuser_id))) {
	// display user questions
	$sql = 'SELECT * 
		FROM '.K_TABLE_QUESTIONS.', '.K_TABLE_TESTS_LOGS.' 
		WHERE question_id=testlog_question_id 
		AND testlog_testuser_id='.$testuser_id.'
		ORDER BY testlog_id';
	if($r = F_db_query($sql, $db)) {
		echo '<ol class="question">'.K_NEWLINE;
		while($m = F_db_fetch_array($r)) {
			echo '<li>'.K_NEWLINE;
			// display question stats
			echo '<strong>['.$m['testlog_score'].']'.K_NEWLINE;
			echo ' (';
			echo 'IP:'.getIpAsInt($m['testlog_user_ip']).K_NEWLINE;
			if (isset($m['testlog_display_time']) AND (strlen($m['testlog_display_time']) > 0)) {
				echo ' | '.substr($m['testlog_display_time'], 11, 8).K_NEWLINE;
			} else {
				echo ' | --:--:--'.K_NEWLINE;
			}
			if (isset($m['testlog_change_time']) AND (strlen($m['testlog_change_time']) > 0)) {
				echo ' | '.substr($m['testlog_change_time'], 11, 8).K_NEWLINE;
			} else {
				echo ' | --:--:--'.K_NEWLINE;
			}
			if (isset($m['testlog_display_time']) AND isset($m['testlog_change_time'])) {
				echo ' | '.date('i:s', (strtotime($m['testlog_change_time']) - strtotime($m['testlog_display_time']))).'';
			} else {
				echo ' | --:--'.K_NEWLINE;
			}
			if (isset($m['testlog_reaction_time']) AND ($m['testlog_reaction_time'] > 0)) {
				echo ' | '.($m['testlog_reaction_time']/1000).'';
			} else {
				echo ' | ------'.K_NEWLINE;
			}
			echo ')</strong>'.K_NEWLINE;
			echo '<br />'.K_NEWLINE;
			// display question description
			echo F_decode_tcecode($m['question_description']).K_NEWLINE;
			if (K_ENABLE_QUESTION_EXPLANATION AND !empty($m['question_explanation'])) {
				echo '<br /><span class="explanation">'.$l['w_explanation'].':</span><br />'.F_decode_tcecode($m['question_explanation']).''.K_NEWLINE;
			}
			if ($m['question_type'] == 3) {
				// TEXT
				echo '<ul class="answer"><li>'.K_NEWLINE;
				echo F_decode_tcecode($m['testlog_answer_text']);
				echo '&nbsp;</li></ul>'.K_NEWLINE;
			} else {
				echo '<ol class="answer">'.K_NEWLINE;
				// display each answer option
				$sqla = 'SELECT *
					FROM '.K_TABLE_LOG_ANSWER.', '.K_TABLE_ANSWERS.'
					WHERE logansw_answer_id=answer_id
						AND logansw_testlog_id=\''.$m['testlog_id'].'\'
					ORDER BY logansw_order';
				if($ra = F_db_query($sqla, $db)) {
					while($ma = F_db_fetch_array($ra)) {
						echo '<li>';
						if ($m['question_type'] == 4) {
							// ORDER
							if ($ma['logansw_position'] > 0) {
								if ($ma['logansw_position'] == $ma['answer_position']) {
									echo '<acronym title="'.$l['h_answer_right'].'" class="okbox">'.$ma['logansw_position'].'</acronym>';
								} else {
									echo '<acronym title="'.$l['h_answer_wrong'].'" class="nobox">'.$ma['logansw_position'].'</acronym>';
								}
							} else {
								echo '<acronym title="'.$l['m_unanswered'].'" class="offbox">&nbsp;</acronym>';
							}
						} elseif ($ma['logansw_selected'] > 0) {
							if (F_getBoolean($ma['answer_isright'])) {
								echo '<acronym title="'.$l['h_answer_right'].'" class="okbox">x</acronym>';
							} else {
								echo '<acronym title="'.$l['h_answer_wrong'].'" class="nobox">x</acronym>';
							}
						} elseif ($m['question_type'] == 1) {
							// MCSA
							echo '<acronym title="-" class="offbox">&nbsp;</acronym>';
						} else {
							if ($ma['logansw_selected'] == 0) {
								if (F_getBoolean($ma['answer_isright'])) {
									echo '<acronym title="'.$l['h_answer_wrong'].'" class="nobox">&nbsp;</acronym>';
								} else {
									echo '<acronym title="'.$l['h_answer_right'].'" class="okbox">&nbsp;</acronym>';
								}
							} else {
								echo '<acronym title="'.$l['m_unanswered'].'" class="offbox">&nbsp;</acronym>';
							}
						}
						echo '&nbsp;';
						if ($m['question_type'] == 4) {
							echo '<acronym title="'.$l['w_position'].'" class="onbox">'.$ma['answer_position'].'</acronym>';
						} elseif (F_getBoolean($ma['answer_isright'])) {
							echo '<acronym title="'.$l['w_answers_right'].'" class="onbox">&reg;</acronym>';
						} else {
							echo '<acronym title="'.$l['w_answers_wrong'].'" class="offbox">&nbsp;</acronym>';
						}
						echo ' ';
						echo F_decode_tcecode($ma['answer_description']);
						if (K_ENABLE_ANSWER_EXPLANATION AND !empty($ma['answer_explanation'])) {
							echo '<br /><span class="explanation">'.$l['w_explanation'].':</span><br />'.F_decode_tcecode($ma['answer_explanation']).''.K_NEWLINE;
						}
						echo '</li>'.K_NEWLINE;
					}
				} else {
					F_display_db_error();
				}
				echo '</ol>'.K_NEWLINE;
			} // end multiple answers
			// display teacher/supervisor comment to the question
			if (isset($m['testlog_comment']) AND (!empty($m['testlog_comment']))) {
				echo '<ul class="answer"><li class="comment">'.K_NEWLINE;
				echo F_decode_tcecode($m['testlog_comment']);
				echo '&nbsp;</li></ul>'.K_NEWLINE;
			}
			echo '<br /><br />'.K_NEWLINE;
			echo '</li>'.K_NEWLINE;
		}
		echo '</ol>'.K_NEWLINE;
	} else {
		F_display_db_error();
	}
}
?>
</div>

<div class="row">
<?php
// show buttons by case
if (isset($test_id) AND ($test_id > 0) AND isset($user_id) AND ($user_id > 0)) {
	F_submit_button('delete', $l['w_delete'], $l['h_delete']);
	
	if($testuser_status < 4) {
		// lock test button
		F_submit_button('lock', $l['w_lock'], $l['w_lock']);
	} else {
		// unlock test button
		F_submit_button('unlock', $l['w_unlock'], $l['w_unlock']);
	}
	
	echo '<br /><br />';
	echo '<a href="'.pdfLink(3, $test_id, 0, $user_id, '').'" class="xmlbutton" title="'.$l['h_pdf'].'">'.$l['w_pdf'].'</a> ';
	echo '<a href="tce_email_results.php?testid='.$test_id.'&amp;userid='.$user_id.'" class="xmlbutton" title="'.$l['h_email_result'].'">'.$l['w_email_result'].'</a> ';
}
?>
<!-- comma separated list of required fields -->
<input type="hidden" name="ff_required" id="ff_required" value="" />
<input type="hidden" name="ff_required_labels" id="ff_required_labels" value="" />
</div>

<?php
} // end "if (isset($usrtestdata))"
?>

</form>

</div>
<?php

echo '<div class="pagehelp">'.$l['hp_result_user'].'</div>'.K_NEWLINE;
echo '</div>'.K_NEWLINE;

require_once('../code/tce_page_footer.php');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>