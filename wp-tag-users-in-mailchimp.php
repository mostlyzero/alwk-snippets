<?php
/*
 *	@snippet			Tag Users in MailChimp
 *	@description	Tag users individually, or as a batch update.
 *								Fetch tags from MailChimp for new users at registration.
 *								Add affiliate ID for users to MailChimp merge field.
 */

class alwkMailChimpTag {
	
	public $api_key = "b50a7ce2de17c4ff41b04545379ec090-us6";
	public $list_id = "52e69d9899"; // Art Life with Kelli 52e69d9899
	public $hashed_email;
	
	function __construct( $email = false ) {
		if ($email) $this->hashed_email = $this->hashEmail($email);
	}
	
	function hashEmail($email){
		return md5(strtolower($email));
	}
	
	function tagUser($tag_name){
		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->api_key )
			),
			'body' => json_encode(array(
				'tags' => array(['name'=> $tag_name ,'status'=>'active'])
			))
		);
		$response = wp_remote_post( 'https://us6.api.mailchimp.com/3.0/lists/' . $this->list_id . '/members/' . $this->hashed_email . '/tags', $args );
		return json_decode( $response['body'] );
	}
	
	function untagUser($tag_name){
		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->api_key )
			),
			'body' => json_encode(array(
				'tags' => array(['name'=> $tag_name ,'status'=>'inactive'])
			))
		);
		$response = wp_remote_post( 'https://us6.api.mailchimp.com/3.0/lists/' . $this->list_id . '/members/' . $this->hashed_email . '/tags', $args );
		return json_decode( $response['body'] );
	}
	
	
	
	/**
	 * Sends a batch request to the MailChimp API to update tags for each contact
	 * 
	 * @param		array		$users	associative array of emails and tags active/inactive status
	 * @return	string					json decoded response body string from API call
	 */
	
	function batchTagUsers( $users, $update_affiliate_ids = true ){
		
		foreach( $users as $email => $tags ) {

			$hashedEmail = $this->hashEmail($email);
			
			$merge_fields = array(["MEMBER" => "1"]);
			
			// Update affiliate ID
			if ($update_affiliate_ids) {
				$u = get_user_by("email", $email);
				$merge_fields = array("MEMBER" => "1", "AFF_ID" => "{$u->ID}");
			}
			
			// Update tags
			$operations[] = array(
				'method' => 'POST',
				'path' => '/lists/' . $this->list_id . '/members/' . $hashedEmail . '/tags',
				'operation_id' => 'batchTagUsersTags_' . $hashedEmail,
				'body' => json_encode(array(
					'tags' => $tags
				))
			);
			
			
			// Update mergefields
			$operations[] = array(
				'method' => 'PUT',
				'path' => '/lists/' . $this->list_id . '/members/' . $hashedEmail,
				'operation_id' => 'batchTagUsersMergeFields_' . $hashedEmail,
				'body' => json_encode(array(
					'merge_fields' => $merge_fields
				))
			);
			
			
		}
				
		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->api_key )
			),
			'body' => json_encode( array('operations' => $operations ))
		);
		
		//return $operations;
		
		$response = wp_remote_post( 'https://us6.api.mailchimp.com/3.0/batches/', $args );
		return json_decode( $response['body'] );
	}	
	
	/**
	 *	Get user info
	 */
	function getContactInfo(){
		$args = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->api_key )
			)
		);
		$response = wp_remote_get( 'https://us6.api.mailchimp.com/3.0/lists/' . $this->list_id . '/members/' . $this->hashed_email, $args );
		return json_decode( $response['body'] );
	}
	
	/**
	 *	Get user activity
	 */
	function getContactActivity(){
		$args = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->api_key )
			)
		);
		$response = wp_remote_get( 'https://us6.api.mailchimp.com/3.0/lists/' . $this->list_id . '/members/' . $this->hashed_email . '/activity', $args );
		return json_decode( $response['body'] );
	}
	
}

