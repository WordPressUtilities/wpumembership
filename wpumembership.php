<?php

/*
Plugin Name: WP Utilities Membership
Description: Handle user membership
Version: 0.1.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUMembership {
    public function __construct() {
    }

    /* ----------------------------------------------------------
      Membership
    ---------------------------------------------------------- */

    public function is_user_member($user_id = false) {
        if (!$user_id) {
            if (!is_user_logged_in()) {
                return false;
            }
            $user_id = get_current_user_id();
        }
        $is_member = get_user_meta($user_id, 'wpumembership_is_member', 1);
        $member_until = intval(get_user_meta($user_id, 'wpumembership_member_until', 1), 10);
        if ($member_until < time() && $is_member) {
            update_user_meta($user_id, 'wpumembership_is_member', 0);
            $is_member = false;
        }
        return $is_member;
    }

    public function set_user_membership($user_id = false, $duration = 86400) {
        if (!$user_id) {
            if (!is_user_logged_in()) {
                return false;
            }
            $user_id = get_current_user_id();
        }
        update_user_meta($user_id, 'wpumembership_is_member', 1);
        update_user_meta($user_id, 'wpumembership_member_until', time() + $duration);
    }
}

$WPUMembership = new WPUMembership();

/* ----------------------------------------------------------
  Helper
---------------------------------------------------------- */

if (!function_exists('is_current_user_member')) {
    function is_current_user_member() {
        global $WPUMembership;
        if (!is_user_logged_in()) {
            return false;
        }
        return apply_filters('wpumembership__is_current_user_member', $WPUMembership->is_user_member());
    }
}
