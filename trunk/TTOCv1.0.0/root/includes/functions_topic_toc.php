<?php
/**
*
*===================================================================
*
*  Topic Table of Contents Class and Methods
*-------------------------------------------------------------------
*    Script info:
* Version:        1.0.0
* Copyright:    (C) 2010 | David King
* License:    	http://opensource.org/licenses/gpl-2.0.php | GNU Public License v2
* Package:		phpBB3
*
*===================================================================
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * @class TopicTOC class for the Topic Table of Contents MOD by imkingdavid.
 */

class TopicTOC
{
	/**
	 * @var Topic ID
	 */
	public $topic_id = 0;
	/**
	 * @var Items within the TOC
	 */
	private $items = array();

	/**
	 * Constructor method.
	 */
	function __construct($topic_id)
	{
		$this->topic_id = $topic_id;
	}

	/**
 	 * Add a post to the table of contents for the topic
	 * @param int $post_id Integer ID of the post to add to the TOC
	 * @param string $title What to name the link; If blank, use the post title
	 * @param int $order The position of this item in the order of the TOC; default to end
	 *
	 * @note this uses either 4 or 6 queries depending on the circumstances.
	 * That's ok because it's not being run on more than one page, so it won't affect load time.
	 */
	function add($post_id, $title = '', $order = 0)
	{
		global $db;
        if (empty($post_id))
        {
            return false;
        }
		// make sure the post is in the specified topic
		$sql = 'SELECT post_subject FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post_id . ' AND topic_id = ' . (int) $this->topic_id;
		$result = $db->sql_query($sql);
		$post_title = $db->sql_fetchfield('post_subject');
		$db->sql_freeresult($result);
		//is the post in the topic?
		if (empty($post_title))
		{
			return false;
		}
		
		// get the current list of TOC for the current topic
		$sql = 'SELECT location,post FROM ' . TTOC_TABLE . ' WHERE topic = ' . (int) $this->topic_id;
		$result = $db->sql_query($sql);
		$last_pos = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			// Don't allow the post to be added to the TOC twice
			if ($row['post'] == $post_id)
			{
				// no good
				return false;
			}
			// All good? Get the position of the last TOC item
			if ($row['location'] > $last_pos)
			{
				$last_pos = $row['location'];
			}
		}
		$db->sql_freeresult($result);
		