function alwk_tag_test(){
	//$response = (new alwkMailChimpTag( 'sgkrafts@aol.com' ))->getContactActivity();	
	
	// get the INactive members
	$members = alwk_get_active_member_tags();
	
	echo '<script>console.log("alwk_tag_test",'.json_encode($members).')</script>';
	
	// send a batch update request to mailchimp
	//$response = (new alwkMailChimpTag())->batchTagUsers( $members );
	
	//echo '<script>console.log("alwk_tag_test",'.json_encode($response).')</script>';
}



/*
 * Function to get associative array of members
 * and their active subscriptions
 */


function alwk_get_active_member_tags() {
	
	if ( !is_plugin_active('memberpress/memberpress.php') ) {
		return false;
	}
	
    global $wpdb;
    $mepr_db = new MeprDb();

	// MemberPress IDs and corresponding Tags for MailChimp
	$memberships = array(	10891 	=> "ALS Annual",
							10796	=> "ALS Monthly",
							59	 	=> "VITAL",
							2840	=> "MM 2021",
						 	2847	=> "MM 2021", // (monthly)
						 	16093	=> "5DC 03-22", // (March 2022)
							19670	=> "5DC 06-22", // (June 2022)
						 	21918	=> "5DC 10-22", // (October 2022)
						 	24398	=> "5DC 03-23", // (March 2023)
						 	20499	=> "Art Stars 2022", 
							20836	=> "5DC", // (evergreen 5 Day Challenge)
						 	21362	=> "ASBBP", // (Art Stars Biz Builders 2022 monthly)
						 	21532	=> "ASBBP", // (Art Stars Biz Builders 2022 pay in full)
						 	22124 	=> "ALWKND", // (Art Life Weekend - Nov 5&6 2022)
							22270	=> "ALWKND 11/19/222", // (Art Life Weekend - Nov 19&20 2022)
						 	"TRIAL" => "ALS TRIAL",
						 	9537	=> "MC SIMPLE", // Simple Scenes Masterclass
						 	10103	=> "MC FORM", // Improve Your Form Masterclass
						 	22425	=> "MC BUNDLE", // MASTERCLASS BUNDLE - all 12
						 	7061	=> "MC SUMMER", // Summer Flowers Masterclass
						 	5650	=> "MC PEONIES", // Peonies Masterclass
						 	5135	=> "MC SPRING", // Spring Flowers Masterclass
						 	3911	=> "MC ROSE", // Rose Series Masterclass
						 	3363	=> "MC BEGINNERS", // Beginners Masterclass
						 	3217	=> "MC PL FALL", // Plein Air Fall in Colorado Masterclass
						 	1531	=> "MC PL TAOS", // Plein Air Taos 
						 	109		=> "MC PL RURAL", // Plein Air Rural Scenes
						 	22329	=> "MC FALL STILL", // Fall Still Life Masterclass						 
	);
	
	
	// MemberPress IDs and corresponding Tags for MailChimp for FORMER MEMBER TAGGING
	$membershipsX = array(	10891 	=> "ALS Annual",
													10796		=> "ALS Monthly",
													59	 		=> "VITAL",
											  	16093		=> "5DC 03-22", // (March 2022)
													19670		=> "5DC 06-22", // (June 2022)
											 		21918		=> "5DC 10-22", // (October 2022)
											  	24398		=> "5DC 03-23", // (March 2023)
	);

	// Prefix for former/past members
	$former_prefix = "Former ";					 
	
	foreach( $memberships as $product_id => $product_name ){
		if (is_int($product_id)) {
			$product_ids .= $product_id . ",";	
		}
	}
	$andproduct = " AND product_id IN (" . substr($product_ids,0,-1) . ") ";	
	
	// query for active users
  $query = "
    SELECT DISTINCT u.user_email, product_id
      FROM {$mepr_db->transactions} AS tr
     INNER JOIN {$wpdb->users} AS u
        ON u.ID=tr.user_id
     WHERE (tr.expires_at >= %s OR tr.expires_at IS NULL OR tr.expires_at = %s)
       AND tr.status IN (%s, %s)
	 {$andproduct}
   ORDER BY u.user_email 
  ";

  $active_users_query = $wpdb->prepare(
    $query,
    MeprUtils::db_now(),
    MeprUtils::db_lifetime(),
    MeprTransaction::$complete_str,
    MeprTransaction::$confirmed_str
  );
				 
						 
	// and statement for inactive members
	$product_ids = '';
	foreach( $membershipsX as $product_id => $product_name ){
		if (is_int($product_id)  ) {
			if ( strpos($product_name, "MC ") !== 0 ) {
				$product_ids .= $product_id . ",";	
			}
		}
	}
	$andproduct = " AND product_id IN (" . substr($product_ids,0,-1) . ") ";
	
	// query for INactive users
	$query = "
      SELECT DISTINCT u.user_email, product_id
        FROM {$mepr_db->transactions} AS tr
       INNER JOIN {$wpdb->users} AS u
          ON u.ID=tr.user_id
       WHERE (tr.expires_at < %s)
         AND tr.status IN (%s, %s)
		 {$andproduct}
	   ORDER BY u.user_email 
    ";

    $inactive_users_query = $wpdb->prepare(
      $query,
      MeprUtils::db_now(),
      MeprTransaction::$complete_str,
      MeprTransaction::$confirmed_str
    );
	
	$members = []; // blank array to hold return values
	$active_members = $wpdb->get_results($active_users_query);
	$inactive_members = $wpdb->get_results($inactive_users_query);
	$trial_members = alwk_get_active_members_in_trial();
	
	// if a member/membership combo is found in both the active members array
	// AND the inactive members array, remove it from inactive members array
	
	foreach($inactive_members as $key => $entry) {
		if (in_array($entry, $active_members)) {
			unset($inactive_members[$key]);
		} 
	}
	
	// possible tags
	$possible_tags = array();
	foreach ($memberships as $product_id => $tag_name) {
		$tag_arr = array("name" => $tag_name, "status" => "inactive");
		if (!in_array($tag_arr, $possible_tags)){
			$possible_tags[] = $tag_arr;
		}
	}
	
	// possible tags (for former members)
	foreach ($membershipsX as $product_id => $tag_name) {
		$tag_arr = array("name" => $former_prefix . $tag_name, "status" => "inactive");
		if (!in_array($tag_arr, $possible_tags)){
			$possible_tags[] = $tag_arr;
		}
	}
	
	// add possible tags, set to inactive	
	foreach( $active_members as $key => $member ) {
		$members[$member->user_email] = $possible_tags;
	}
	// add possible tags (for FORMER members), set to inactive	
	foreach( $active_members as $key => $member ) {
		$members[$member->user_email] = $possible_tags;
	}
	// add active tag
	foreach( $active_members as $key => $member ) {
		$active_tag = $memberships[$member->product_id];
		foreach($members[$member->user_email] as $k => $val){
			if ($val['name'] == $active_tag){
				$members[$member->user_email][$k]["status"] = "active";
			}
		}
	}	
	
	// add FORMER tag
	foreach( $inactive_members as $key => $member ) {
		$inactive_tag = $membershipsX[$member->product_id];
		foreach($members[$member->user_email] as $k => $val){
			if ($val['name'] == $former_prefix . $inactive_tag){
				$members[$member->user_email][$k]["status"] = "active";
			}
		}
	}	
	
	// tag members in trial
	foreach( $trial_members as $key => $user_id ) {
		$active_tag = $memberships['TRIAL'];
		$user_info = get_userdata($user_id);
		$k = array_search(array("name" => $active_tag, "status" => "inactive"), $members[$user_info->user_email]);
		$members[$user_info->user_email][$k]["status"] = "active";	
	}
	
	return $members;
}

