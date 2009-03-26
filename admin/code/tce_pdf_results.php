<?php
//============================================================+
// File name   : tce_pdf_results.php
// Begin       : 2004-06-10
// Last Update : 2009-02-20
// 
// Description : Create PDF document to display test results   
//               summary for all users.
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
 * Create PDF document to display users' tests results.
 * @package com.tecnick.tcexam.admin
 * @author Nicola Asuni
 * @copyright Copyright &copy; 2004-2009, Nicola Asuni - Tecnick.com S.r.l. - ITALY - www.tecnick.com - info@tecnick.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link www.tecnick.com
 * @since 2004-06-11
 * @param int $_REQUEST['mode'] document mode: 1=all users results, 2=questions stats, 3=detailed report for single user; 4=detailed report for all users; 5=detailed report for all users with only TEXT questions.
 * @param int $_REQUEST['testid'] test ID
 * @param int $_REQUEST['userid'] user ID
 * @param string $_REQUEST['orderfield'] ORDER BY portion of SQL selection query
 */

/**
 */

require_once('../config/tce_config.php');
require_once('../../shared/code/tce_functions_tcecode.php');
require_once('../../shared/code/tce_functions_test.php');
require_once('../../shared/code/tce_functions_test_stats.php');
require_once('../../shared/config/tce_pdf.php');
require_once('../../shared/code/tcpdf.php');
require_once('../code/tce_functions_statistics.php');

if(!isset($_REQUEST['mode'])) {
	$_REQUEST['mode'] = '';
}

$numberfont = "courier";

if (isset($_REQUEST['testid']) AND ($_REQUEST['testid'] > 0)) {
	$test_id = intval($_REQUEST['testid']);
	if (!isset($_REQUEST['email'])) {
		// check user's authorization
		require_once('../../shared/code/tce_authorization.php');
		if (!F_isAuthorizedUser(K_TABLE_TESTS, 'test_id', $test_id, 'test_user_id')) {
			exit;
		}
	}
} else {
	exit;
}

if (isset($_REQUEST['groupid']) AND ($_REQUEST['groupid'] > 0)) {
	$group_id = intval($_REQUEST['groupid']);
} else {
	$group_id = 0;
}

if (isset($_REQUEST['userid']) AND ($_REQUEST['userid'] > 0)) {
	$user_id = intval($_REQUEST['userid']);
} else {
	$user_id = 0;
}

switch ($_REQUEST['mode']) {
	case 1: {
		// all users results
		$doc_title = unhtmlentities($l['t_result_all_users']);
		$doc_description = F_compact_string(unhtmlentities($l['hp_result_alluser']));
		$page_elements = 9;
		$temp_order_field = 'total_score, user_lastname, user_firstname';
		break;
	}
	case 2: {
		// questions stats
		$doc_title = unhtmlentities($l['t_result_questions']);
		$doc_description = F_compact_string(unhtmlentities($l['hp_result_questions']));
		$page_elements = 9;
		$temp_order_field = 'recurrence DESC, average_score DESC';
		break;
	}
	case 3: // detailed report for specific user
	case 4: // detailed report for all users
	case 5: { // detailed report for all users with only open questions
		$doc_title = unhtmlentities($l['t_result_user']);
		$doc_description = F_compact_string(unhtmlentities($l['hp_result_user']));
		$page_elements = 7;
		$temp_order_field = '';
		if (isset($_REQUEST['userid']) AND $_REQUEST['userid']) {
			$user_id = $_REQUEST['userid'];
		}
		$qtype = array('S', 'M', 'T', 'O'); // question types
		break;
	}
	default: {
		echo $l['m_authorization_denied'];
		exit;
	}
}

// set sql select limit
if ($_REQUEST['mode'] == 4) {
	$sql_limit = '';
} else {
	$sql_limit = ' LIMIT 1';
}

// order fields for SQL query
if(isset($_REQUEST['orderfield'])) {
	$full_order_field = urldecode($_REQUEST['orderfield']);
} else {
	$full_order_field = $temp_order_field;
}

// --- create pdf document

if ($l['a_meta_dir'] == 'rtl') {
	$dirlabel = 'L';
	$dirvalue = 'R';
} else {
	$dirlabel = 'R';
	$dirvalue = 'L';
}

$isunicode = (strcasecmp($l['a_meta_charset'], 'UTF-8') == 0);
//create new PDF document (document units are set by default to millimeters)
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, $isunicode); 

// set document information
$pdf->SetCreator('TCExam ver.'.K_TCEXAM_VERSION.'');
$pdf->SetAuthor(PDF_AUTHOR);
$pdf->SetTitle($doc_title);
$pdf->SetSubject($doc_description);
$pdf->SetKeywords("TCExam, ".$doc_title);

$pdf->setHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->setHeaderMargin(PDF_MARGIN_HEADER);
$pdf->setFooterMargin(PDF_MARGIN_FOOTER);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$pdf->setLanguageArray($l); //set language items

