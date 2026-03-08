<?php
// Register the webhook endpoint
add_action('rest_api_init', function () {
    register_rest_route('ghl/v1', '/contact-update/', array(
        'methods'  => 'POST',
        'callback' => 'ghl_contact_update_handler',
        'permission_callback' => '__return_true',
    ));
});

// Handle incoming webhook data
function ghl_contact_update_handler($request) {  
  $params = $request->get_json_params();

  if (empty($params)) {
    return new WP_REST_Response(['error' => 'No data received'], 400);
  }
  file_put_contents(__DIR__.'/ghl_log.txt', print_r($params, true), FILE_APPEND);
  // Extract fields (adjust based on GHL webhook payload)

  $contact_id = $first_name = $last_name = $email = $phone = $tags = $country = $contact_type = $date_created = $city = $address1 = $state = $postal_code = $exists = $updated_row_id = $is_updated = '';
  $tagsArr = $user_data = $data = $where = $updated = [];

  $contact_id = $params['contact_id'] ?? '';
  $first_name = sanitize_text_field($params['first_name'] ?? '');
  $last_name  = sanitize_text_field($params['last_name'] ?? '');
  $email      = sanitize_email($params['email'] ?? '');
  $phone      = sanitize_text_field($params['phone'] ?? '');
  $tags       = maybe_serialize($params['tags'] ?? []);
  if(!empty($tags)) {
    $tagsArr = explode(",", $tags);
  }  
  // file_put_contents(__DIR__.'/ghl_fields_log.txt', $log_info."\n", FILE_APPEND);
  // file_put_contents(__DIR__.'/ghl_fields_log.txt', print_r($tagsArr, true), FILE_APPEND);

  $country  = sanitize_text_field($params['country'] ?? '');
  $contact_type  = sanitize_text_field($params['contact_type'] ?? '');
  $date_created  = sanitize_text_field($params['date_created'] ?? '');
  $city  = sanitize_text_field($params['city'] ?? '');
  $address1  = sanitize_text_field($params['address1'] ?? '');
  $state  = sanitize_text_field($params['state'] ?? '');
  $postal_code  = sanitize_text_field($params['postal_code'] ?? '');

  $user_data = array(
    "firstName" =>  $first_name,
    "lastName" =>   $last_name,
    "email" =>  $email,
    "country" =>  $country,
    "type" =>  $contact_type,
    "dateAdded" =>  $date_created,
    "phone" =>  $phone,
    "dateOfBirth" =>  "",
    "additionalPhones" =>  "",
    "city" =>  $city,
    "address1" =>  $address1,
    "companyName" =>  "",
    "state" =>  $state,
    "postalCode" =>  $postal_code,
    "additionalEmails" =>  "",
  );

  // Save into wp_lcw_contacts
  global $wpdb;

  // Table name (with prefix)
  $table = $wpdb->prefix . 'lcw_contacts';
  $serialtagsArr = $serialUserData = '';
  $serialtagsArr = serialize($tagsArr);
  $serialUserData = serialize($user_data);

  // Check if record exists
  $exists = $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE contact_id = %s", $contact_id)
  );
  if ($exists > 0) {
    // Data to update
    $data = array(
      'tags'  =>  $serialtagsArr,
      'contact_fields' => $serialUserData
    );

    // WHERE condition (which record to update)
    $where = array(
      'contact_id' => $contact_id,
    );

    // Execute update
    $updated = $wpdb->update(
      $table,   // table name
      $data,    // data to update
      $where,   // where clause
      array('%s', '%s'), // data formats (string/string)
      array('%s') // where format
    );
    // Build the raw MySQL query
    if ($updated) {
      // ✅ This is your updated row ID
      $updated_row_id = $contact_id;
      $is_updated = 'Updated row ID: ' . $updated_row_id;
    } else {
      $is_updated = 'Update failed.';
    }
  }
  $log_info = 'Contact ID: ' . $contact_id;
  file_put_contents(__DIR__.'/ghl_fields_log.txt', $log_info."\n", FILE_APPEND);
  file_put_contents(__DIR__.'/ghl_fields_log.txt', print_r($params, true), FILE_APPEND);

  return new WP_REST_Response(['success' => true, 'message' => 'Contact saved'], 200);
}