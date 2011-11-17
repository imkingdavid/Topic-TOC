<?php
/**
*
* @author David King (king.davidp@gmail.com) http://www.thedavidking.com
*
* @package phpBB3
* @copyright (c) 2010 David King
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
    exit;
}
// Hooks allow you to include stuff without actually editing the main files as much
function ttoc_start()
{
	global $phpbb_root_path, $phpEx, $user, $table_prefix;
	$user->add_lang('mods/topic_toc');
    if(!defined('TTOC_TABLE'))
    {
        define('TTOC_TABLE', $table_prefix . 'ttoc');
    }
	if (!class_exists('topicTOC'))
	{
		include($phpbb_root_path . 'includes/mods/functions_topic_toc.' . $phpEx);
	}
	if(!empty($_GET['t']))
    {
        $topic = request_var('t', 0);
        $ttoc = new topicTOC($topic);
        
        if(!empty($_GET['ttoc_act']) && !empty($_GET['i']))
        {
            $action = request_var('ttoc_act', '', true);
            $id = request_var('i', 0);
            switch($action)
            {
                case 'up':
                case 'down':
                    $ttoc->reorder($action, $id);
                break;
                
                case 'delete':
                    $ttoc->delete($id);
                break;
                
                case 'add':
                    // in this case, $id should hold the post ID.
                    $ttoc->add($id);
                break;
                
                default:
                break;
            }
        // Because everything was done before this was called,
        // we don't have to reload the page yet again to see the changes.
        // Let's go ahead and set the template variables.
        $ttoc->display();
    }
}
//don't break the UMIL install
if(!defined('UMIL_AUTO') && !defined('IN_INSTALL'))
{
	$phpbb_hook->register('phpbb_user_session_handler', 'ttoc_start');
}