//initialize document
$pdf->AliasNbPages();

// calculate some sizes
$page_width = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
$data_cell_height = round((K_CELL_HEIGHT_RATIO * PDF_FONT_SIZE_DATA) / $pdf->getScaleFactor(), 2);
$main_cell_height = round((K_CELL_HEIGHT_RATIO * PDF_FONT_SIZE_MAIN) / $pdf->getScaleFactor(), 2);
$data_cell_width = round($page_width / $page_elements, 2);
$data_cell_width_third = round($data_cell_width / 3, 2);
$data_cell_width_half = round($data_cell_width / 2, 2);


// get test data
$sql = 'SELECT * 
	FROM '.K_TABLE_TESTS.' 
	WHERE test_id='.$test_id.'';
if($r = F_db_query($sql, $db)) {
	if($m = F_db_fetch_array($r)) {
		$test_id = $m['test_id'];
		$test_name = $m['test_name'];
		$test_description = $m['test_description'];
		$test_begin_time = $m['test_begin_time'];
		$test_end_time = $m['test_end_time'];
		$test_duration_time = $m['test_duration_time'];
		$test_ip_range = $m['test_ip_range'];
		$test_score_right = $m['test_score_right'];
		$test_score_wrong = $m['test_score_wrong'];
		$test_score_unanswered = $m['test_score_unanswered'];
		$test_max_score = $m['test_max_score'];
		$test_score_threshold = $m['test_score_threshold'];
		/*
		// Additional test information that could be retrieved if needed
		$test_results_to_users = F_getBoolean($m['test_results_to_users']);
		$test_report_to_users = F_getBoolean($m['test_report_to_users']);
		$test_random_questions_select = F_getBoolean($m['test_random_questions_select']);
		$test_random_questions_order = F_getBoolean($m['test_random_questions_order']);
		$test_random_answers_select = F_getBoolean($m['test_random_answers_select']);
		$test_random_answers_order= F_getBoolean($m['test_random_answers_order']);
		$test_comment_enabled = F_getBoolean($m['test_comment_enabled']);
		$test_menu_enabled = F_getBoolean($m['test_menu_enabled']);
		$test_noanswer_enabled = F_getBoolean($m['test_noanswer_enabled']);
		$test_mcma_radio = F_getBoolean($m['test_mcma_radio']);
		*/
	}
} else {
	F_display_db_error();
}

if (($_REQUEST['mode'] == 3) AND ($user_id > 0)) { // detailed report for single user
	$sql = 'SELECT testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_comment, user_lastname, user_firstname, user_name, SUM(testlog_score) AS test_score, MAX(testlog_change_time) AS test_end_time
		FROM '.K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_USERS.' 
		WHERE testlog_testuser_id=testuser_id
			AND testuser_user_id=user_id
			AND testuser_test_id='.$test_id.'
			AND testuser_user_id='.$user_id.'
			AND testuser_status>0
		GROUP BY testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_comment, user_lastname, user_firstname, user_name
		'.$sql_limit.'';
} else {
	$sql = 'SELECT testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_comment, user_lastname, user_firstname, user_name, SUM(testlog_score) AS test_score, MAX(testlog_change_time) AS test_end_time
		FROM '.K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_USERS.' 
		WHERE testlog_testuser_id=testuser_id
			AND testuser_user_id=user_id
			AND testuser_test_id='.$test_id.' 
			AND testuser_status>0';
	if ($group_id > 0) {
		$sql .= ' AND testuser_user_id IN (
				SELECT usrgrp_user_id
				FROM '.K_TABLE_USERGROUP.' 
				WHERE usrgrp_group_id='.$group_id.'
			)';
	}
	$sql .= ' GROUP BY testuser_id, testuser_test_id, testuser_user_id, testuser_creation_time, testuser_comment, user_lastname, user_firstname, user_name
		ORDER BY testuser_test_id, user_lastname, user_firstname
		'.$sql_limit.'';
}

