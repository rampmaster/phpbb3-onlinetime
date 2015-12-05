<?php
/**
 * 
 * User Online Time
 * 
 * @copyright (c) 2014 Wolfsblvt ( www.pinkes-forum.de )
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author Clemens Husung (Wolfsblvt)
 */

namespace wolfsblvt\onlinetime\migrations;

class v1_1_0_schema extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return array('\wolfsblvt\onlinetime\migrations\v1_0_0_schema');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'wolfsblvt_onlinetime_hide'		=> array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'wolfsblvt_onlinetime_hide',
				),
			),
		);
	}
}