function alwk_get_active_members_in_trial() {
	
	if ( !is_plugin_active('memberpress/memberpress.php') ) {
		return false;
	}
	
    global $wpdb;
	$members = [];
    $mepr_db = new MeprDb();

	// Membership ID 10796 = ALS Monthly
	$andproduct = " AND (product_id = '10796' OR product_id = '10891') ";	
	
    $query = "
      SELECT u.ID, u.user_email, product_id
        FROM {$mepr_db->transactions} AS tr
       INNER JOIN {$wpdb->users} AS u
          ON u.ID=tr.user_id
       WHERE (tr.expires_at >= %s OR tr.expires_at IS NULL OR tr.expires_at = %s)
         AND tr.status IN (%s, %s)
		 {$andproduct}
	   ORDER BY u.user_email 
    ";

    $query = $wpdb->prepare(
      $query,
      MeprUtils::db_now(),
      MeprUtils::db_lifetime(),
      MeprTransaction::$complete_str,
      MeprTransaction::$confirmed_str
    );
	
	$active_members = $wpdb->get_results($query);
	
	// find only members who are in the trial
	foreach( $active_members as $key => $member ) {
		$mepr_subs = MeprSubscription::get_all_active_by_user_id($member->ID);
		foreach( $mepr_subs as $sub){
			if ( (isset($sub->trial) && $sub->trial == 1) 
				&& 
			 (isset($sub->trial_days) && $sub->trial_days <= 7)
			) {
				$members[] = $sub->user_id;	
			}
		}
		
	}	
	$members = array_unique($members);
	return $members;
}

