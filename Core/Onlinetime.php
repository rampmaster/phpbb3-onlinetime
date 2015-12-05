<?php
/**
 *
 * User Online Time
 *
 * @copyright (c) 2014 Wolfsblvt ( www.pinkes-forum.de )
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author Clemens Husung (Wolfsblvt)
 */

namespace rampmaster\onlinetime\core;

use rampmaster\onlinetime\core\formatter;
use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\template\template;
use phpbb\user;
use phpbb\auth\auth;

class onlinetime
{
    /** @var \rampmaster\onlinetime\core\formatter $formatter */
    protected $formatter;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    protected $container;

    /** @var \phpbb\db\driver\driver_interface $db */
    protected $db;

    /** @var \phpbb\config\config $config */
    protected $config;

    /** @var \phpbb\template\template $template */
    protected $template;

    /** @var \phpbb\user $user */
    protected $user;

    /** @var \phpbb\auth\auth $auth */
    protected $auth;

    protected $TABLE_ONLINE_TIME;
    protected $TABLE_ONLINE_TIME_DAYS;

    /**
     * Constructor
     *
     * @param \rampmaster\onlinetime\core\formatter $formatter Formatter
     * @param \phpbb\db\driver\driver_interface $db Database
     * @param \phpbb\config\config $config Config helper
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\user $user User object
     * @param \phpbb\auth\auth $auth Auth object
     */
    public function __construct(formatter $formatter, driver_interface $db, config $config, template $template, user $user, auth $auth)
    {
        global $phpbb_container;

        $this->container = &$phpbb_container;
        $this->formatter = $formatter;
        $this->db = $db;
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;
        $this->auth = $auth;

        $this->TABLE_ONLINE_TIME = $this->container->getParameter('tables.rampmaster.onlinetime.online_time');
        $this->TABLE_ONLINE_TIME_DAYS = $this->container->getParameter('tables.rampmaster.onlinetime.online_time_days');

        // Add language vars
        $this->user->add_lang_ext('rampmaster/onlinetime', 'onlinetime');
    }

    /**
     * Adds the online time to user profile if it can be displayed
     *
     * @param int $member_id The Id of the member
     * @param bool $is_invisible If the member is invisble.
     * @return void
     */
    public function add_onlinetime_to_memberlist_view_profile($member_id, $is_invisible)
    {
        // can you see the online time?
        $i_can_see = $this->auth->acl_get('u_onlinetime_view');
        if ($is_invisible && $member_id != $this->user->data['user_id'] && !$this->auth->acl_get('u_viewonline')) {
            $i_can_see = false;
        }

        if ($i_can_see) {
            // load total online time
            $sql = 'SELECT user_total_time
				FROM ' . $this->TABLE_ONLINE_TIME . "
				WHERE user_id = $member_id";
            $result = $this->db->sql_query($sql);
            $onlinetime_total = (int)$this->db->sql_fetchfield('user_total_time');
            $this->db->sql_freeresult($result);

            // load averageonline time
            $sql = 'SELECT AVG(day_total_time) as user_average_time
				FROM ' . $this->TABLE_ONLINE_TIME_DAYS . "
				WHERE user_id = $member_id";
            $result = $this->db->sql_query($sql);
            $onlinetime_average = (int)$this->db->sql_fetchfield('user_average_time');
            $this->db->sql_freeresult($result);

            $this->template->assign_vars(array(
                'ONLINETIME_CAN_SEE' => true,
                'ONLINETIME_TOTAL' => $this->formatter->format_timespan($onlinetime_total),
                'ONLINETIME_AVERAGE' => $this->user->lang('ONLINETIME_AVERAGE', $this->formatter->format_timespan($onlinetime_average)),
            ));
        }
    }

    /**
     * Updates the user online time
     *
     * @return void
     */
    public function update_user_online_time()
    {
        $user_id = $this->user->data['user_id'];

        $new_time_to_add = 0;

        // load lastonline time and total online time
        $sql = 'SELECT *
				FROM ' . $this->TABLE_ONLINE_TIME . "
				WHERE user_id = $user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $user_online_data = $row;
        $this->db->sql_freeresult($result);

        if (isset($user_online_data) && isset($user_online_data['user_last_action'])) {
            $act_time = time();
            $user_total_time = $user_online_data['user_total_time'];
            $new_time_to_add = ($act_time - $user_online_data['user_last_action']);

            if ($user_online_data['user_last_action'] > ($act_time - $this->config['load_online_time'] * 60)) {
                $user_total_time = $user_total_time + $new_time_to_add;
            } else {
                // Was not online in the last XXX minutes, so day time should not be added up
                $new_time_to_add = 0;
            }

            $sql = 'UPDATE ' . $this->TABLE_ONLINE_TIME . "
					SET user_last_action = $act_time, user_total_time = $user_total_time
					WHERE user_id = $user_id";
            $this->db->sql_query($sql);
        } else {
            $new_time_to_add = 0;

            $sql = 'INSERT INTO ' . $this->TABLE_ONLINE_TIME . ' ' . $this->db->sql_build_array('INSERT', array(
                    'user_id' => $user_id,
                    'user_last_action' => (int)time(),
                    'user_total_time' => 0));
            $this->db->sql_query($sql);
        }

        $today = (int)strtotime('today');

        // if new time exceeds midnight, we need to split it up
        if (($new_time_to_add > 0) && ($user_online_data['user_last_action'] < $today)) {
            $time_from_yesterday = $today - $user_online_data['user_last_action'];

            // add the time to the old day
            $sql = 'UPDATE ' . $this->TABLE_ONLINE_TIME_DAYS . "
				SET day_total_time = day_total_time + $time_from_yesterday
				WHERE user_id = $user_id
					AND day = " . (int)strtotime('yesterday');
            $this->db->sql_query($sql);

            $new_time_to_add -= $time_from_yesterday;
        }

        // if day exists, update, otherwise insert
        if ($user_online_data['user_last_action'] > $today) {
            $sql = 'UPDATE ' . $this->TABLE_ONLINE_TIME_DAYS . "
				SET day_total_time = day_total_time + $new_time_to_add
				WHERE user_id = $user_id
					AND day = $today";
            $this->db->sql_query($sql);
        } else {
            $sql = 'INSERT INTO ' . $this->TABLE_ONLINE_TIME_DAYS . ' ' . $this->db->sql_build_array('INSERT', array(
                    'user_id' => $user_id,
                    'day' => (int)$today,
                    'day_total_time' => $new_time_to_add
                ));
            $this->db->sql_query($sql);
        }
    }
}
