<?php 
/**
 * migrates old 3.1 events, and start_end entries to 4.1 event CPTs, tickets (although doesn't assign them any prices, only datetimes; also
 * this is run BEFORE migrating prices), and datetimes.
 * 3.1 events table:
 * delimiter $$

CREATE TABLE `wp_events_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_code` varchar(26) DEFAULT '0',
  `event_name` varchar(100) DEFAULT NULL,
  `event_desc` text,
  `display_desc` varchar(1) DEFAULT 'Y',
  `display_reg_form` varchar(1) DEFAULT 'Y',
  `event_identifier` varchar(75) DEFAULT NULL,
  `start_date` varchar(15) DEFAULT NULL,
  `end_date` varchar(15) DEFAULT NULL,
  `registration_start` varchar(15) DEFAULT NULL,
  `registration_end` varchar(15) DEFAULT NULL,
  `registration_startT` varchar(15) DEFAULT NULL,
  `registration_endT` varchar(15) DEFAULT NULL,
  `visible_on` varchar(15) DEFAULT NULL,
  `address` text,
  `address2` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(11) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `venue_title` varchar(250) DEFAULT NULL,
  `venue_url` varchar(250) DEFAULT NULL,
  `venue_image` text,
  `venue_phone` varchar(15) DEFAULT NULL,
  `virtual_url` varchar(250) DEFAULT NULL,
  `virtual_phone` varchar(15) DEFAULT NULL,
  `reg_limit` varchar(25) DEFAULT '999999',
  `allow_multiple` varchar(15) DEFAULT 'N',
  `additional_limit` int(10) DEFAULT '5',
  `send_mail` varchar(2) DEFAULT 'Y',
  `is_active` varchar(1) DEFAULT 'Y',
  `event_status` varchar(2) DEFAULT 'A',
  `conf_mail` text,
  `use_coupon_code` varchar(1) DEFAULT 'N',
  `use_groupon_code` varchar(1) DEFAULT 'N',
  `category_id` text,
  `coupon_id` text,
  `tax_percentage` float DEFAULT NULL,
  `tax_mode` int(11) DEFAULT NULL,
  `member_only` varchar(1) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `post_type` varchar(50) DEFAULT NULL,
  `country` varchar(200) DEFAULT NULL,
  `externalURL` varchar(255) DEFAULT NULL,
  `early_disc` varchar(10) DEFAULT NULL,
  `early_disc_date` varchar(15) DEFAULT NULL,
  `early_disc_percentage` varchar(1) DEFAULT 'N',
  `question_groups` longtext,
  `item_groups` longtext,
  `event_type` varchar(250) DEFAULT NULL,
  `allow_overflow` varchar(1) DEFAULT 'N',
  `overflow_event_id` int(10) DEFAULT '0',
  `recurrence_id` int(11) DEFAULT '0',
  `email_id` int(11) DEFAULT '0',
  `alt_email` text,
  `event_meta` longtext,
  `wp_user` int(22) DEFAULT '1',
  `require_pre_approval` int(11) DEFAULT '0',
  `timezone_string` varchar(250) DEFAULT NULL,
  `likes` int(22) DEFAULT NULL,
  `ticket_id` int(22) DEFAULT '0',
  `submitted` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_code` (`event_code`),
  KEY `wp_user` (`wp_user`),
  KEY `event_name` (`event_name`),
  KEY `city` (`city`),
  KEY `state` (`state`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `registration_start` (`registration_start`),
  KEY `registration_end` (`registration_end`),
  KEY `reg_limit` (`reg_limit`),
  KEY `event_status` (`event_status`),
  KEY `recurrence_id` (`recurrence_id`),
  KEY `submitted` (`submitted`),
  KEY `likes` (`likes`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8$$


 * 
 * 4.1 Event model:
 * $this->_tables = array(
			'Event_CPT'=>new EE_Primary_Table('posts','ID'),
			'Event_Meta'=> new EE_Secondary_Table('esp_event_meta', 'EVTM_ID','EVT_ID')
		);
		
		$this->_fields = array(
			'Event_CPT'=>array(
				'EVT_ID'=>new EE_Primary_Key_Int_Field('ID', __('Post ID for Event','event_espresso')),
				'EVT_name'=>new EE_Plain_Text_Field('post_title', __('Event Name','event_espresso'), false, ''),
				'EVT_desc'=>new EE_Simple_HTML_Field('post_content', __("Event Description", "event_espresso"), false, ''),
				'EVT_slug'=>new EE_Slug_Field('post_name', __("Event Slug", "event_espresso"), false, ''),
				'EVT_created'=>new EE_Datetime_Field('post_date', __("Date/Time Event Created", "event_espresso"), false, current_time('timestamp')),
				'EVT_short_desc'=>new EE_Simple_HTML_Field('post_excerpt', __("Event Short Descripiton", "event_espresso"), false,''),
				'EVT_modified'=>new EE_Datetime_Field('post_modified', __("Dateim/Time Event Modified", "event_espresso"), true, current_time('timestamp')),
				'EVT_wp_user'=>new EE_Integer_Field('post_author', __("Wordpress User ID", "event_espresso"), false,1),
				'parent'=>new EE_Integer_Field('post_parent', __("Event Parent ID", "event_espresso"), true),
				'EVT_order'=>new EE_Integer_Field('menu_order', __("Event Menu Order", "event_espresso"), false, 1),
				'post_type'=>new EE_Plain_Text_Field('post_type', __("Event Post Type", "event_espresso"), false, 'espresso_events'),
				'status' => new EE_WP_Post_Status_Field('post_status', __("Event Status", "event_espresso"), false, 'draft', $custom_stati)
			),
			'Event_Meta'=>array(
				'EVTM_ID'=> new EE_DB_Only_Float_Field('EVTM_ID', __('Event Meta Row ID','event_espresso'), false),
				'EVT_ID_fk'=>new EE_DB_Only_Int_Field('EVT_ID', __("Foreign key to Event ID from Event Meta table", "event_espresso"), false),
				'EVT_display_desc'=>new EE_Boolean_Field('EVT_display_desc', __("Display Description Flag", "event_espresso"), false, 1),
				'EVT_display_reg_form'=>new EE_Boolean_Field('EVT_display_reg_form', __("Display Registration Form Flag", "event_espresso"), false, 1),
				'EVT_visible_on'=>new EE_Datetime_Field('EVT_visible_on', __("Event Visible Date", "event_espresso"), true, current_time('timestamp')),
				'EVT_allow_multiple'=>new EE_Boolean_Field('EVT_allow_multiple', __("Allow Multiple Registrations on Same Transaction Flag", "event_espresso"), false, false),
				'EVT_additional_limit'=>new EE_Integer_Field('EVT_additional_limit', __("Limit of Additional Registrations on Same Transaction", "event_espresso"), true),
				'EVT_additional_attendee_reg_info'=>new EE_Enum_Integer_Field('EVT_additional_attendee_reg_info', __("Info Requested for Additional Attendees?", "event_espresso"), true, EEM_Event::additional_attendee_reg_info_none, self::$_additional_attendee_reg_info_enum),
				'EVT_default_registration_status'=>new EE_Enum_Text_Field('EVT_default_registration_status', __("Default Registration Status on this Event", "event_espresso"), false, EEM_Registration::status_id_pending, EEM_Registration::reg_status_array()),
				'EVT_require_pre_approval'=>new EE_Boolean_Field('EVT_require_pre_approval', __("Event Requires Pre-Approval before Registration Complete", "event_espresso"), false, false),
				'EVT_member_only'=>new EE_Boolean_Field('EVT_member_only', __("Member-Only Event Flag", "event_espresso"), false, false),
				'EVT_phone'=> new EE_Plain_Text_Field('EVT_phone', __('Event Phone Number', 'event_espresso'), false ),
				'EVT_allow_overflow'=>new EE_Boolean_Field('EVT_allow_overflow', __("Allow Overflow on Event", "event_espresso"), false, false),
				'EVT_timezone_string'=>new EE_Plain_Text_Field('EVT_timezone_string', __("Timezone (name) for Event times", "event_espresso"), false),
				'EVT_external_URL'=>new EE_Plain_Text_Field('EVT_external_URL', __("URL of Event Page if hosted elsewhere", "event_espresso"), true),
				'EVT_donations'=>new EE_Boolean_Field('EVT_donations', __("Accept Donations?", "event_espresso"), false, false)
				
			));
 * 
 * @todo: calculate new CPT event stati
 * @todo: how to handle posts attached to events?
 * @todo: how ot handle recurring events?
 * @todo: convert post image
 * @todo: how to handle venue info on event row?
 * @todo: realized we're not handling post created/modified GMTs
 * @todo: what to do with these 3.1 columsn:  -`use_coupon_code` varchar(1) DEFAULT 'N',
  -`use_groupon_code` varchar(1) DEFAULT 'N',
  -`category_id` text,
  -`coupon_id` text,
  -`tax_percentage` float DEFAULT NULL,
  -`tax_mode` int(11) DEFAULT NULL,
  -`early_disc` varchar(10) DEFAULT NULL,
  -`early_disc_date` varchar(15) DEFAULT NULL,
  -item_groups` longtext,
  -`event_type` varchar(250) DEFAULT NULL,
  -`alt_email` text,
  -`likes` int(22) DEFAULT NULL,
  -`ticket_id` int(22) DEFAULT '0',
 */
