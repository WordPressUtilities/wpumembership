<?php

/*
Plugin Name: WP Utilities Membership
Description: Handle user membership
Version: 0.3.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUMembership {
    private $min_duration = 86400;
    private $meta_is_member = 'wpumembership_is_member';
    private $meta_member_until = 'wpumembership_member_until';
    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {
        /* Language */
        load_plugin_textdomain('wpumembership', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        /* Filter settings */
        $this->min_duration = apply_filters('wpumembership__min_duration', $this->min_duration);
        $this->meta_is_member = apply_filters('wpumembership__meta_is_member', $this->meta_is_member);
        $this->meta_member_until = apply_filters('wpumembership__meta_member_until', $this->meta_member_until);

        /* Hooks */
        add_filter('wpu_usermetas_sections', array(&$this, 'set_wpu_usermetas_sections'), 10, 3);
        add_filter('wpu_usermetas_fields', array(&$this, 'set_wpu_usermetas_fields'), 10, 3);
        add_action('wpuusermetas_update_user_meta__wpumembership_is_member', array(&$this, 'wpuusermetas_update_user_meta'), 10, 3);
    }

    /* ----------------------------------------------------------
      Membership helpers
    ---------------------------------------------------------- */

    public function is_user_member($user_id = false) {
        if (!$user_id) {
            if (!is_user_logged_in()) {
                return false;
            }
            $user_id = get_current_user_id();
        }
        $is_member = get_user_meta($user_id, $this->meta_is_member, 1);
        $member_until = intval(get_user_meta($user_id, $this->meta_member_until, 1), 10);
        if ($member_until < time() && $is_member) {
            update_user_meta($user_id, $this->meta_is_member, 0);
            $is_member = false;
        }
        return $is_member;
    }

    public function set_user_membership($user_id = false, $duration = false) {
        if (!$duration) {
            $duration = $this->min_duration;
        }
        if (!$user_id) {
            if (!is_user_logged_in()) {
                return false;
            }
            $user_id = get_current_user_id();
        }
        update_user_meta($user_id, $this->meta_is_member, 1);
        update_user_meta($user_id, $this->meta_member_until, time() + $duration);
    }

    public function unset_user_membership($user_id = false) {
        if (!$user_id) {
            if (!is_user_logged_in()) {
                return false;
            }
            $user_id = get_current_user_id();
        }
        update_user_meta($user_id, $this->meta_is_member, 0);
        delete_user_meta($user_id, $this->meta_member_until);
    }

    /* ----------------------------------------------------------
      Add user metas
    ---------------------------------------------------------- */

    public function set_wpu_usermetas_sections($sections) {
        $sections['wpu-membership'] = array(
            'name' => 'WPU Membership'
        );
        return $sections;
    }

    public function set_wpu_usermetas_fields($fields) {
        $fields[$this->meta_member_until] = array(
            'name' => __('Member until', 'wpumembership'),
            'type' => 'number',
            'section' => 'wpu-membership'
        );
        $fields[$this->meta_is_member] = array(
            'name' => __('Member', 'wpumembership'),
            'type' => 'checkbox',
            'section' => 'wpu-membership'
        );
        return $fields;
    }

    public function wpuusermetas_update_user_meta($user_id, $value, $old_value) {
        /* If membership value change */
        if ($value != $old_value) {
            /* Cancel duration */
            if (!$value) {
                $this->unset_user_membership($user_id);
            } else {
                $member_until = intval(get_user_meta($user_id, $this->meta_member_until, 1), 10);
                if ($member_until < $this->min_duration) {
                    $this->set_user_membership($user_id);
                }
            }
        }
    }
}

$WPUMembership = new WPUMembership();

/* ----------------------------------------------------------
  Helper
---------------------------------------------------------- */

if (!function_exists('is_current_user_member')) {
    function is_current_user_member() {
        global $WPUMembership;
        return is_user_logged_in() ? apply_filters('wpumembership__is_current_user_member', $WPUMembership->is_user_member()) : false;
    }
}
