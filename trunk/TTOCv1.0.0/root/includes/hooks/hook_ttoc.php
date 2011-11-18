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
    static function start(phpbb_hook $hook)
    {
        global $user;
        $user->add_lang('mods/topic_toc');
        //don't break the UMIL install
        if (!defined('UMIL_AUTO') && !defined('IN_INSTALL'))
        {
            $hook->register('phpbb_user_session_handler', 'hook_ttoc::setup');
    		$hook->register(array('template', 'display'), 'hook_ttoc::go');
        }
    }
    
	/**
	 * Define the DB table constant and include the class, if needed
	 *
	 * @return void
	 */
    static function setup()
    {
		global $table_prefix, $phpbb_root_path, $phpEx;
        if (!defined('TTOC_TABLE'))
        {
            define('TTOC_TABLE', $table_prefix . 'ttoc');
        }
        if (!class_exists('topicTOC'))
    	{
    		include($phpbb_root_path . 'includes/functions_topic_toc.' . $phpEx);
    	}
    }
    /**
     * Do all of the stuff needed
     *
     * @return bool
     */
    static function go()
    {
    	global $phpbb_root_path, $phpEx, $user, $table_prefix, $db, $template;
    	$topic = request_var('t', 0);
		$post = request_var('t', 0);
		$action = request_var('ttoc_act', '', true);
		$id = request_var('i', 0);
		
        
    	if (!empty($topic) || !empty($post))
        {
			// use post ID to get topic ID
            // if the topic ID is not provided
			if(empty($topic))
			{
				$sql = 'SELECT topic_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post;
				$result = $db->sql_query($sql);
				$topic = $db->sql_fetchfield('topic_id');
                $db->sql_freeresult($result);
				if(empty($topic))
				{
					return false;
				}
			}
            $ttoc = new topicTOC($topic);
            if (!empty($action) && !empty($id))
            {
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
                        return false;
                    break;
                }
			}
            // Because everything was done before this was called,
            // we don't have to reload the page yet again to see the changes.
            // Let's go ahead and set the template variables.
			$topic_starter = $ttoc->display();
			// Of course, we'll need to also inject the URL to add a post to the TOC
			// into the postrow for access within viewtopic
			foreach ($template->_tpldata['postrow'] as $postrow)
			{
                $postrow['S_TTOC'] = ($auth->acl_get('m_') || ($user->data['user_id'] == $topic_starter)) ? true : false,
				$postrow['U_TTOC_ADD'] = append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('t' => $ttoc->topic_id, 'p' => $postrow['POST_ID'], 'ttoc_act' => 'add', 'i' => $id));
			}
            return true;
        }
    }
}
hook_ttoc::start($phpbb_hook);
