<?php

defined( 'ABSPATH' ) || exit;

if( !class_exists( 'Skywin_Jumplog' ) ):

class Skywin_Jumplog {
	
	public static $instance = null;
		
	private $name;
	
	private $title;
	
	private $shortcode;
	
	private $id;
	
	private $conn;
	
	private $items;
	
	private $pagination_args;
	
	private $typejumps;
	
	private $column_primary;
	
	public function __construct( $args = array() ) {
		error_log('Skywin_Jumplog::__construct');
						 
		$this->name = "jumplog";
		
		$this->title = "Jumplog";
		
		$this->shortcode = "skywin-" . $this->name;
		
		$this->column_primary = "TimeForInsert";
		
		$this->create_page_if_not_exist();
		
		$this->add_actions();
																	
		$this->add_filters();
			
		$this->add_shortcodes();		
			
	}
		
	public function add_actions() {
		
		error_log('Skywin_Jumplog::add_actions');
		
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 10 );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_styles' ), 10 );
		
		add_action( 'wp_ajax_list_'. $this->name .'_table', array ( $this, 'ajax_list_table' ), 10 );
		
		add_action( 'wp_ajax_nopriv_list_'. $this->name .'_table', array ( $this, 'ajax_list_table' ), 10 );
		
		add_action('template_redirect', array($this, 'redirect_myaccount'));
		
	}
	
	public function add_filters() {
		
	}
	
	public function wp_enqueue_scripts( ) {
		error_log('Skywin_Jumplog::wp_enqueue_scripts');
		
		if ( is_page( $this->name ) ) :
			
		wp_enqueue_script( 'skywin-jumplog-js', plugin_dir_url( WC_Skywin_Hub::PLUGIN_FILE ) . 'assets/js/skywin-jumplog.js', array('jquery'), null, true );
		
		wp_localize_script( 'skywin-jumplog-js', 'ajax_list_table_params', array(
			'ajax_url' =>  admin_url( 'admin-ajax.php' ),
			'action' => 'list_'. $this->name .'_table',
			'nonce' => wp_create_nonce( 'ajax_list_table_nonce' )
		));
				
		endif;
		
	}
	
	public function wp_enqueue_styles( ) {
		
		if ( is_page( $this->name ) ) :
		
		wp_enqueue_style( 'daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css' );
		
		wp_enqueue_style( 'skywin-jumplog-css', plugin_dir_url( WC_Skywin_Hub::PLUGIN_FILE ) . 'assets/css/skywin-jumplog.css' );
		
		endif;
	
	}
	
	public function add_shortcodes() {
		
		error_log('Skywin_Jumplog::add_shortcodes');
		
		add_shortcode( $this->shortcode, array($this, 'display') );
	
	}
			
	public function create_page_if_not_exist() {
		error_log('Skywin_Jumplog::create_page_if_not_exist');
		
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $this->name,
			'post_title'     => $this->title,
			'post_content'   => '<!-- wp:shortcode -->['. $this->shortcode .']<!-- /wp:shortcode -->',
			'post_parent'    => 0,
			'comment_status' => 'closed',
		);
		 
		if ( ! get_page_by_path( $this->name, OBJECT, 'page') ) { 
			
			$new_page_id = wp_insert_post( $page_data );
		
		}
		
	}
		
	public function redirect_myaccount(){
		error_log('Skywin_Jumplog::redirect_myaccount');
		
		if( is_page( $this->name ) && ! is_user_logged_in() ) {
			
			wp_redirect( wc_get_page_permalink( 'myaccount' ) );
		
		}
		
	}
	
	public function prepare(){	
		error_log('Skywin_Jumplog::prepare');
				
		$this->typejumps = $this->get_typejumps();
		
		$this->items = $this->get_items();
		
		$all_items = count( $this->items );
		
		$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : '';

		$endDate = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : '';
			
		$user = wp_get_current_user();
		
		$columns = $this->get_columns();
		
		$items = $this->items;
		
		$items = is_array($items) ? $items : array();
		
		if (count($items)) {

			$items = $this->filter_items( $items );
			
			$items = $this->sort_items( $items );

		}

		$perPage = 500;
		
		$currentPage = $this->get_pagenum();
		
		$totalItems = count( $items );
		
		$offset = ( ( $currentPage-1 )*$perPage );
		
		$total_pages = ceil($totalItems/$perPage);
				
		$args = array(
			'all_items' 	=> $all_items,
			'total_items' 	=> $totalItems,
			'per_page'    	=> $perPage,
			'total_pages' 	=> $total_pages,
			'offset' 		=> $offset,
			'paged' 		=> $currentPage,
		);
				
		$this->pagination_args = $args;
		
		$items = array_slice($items, $offset, $perPage);
			
		$this->items = $items;
		
	}
	
	public function ajax_list_table () {
		error_log('Skywin_Jumplog::ajax_list_table');
		
		check_ajax_referer('ajax_list_table_nonce', 'security');
		
		$response = array();
		
		$response['html'] = $this->display();
		
		$response['items'] = $this->items;
		
		$response['pagination'] = $this->pagination_args;
		
		wp_send_json( $response );
		
	}

	public function get_items( ){
		error_log('Skywin_Jumplog::get_items');
		
		$items = array();
		
		if ( ($user = wp_get_current_user()) && is_user_logged_in() ) {
						
			$items = wc_skywin_api()->get_jumplog( $user->Skywin_InternalNo );
				
		}
		
		return $items; 

	}
	
	public function get_typejumps () {
		
		error_log('Skywin_Jumplog::get_typejumps');
				
		$items = array();
		
		if ( $user = wp_get_current_user() && is_user_logged_in() ) {
			
			$items = wc_skywin_api()->get_typejumps();
			
		}
				
		return $items;

	}
	
	public function filter_items( $items ) {
		error_log('Skywin_Jumplog::filter_items');
		
		if ( !isset( $_REQUEST['startDate'] ) || ( isset($_REQUEST['latest']) && $_REQUEST['latest'] === 'true') ) {
			
			$items = array_slice($items, -50, 50, true);
			
			$first = reset($items);
			
			$last = end($items);
			
			$start = date("Y-m-d", strtotime($first["TimeForInsert"]) );
			$end = date("Y-m-d", strtotime($last["TimeForInsert"]) );
			
			add_action('wp_footer', function () use ($start, $end) {
				
				?>
				
				<script>
					var startDate = <?= json_encode($start) ?>;
					var endDate = <?= json_encode($end) ?>;
				</script>
				
				<?php
				
			} );

			return $items;
			
		} else {
		
			$items = array_filter($items, function ( $val ) {
									
				$startDate = $_REQUEST['startDate'];
				
				$endDate = $_REQUEST['endDate'];
							
				$date = date("Y-m-d", strtotime($val["TimeForInsert"]) );
				
				$start = date("Y-m-d", strtotime($startDate) );
				
				$end = date("Y-m-d", strtotime($endDate) );
				
				return $date >= $start && $date <= $end;
								
			});
		
		}	
		
		return $items;
	
	}
	
	public function sort_items( $items ) {
		error_log('Skywin_Jumplog::sort_items');
		
		$order = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc';

		$orderby = isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'TimeForInsert';
				
		$sortable = $this->get_sortable_columns();
		
		if ( is_array( $items ) ) {
			usort($items, function ($a, $b) use ($order, $orderby, $sortable) {
				
				$orderby = $sortable[$orderby];

				if( $order === 'asc' ) {
					
					if ( $orderby === 'TimeForInsert' ) {
						return strtotime( $a[$orderby] ) <=> strtotime( $b[$orderby] );
					}
					
					return $a[$orderby] <=> $b[$orderby];
				}
	
				if( $order === 'desc' ) {
					
					if ( $orderby === 'TimeForInsert' ) {
						return strtotime( $b[$orderby] ) <=> strtotime( $a[$orderby] );
					}
					
					return $b[$orderby] <=> $a[$orderby];
				}
	
			});
		}
		
		return $items;
	
	}
		
	public function get_columns() {
		error_log('Skywin_Jumplog::get_columns');
		
		$columns['TimeForInsert'] = __('Date', 'woo-skywin-hub');
		
		$columns['PlaneReg'] = __('Airplane', 'woo-skywin-hub');
		
		$columns['LoadNo'] = __('Load', 'woo-skywin-hub');
		
		$columns['JumpNo'] = __('Jump', 'woo-skywin-hub');
		
		$columns['Jumptype'] = __('Type', 'woo-skywin-hub');
		
		$columns['Altitude'] = __('Altitude', 'woo-skywin-hub');
		
		$columns['Price'] = __('Price', 'woo-skywin-hub');
		
		return $columns;	
	
	}
	
	public function column_default( $item, $column_name ){
		error_log('Skywin_Jumplog::column_default');
		
		switch( $column_name ) {
			
			case 'TimeForInsert':
			
			case 'PlaneReg':
			
			case 'LoadNo':
			
			case 'JumpNo':
			
			case 'Jumptype':
			
			case 'Altitude':
			
			case 'Price':
			
				return $item[$column_name];
			
			default:		
			
				return print_r( $item, true ) ;
		}
		
	}
	
	public function column_TimeForInsert( $item ) {
		return '<span style="white-space: nowrap;">'. $item['TimeForInsert'] .'</span>';
	}
	
	public function column_Jumptype( $item ) {
		
		$typejumps = $this->typejumps;

		$name = "";
		
		foreach ($typejumps as $type) {
		
			if ( $type["Jumptype"] === $item["Jumptype"] ) {
				
				$name = $type["JumptypeName"];
			
			}
		
		}
		
		return '<span class="value" style="">' . $name . '</span>';
	
	}
	
	public function get_primary_column () {
		
		return $this->column_primary;
	
	}
	
	public function get_sortable_columns() {
				
		return array( 'TimeForInsert' => 'TimeForInsert' );
	
	}
	
	public function get_hidden_columns(){
	
		return array( 'AccountNo', 'TransNo', 'TransType', 'Regdate' );
	
	}
	
	public function get_pagenum() {
		
		$pagenum = get_query_var('paged') ?  get_query_var('paged') : 0;
		
		if ( isset( $_REQUEST['paged'] ) ) {
		
			$pagenum = $_REQUEST['paged'] ?  $_REQUEST['paged'] : 0;
		
		}
		
		if ( isset( $this->pagination_args['total_pages'] ) && $pagenum > $this->pagination_args['total_pages'] ) {
		
			$pagenum = $this->pagination_args['total_pages'];
		
		}
		
		return max( 1, $pagenum );
	
	}
			
	public function pagination( $which ) {
		error_log('Skywin_Jumplog::pagination');
		
		if ( empty( $this->pagination_args ) ) {
			
			return;
			
		}
				
		$all_items 		= $this->pagination_args['all_items'];
		
		$total_items    = $this->pagination_args['total_items'];
		
		$total_pages	= $this->pagination_args['total_pages'];
									
		$current = $this->get_pagenum();
		
		$removable_query_args = wp_removable_query_args();
				
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$disable_first = false;
		
		$disable_last  = false;
		
		$disable_prev  = false;
		
		$disable_next  = false;
		
		if ( 1 == $current ) {
		
			$disable_first = true;
		
			$disable_prev  = true;
		
		}
		
		if ( 2 == $current ) {
		
			$disable_first = true;
		
		}
		
		if ( $total_pages == $current ) {
		
			$disable_last = true;
		
			$disable_next = true;
		
		}
		
		if ( $total_pages - 1 == $current ) {
		
			$disable_last = true;
		
		}
				
		if ($total_pages > 1) {
			
			if ( $disable_first ) {
				
				$page_links[] = "<a class='tablenav-pages-navspan button disabled' disabled>&laquo;</a>";
			
			} else {
			
				$page_links[] = sprintf( "<a class='first-page button' href='%s'>%s</a>", esc_url( add_query_arg( array( 'paged' => 1 ), $current_url ) ), '&laquo;' );
				
			}
	
			if ( $disable_prev ) {
				
				$page_links[] = "<a class='tablenav-pages-navspan button disabled' disabled>&lsaquo;</a>";
			
			} else {
			
				$page_links[] = sprintf( "<a class='prev-page button' href='%s'>%s</a>", esc_url( add_query_arg( array('paged' => max( 1, $current - 1 ) ), $current_url ) ), '&lsaquo;' );
			
			}
	
			$html_current_page  = sprintf( "%s", number_format_i18n( $current ) );
	
			$html_total_pages = sprintf( "%s", number_format_i18n( $total_pages ) );
			
			$page_links[] = '<span class="current-page">' .sprintf( _x( '%1$s of %2$s', 'paging' ) , $html_current_page, $html_total_pages ) .'</span>';
			
			if ( $disable_next ) {
				$page_links[] = "<a class='tablenav-pages-navspan button disabled' disabled>&rsaquo;</a>";
			} else {
				$page_links[] = sprintf( "<a class='next-page button' href='%s'>%s</a>", esc_url( add_query_arg( array('paged' => min( $total_pages, $current + 1 ) ), $current_url ) ), '&rsaquo;' );
			}
	
			if ( $disable_last ) {
				$page_links[] = "<a class='tablenav-pages-navspan button disabled' disabled>&raquo;</a>";
			} else {
				$page_links[] = sprintf( "<a class='last-page button' href='%s'>%s</a>", esc_url( add_query_arg( array( 'paged' => $total_pages ), $current_url ) ), '&raquo;' );
			}
		}
		
		$total_items = '<span id="total_items">'. sprintf( _n( '%s', '%s', $total_items ) .' of '. _n( '%s item', '%s items', $all_items ), number_format_i18n( $total_items ), number_format_i18n( $all_items ) ) .'</span>';
		
		$output =  implode( " ", $page_links );

		echo '<div class="tablenav-pages" id="tablenav-'. esc_attr( $which ) .'">';
		
		echo $total_items;
		
		echo '<span style="white-space: nowrap">'. $output .'</span>';
		
		echo '</div>';

	}
	
	public function get_column_headers() {
		
		$current_url = get_permalink();
		
		$current_orderby = isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		
		$current_order = isset( $_REQUEST['order'] ) && 'desc' === $_REQUEST['order']  ? 'desc' : 'asc';
				
		$sortable = $this->get_sortable_columns();
		
		foreach( $this->get_columns() as $column_id => $column_name ) {
			
			$class = array("column-$column_id");
			 
			$hidden_columns = $this->get_hidden_columns();
			
			if ( in_array( $column_id, $hidden_columns) ) {
			
				continue;
			
			}
			
			if ( is_array( $sortable ) && in_array($column_id, $sortable) ) {
			
				$class[] = "sortable";
			
			}
			
			if ( $this->column_primary ===  $column_id ) {
				
				$class[] = "column-primary";
			
			}
			
			if ( ! empty( $class ) ) {
			
				$class = 'class="'. implode(' ', $class) .'"'; 
			
			}
			
			echo '<th '. $class .' align="left">';
				
			if( array_key_exists( $column_id, $sortable ) ) {
														
				if ( $current_orderby === $column_id ) {
					
					$order = 'asc' === $current_order ? 'desc' : 'asc';
				
				} else {
				
					$order = 'asc';
				
				}
				
				$current_url = add_query_arg( array('orderby' => $column_id, 'order' => $order ) , $current_url);

				echo '<a href=' . esc_url( $current_url ) . '>' . esc_html($column_name) . '</a>';
					
			} else {
				
				echo  esc_html($column_name);
				
			};
			
			echo '</th>';
		
		};
	}
	
	public function display_rows() {

		if ( $this->items ) {
			
			$columns = $this->get_columns();
						
			$hidden_columns = $this->get_hidden_columns();
			
			foreach( $this->items as $item ) {
				
				echo '<tr class="body_row">';
								
				foreach( $columns as $column_id => $column_name ) {
					
					if (in_array( $column_id, $hidden_columns) ) {
						continue;
					}
				
					if ( method_exists( $this, 'column_' . $column_id )) {
						
						echo '<td valign="top">';
						
						echo '<span class="title is-sm-screen hidden">' . $column_name . '</span>';
						
						echo call_user_func( array( $this, 'column_' . $column_id ), $item );
						
						echo '</td>';
						
					} else {
						
						echo '<td valign="top">';
						
						echo '<span class="title is-sm-screen hidden">' . $column_name . '</span>';
						
						echo $item[$column_id];
						
						echo '</td>';
						
					}
				
				};
				
				echo '</tr>';
			
			};
		} else {
			
			echo '<tr>';
			
			echo '<td valign="top" colspan="' . esc_attr( count( $this->get_columns()) ) . '">';
			
			echo '<span class="value">' . __('No result', 'woo-skywin-hub') . '</span>';
			
			echo '</td>';
			
			echo '</tr>';
		
		}

	}
	
	public function daterange(){
								
		echo '<div id="reportrange">';
		
		echo '<i class="fa fa-calendar"></i>&nbsp;';
		
		echo '<span></span>';
		
		echo '<i class="fa fa-caret-down"></i>';
		
		echo '</div>';
				
	}
	
	public function list_table(){
		
		ob_start();
		
		?>
			
		<table id="list-table" class="is-style-stripes">
		
			<thead>

				<tr>
		
					<?php $this->get_column_headers(); ?>
		
				</tr>
		
			</thead>
		
			<tbody>
		
				<?php $this->display_rows(); ?>
		
			</tbody>
		
			<tfoot>
		
				<tr>
			
					<?php $this->get_column_headers(); ?>
				
				</tr>
			
			</tfoot>
		
		</table>
		
		<?php
		
		$html = ob_get_clean();
		
		echo $html;
		
	}
	
	public function display(){
		
		$this->prepare();
		
		ob_start();
		
		?>
		
		<style>
			.visible-xs {
				display: none;
			}
			
			.is-sm-screen{
			
				display:none;
				
			}
						
			tbody #list-table tr:nth-child(odd) {
				  background-color: var(--table--stripes-background-color);
			}
						
			#reportrange{
				float:left;
				padding: 10px;
				border: 1px solid #000000;
			}
			
			.daterangepicker{
				color: black;
			}
						
			.tablenav-pages{
				float:right;
				padding: 6px;	
			}
					
			.tablenav-pages a.button{
				padding:6px 16px;
			}
			
			@media only screen and (max-width: 600px) {
				.visible-xs {
					display: block;
				}
						
				.is-sm-screen{
					display: inline-block;
				}
				
				.is-lg-screen{
					display: none;
				}
				
				#reportrange{
					max-width: 95vw;
				}
				
				.tablenav-pages {
					max-width: 95vw;
				}
				
				#list-table table, 
				#list-table thead, 
				#list-table tbody, 
				#list-table th, 
				#list-table td, 
				#list-table tr { 
					display: block;
				}
				
				
				#list-table thead th.column-primary~th{
					display: none;
				}
			 
				#list-table {
					max-width: 95vw;
				}
				
				#list-table thead tr { 
/* 					position: absolute;
					top: -9999px;
					left: -9999px; */
				}
				
				#list-table tfoot tr { 
						position: absolute;
						top: -9999px;
						left: -9999px;
					}
			 
				#list-table tbody td { 
					border: none;
					border-bottom: 1px solid #eee; 
					
					white-space: normal;
					text-align:left;
				}
				
				#list-table tbody td span.title{
					width: 50%;
				}
				
			    #list-table tbody td span.value{
				   display: inline-block;
				   width: 50%;
				}				
			}
			
		</style>
		
		<div class="" style="max-width:90vw">
		
			<?php $this->daterange(); ?>
			
			<?php $this->pagination('top'); ?>
			
			<?php $this->list_table(); ?>
			
			<?php $this->pagination('bottom'); ?>
		
		</div>
		
		<?php
		
		$html = ob_get_clean();
		
		return $html;
	
	}
						
	public static function instance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
			
	}
		
}

function wswh_jumplog() {
	return Skywin_Jumplog::instance();
}

$wswh_jumplog = wswh_jumplog();

endif;