<?php
/***********************************************************************/
/* ATutor															   */
/***********************************************************************/
/* Copyright (c) 2002-2009											   */
/* Adaptive Technology Resource Centre / Inclusive Design Institute	   */
/* http://atutor.ca													   */
/*																	   */
/* This program is free software. You can redistribute it and/or	   */
/* modify it under the terms of the GNU General Public License		   */
/* as published by the Free Software Foundation.					   */
/***********************************************************************/
// $Id$

define(AT_INCLUDE_PATH, '../../../include/');
include(AT_INCLUDE_PATH.'vitals.inc.php');
include(AT_JB_INCLUDE.'classes/Job.class.php');
$_custom_css = $_base_path . AT_JB_BASENAME . 'module.css'; // use a custom stylesheet

//TODO: If not authenticated with user login, quit.

$job = new Job();
$page = intval($_GET['p']);
$page = ($page==0)?1:$page;
$current_job_posts = $job->getMyJobs($page);
$all_job_posts = $job->getMyJobs();

include(AT_INCLUDE_PATH.'header.inc.php');
print_paginator($page, sizeof($all_job_posts), '', AT_JB_ROWS_PER_PAGE);
$savant->assign('job_obj', $job);
$savant->assign('job_posts', $current_job_posts);
$savant->display('employer/jb_home.tmpl.php');
print_paginator($page, sizeof($all_job_posts), '', AT_JB_ROWS_PER_PAGE);
include(AT_INCLUDE_PATH.'footer.inc.php'); 
?>