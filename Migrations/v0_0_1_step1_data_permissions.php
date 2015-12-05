<?php
/**
 * 
 * User Online Time
 * 
 * @copyright (c) 2014 Wolfsblvt ( www.pinkes-forum.de )
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author Clemens Husung (Wolfsblvt)
 */

namespace rampmaster\onlinetime\migrations;

use phpbb\db\migration\migration;

class v0_0_1_step1_data_permissions extends migration
{
	public static function depends_on()
	{
		return array('\rampmaster\onlinetime\migrations\v0_0_1_schema');
	}

	public function update_data()
	{
		return array(
			array('permission.add', array('u_onlinetime_view')),
			array('permission.permission_set', array('ROLE_USER_FULL', 'u_onlinetime_view')),
			array('permission.permission_set', array('ROLE_USER_STANDARD', 'u_onlinetime_view')),
			array('permission.permission_set', array('REGISTERED', 'u_onlinetime_view', 'group')),
			array('permission.permission_set', array('REGISTERED_COPPA', 'u_onlinetime_view', 'group')),
		);
	}

	public function revert_data()
	{
		return array(
			array('permission.remove', array('u_onlinetime_view')),
			array('permission.permission_unset', array('ROLE_USER_FULL', 'u_onlinetime_view')),
			array('permission.permission_unset', array('ROLE_USER_STANDARD', 'u_onlinetime_view')),
			array('permission.permission_unset', array('REGISTERED', 'u_onlinetime_view', 'group')),
			array('permission.permission_unset', array('REGISTERED_COPPA', 'u_onlinetime_view', 'group')),
		);
	}
}
