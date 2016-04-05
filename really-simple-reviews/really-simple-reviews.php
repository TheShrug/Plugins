<?php
/*
Plugin Name: Really Simple Reviews
Description: Allows reviews of post types with the click of a star rating.
Version:     1.0
Author:      Stewart Gordon
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {License URI}.
*/

global $rsr_db_version;
$rsr_db_version = '1.0';


/* Install Function */
function rsr_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rsr_reviews';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
	  id INT NOT NULL AUTO_INCREMENT,
	  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  post_id INT,
	  name varchar(55),
	  text text,
	  rating INT,
	  ip INT NOT NULL,
	  approved TINYINT(1) DEFAULT '0',
	  UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

}

/* Add Admin Page here in the future */
include_once 'includes/rsr-options.php';


/* Enqueue Files */
function plugin_files_enqueue() {
	wp_enqueue_style( 'rsr-styles',  plugins_url( '/css/rsr-styles.css', __FILE__ ) );
    wp_enqueue_script( 'rsr-ajax', plugins_url( '/js/rsr-ajax.js', __FILE__ ), array('jquery'), '1.0.0', true );
    wp_localize_script( 'rsr-ajax', 'ajax_object', array('ajax_url' => admin_url( 'admin-ajax.php' )));
}

add_action( 'wp_enqueue_scripts', 'plugin_files_enqueue' );

/* Hooks for Ajax Functions */
add_action( 'wp_ajax_nopriv_rsr_ajax', 'rsr_ajax' );
add_action( 'wp_ajax_rsr_ajax', 'rsr_ajax' );
add_action( 'wp_ajax_nopriv_rsr_ajax_full', 'rsr_ajax_full' );
add_action( 'wp_ajax_rsr_ajax_full', 'rsr_ajax_full' );

/* Ajax Functions */
function rsr_ajax() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rsr_reviews';
	// get values from jquery
	$post_id = $_POST['postid'];
	$rating = $_POST['rating'];
	$remote  = $_SERVER['REMOTE_ADDR'];
	$iplong = ip2long($remote);

	$results = $wpdb->get_col( 'SELECT id from ' . $table_name . ' WHERE post_id = ' . $post_id . ' AND ip = ' . $iplong . '' );
	
	$wpdb->replace( 
		$table_name, 
		array( 
	        'id' => $results[0],
			'post_id' => $post_id, 
			'rating' => $rating,
			'ip' => $iplong,
			'approved' => 1,
			'time' => current_time( 'mysql' )
		), 
		array( 
	        '%d',
			'%d', 
			'%d', 
			'%d', 
			'%d'
			
		) 
	);
	rsr_reviews($post_id);
	die();
}

function rsr_ajax_full() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rsr_reviews';
	// get values from jquery
	$post_id = $_POST['postid'];
	$rating = $_POST['rating'];
	$name = $_POST['name'];
	$text = $_POST['text'];
	$remote  = $_SERVER['REMOTE_ADDR'];
	$iplong = ip2long($remote);


	
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'post_id' => $post_id, 
			'rating' => $rating,
			'name' => $name,
			'text' => $text,
			'ip' => $iplong,
			'time' => current_time( 'mysql' ) 
		), 
		array( 
	        '%s',
			'%d', 
			'%s', 
			'%s', 
			'%d'
			
		) 
	);
	
	die();
}


/* Function to return full database table */
function get_rsr_reviews_array() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rsr_reviews';
	$post_id = $post->ID;
	$results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . '' );
	return $results;
}

/* Function to return database table of $post_id attribute, defaults to current page/post ID if no variable is provided */
function rsr_reviews_array($post_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rsr_reviews';
	if(empty($post_id)) {
		$post_id = get_the_ID();
	} 
	$results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE post_id = ' . $post_id . '' );
	return $results;
}

/* Main Function */
function rsr_reviews($post_id, $full) {
		global $wpdb;
		if(empty($post_id)) {
			$post_id = get_the_ID();
		}
		$table_name = $wpdb->prefix . 'rsr_reviews';
		// get values from database
		$results = $wpdb->get_col( 'SELECT rating from ' . $table_name . ' WHERE post_id = ' . $post_id . '' );
		// separate into useful values
		$count = count($results);
		$sum = array_sum($results);
		$avg = $sum / $count;

		/* Round up to 1 decimal*/
		$avgRound = round($avg, 1, PHP_ROUND_HALF_UP);
		$num = 0;

		if($full == true) {
			$type = 'full';
		} else {
			$type = 'simple';
		}

		/* Display Review Form */
		echo '<div class="rsr-reviews ' . $type . '" data-id="' . $post_id . '">';

		// if its simple form just show stars
		if ($full == false) {
			while($num < 5) {
				$num++;
				if ($avgRound >= $num) {
					echo '<span data-rating="' . $num . '"><i class="fa fa-star"></i></span>';
				} else {
					if ($num - $avgRound < .25 ) {
						echo '<span data-rating="' . $num . '"><i class="fa fa-star"></i></span>';
					} elseif ($num - $avgRound < .75) {
						echo '<span data-rating="' . $num . '"><i class="fa fa-star-half-o"></i></span>';
					} else {
						echo '<span data-rating="' . $num . '"><i class="fa fa-star-o"></i></span>';
					}
				}
			}
		}

		/* Display Form if full is true */
		if ($full == true) {
			echo '<div class="star-container">';
			for ($i=1; $i < 6; $i++) { 
				echo '<span data-rating="' . $i . '"><i class="fa fa-star-o"></i></span>';
			}
			echo '</div>';
			echo '<form class="rsr-full-review-form">';
			echo '<input type="text" name="name" placeholder="Your Name">';
			echo '<textarea name="text" placeholder="Your Review"></textarea>';
			echo '<button type="submit" class="rsr-submit">Submit Review</button>';
			echo '</form>';


		}
		// Close reviews container
		echo '</div>';
}

register_activation_hook( __FILE__, 'rsr_install' );