class EE_DMS_4_1_0P_events extends EE_Data_Migration_Script_Stage{
	private $_old_table;
	private $_new_table;
	private $_new_meta_table;
	function __construct() {
		global $wpdb;
		$this->_old_table = $wpdb->prefix."events_detail";
		$this->_new_table = $wpdb->prefix."posts";
		$this->_new_meta_table = $wpdb->prefix."esp_event_meta";
		$this->_pretty_name = __("Events", "event_espresso");
		parent::__construct();
	}
	function _count_records_to_migrate() {
		global $wpdb;
		$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$this->_old_table);
		return intval($count);
	}
	protected function _migration_step($num_items_to_migrate = 50) {
		global $wpdb;
		$events = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->_old_table LIMIT %d,%d",$this->count_records_migrated(),$num_items_to_migrate),ARRAY_A);
		$items_migrated_this_step = 0;

		foreach($events as $event_row){
			//insert new 4.1 Attendee object using $wpdb
			$post_id = $this->_insert_cpt($event_row);
			if($post_id){
				$this->get_migration_script()->set_mapping($this->_old_table, $event_row['id'], $this->_new_table, $post_id);
				$meta_id = $this->_insert_event_meta($event_row, $post_id);
				if($meta_id){
					$this->get_migration_script()->set_mapping($this->_old_table, $event_row['id'], $this->_new_meta_table, $meta_id);
				}
				$this->_add_post_metas($event_row, $post_id);
			}
			$items_migrated_this_step++;
		}
		if($this->count_records_migrated() + $items_migrated_this_step >= $this->count_records_to_migrate()){
			$this->set_status(EE_Data_Migration_Manager::status_completed);
		}
		return $items_migrated_this_step;
	}
	
	/**
	 * Stores any extra 3.1 "event_meta" column things as post meta
	 * @param type $old_event
	 * @param type $post_id
	 * @return void
	 */
	private function _add_post_metas($old_event,$post_id){
		$event_meta = maybe_unserialize($old_event['event_meta']);
		unset($event_meta['date_submitted']);//factored into CPT
		unset($event_meta['additional_attendee_reg_info']);//facotred into event meta table 
		unset($event_meta['default_payment_status']);//dido
		foreach($event_meta as $meta_key => $meta_value){
			if ($meta_key){//if th emeta key is just an empty string, ignore it
				$success = add_post_meta($post_id,$meta_key,$meta_value,true);
				if( ! $success ){
					$this->add_error(sprintf(__("Could not add post meta for CPT with ID #%d. Meta key: '%s',meta value:'%d' for 3.1 event: %s", "event_espresso"),$post_id,$meta_key,$meta_value,implode(",",$old_event)));
				}
			}
		}
	}
	private function _insert_cpt($old_event){
		global $wpdb;
		//convert 3.1 event status to 4.1 CPT status
		//for refrence, 3.1 event stati available for setting are:  
//		$status = array(array('id' => 'A', 'text' => __('Public', 'event_espresso')), array('id' => 'S', 'text' => __('Waitlist', 'event_espresso')), array('id' => 'O', 'text' => __('Ongoing', 'event_espresso')), array('id' => 'R', 'text' => __('Draft', 'event_espresso')), array('id' => 'D', 'text' => __('Deleted', 'event_espresso')));
//		and the json api uses teh following to convert from 3.1 to 4.0
//		'S'=>'secondary/waitlist',
//		'A'=>'active',
//		'X'=>'denied',
//		'IA'=>'inactive',
//		'O'=>'ongoing',
//		'P'=>'pending',
//		'R'=>'draft',x
//		'D'=>'deleted');
//		4.1 Event Post stati are the normal post statis 
//		(publish,future,draft,pending,private,trash,auto-draft,inherit)
//		and 3 custom ones: cancelled,postponed,sold_out
		$status_conversions = array(
			'R'=>'draft',
			'X'=>'draft',//4.1 doesn't have a "not approved for publishing" status
			'P'=>'pending',
			'IA'=>'publish',//IA=inactive in 3.1: events were switched to this when they expired. in 4.1 that's just calculated
			'O'=>'publish',//@todo: there is no ongoing events in 4.1 right?
			'A'=>'publish',
			'S'=>'publish',//@todo: what DO we do with secondary events?
			'D'=>'trash',
		);
		if($old_event['event_status']=='IA'){//its ineactive eh? well is it sold_out, postponed, expired, or cancelled?
			//check if we're ahead or behind the start time
			
			
		}
		$post_status = $status_conversions[$old_event['event_status']];
		$event_meta = maybe_unserialize($old_event['event_meta']);
		$cols_n_values = array(
			'post_title'=>$old_event['event_name'],//EVT_name
			'post_content'=>$old_event['event_desc'],//EVT_desc
			'post_name'=>$old_event['event_identifier'],//EVT_slug
			'post_date'=>$event_meta['date_submitted'],//EVT_created NOT $old_event['submitted']
			'post_excerpt'=>wp_trim_words($old_event['event_desc'],50),//EVT_short_desc
			'post_modified'=>$old_event['submitted'],//EVT_modified
			'post_author'=>$old_event['wp_user'],//EVT_wp_user
			'post_parent'=>null,//parent maybe get this from some REM field?
			'menu_order'=>null,//EVT_order
			'post_type'=>'espresso_events',//post_type
			'post_status'=>$post_status,//status
		);
		$datatypes = array(
			'%s',//EVT_name
			'%s',//EVT_desc
			'%s',//EVT_slug
			'%s',//EVT_created
			'%s',//EVT_short_desc
			'%s',//EVT_modified
			'%s',//EVT_wp_user
			'%d',//post_parent
			'%d',//EVT_order
			'%s',//post_type
			'%s',//status
		);	
		$success = $wpdb->insert($this->_new_table,
				$cols_n_values,
				$datatypes);
		if( ! $success ){
			$this->add_error($this->get_migration_script->_create_error_message_for_db_insertion($this->_old_table, $old_event, $this->_new_table, $cols_n_values, $datatypes));
			return 0;
		}
		return $wpdb->insert_id;
	}
	
	private function _insert_event_meta($old_event,$new_cpt_id){
		global $wpdb;
		$event_meta = maybe_unserialize($old_event['event_meta']);
//		for reference, 3.1 'default_payment_status' are: $default_payment_status = array(
//	array('id' => "", 'text' => 'No Change'),
//	array('id' => 'Incomplete', 'text' => 'Incomplete'),
//	array('id' => 'Pending', 'text' => 'Pending'),
//	//array('id' => 'Completed', 'text' => 'Completed')
//);
		$old_default_reg_stati_conversions = array(
			''=>'RNA',
			'Incomplete'=>'RNA',
			'Pending'=>'RPN'
		);
		$cols_n_values = array(
			'EVT_ID'=>$new_cpt_id,//EVT_ID_fk
			'EVT_display_desc'=> 'Y' == $old_event['display_desc'],
			'EVT_display_reg_form'=> 'Y'== $old_event['display_reg_form'],
			'EVT_visible_on'=> $old_event['visible_on'],
			'EVT_allow_multiple'=> 'Y' == $old_event['allow_multiple'],
			'EVT_additional_limit'=> $old_event['additional_limit'],
			'EVT_additional_attendee_reg_info' => $event_meta['additional_attendee_reg_info'],
			'EVT_default_registration_status' => isset($old_default_reg_stati_conversions[$event_meta['default_payment_status']]) ? $old_default_reg_stati_conversions[$event_meta['default_payment_status']] : 'RNA',
			'EVT_require_pre_approval'=>$old_event['require_pre_approval'],
			'EVT_member_only'=>$old_event['member_only'],
			'EVT_phone'=> $old_event['phone'],
			'EVT_allow_overflow' => 'Y' == $old_event['allow_overflow'],
			'EVT_timezone_string'=> $old_event['timezone_string'],
			'EVT_external_URL'=>$old_event['externalURL'],
			'EVT_donations'=>false//doesnt exist in 3.1
			
		);
		$datatypes = array(
			'%s',//EVT_ID
			'%d',//EVT_display_desc
			'%d',//EVT_display_reg_form
			'%s',//EVT_visible_on
			'%d',//EVT_allow_mutliple
			'%d',//EVT_additional_limit
			'%d',//EVT_additional_attendee_reg_info
			'%d',//EVT_default_registration_status
			'%d',//EVT_rqeuire_pre_approval
			'%d',//EVT_member_only
			'%s',//EVT_phone
			'%d',//EVT_allow_overflow
			'%s',//EVT_timezone_string
			'%s',//EVT_external_URL
			'%d',//EVT_donations
		);
		$success = $wpdb->insert($this->_new_meta_table,
				$cols_n_values,
				$datatypes);
		if( ! $success ){
			$this->add_error($this->get_migration_script->_create_error_message_for_db_insertion($this->_old_table, $old_event, $this->_new_table, $cols_n_values, $datatypes));
			return 0;
		}
		return $wpdb->insert_id;
	}
	
	
}