<?php
/*
Plugin Name: WP Duplicate
Plugin URI: https://Walor.org
Description: A plugin for duplicating posts and pages in WordPress.
Version: 1.0
Author: Dominik
Author URI: https://Walor.org
License: GPL2
*/


add_action('admin_action_duplicate_post', 'wp_duplicate');

function wp_duplicate() {
    // Check user permissions
    if (!current_user_can('edit_posts')) {
        error_log('Brak uprawnień do duplikowania. Użytkownik: ' . get_current_user_id());
        wp_die(__('Brak uprawnień do duplikowania.', 'wp-duplicate'));
    }

    // Checking nonce for security (CSRF prevention)
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'duplicate_post_nonce')) {
        wp_die(__('Nieprawidłowy token bezpieczeństwa.', 'wp-duplicate'));
    }

    // Get the ID of the entry to copy
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

    if (!$post_id) {
        error_log('Nieprawidłowy ID wpisu. Parametr: ' . $_GET['post']);
        wp_die(__('Nieprawidłowy ID wpisu.'));
    }

    // Get original entry data
    $post = get_post($post_id);

    if (!$post) {
        error_log('Nie znaleziono wpisu do duplikowania. ID wpisu: ' . $post_id);
        wp_die(__('Nie znaleziono wpisu do duplikowania.'));
    }

    // Prepare new entry
    $new_post = array(
        'post_title'   => $post->post_title . ' (Kopia)',
        'post_content' => $post->post_content,
        'post_status'  => 'draft',
        'post_type'    => $post->post_type,
        'post_author'  => get_current_user_id(),
    );

    // Insert new entry
    $new_post_id = wp_insert_post($new_post);

    // Copy metadata
    $meta_data = get_post_meta($post_id);
    foreach ($meta_data as $key => $values) {
        foreach ($values as $value) {
            add_post_meta($new_post_id, $key, maybe_unserialize($value));
        }
    }

    // Redirect to edit new entry
    wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
    exit;
}


add_filter('post_row_actions', 'add_duplicate_link', 10, 2);
add_filter('page_row_actions', 'add_duplicate_link', 10, 2);

function add_duplicate_link($actions, $post) {
    if (current_user_can('edit_posts')) {
        // Generating nonce
        $nonce = wp_create_nonce('duplicate_post_nonce');
        // Adding nonce to link
        $actions['duplicate'] = '<a href="' . admin_url('admin.php?action=duplicate_post&post=' . $post->ID . '&_wpnonce=' . $nonce) . '" title="' . __('Duplikuj zawartość', 'wp-duplicate') . '">' . __('Duplikuj', 'wp-duplicate') . '</a>';
    }
    return $actions;
}
