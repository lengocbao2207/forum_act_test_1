<?php 

class ZangF_Model_Feed extends XFCP_ZangF_Model_Feed
{
	protected function _insertFeedEntry(array $entryData, array $feedData, array $feed)
	{
		$db = $this->_getDb();
		XenForo_Db::beginTransaction($db);

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
		$writer->setOption(XenForo_DataWriter_Discussion::OPTION_TRIM_TITLE, true);
		//remove some text 
		$entryData['message'] = str_replace('[IMG]http://pixel.quantserve.com/pixel/p-89EKCgBk8MZdE.gif[/IMG]', '', $entryData['message']);
		$z_thumb = $this->thumbModel()->getThumb($entryData['message'], 0);
		if($z_thumb AND $z_thumb != '')
		{
			$writer->bulkSet(array(
				'node_id' => $feed['node_id'],
				'prefix_id' => $feed['prefix_id'],
				'discussion_state' => $feed['discussion_visible'] ? 'visible' : 'moderated',
				'discussion_open' => $feed['discussion_open'],
				'sticky' => $feed['discussion_sticky'],
				'title' => $entryData['title'],
				'user_id' => $feed['user_id'],
				'z_thumb' => $z_thumb,
			));
		} else {
			$writer->bulkSet(array(
				'node_id' => $feed['node_id'],
				'prefix_id' => $feed['prefix_id'],
				'discussion_state' => $feed['discussion_visible'] ? 'visible' : 'moderated',
				'discussion_open' => 'moderated',
				'sticky' => $feed['discussion_sticky'],
				'title' => $entryData['title'],
				'user_id' => $feed['user_id'],
				'z_thumb' => '',
			));
		}
		
		
		// TODO: The wholeWordTrim() used here may not be exactly ideal. Any better ideas?
		if ($feed['user_id'])
		{
			// post as the specified registered user
			$writer->set('username', $feed['username']);
		}
		else if ($entryData['author'])
		{
			// post as guest, using the author name(s) from the entry
			$writer->set('username', XenForo_Helper_String::wholeWordTrim($entryData['author'], 25, 0, ''));
		}
		else
		{
			// post as guest, using the feed title
			$writer->set('username', XenForo_Helper_String::wholeWordTrim($feed['title'], 25, 0, ''));
		}

		$postWriter = $writer->getFirstMessageDw();
		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_VERIFY_GUEST_USERNAME, false);
		$postWriter->set('message', $entryData['message']);

		$writer->save();

		$threadId = $writer->get('thread_id');

		if ($threadId)
		{
			try
			{
				$db->query('
					INSERT INTO xf_feed_log
						(feed_id, unique_id, hash, thread_id)
					VALUES
						(?, ?, ?, ?)
				', array($feed['feed_id'], utf8_substr($entryData['id'], 0, 250), $entryData['hash'], $threadId));
			}
			catch (Zend_Db_Exception $e)
			{
				XenForo_Db::rollback($db);
				return false;
			}
		}

		XenForo_Db::commit($db);

		return $threadId;
	}
	
	
	protected function thumbModel()
	{
		return $this->getModelFromCache('ZangF_Model_Thumb');
	}
}