function alwkUpdateTagsInMailChimp(){
	
	// get the active members and associated tags set as active/inactive
	$members = alwk_get_active_member_tags();
	
	// send a batch update request to mailchimp
	$response = (new alwkMailChimpTag())->batchTagUsers( $members );
}

add_action('run_snippet_twice_daily', 'alwkUpdateTagsInMailChimp' );	// Run twice daily


/*
 * Check for experience level tag in Mailchimp on new user sign up
 */

function alwkGetExpLevelForNewUser( $user_id = false, $userdata = array() ){
	
	if (!$user_id || !is_array($userdata) || !isset($userdata['user_email'])) return false;
	
	// Get user email for API call
	$user_email = $userdata['user_email'];
	
	// Check MailChimp for user data
	$response = (new alwkMailChimpTag( $user_email ))->getContactInfo();
	
	// Get any tags from MailChimp
	$tags = is_array($response->tags) ? $response->tags : array();
	
	// Define tags we're looking for
	$possible_tags = array("Quiz Beginner", "Quiz Intermediate", "Quiz Advanced");

	// Find first match (most recent tag will be top of the list)	
	foreach ($tags as $tag) {
		$name = $tag->name;
		if (in_array($tag->name, $possible_tags)){
			$exp_level = strtolower(str_replace("quiz ", "", $tag->name));
			break;
		}
	}
	
	// If we found an experience level tag, update the user in wordpress
	if ($exp_level) {
		update_user_meta($user_id, 'exp_level', $exp_level);
	} 	
	
}
add_action( 'user_register', 'alwkGetExpLevelForNewUser', 10, 2 );


function alwkTagUserOnSignUp( $user_id = false, $userdata = array() ) {
	
	if (!$user_id || !is_array($userdata) || !isset($userdata['user_email'])) return false;
	
	// Get user email for API call
	$user_email = $userdata['user_email'];
	
	// Check MailChimp for user data
	$response = (new alwkMailChimpTag( $user_email ))->getContactInfo();
	
	// Get any tags from MailChimp
	$tags = is_array($response->tags) ? $response->tags : array();
	
	// Define tags we're looking for
	//$possible_tags = array("Quiz Beginner", "Quiz Intermediate", "Quiz Advanced");
	
	//$response = (new alwkMailChimpTag( 'sgkrafts@aol.com' ))->tagUser();		
}
//add_action( 'user_register', 'alwkTagUserOnSignUp', 11, 2 );