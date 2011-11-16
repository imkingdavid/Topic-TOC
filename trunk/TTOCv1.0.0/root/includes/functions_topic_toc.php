<?php
/**
*
*===================================================================
*
*  Topic Table of Contents Class and Methods
*-------------------------------------------------------------------
*    Script info:
* Version:		1.0.0
* Copyright:	(C) 2010 | David King
* License:		http://opensource.org/licenses/gpl-2.0.php | GNU Public License v2
* Package:		phpBB3
*
*===================================================================
*
*/

/**
* @ignore
*/
if(!defined('IN_PHPBB'))
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
	private $topic_id = 0;
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
	 * @param int $order The position of this item in the order of the TOC
	 *
	 * @note this uses either 4 or 6 queries depending on the circumstances.
	 * That's ok because it's not being run on more than one page, so it won't affect load time.
	 */
	function add($post_id, $title = '', $order = 0)
	{
		global $db;
		// make sure the post is in the specified topic
		$sql = 'SELECT post_title FROM ' . TOPICS_TABLE . ' WHERE post_id = ' . (int) $post_id . ' AND topic_id = ' . (int) $this->topic_id;
		$result = $db->sql_query($sql);
		$post_title = $db->sql_fetchfield('post_title');
		$db->sql_freeresult($result);
		//is the post in the topic?
		if(empty($post_title))
		{
			return false;
		}
		
		// get the current list of TOC for the current topic
		$sql = 'SELECT location,post_id FROM ' . TTOC_TABLE . ' WHERE topic_id = ' . (int) $this->topic_id;
		$result = $db->sql_query($sql);
		$last_pos = 0;
		while($row = $db->sql_fetchrow($result))
		{
			// Don't allow the post to be added to the TOC twice
			if($row['post_id'] == $post_id)
			{
				// no good
				return false;
			}
			// All good? Get the position of the last TOC item
			if($row['location'] > $last_pos)
			{
				$last_pos = $row['location'];
			}
		}
		$db->sql_freeresult($result);
		
		// finalize the variables
		$title = (empty($title)) ? $post_title : $title;
		$order = ($order) ? $order : ($last_pos + 1);
		// if the item is being placed before other items reorder them
		if($order < $last_pos)
		{
			// increment all TOC items order by one if they come after the current item's slot
			$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = (location + 1) WHERE location > ' . (int) $order;
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
			// NOTE this will need to be undone if insertion fails
			// set this variable so we know this was run
			$reordered = true;
		}
		// now add it to the toc table
		$sql_ary = array(
			// what topic is this in?
			'topic_id'	=> (int) $this->topic_id,
			// which post to link to
			'post_id'	=> (int) $post_id,
			// default to the post title if no other title is given
			'title'		=> $title,
			// what order to put it in
			'location'		=> (int) $order,
		);
		$sql = 'INSERT INTO ' . TTOC_TABLE . ' ' . $db->sql_build_query('INSERT', $sql_ary);
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		// Because we changed the order of the items, make sure it inserted properly
		// Otherwise, we get could weird issues with ordering later on.
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE post_id = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$item = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		// if there is no item, it must not have been inserted.
		// roll back any changes made
		if(empty($item['title']) && $reordered)
		{
			$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = (location - 1) WHERE topic_id = ' . $this->topic_id . ' AND location > ' . (int) $order;
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
			// it ultimately didn't work :(
			return false;
		}
		// yay! it worked! :)
		return true;
	}
	
	// move an item up or down one spot
	// @todo make it work
	function reorder($direction = 'up', $item_id = 0)
	{
		global $db;
		// make sure the arguments are good to go
		if(empty($item_id) || !is_int($item_id) || !in_array($direction, array('up','down')))
		{
			return false;
		}

		
		$subselect = 'SELECT item_id FROM ' . TTOC_TABLE . ' WHERE location = (c_order ' . (($direction == 'up') ? '+' : '-') . ' 1) AND topic_id = ' . (int) $this->topic_id;
		// first we have to select the item's current position
		$sql = 'SELECT location AS c_order, max_order AS (SELECT MAX(location) FROM ' . TTOC_TABLE . ' WHERE topic_id = ' . $this->topic_id . '), consecutive AS (' . $subselect . ') FROM ' . TTOC_TABLE . ' WHERE item_id = ' . (int) $item_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if(empty($row))
		{
			return false;
		}
		$topic_id = $this->topic_id;
		$order = $row['c_order'];
		switch($direction)
		{
			case 'up':
				$order++;
				// if the requested order is greater than the maximum order
				if($row['max_order'] > $order)
				{
					return false;
				}
			break;

			case 'down':
				$order--;
				// if the requested order is less than the maximum order
				if($row['max_order'] < $order)
				{
					return false;
				}
			break;
		}

		// Now we need to update the order
		// First, depending on the operation, we do the opposite
		// operation to the item above or below the current item
		$sql = 'UPDATE ' . TTOC_TABLE . '
				SET location = (location ' . (($direction == 'up') ? '-' : '+') . ' 1)
				WHERE item_id = ' . (int) $row['consecutive'];
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		// Then we update the current item's order
		$sql = 'UPDATE ' . TTOC_TABLE . '
				SET location = ' . $order . '
				WHERE item_id = ' . $item_id;
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		return true;
	}
	
	/**
	 * Delete an item from the table of contents
	 */
	function delete($post_id)
	{
		global $db;
		// First, make sure the item is there
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE post_id = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if(empty($row))
		{
				return false;
		}
		
		// now remove it
		$sql = 'DELETE FROM ' . TTOC_TABLE . ' WHERE post_id = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);

		// finally, reorder the subsequent items down by 1 to make up for the item being gone
		$sql = 'UPDATE ' . TTOC_TABLE . ' SET location = (location - 1) WHERE location > ' . (int) $row['location'] . ' AND topic_id = ' . (int) $this->topic_id;
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
		global $template, $user, $auth, $phpbb_root_path;
		
		// We need to get the user ID of the topic starter to see if the current user can modify the TOC
		$sql = 'SELECT topic_starter FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int) $this->topic_id;

		// The item block
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE topic_id = ' . $this->topic_id . ' ORDER BY location ASC';
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result))
		{
				$template->assign_block_vars('ttoc', array(
						'TITLE'		=> $row['title'],
						'URL'		=> append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('t' => $this->topic_id, 'p' => $row['post_id'])),
						'S_REORDER'	=> ($auth->acl_get('m_') || )
				));
		}
		$db->sql_freeresult($result);
	}
}