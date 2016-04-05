<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Reviews_list extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Review', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Reviews', 'sp' ), //plural name of the listed records
			'ajax'     => true //should this table support ajax?

		] );

	}

	/**
	 * Retrieve customer’s data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_rsr_reviews( $per_page = 20, $page_number = 1 ) {

	  global $wpdb;
	  $table_name = $wpdb->prefix . 'rsr_reviews';
	  $sql = "SELECT * FROM $table_name";

	  if ( ! empty( $_REQUEST['orderby'] ) ) {
	    $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
	    $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
	  }

	  $sql .= " LIMIT $per_page";

	  $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


	  $result = $wpdb->get_results( $sql, 'ARRAY_A' );

	  return $result;
	}

	/**
	 * Delete a review.
	 *
	 * @param int $id customer ID
	 */
	public static function delete_review( $id ) {
	  global $wpdb;
	  $table_name = $wpdb->prefix . 'rsr_reviews';
	  $wpdb->delete(
	    "$table_name",
	    [ 'id' => $id ],
	    [ '%d' ]
	  );
	}

	public static function approve_review( $id ) {
	  global $wpdb;
	  $table_name = $wpdb->prefix . 'rsr_reviews';
	  $wpdb->update(
	    "$table_name",
	    array("approved" => 1),
	    array("id" => $id),
	    array("%d"),
	    array("%d")
	  );
	}

	public static function record_count() {
	  global $wpdb;
	  $table_name = $wpdb->prefix . 'rsr_reviews';
	  $sql = "SELECT COUNT(*) FROM $table_name";
	  return $wpdb->get_var( $sql );
	}
	public function no_items() {
	  _e( 'No Reviews avaliable.', 'sp' );
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

	  // create a nonce
	  $delete_nonce = wp_create_nonce( 'sp_delete_review' );
	  $approve_nonce = wp_create_nonce( 'sp_approve_review' );

	  $title = '<strong>' . $item['name'] . '</strong>';
	  if ($item['approved'] == 0) {
	  $actions = [
	  	'edit'      => sprintf('<a href="?page=%s&action=%s&review=%s&_wpnonce=%s">Approve</a>',$_REQUEST['page'],'approve',absint( $item['id'] ), $approve_nonce),
	    'delete' => sprintf( '<a href="?page=%s&action=%s&review=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
	  ];
	  } else {
	   $actions = [
	    'delete' => sprintf( '<a href="?page=%s&action=%s&review=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
	  ];
	  }
	  


	  return $title . $this->row_actions( $actions );
	}



	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
	  switch ( $column_name ) {
	    case 'name':
	    case 'text':
	    case 'rating':
	    case 'post_id':
	    case 'approved':
	      return $item[ $column_name ];
	    default:
	      return print_r( $item, true ); //Show the whole array for troubleshooting purposes
	  }
	}

	public function column_post_id( $item ) {
	   $title = '<strong>' . $item['post_id'] . '</strong>';
	   return $title ;

	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
	  $columns = [
	    'name'    => __( 'Name', 'sp' ),
	    'text' => __( 'Text', 'sp' ),
	    'rating'    => __( 'Rating', 'sp' ),
	    'post_id'    => __( 'Post ID', 'sp' ),
	    'approved'    => __( 'Approved', 'sp' )
	  ];

	  return $columns;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

	  $this->_column_headers = $this->get_column_info();

	  /** Process bulk action */
		$this->process_bulk_action();

	  $per_page     = $this->get_items_per_page( 'reviews_per_page', 5 );
	  $current_page = $this->get_pagenum();
	  $total_items  = self::record_count();

	  $this->set_pagination_args( [
	    'total_items' => $total_items, //WE have to calculate the total number of items
	    'per_page'    => $per_page //WE have to determine how many items to show on a page
	  ] );


	  $this->items = self::get_rsr_reviews( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_review' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_review( absint( $_GET['review'] ) );

				wp_redirect( esc_url( add_query_arg() ) );
				exit;
			}

		}
		if ( 'approve' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_approve_review' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::approve_review( absint( $_GET['review'] ) );

				wp_redirect( esc_url( add_query_arg() ) );
				exit;
			}

		}

		
	}

}


class SP_Plugin {

	// class instance
	static $instance;

	// reviews WP_List_Table object
	public $reviews_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			'Really Simple Reviews Plugin Page',
			'Really Simple Reviews',
			'manage_options',
			'really_simple_reviews',
			[ $this, 'plugin_settings_page' ]
		);

		add_action( "load-$hook", [ $this, 'screen_option' ] );

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap">
			<h2>Really Simple Reviews</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->reviews_obj->prepare_items();
								$this->reviews_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Reviews',
			'default' => 5,
			'option'  => 'reviews_per_page'
		];

		add_screen_option( $option, $args );

		$this->reviews_obj = new Reviews_List();
	}


	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


add_action( 'plugins_loaded', function () {
	SP_Plugin::get_instance();
} );
