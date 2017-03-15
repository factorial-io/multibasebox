<?php

/**
 * @file
 * Provide views data for view_unpublished.module.
 */

/**
 * Implements hook_views_data_alter()
 *
 * @param array $data
 *   An array of all information about Views tables and fields, collected from
 *   hook_views_data(), passed by reference.
 *
 * @see hook_views_data()
 * @see hook_views_data_alter()
 */
function view_unpublished_views_data_alter(array &$data) {
  // published status + extra handler is taken over by our handler
  $data['node_field_data']['status_extra']['filter']['id'] = 'view_unpublished_node_status';
}