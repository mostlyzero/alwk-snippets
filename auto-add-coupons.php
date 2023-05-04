<?php

function alwkMasterclassSale() {
	
	global $wp;
	
	if (isset($_GET['coupon'])) {
		return; // in case user has input their own coupon
	}
	
	$page_ids = array(	3378, 	// beginners
						126,	// rural
						1498,	// taos
						3235,	// fall
						9509,	// simple
						3887,	// rose		
						5120,	// spring
						5645,	// peonies
						7049,	// summer
						10105,	// improve
						10323,	// time
						22274,	// fall still life
						22458,	// color theory
					  	24023,	// garden rose
	);
	
	$page_ids_always_on = array(
						/* 22425,	// masterclass bundle */
	);
	
	$post = get_post();
	
	// 'ALWAYS ON' auto apply coupons
	
	// show post/popup if time matches
	if ( in_array($post->ID, $page_ids_always_on) ){

		wp_redirect( home_url( $wp->request ) . '?coupon=MCBUNDLE' );
		die;

	}	
	
	
	
	// SCHEDULE auto apply coupon below
	
	
	$do_when = 	array(
		'show'	=> '2022-11-28 00:00:00',		/* the date / time to enable the auto apply */
		'hide'	=> '2022-12-22 23:59:59'		/* the date / time to disable the auto apply */
	);
	
	$do_when_50 = 	array(
		'show'	=> '2022-12-23 00:00:00',		/* the date / time to enable the auto apply */
		'hide'	=> '2022-12-26 23:59:59'		/* the date / time to disable the auto apply */
	);
	
	$do_when_60 = 	array(
		'show'	=> '2023-04-21 00:00:00',		/* the date / time to enable the auto apply */
		'hide'	=> '2023-04-24 23:59:59'		/* the date / time to disable the auto apply */
	);
	
	/*if ($_SERVER['REMOTE_ADDR'] == '216.230.47.197') {
		$do_when_60 = 	array(
			'show'	=> '2023-04-01 00:00:00',		
			'hide'	=> '2023-04-24 23:59:59'		
		);
	}
	*/
	
	$do_after	= 	array(
		'show'	=> '2022-12-27 00:00:00',		/* the date / time to enable the auto apply */
		'hide'	=> '2023-01-12 23:59:59'		/* the date / time to disable the auto apply */
	);
		
	if ( in_array($post->ID, $page_ids) ) {

		alwk_timed_coupon_redirect($do_when, 'MC25');
		alwk_timed_coupon_redirect($do_when_50, 'MC50');
		alwk_timed_coupon_redirect($do_when_60, 'MC60');
		alwk_timed_coupon_redirect($do_after, 'MC25');
		
	}
}

function alwk_timed_coupon_redirect($schedule, $coupon){
	
	global $wp;
	
	// get/set the timezone setting from wordpress
	$tz = wp_timezone_string();

	// create datetime object 
	$dt = new DateTime();
	$dt->setTimezone(new DateTimeZone($tz));

	// current timestamp in correct time zone
	$ts_now = $dt->getTimestamp();

	// change datetime object to the 'show' time
	$dt = new DateTime( $schedule['show'] );	
	$dt->setTimezone(new DateTimeZone($tz));

	// timestamp for show time
	$ts_show = $dt->getTimestamp();

	// change datetime object to the 'hide' time
	$dt = new DateTime( $schedule['hide'] );	
	$dt->setTimezone(new DateTimeZone($tz));

	// timestamp for hide time
	$ts_hide = $dt->getTimestamp();

	// show post/popup if time matches
	if ( $ts_now > $ts_show && $ts_now < $ts_hide ){

		wp_redirect( home_url( $wp->request ) . '?coupon=' . $coupon );
		die;

	}		
}

add_action( 'template_redirect', 'alwkMasterclassSale' );
