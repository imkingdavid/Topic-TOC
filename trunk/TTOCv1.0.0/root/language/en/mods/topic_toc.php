<?php
/**
*
*===================================================================
*
*  Topic TOC Language File
*-------------------------------------------------------------------
*    Script info:
* Version:		1.0.0
* Copyright:	(C) 2010 | David
* License:		http://opensource.org/licenses/gpl-2.0.php | GNU Public License v2
* Package:		phpBB3
*
*===================================================================
*
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	//! MOD Title for install script
	'TOPIC_TOC'					=> 'Topic Table of Contents',
	//! Install
	'INSTALL_TOPIC_TOC'					=> 'Install Topic Table of Contents',
	'INSTALL_TOPIC_TOC_CONFIRM'			=> 'Are you ready to install Topic Table of Contents?',

	//! Uninstall
	'UNINSTALL_TOPIC_TOC'					=> 'Uninstall Topic Table of Contents',
	'UNINSTALL_TOPIC_TOC_CONFIRM'			=> 'Are you ready to uninstall Topic Table of Contents?  All settings and data saved by this mod will be removed! You will have to manually remove any files and undo any file edits, which can be found in the MOD\'s install.xml file.',
	
	//! Update
	'UPDATE_TOPIC_TOC'					=> 'Update Topic Table of Contents',
	'UPDATE_TOPIC_TOC_CONFIRM'			=> 'Are you ready to update Topic Table of Contents?',
	
	// For use when viewing a topic
	'TOC_TITLE'			=> 'Table of Contents',
	'TOC_EXPLAIN'		=> 'The following links will direct you to key points in the topic.',
	'ADD_TO_TOC'		=> 'Add to TOC',
    
    'TTOC_DELETE'       => 'Delete post from TOC',
    'TTOC_UP'           => 'Move post up one on the TOC',
    'TTOC_DOWN'         => 'Move post down one on the TOC',
));