		// finalize the variables that may have been sent as args
		$title = (empty($title)) ? $post_title : $title;
		$order = ($order) ? $order : ($last_pos + 1);
		// if the item is being placed before other items reorder them
		$reordered = false;
		if ($order < $last_pos)
		{
			// increment all TOC items order by one if they come after the current item's slot
			$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = (location + 1) WHERE location > ' . (int) $order;
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
			// NOTE this will need to be undone if insertion fails
			// set this variable so we know this was run
			$reordered = true;
		}
		$sql_ary = array(
			'topic'	=> (int) $this->topic_id,
			'post'	=> (int) $post_id,
			'title'		=> $title,
			'location'		=> (int) $order,
		);
		$sql = 'INSERT INTO ' . TTOC_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		// Because we changed the order of the items, make sure it inserted properly
		// Otherwise, we get could weird issues with ordering later on.
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE post = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$item = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		// if there is no item, it must not have been inserted.
		// roll back any changes made
		if (empty($item) && $reordered)
		{
			$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = (location - 1) WHERE topic = ' . $this->topic_id . ' AND location > ' . (int) $order;
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
			// it ultimately didn't work :(
			return false;
		}
		// yay! it worked! :)
		return true;
	}
	
	/**
     * Change the order of an item in the TOC, either up or down by one
     * 
     * @var string $direction Either 'up' or 'down'
     * @var int $item_id Numerical ID of the TOC item to move
     *
     * @note 'UP' is DECREASING and 'DOWN' is INCREASING. UP and DOWN refer to
     * 		 the item's location in the list, which is ordered ascending from 1, going up.
     */
	function reorder($direction = 'up', $item_id = 0)
	{
		global $db;
		// make sure the arguments are good to go
		if (empty($item_id) || !is_int($item_id) || !in_array($direction, array('up','down')))
		{
			return false;
		}


		// The next three queries do the following:
		// 1) get the highest location/order in the table
		// 2) get the item's current location.
		// 3) get the consecutive item's id (up or down, depending on direction of reorder)

		
		$sql = 'SELECT MAX(location) AS max_order FROM ' . TTOC_TABLE . ' WHERE topic = ' . $this->topic_id;
		$result = $db->sql_query($sql);
		$max_order = $db->sql_fetchfield('max_order');
		$db->sql_freeresult($result);
		
		$sql = 'SELECT location FROM ' . TTOC_TABLE . ' WHERE id = ' . (int) $item_id;
		$result = $db->sql_query($sql);
		$order = $db->sql_fetchfield('location');
		$db->sql_freeresult($result);
		if (empty($order))
		{
			return false;
		}
		$sql = 'SELECT id FROM ' . TTOC_TABLE . ' WHERE location = (' . $order . ' ' . (($direction == 'up') ? '-' : '+') . ' 1) AND topic = ' . (int) $this->topic_id;
		$result = $db->sql_query($sql);
		$consecutive = $db->sql_fetchfield('id');
		$db->sql_freeresult($result);

		$topic_id = $this->topic_id;

		// If the following is confusing, note the @note in the function header block
		switch($direction)
		{
			case 'up':
				$new_order = $order - 1;
				// if the new order is less than 1
				if ($max_order < 1)
				{
					return false;
				}
			break;

			case 'down':
				$new_order = $order + 1;
				// if the new order is greater than the maximum
				if ($max_order > $max_order)
				{
					return false;
				}
			break;
		}

		// Now we need to update the order
		// First, depending on the operation, we do the opposite
		// operation to the item above or below the current item
		$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = ' . $order . ' WHERE id = ' . (int) $consecutive;
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		// Then we update the current item's order
		$sql = 'UPDATE ' . TTOC_TABLE . '
				SET location = ' . $new_order . '
				WHERE id = ' . $item_id;
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		return true;
	}
    	
	/**
	 * Delete an item from the table of contents
     *
     * @var int $id ID of either the post that is related to the TOC item to delete or the item's ID itself
     * @var bool $post_id If true, use the $id given as the post id; false (default) uses $id as the item's id
	 */
	function delete($id, $post_id = false)
	{
		global $db;
        if (empty($id))
        {
            return false;
        }
        $where = ($post_id) ? ' post ' : ' id ';
		// First, make sure the item is there
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE' . $where . '= ' . (int) $id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if (empty($row))
		{
				return false;
		}
		
		// now remove it
		$sql = 'DELETE FROM ' . TTOC_TABLE . ' WHERE' . $where . '= ' . (int) $id;
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);

		// finally, reorder the subsequent items down by 1 to make up for the item being gone
        // otherwise there could be some odd errors with ordering later on.
		$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = (location - 1) WHERE location > ' . (int) $row['location'] . ' AND topic = ' . (int) $this->topic_id;
		$result = $db->sql_query($sql);
		$db->sql_query($sql);
		$db->sql_freeresult($result);
		
		// yay!
		return true;
	}
	/**
	 * Send the variables to the template to display the TOC
	 */
	function display()
	{
		global $template, $user, $auth, $phpbb_root_path, $db, $phpEx;
		
		// We need to get the user ID of the topic starter to see if the current user can modify the TOC
		$sql = 'SELECT topic_poster,forum_id FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int) $this->topic_id;
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $topic_starter = $row['topic_poster'];
        $forum_id = $row['forum_id'];
        $db->sql_freeresult($result);

		// The item block
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE topic = ' . $this->topic_id . ' ORDER BY location ASC';
		$result = $db->sql_query($sql);
		$total = 0;
        $items = $posts = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$total++;
			$template->assign_block_vars('ttoc', array(
				'TITLE'		    => $row['title'],
				'LOCATION'		=> $row['location'],
				'URL'	    	=> append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('f' => $forum_id, 't' => $this->topic_id, 'p' => $row['post'])) . '#p' . $row['post'],
				'S_REORDER'	    => ($auth->acl_get('m_') || ($user->data['user_id'] == $topic_starter)) ? true : false,
                
                'U_ORDER_UP'    => append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('f' => $forum_id, 't' => $this->topic_id, 'p' => $row['post'], 'ttoc_act' => 'up', 'i' => $row['id'])),
                'U_ORDER_DOWN'  => append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('f' => $forum_id, 't' => $this->topic_id, 'p' => $row['post'], 'ttoc_act' => 'down', 'i' => $row['id'])),
                'U_DELETE'      => append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('f' => $forum_id, 't' => $this->topic_id, 'p' => $row['post'], 'ttoc_act' => 'delete', 'i' => $row['id'])),
			));
            $posts[] = $row['post'];
            
		}
        // non loop-specific variables
        $template->assign_vars(array(
            'IMG_DELETE'    => $phpbb_root_path . 'images/icons/ttoc/delete.png',
            'IMG_UP'        => $phpbb_root_path . 'images/icons/ttoc/bullet_up.png',
            'IMG_DOWN'      => $phpbb_root_path . 'images/icons/ttoc/bullet_down.png',
            'TOTAL_ITEMS'   => $total,
            'S_TTOC'        => (($auth->acl_get('m_') || ($user->data['user_id'] == $info['topic_starter']))) ? true : false,
        ));
		$db->sql_freeresult($result);
        
        return array('topic_starter' => $topic_starter, 'posts' => $posts);
	}
}