if($r = F_db_query($sql, $db)) {
	while($m = F_db_fetch_array($r)) {
		$testuser_id = $m['testuser_id'];
		$test_id = $m['testuser_test_id'];
		$user_id = $m['testuser_user_id'];
		$user_lastname = $m['user_lastname'];
		$user_firstname = $m['user_firstname'];
		$user_name = $m['user_name'];
		$test_start_time = $m['testuser_creation_time'];
		$test_score = $m['test_score'];	
		$testuser_comment = F_decode_tcecode($m['testuser_comment']);
		$test_end_time = $m['test_end_time'];
		
		// ------------------------------------------------------------
		// --- start page data ---
		
		$pdf->AddPage();
		
		// set barcode
		$pdf->setBarcode($test_id.':'.$user_id.':'.$test_start_time);
		
		$pdf->SetFillColor(204, 204, 204);
		$pdf->SetLineWidth(0.1);
		$pdf->SetDrawColor(0, 0, 0);
		
		// print document name (title)
		$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA * K_TITLE_MAGNIFICATION);
		$pdf->Cell(0, $main_cell_height * K_TITLE_MAGNIFICATION, $doc_title, 1, 1, 'C', 1);
		
		$pdf->Ln(5);
		
		// display user info
		if ($_REQUEST['mode'] >= 3) {
			
			// add a bookmark
			$pdf->Bookmark($user_lastname." ".$user_firstname." (".$user_name."), ".$test_score." ".F_formatPdfPercentage($test_score / $test_max_score)."", 0, 0);
		
			// calculate some sizes
			$user_elements = 4;
			$user_data_cell_width = round($page_width / $user_elements, 2);
			
			// print table headings
			$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA);
			
			$pdf->Cell($user_data_cell_width, $data_cell_height, $l['w_lastname'], 1, 0, 'C', 1);
			$pdf->Cell($user_data_cell_width, $data_cell_height, $l['w_firstname'], 1, 0, 'C', 1);
			$pdf->Cell($user_data_cell_width, $data_cell_height, $l['w_user'], 1, 0, 'C', 1);
			$pdf->Cell($user_data_cell_width, $data_cell_height, $l['w_score'], 1, 1, 'C', 1);
			
			$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
			
			// minimum required score to pass the exam
			$passmsg = '';
			if ($test_score_threshold > 0) {
				if ($test_score >= $test_score_threshold) {
					$passmsg = ' - '.$l['w_passed'];
				} else {
					$passmsg = ' - '.$l['w_not_passed'];
				}
			}
		
			$pdf->Cell($user_data_cell_width, $data_cell_height, $user_lastname, 1, 0, 'C', 0);
			$pdf->Cell($user_data_cell_width, $data_cell_height, $user_firstname, 1, 0, 'C', 0);
			$pdf->Cell($user_data_cell_width, $data_cell_height, $user_name, 1, 0, 'C', 0);
			$pdf->Cell($user_data_cell_width, $data_cell_height, $test_score." ".F_formatPdfPercentage($test_score / $test_max_score)."".$passmsg."", 1, 1, 'C', 0);
			
			$pdf->Ln(5);
		}
		
		// --- display test info ---
		
		$info_cell_width = round($page_width / 4, 2);
		
		$boxStartY = $pdf->GetY(); // store current Y position
		
		// test name
		$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA * HEAD_MAGNIFICATION);
		$pdf->Cell($page_width, $data_cell_height * HEAD_MAGNIFICATION, $l['w_test'].': '.$test_name, 1, 1, '', 1);
		
		$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
		
		$infoStartY = $pdf->GetY() + 2; // store current Y position
		$pdf->SetY($infoStartY);
		
		$column_names_width = round($info_cell_width * 1.2, 2);
		
		// test start time
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_time_begin'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_start_time, 0, 1, $dirvalue, 0);
		
		// test end time
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_time_end'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_end_time, 0, 1, $dirvalue, 0);
		
		if (!isset($test_end_time) OR ($test_end_time <= 0)) {
			$time_diff = $test_duration_time * 60;
		} else {
			$time_diff = strtotime($test_end_time) - strtotime($test_start_time); //sec
		}
		$time_diff = gmdate("H:i:s", $time_diff);
		
		// elapsed time (time difference)
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_time'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $time_diff, 0, 1, $dirvalue, 0);
		
		// test duration
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_test_time']." [".$l['w_minutes']."]: ", 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_duration_time, 0, 1, $dirvalue, 0);
		
		// authorized IPs
		//$pdf->Cell($column_names_width, $data_cell_height, $l['w_ip_range'].': ', 0, 0, $dirlabel, 0);
		//$pdf->Cell($info_cell_width, $data_cell_height, $test_ip_range, 0, 1, $dirvalue, 0);
		
		// score for right answer
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_score_right'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_score_right, 0, 1, $dirvalue, 0);
		
		// score for wrong answer
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_score_wrong'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_score_wrong, 0, 1, $dirvalue, 0);
		
		// score for missing answer
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_score_unanswered'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_score_unanswered, 0, 1, $dirvalue, 0);
		
		// max score
		$pdf->Cell($column_names_width, $data_cell_height, $l['w_max_score'].': ', 0, 0, $dirlabel, 0);
		$pdf->Cell($info_cell_width, $data_cell_height, $test_max_score, 0, 1, $dirvalue, 0);
		
		if ($test_score_threshold > 0) {
			$pdf->Cell($column_names_width, $data_cell_height, $l['w_test_score_threshold'].': ', 0, 0, $dirlabel, 0);
			$pdf->Cell($info_cell_width, $data_cell_height, $test_score_threshold, 0, 1, $dirvalue, 0);
		}
		
		if ($_REQUEST['mode'] > 2) {
			$usrtestdata = F_getUserTestStat($test_id, $user_id);
			// right answers
			$pdf->Cell($column_names_width, $data_cell_height, $l['w_answers_right'].': ', 0, 0, $dirlabel, 0);
			$pdf->Cell($info_cell_width, $data_cell_height, $usrtestdata['right']." ".F_formatPdfPercentage($usrtestdata['right'] / $usrtestdata['all'])."", 0, 1, $dirvalue, 0);
		}
		/*
		// Additional test information that could be printed if needed
		$test_results_to_users
		$test_report_to_users
		$test_random_questions_select
		$test_random_questions_order
		$test_random_answers_select
		$test_random_answers_order
		$test_comment_enabled
		$test_menu_enabled
		$test_noanswer_enabled
		$test_mcma_radio
		*/
		
		$boxEndY = $pdf->GetY();
		
		$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
		
		// test description
		$pdf->writeHTMLCell(0, ($boxEndY - $infoStartY + 4), (PDF_MARGIN_LEFT + ($info_cell_width * 2)), $infoStartY - 2, $test_description, 1, 0);
		
		$boxEndY = max($boxEndY, $pdf->GetY());
		
		// print box around test info
		$pdf->SetY($boxStartY);
		$pdf->Cell($page_width, ($boxEndY - $boxStartY + 2), '', 1, 1, 'C', 0);
		
		// --- end test info ---
		
		// print user's comments
		if (!empty($testuser_comment)) {
			$pdf->Cell($page_width, $data_cell_height, '', 0, 1, '', 0);
			$pdf->writeHTMLCell($page_width, $data_cell_height, '', '', $testuser_comment, 1, 1);
		}
		
		$pdf->Ln(5);
		
		// display different things by case
		switch ($_REQUEST['mode']) {
			case 1: {
				// all users results
				
				// print table headings
				$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA);
				$pdf->Cell($data_cell_width_third, $data_cell_height, '#', 1, 0, 'C', 1);
				$pdf->Cell(3 * $data_cell_width_third, $data_cell_height, $l['w_score'], 1, 0, 'C', 1);
				$pdf->Cell((2 * $data_cell_width) - (0.5 * $data_cell_width_third), $data_cell_height, $l['w_lastname'], 1, 0, 'C', 1);
				$pdf->Cell((2 * $data_cell_width) - (0.5 * $data_cell_width_third), $data_cell_height, $l['w_firstname'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_user'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $l['w_answers_right_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $l['w_answers_wrong_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $l['w_questions_unanswered_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $l['w_questions_undisplayed_th'], 1, 1, 'C', 1);
				// print table rows
				
				$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
				
				$statsdata = array();
				$statsdata['score'] = array();
				$statsdata['right'] = array();
				$statsdata['wrong'] = array();
				$statsdata['unanswered'] = array();
				$statsdata['undisplayed'] = array();
				$statsdata['unrated'] = array();
					
				$sqlr = 'SELECT testuser_id, user_id, user_lastname, user_firstname, user_name, SUM(testlog_score) AS total_score, MAX(testlog_change_time) AS test_end_time
					FROM '.K_TABLE_TESTS_LOGS.', '.K_TABLE_TEST_USER.', '.K_TABLE_USERS.' 
					WHERE testlog_testuser_id=testuser_id
						AND testuser_user_id=user_id 
						AND testuser_test_id='.$test_id.'';
					if ($group_id > 0) {
						$sqlr .= ' AND testuser_user_id IN (
								SELECT usrgrp_user_id
								FROM '.K_TABLE_USERGROUP.' 
								WHERE usrgrp_group_id='.$group_id.'
							)';
					}
					$sqlr .= ' GROUP BY testuser_id, user_id, user_lastname, user_firstname, user_name
					ORDER BY '.$full_order_field.'';
				if($rr = F_db_query($sqlr, $db)) {
					$itemcount = 0;
					$passed = 0;
					$pdf->SetFillColor(128, 255, 128);
					while($mr = F_db_fetch_array($rr)) {
						$itemcount++;
						$usrtestdata = F_getUserTestStat($test_id, $mr['user_id']);
						$pdf->SetFont($numberfont, '', 6);
						$pfill = 0;
						if ($usrtestdata['score_threshold'] > 0) {
							if ($usrtestdata['score'] >= $usrtestdata['score_threshold']) {
								$pfill = 1;
								$passed++;
							} else {
								$pfill = 0;
							}
						}
						$pdf->Cell($data_cell_width_third, $data_cell_height, $itemcount, 1, 0, 'R', $pfill);
						$pdf->Cell(3 * $data_cell_width_third, $data_cell_height, sprintf('%.3f', round($mr['total_score'], 3)).' '.F_formatPdfPercentage($usrtestdata['score'] / $usrtestdata['max_score']), 1, 0, 'R', 0);
						$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
						$pdf->Cell((2 * $data_cell_width) - (0.5 * $data_cell_width_third), $data_cell_height, $mr['user_lastname'], 1, 0, '', 0);
						$pdf->Cell((2 * $data_cell_width) - (0.5 * $data_cell_width_third), $data_cell_height, $mr['user_firstname'], 1, 0, '', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $mr['user_name'], 1, 0, '', 0);
						$pdf->SetFont($numberfont, '', 6);
						$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $usrtestdata['right'].' '.F_formatPdfPercentage($usrtestdata['right'] / $usrtestdata['all']), 1, 0, 'R', 0);
						$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $usrtestdata['wrong'].' '.F_formatPdfPercentage($usrtestdata['wrong'] / $usrtestdata['all']), 1, 0, 'R', 0);
						$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $usrtestdata['unanswered'].' '.F_formatPdfPercentage($usrtestdata['unanswered'] / $usrtestdata['all']), 1, 0, 'R', 0);
						$pdf->Cell($data_cell_width * 3 / 4, $data_cell_height, $usrtestdata['undisplayed'].' '.F_formatPdfPercentage($usrtestdata['undisplayed'] / $usrtestdata['all']), 1, 1, 'R', 0);
						// collects data for descriptive statistics
						$statsdata['score'][] = $mr['total_score'];
						$statsdata['right'][] = $usrtestdata['right'];
						$statsdata['wrong'][] = $usrtestdata['wrong'];
						$statsdata['unanswered'][] = $usrtestdata['unanswered'];
						$statsdata['undisplayed'][] = $usrtestdata['undisplayed'];
						$statsdata['unrated'][] = $usrtestdata['unrated'];
					}
					$pdf->SetFillColor(204, 204, 204);
				} else {
					F_display_db_error();
				}
				// calculate statistics
				$stats = F_getArrayStatistics($statsdata);
				$excludestat = array('sum', 'variance');
				$calcpercent = array('mean', 'median', 'mode', 'minimum', 'maximum', 'range', 'standard_deviation');
				$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA);
				$pdf->Ln();
				$pdf->Cell($page_width, $data_cell_height, $l['w_statistics'], 1, 1, 'C', 1);
				
				if (($usrtestdata['score_threshold'] > 0) AND ($stats['number']['score'] > 0)) {
					$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
					$pdf->Cell(2 * $data_cell_width, $data_cell_height, $l['w_passed'], 1, 0, $dirlabel, 0);
					$pdf->SetFont($numberfont, '', 6);
					$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, ''.$passed.' '.F_formatPdfPercentage($passed / $stats['number']['score']).'', 1, 0, 'R', 0);
					$pdf->Cell($data_cell_width * 28 / 5, $data_cell_height, '', 1, 1, '', 0);
					$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA);
				}
				// columns headers
				$pdf->Cell(2 * $data_cell_width, $data_cell_height, '', 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $l['w_score'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $l['w_answers_right_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $l['w_answers_wrong_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $l['w_questions_unanswered_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $l['w_questions_undisplayed_th'], 1, 1, 'C', 1);
				
				$pdf->SetFont($numberfont, '', 6);
				foreach ($stats as $row => $columns) {
					if (!in_array($row, $excludestat)) {
						$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
						$pdf->Cell(2 * $data_cell_width, $data_cell_height, $l['w_'.$row], 1, 0, $dirlabel, 0);
						$pdf->SetFont($numberfont, '', 6);
						$cstr = ''.round($columns['score'], 3).'';
						if (in_array($row, $calcpercent)) {
							$cstr .= ' '.F_formatPdfPercentage($columns['score'] / $usrtestdata['max_score']);
						}
						$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $cstr, 1, 0, 'R', 0);
						$cstr = ''.round($columns['right'], 3).'';
						if (in_array($row, $calcpercent)) {
							$cstr .= ' '.F_formatPdfPercentage($columns['right'] / $usrtestdata['all']);
						}
						$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $cstr, 1, 0, 'R', 0);
						$cstr = ''.round($columns['wrong'], 3).'';
						if (in_array($row, $calcpercent)) {
							$cstr .= ' '.F_formatPdfPercentage($columns['wrong'] / $usrtestdata['all']);
						}
						$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $cstr, 1, 0, 'R', 0);
						$cstr = ''.round($columns['unanswered'], 3).'';
						if (in_array($row, $calcpercent)) {
							$cstr .= ' '.F_formatPdfPercentage($columns['unanswered'] / $usrtestdata['all']);
						}
						$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $cstr, 1, 0, 'R', 0);
						$cstr = ''.round($columns['undisplayed'], 3).'';
						if (in_array($row, $calcpercent)) {
							$cstr .= ' '.F_formatPdfPercentage($columns['undisplayed'] / $usrtestdata['all']);
						}
						$pdf->Cell($data_cell_width * 7 / 5, $data_cell_height, $cstr, 1, 1, 'R', 0);
					}
				}
				break;
			}
			case 2: {
				// questions stats
				
				// print table headings
				
				$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA);
				
				$pdf->Cell(2 * $data_cell_width_third, $data_cell_height, '#', 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_recurrence'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_score'], 1, 0, 'C', 1);
				$pdf->Cell(4 * $data_cell_width_third, $data_cell_height, $l['w_answer_time'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_answers_right_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_answers_wrong_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_questions_unanswered_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_questions_undisplayed_th'], 1, 0, 'C', 1);
				$pdf->Cell($data_cell_width, $data_cell_height, $l['w_questions_unrated_th'], 1, 1, 'C', 1);
				$pdf->Ln(2);
				
				// print table rows
				
				$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
				
				// output questions stats
				$sqlr = 'SELECT question_id, question_description, COUNT(question_id) AS recurrence, AVG(testlog_score) AS average_score, AVG(testlog_change_time - testlog_display_time) AS average_time
					FROM '.K_TABLE_TESTS_LOGS.', '.K_TABLE_TEST_USER.', '.K_TABLE_QUESTIONS.' 
					WHERE testlog_testuser_id=testuser_id
						AND testlog_question_id=question_id 
						AND testuser_test_id='.$test_id.'
					GROUP BY question_id, question_description 
					ORDER BY '.$full_order_field.'';
				if($rr = F_db_query($sqlr, $db)) {
					$itemcount = 1;
					while($mr = F_db_fetch_array($rr)) {
						
						$qsttestdata = F_getQuestionTestStat($test_id, $mr['question_id']);
						
						$pdf->Cell(2 * $data_cell_width_third, $data_cell_height, $itemcount, 1, 0, 'R', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $mr['recurrence'], 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, number_format($mr['average_score'], 3, '.', ''), 1, 0, 'C', 0);
						if (stripos($mr['average_time'], ':') !== FALSE) {
							// PostgreSQL returns formatted time, while MySQL returns the number of seconds
							$mr['average_time'] = strtotime($mr['average_time']);
						}
						$pdf->Cell(4 * $data_cell_width_third, $data_cell_height, date("i:s", $mr['average_time']), 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $qsttestdata['right'], 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $qsttestdata['wrong'], 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $qsttestdata['unanswered'], 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $qsttestdata['undisplayed'], 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $qsttestdata['unrated'], 1, 1, 'C', 0);
						$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + (2 * $data_cell_width_third)), $pdf->GetY(), F_decode_tcecode($mr['question_description']), 1, 1);
						
						//$pdf->Ln(2);
						
						$itemcount++;
						
						// answers statistics
						
						$sqla = 'SELECT *
							FROM '.K_TABLE_ANSWERS.' 
							WHERE answer_question_id='.$mr['question_id'].'
							ORDER BY answer_id';
						if($ra = F_db_query($sqla, $db)) {
							$answcount = 1;
							while($ma = F_db_fetch_array($ra)) {
								
								$right_answers = F_count_rows(K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_ANSWERS.', '.K_TABLE_LOG_ANSWER, 'WHERE answer_id='.$ma['answer_id'].' AND logansw_answer_id=answer_id AND logansw_testlog_id=testlog_id AND testlog_testuser_id=testuser_id AND testuser_test_id='.$test_id.' AND testlog_question_id='.$mr['question_id'].' AND ((answer_isright=\'0\' AND logansw_selected=0) OR (answer_isright=\'1\' AND logansw_selected=1) OR (answer_position=logansw_position))');
				
								$wrong_answers = F_count_rows(K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_ANSWERS.', '.K_TABLE_LOG_ANSWER, 'WHERE answer_id='.$ma['answer_id'].' AND logansw_answer_id=answer_id AND logansw_testlog_id=testlog_id AND testlog_testuser_id=testuser_id AND testuser_test_id='.$test_id.' AND testlog_question_id='.$mr['question_id'].' AND ((answer_isright=\'0\' AND logansw_selected=1) OR (answer_isright=\'1\' AND logansw_selected=0)) AND (answer_position!=logansw_position)');
				
								$unanswered = F_count_rows(K_TABLE_TEST_USER.', '.K_TABLE_TESTS_LOGS.', '.K_TABLE_ANSWERS.', '.K_TABLE_LOG_ANSWER, 'WHERE answer_id='.$ma['answer_id'].' AND logansw_answer_id=answer_id AND logansw_testlog_id=testlog_id AND testlog_testuser_id=testuser_id AND testuser_test_id='.$test_id.' AND testlog_question_id='.$mr['question_id'].' AND logansw_selected=-1');
				
								$pdf->Cell(2 * $data_cell_width_third, $data_cell_height, '', '', 0, 'R', 0);
								$pdf->Cell($data_cell_width_third, $data_cell_height, $answcount, 1, 0, 'R', 0);
								$pdf->Cell($data_cell_width - $data_cell_width_third, $data_cell_height, ''.($right_answers + $wrong_answers + $unanswered).'', 1, 0, 'C', 0);
								$pdf->Cell(2 * $data_cell_width + $data_cell_width_third, $data_cell_height, '', 1, 0, 'C', 0);
								
								$pdf->Cell($data_cell_width, $data_cell_height, $right_answers, 1, 0, 'C', 0);
								$pdf->Cell($data_cell_width, $data_cell_height, $wrong_answers, 1, 0, 'C', 0);
								$pdf->Cell($data_cell_width, $data_cell_height, $unanswered, 1, 0, 'C', 0);
								$pdf->Cell(2 * $data_cell_width, $data_cell_height, '', 1, 1, 'C', 0);
						$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + (3 * $data_cell_width_third)), $pdf->GetY(), F_decode_tcecode($ma['answer_description']), 1, 1);
								$answcount++;
							}
						} else {
							F_display_db_error();
						}
						$pdf->Ln(2);
					}
				} else {
					F_display_db_error();
				}
				break;
			}
			case 3: // detailed report for single user
			case 4: // detailed report for all users
			case 5: { // detailed report for all users with only open questions
				$sqlq = 'SELECT * 
					FROM '.K_TABLE_QUESTIONS.', '.K_TABLE_TESTS_LOGS.' 
					WHERE question_id=testlog_question_id 
					AND testlog_testuser_id='.$testuser_id.'';
				if ($_REQUEST['mode'] == 5) {
					// display only TEXT questions
					$sqlq .= ' AND question_type=3';
				}
				$sqlq .= ' ORDER BY testlog_id';
				if($rq = F_db_query($sqlq, $db)) {
					
					$pdf->SetFont(PDF_FONT_NAME_DATA, 'B', PDF_FONT_SIZE_DATA);
					
					$pdf->Cell($data_cell_width_third, $data_cell_height, '#', 1, 0, 'C', 1);
					$pdf->Cell($data_cell_width, $data_cell_height, $l['w_score'], 1, 0, 'C', 1);
					$pdf->Cell($data_cell_width, $data_cell_height, $l['w_ip'], 1, 0, 'C', 1);
					$pdf->Cell($data_cell_width + $data_cell_width_third, $data_cell_height, $l['w_start'].' ['.$l['w_time_hhmmss'].']', 1, 0, 'C', 1);
					$pdf->Cell($data_cell_width + $data_cell_width_third, $data_cell_height, $l['w_end'].' ['.$l['w_time_hhmmss'].']', 1, 0, 'C', 1);
					$pdf->Cell($data_cell_width, $data_cell_height, $l['w_time'].' ['.$l['w_time_mmss'].']', 1, 0, 'C', 1);
					$pdf->Cell($data_cell_width, $data_cell_height, $l['w_reaction'].' [sec]', 1, 1, 'C', 1);
					$pdf->Ln($data_cell_height);
					
					// print table rows
					
					$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
					$itemcount = 1;
					
					while($mq = F_db_fetch_array($rq)) {
						$pdf->Cell($data_cell_width_third, $data_cell_height, $itemcount.' '.$qtype[($mq['question_type']-1)], 1, 0, 'R', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $mq['testlog_score'], 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, getIpAsInt($mq['testlog_user_ip']), 1, 0, 'C', 0);
						if (isset($mq['testlog_display_time']) AND (strlen($mq['testlog_display_time']) > 0)) {
							$display_time =  substr($mq['testlog_display_time'], 11, 8);
						} else {
							$display_time =  '--:--:--';
						}
						if (isset($mq['testlog_change_time']) AND (strlen($mq['testlog_change_time']) > 0)) {
							$change_time = substr($mq['testlog_change_time'], 11, 8);
						} else {
							$change_time = '--:--:--';
						}
						if (isset($mq['testlog_display_time']) AND isset($mq['testlog_change_time'])) {
							$diff_time = date('i:s', (strtotime($mq['testlog_change_time']) - strtotime($mq['testlog_display_time'])));
						} else {
							$diff_time = '--:--';
						}
						if (isset($mq['testlog_reaction_time']) AND (strlen($mq['testlog_reaction_time']) > 0)) {
							$reaction_time =  ($mq['testlog_reaction_time'] / 1000);
						} else {
							$reaction_time =  '';
						}
						$pdf->Cell($data_cell_width + $data_cell_width_third, $data_cell_height, $display_time, 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width + $data_cell_width_third, $data_cell_height, $change_time, 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $diff_time, 1, 0, 'C', 0);
						$pdf->Cell($data_cell_width, $data_cell_height, $reaction_time, 1, 1, 'C', 0);
						
						$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + $data_cell_width_third), $pdf->GetY(), F_decode_tcecode($mq['question_description']), 1, 1);
						if (K_ENABLE_QUESTION_EXPLANATION AND !empty($mq['question_explanation'])) {
							$pdf->Cell($data_cell_width_third, $data_cell_height, '', 0, 0, 'C', 0);
							$pdf->SetFont('', 'BIU');
							$pdf->Cell(0, $data_cell_height, $l['w_explanation'], 'LTR', 1, '', 0, '', 0);
							$pdf->SetFont('', '');
							$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + $data_cell_width_third), $pdf->GetY(), F_decode_tcecode($mq['question_explanation']), 'LRB', 1, '', '');
						}
						
						if ($mq['question_type'] == 3) {
							// free-text question - print user text answer
							$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + (2 * $data_cell_width_third)), $pdf->GetY(), F_decode_tcecode($mq['testlog_answer_text']), 1, 1);
						} else {
							// display each answer option
							$sqla = 'SELECT *
								FROM '.K_TABLE_LOG_ANSWER.', '.K_TABLE_ANSWERS.'
								WHERE logansw_answer_id=answer_id
									AND logansw_testlog_id=\''.$mq['testlog_id'].'\'
								ORDER BY logansw_order';
							if($ra = F_db_query($sqla, $db)) {
								$idx = 0; // count items
								while($ma = F_db_fetch_array($ra)) {
									$posfill = 0;
									$idx++;
									$pdf->Cell($data_cell_width_third, $data_cell_height, '', 0, 0, 'C', 0);
									if ($mq['question_type'] == 4) {
										if ($ma['logansw_position'] > 0) {
											if ($ma['logansw_position'] == $ma['answer_position']) {
												$posfill = 1;
												$pdf->Cell($data_cell_width_third, $data_cell_height, $ma['logansw_position'], 1, 0, 'C', 1);
											} else {
												$pdf->Cell($data_cell_width_third, $data_cell_height, $ma['logansw_position'], 1, 0, 'C', 0);
											}
										} else {
											$pdf->Cell($data_cell_width_third, $data_cell_height, ' ', 1, 0, 'C', 0);
										}
									} elseif ($ma['logansw_selected'] > 0) {
										// selected
										if (F_getBoolean($ma['answer_isright'])) {
											$pdf->Cell($data_cell_width_third, $data_cell_height, '+', 1, 0, 'C', 1);
										} else {
											$pdf->Cell($data_cell_width_third, $data_cell_height, '-', 1, 0, 'C', 1);
										}
									} elseif ($mq['question_type'] == 1) {
										// MCSA
										$pdf->Cell($data_cell_width_third, $data_cell_height, ' ', 1, 0, 'C', 0);
									} else {
										if ($ma['logansw_selected'] == 0) {
											// unselected
											if (F_getBoolean($ma['answer_isright'])) {
												$pdf->Cell($data_cell_width_third, $data_cell_height, '-', 1, 0, 'C', 0);
											} else {
												$pdf->Cell($data_cell_width_third, $data_cell_height, '+', 1, 0, 'C', 0);
											}
										} else {
											// no answer
											$pdf->Cell($data_cell_width_third, $data_cell_height, ' ', 1, 0, 'C', 0);
										}
									}
									if ($mq['question_type'] == 4) {
											$pdf->Cell($data_cell_width_third, $data_cell_height, $ma['answer_position'], 1, 0, 'C', $posfill);
									} elseif (F_getBoolean($ma['answer_isright'])) {
										$pdf->Cell($data_cell_width_third, $data_cell_height, $idx, 1, 0, 'C', 1);
									} else {
										$pdf->Cell($data_cell_width_third, $data_cell_height, $idx, 1, 0, 'C', 0);
									}
									$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + $data_cell_width), $pdf->GetY(), F_decode_tcecode($ma['answer_description']), 'LRTB', 1);
									if (K_ENABLE_ANSWER_EXPLANATION AND !empty($ma['answer_explanation'])) {
										$pdf->Cell((3 * $data_cell_width_third), $data_cell_height, '', 0, 0, 'C', 0);
										$pdf->SetFont('', 'BIU');
										$pdf->Cell(0, $data_cell_height, $l['w_explanation'], 'LTR', 1, '', 0, '', 0);
										$pdf->SetFont('', '');
										$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + (3 * $data_cell_width_third)), $pdf->GetY(), F_decode_tcecode($ma['answer_explanation']), 'LRB', 1, '', '');
									}
								}
							} else {
								F_display_db_error();
							}
						} // end multiple answers
						if (strlen($mq['testlog_comment']) > 0) {
							// teacher / supervisor comment
							$pdf->SetTextColor(255, 0, 0); 
							$pdf->writeHTMLCell(0, $data_cell_height, (PDF_MARGIN_LEFT + (2 * $data_cell_width_third)), $pdf->GetY(), F_decode_tcecode($mq['testlog_comment']), 'LRTB', 1);
							$pdf->SetTextColor(0, 0, 0); 
						}
						$pdf->Ln($data_cell_height);
						$itemcount++;
					}
				} else {
					F_display_db_error();
				}
				break;
			}
		}
		// END page data
		// ------------------------------------------------------------
	}
} else {
	F_display_db_error();
}

// Send PDF output
$pdf->Output('tcexam_results_'.$test_id.'_'.date('YmdHi', strtotime($test_begin_time)).'.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>