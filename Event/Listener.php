<?php
/**
 *
 * User Online Time
 *
 * @copyright (c) 2014 Wolfsblvt ( www.pinkes-forum.de )
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author Clemens Husung (Wolfsblvt)
 */

namespace rampmaster\onlinetime\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \rampmaster\onlinetime\core\onlinetime;
use \phpbb\db\driver\driver_interface;
use \phpbb\path_helper;
use \phpbb\template\template;

/**
 * Event listener
 */
class listener implements EventSubscriberInterface
{
    /** @var \rampmaster\onlinetime\core\onlinetime $onlinetime */
    protected $onlinetime;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\path_helper */
    protected $path_helper;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\request\request */
    protected $request;

    /**
     * Constructor of Event listener
     *
     * @param \rampmaster\onlinetime\core\onlinetime $onlinetime Online Time
     * @param \phpbb\db\driver\driver_interface $db Database
     * @param \phpbb\path_helper $path_helper phpBB path helper
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\user $user User object
     */
    public function __construct(onlinetime $onlinetime, driver_interface $db, path_helper $path_helper, template $template, \phpbb\user $user, \phpbb\request\request $request)
    {
        $this->onlinetime = $onlinetime;
        $this->db = $db;
        $this->path_helper = $path_helper;
        $this->template = $template;
        $this->user = $user;
        $this->request = $request;

        $this->ext_root_path = 'ext/rampmaster/onlinetime';
    }

    /**
     * Assign functions defined in this class to Event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => 'page_load',
            'core.page_header' => 'assign_template_vars',
            'core.memberlist_view_profile' => 'add_onlinetime_to_memberlist_view_profile',
            'core.permissions' => 'add_permissions',

            'core.ucp_prefs_personal_data' => 'ucp_prefs_get_data',
            'core.ucp_prefs_personal_update_data' => 'ucp_prefs_set_data',
        );
    }

    /**
     * Adds functionality to page_header
     *
     * @param object $event The Event object
     * @return void
     */
    public function page_load($event)
    {
        // Updates the user online time
        $this->onlinetime->update_user_online_time();
    }

    /**
     * Add custom permissions language variables
     *
     * @param object $event The Event object
     * @return void
     */
    public function add_permissions($event)
    {
        return;
        $permissions = $event['permissions'];
        $permissions['u_similar_topics'] = array('lang' => 'ACL_U_SIMILARTOPICS', 'cat' => 'misc');
        $event['permissions'] = $permissions;
    }

    /**
     * Get user's option and display it in UCP Prefs View page
     *
     * @param object $event The Event object
     * @return void
     */
    public function ucp_prefs_get_data($event)
    {
        // Request the user option vars and add them to the data array
        $event['data'] = array_merge($event['data'], array(
            'profile_onlinetime_hide' => $this->request->variable('profile_onlinetime_hide', (int)$this->user->data['profile_onlinetime_hide']),
        ));

        // Output the data vars to the template (except on form submit)
        if (!$event['submit']) {
            $data = $event['data'];
            $this->user->add_lang_ext('rampmaster/onlinetime', 'onlinetime');
            $this->template->assign_vars(array(
                'S_ONLINETIME_USER_HIDE' => $data['profile_onlinetime_hide'],
            ));
        }
    }

    /**
     * Add user's option state into the sql_array
     *
     * @param object $event The Event object
     * @return void
     */
    public function ucp_prefs_set_data($event)
    {
        $data = $event['data'];
        $event['sql_ary'] = array_merge($event['sql_ary'], array(
            'profile_onlinetime_hide' => $data['profile_onlinetime_hide'],
        ));
    }

    /**
     * Adds the online time to user profile if it can be displayed
     *
     * @param object $event The Event object
     * @return void
     */
    public function add_onlinetime_to_memberlist_view_profile($event)
    {
        $member_id = $event['member']['user_id'];
        $is_invisible = ((isset($event['session_viewonline'])) ? $event['session_viewonline'] : 0) ? false : true;

        $this->onlinetime->add_onlinetime_to_memberlist_view_profile($member_id, $is_invisible);
    }

    /**
     * Assigns the global template vars
     *
     * @return void
     */
    public function assign_template_vars()
    {
        $this->template->assign_vars(array(
            'T_EXT_ONLINETIME_PATH' => $this->path_helper->get_web_root_path() . $this->ext_root_path,
            'T_EXT_ONLINETIME_THEME_PATH' => $this->path_helper->get_web_root_path() . $this->ext_root_path . '/styles/' . $this->user->style['style_path'] . '/theme',
        ));
    }
}
