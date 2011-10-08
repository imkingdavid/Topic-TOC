<?php
/**
*
*===================================================================
*
*  Topic Table of Contents Class and Methods
*-------------------------------------------------------------------
*	Script info:
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

class topic_toc
{
	private $data = array();
	private $items = array();
	function __construct($topic_id)
	{
		$this->data['topic_id'] = $topic_id;
	}
	/**
 	 * Add a post to the table of contents for the topic
	 * @param int $post_id Integer ID of the post to add to the TOC
	 * @param string $title What to name the link; If blank, use the post title
	 * @param int $order The position of this item in the order of the TOC
	 *
	 * BTW, this uses either 4 or 6 queries depending on the circumstances.
	 * That's ok because it's not being run on more than one page, so it won't affect load time.
	 */
	function add($post_id, $title = '', $order = 0)
	{
		global $db;
		// make sure the post is in the specified topic
		$sql = 'SELECT post_title FROM ' . TOPICS_TABLE . ' WHERE post_id = ' . (int) $post_id . ' AND topic_id = ' . (int) $this->data['topic_id'];
		$result = $db->sql_query($sql);
		$post_title = $db->sql_fetchfield('post_title');
		$db->sql_freeresult($result);
		//is the post in the topic?
		if(empty($post_title))
		{
			// no good
			return false;
		}
		
		$ttoc = array();
		// get the current list of TOC for the current topic
		$sql = 'SELECT order,post_id FROM ' . TTOC_TABLE . ' WHERE topic_id = ' . (int) $this->data['topic_id'];
		$result = $db->sql_query($sql);
		$last_pos = 0;
		while($row = $db->sql_fetchrow($result))
		{
			// get the position of the last TOC item
			if($row['order'] > $last_pos)
			{
				$last_pos = $row['order'];
			}
			// Don't allow the post to be added to the TOC twice
			if($row['post_id'] == $post_id)
			{
				// no good
				return false;
			}
		}
		$db->sql_freeresult($result);
		
		// finalize the variables
		$title = (empty($title)) ? $post_title : $title;
		$order = ($order) ? $order : ($last_pos + 1);
		// if the item is being placed before other items reorder them
		if($order < $last_pos)
		{
			// increment all TOC items order by one if they come after the current item
			$sql = 'UPDATE ' . TTOC_TABLE . ' SET order = (order + 1) WHERE order > ' . (int) $order;
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
			// NOTE this will need to be undone if insertion fails
			// set this variable so we know this was run
			$reordered = true;
		}
		// now add it to the toc table
		$sql_ary = array(
			// what topic is this in?
			'topic_id'	=> $this->data['topic_id'],
			// which post to link to
			'post_id'	=> (int) $post_id,
			// default to the post title if no other title is given
			'title'		=> $title,
			// what order to put it in
			'order'		=> $order,
		);
		$sql = 'INSERT INTO ' . TTOC_TABLE . ' ' . $db->sql_build_query('INSERT', $sql_ary);
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		// Now for good measure, make sure it inserted properly
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE post_id = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$item = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		// if there is no item, it must not have been inserted.
		// roll back any changes made
		if(empty($item['title']) && $reordered)
		{
			if($reordered)
			{
				$sql = 'UPDATE ' . TTOC_TABLE . ' SET order = (order - 1) WHERE topic_id = ' . $this->data['topic_id'] . ' AND order > ' . (int) $order;
				$result = $db->sql_query($sql);
				$db->sql_freeresult($result);
			}
			// no good
			return false;
		}
		// yay!
		return true;
	}
	
	// move an item up or down one spot
	function reorder($direction = 'up', $item_id)
	{
		//$sql = 'SELECT order FROM'
	}
	
	/**
	 * Delete a post from the table of contents for the topic
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
				// no good
				return false;
		}
		
		// now remove it
		$sql = 'DELETE FROM ' . TTOC_TABLE . ' WHERE post_id = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$db->sql_freeresult($result);
		
		// yay!
		return true;
	}
	/**
	 * Send the variables to the template to display the TOC
	 */
	function display()
	{
		global $template, $phpbb_root_path;
		
		// The item block
		$sql = 'SELECT * FROM ' . TTOC_TABLE . ' WHERE topic_id = ' . $this->data['topic_id'];
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result))
		{
				$template->assign_block_vars('ttoc', array(
						'TITLE'		=> $row['title'],
						'URL'		=> append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('t' => $this->data['topic_id'], 'p' => $row['post_id'])),
				));
		}
	}
}