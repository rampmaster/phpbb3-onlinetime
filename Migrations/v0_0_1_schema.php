<?php
/**
 * 
 * User Online Time
 * 
 * @copyright (c) 2014 Wolfsblvt ( www.pinkes-forum.de )
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author Clemens Husung (Wolfsblvt)
 */

namespace Rampmaster\Onlinetime\Migrations;

use phpbb\db\migration\migration;

class v0_0_1_schema extends migration
{
	private $table_online_time_name = 'online_time';
	private $table_online_time_days_name = 'online_time_days';

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . $this->table_online_time_name);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . $this->table_online_time_name => array(
					'COLUMNS' => array(
						'user_id'			=> array('UINT:8', 0),
						'user_last_action'	=> array('UINT:11', 0),
						'user_total_time'	=> array('UINT:11', 0),
					),
					'PRIMARY_KEY'	=> 'user_id',
				),
				$this->table_prefix . $this->table_online_time_days_name => array(
					'COLUMNS' => array(
						'user_id'			=> array('UINT:8', 0),
						'day'				=> array('UINT:11', 0),
						'day_total_time'	=> array('UINT:11', 0),
					),
					'PRIMARY_KEY'	=> array('user_id', 'day'),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . $this->table_online_time_name,
				$this->table_prefix . $this->table_online_time_days_name,
			),
		);
	}
}
