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
abstract class hook_ttoc
{
    /**
     * Register the hooks to load at the appropriate times
     *
     * @param phpbb_hook $hook The phpBB Hook object
     * @return void
     */
    static function ttoc_start(phpbb_hook $hook)
    {
        //don't break the UMIL install
        if (!defined('UMIL_AUTO') && !defined('IN_INSTALL'))
        {
            $hook->register(array('phpbb_user_session_handler', 'hook_ttoc::setup');
            $hook->register(array('template', 'display'), 'hook_ttoc::go');
        }
    }
    
    static function ttoc_setup()
    {
        $user->add_lang('mods/topic_toc');
        if (!defined('TTOC_TABLE'))
        {
            define('TTOC_TABLE', $table_prefix . 'ttoc');
        }
        if (!class_exists('topicTOC'))
    	{
    		include($phpbb_root_path . 'includes/mods/functions_topic_toc.' . $phpEx);
    	}
    }
    /**
     * Do all of the stuff needed
     *
     * @param void
     * @return void
     */
    static function go()
    {
    	global $phpbb_root_path, $phpEx, $user, $table_prefix;
    	
    	if (!empty($_GET['t']))
        {
            $topic = request_var('t', 0);
            $ttoc = new topicTOC($topic);
            
            if (!empty($_GET['ttoc_act']) && !empty($_GET['i']))
            {
                $action = request_var('ttoc_act', '', true);
                $id = request_var('i', 0);
                switch ($action)
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
                // Of course, we'll need to also inject the URL to add a post to the TOC
                // into the postrow for access within viewtopic
                foreach ($template->_tpldata['.postrow'] as $postrow)
                {
                    $postrow['U_TTOC_ADD'] = append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('t' => $this->topic_id, 'p' => $postrow['POST_ID'], 'ttoc_act' => 'add', 'i' => $row['id']));
                }
            }
        }
    }
}
hook_ttoc::start($phpbb_hook);