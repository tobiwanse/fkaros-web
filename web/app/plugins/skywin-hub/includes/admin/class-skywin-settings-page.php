<?php

if ( !defined('ABSPATH') ) { exit; }

if ( !class_exists('Skywin_Admin_Settings_Page') ) :
	
class Skywin_Admin_settings_Page {
	private static $_instance = null;
	protected $args = [];
	protected $page_title;
	protected $menu_title;
	protected $menu_slug;
	protected $option_name;
	protected $option_group;
    protected $option_page;
	protected $user_capability;
    protected $current_tab;
	protected $settings;
    protected $sections;
    protected $tabs;
    public static function instance()
	{
		if ( is_null(self::$_instance) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    public function __construct()
    {
		$this->args             = $this->args();
		$this->menu_title       = $this->args['menu_title'];
		$this->page_title 		= $this->args['page_title'];
		$this->menu_slug        = $this->args['menu_slug'];
		$this->user_capability  = $this->args['user_capability'];
        $this->option_page      = "{$this->menu_slug}_{$this->current_tab}";

        $this->add_actions();
	}
	private function args ()
    {
		return [
			'menu_title'        => __('Skywin Hub', 'skywin-hub'),
            'page_title'        => __('Skywin Hub', 'skywin-hub'),
            'menu_slug'         => 'skywin',
			'user_capability'   => 'manage_options',
		];
	}
    private function add_actions()
    {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script'], 10, 1 );
		add_action( 'admin_menu', [ $this, 'register_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
    }
    public function filter_setting_by_tab( $settings )
    {
        global $current_tab;
        $this->option_page = "{$this->menu_slug}_{$current_tab}";
		$filter_setting = [];
		foreach( $settings as $setting){
			if( $current_tab == $setting['tab']){
				$filter_setting[] = $setting;
			}
		}
		return $filter_setting;
	}
    public function register_menu_page()
    {
		add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->user_capability,
			$this->menu_slug,
			[ $this, 'render_options_page' ]
		);
	}
    public function register_settings()
    {
        $settings = $this->filter_setting_by_tab(skywin_admin_settings()->admin_register_scripts()) ;
        $section_id = 0;
        foreach( $settings as $setting ){
            $custom_attributes = '';

            if( $setting['type'] == 'title' ){
                $this->render_title_field($setting);
                continue;
            }

            if ( $setting['type'] == 'sectionend' ) {
                add_settings_section(
                    $this->option_page . '_' . $section_id,
                    $setting['name'] ?? '',
                    [$this, 'section_callback'],
                    $this->option_page
                );
                $section_id++;
            } else {
                register_setting(
                    $this->option_page . '_settings',
                    $setting['id'] ?? ''
                );
                
                if( isset($setting['custom_attributes']) && !empty($setting['custom_attributes']) ){
                    foreach($setting['custom_attributes'] as $key => $value){
                        $custom_attributes .= "{$key}={$value} ";
                    }
                }

                add_settings_field(
                    $setting['id'] ?? '',
                    $setting['name'] ?? '',
                    array( $this, 'render_' . $setting['type'] . '_field' ),
                    $this->option_page,
                    $this->option_page .'_'. $section_id,
                    [
                        'label_for'         => esc_attr( $setting['id'] ?? '' ),
                        'name'              => esc_html( $setting['name'] ?? '' ),
                        'value'             => get_option( $setting['id'] ?? null, null ),
                        'attr'              => esc_attr( $custom_attributes ),
                        'class'             => esc_attr( $setting['class'] ?? '' ),
                        'desc'              => esc_html( $setting['desc'] ?? '' ),
                        'desc_tip'          => esc_html( $setting['desc_tip'] ?? '' ),
                        'options'           => $setting['options'] ?? array()
                    ]
                );
            }
        }
	}
    public function section_callback($args)
    {
    }
    public function enqueue_admin_script( $hook )
    {
		?>
		<script>
		( function() {			
			document.addEventListener( 'click', ( event ) => {
				
				const target = event.target;
				
				if ( ! target.closest( '.wpex-tabs a' ) ) {
					return;
				}
				
				//event.preventDefault();

				document.querySelectorAll( '.wpex-tabs a' ).forEach( ( tablink ) => {
					tablink.classList.remove( 'nav-tab-active' );
				} );
				
				target.classList.add( 'nav-tab-active' );
				
				activeTarget = target.getAttribute( 'data-tab' );
				
				document.querySelectorAll( '.wpex-options-form .wpex-tab-item' ).forEach( ( item ) => {
					if ( item.classList.contains( `wpex-tab-item--${activeTarget}` ) ) {
						item.style.display = 'block';
					} else {
						item.style.display = 'none';
					}
				} );
				localStorage.setItem( 'active_tab',  activeTarget);
			} );
			
			document.addEventListener( 'DOMContentLoaded', function () {

				active_tab = localStorage.getItem( 'active_tab') ?? '';

				document.querySelectorAll( '.wpex-tabs a' ).forEach( ( tablink ) => {
					
					if( active_tab == tablink.getAttribute( 'data-tab' ) ){
						tablink.classList.add( 'nav-tab-active' );						
					}
					
				} );
				
				document.querySelectorAll( '.wpex-options-form .wpex-tab-item' ).forEach( ( item ) => {
					if ( item.classList.contains( `wpex-tab-item--${active_tab}` ) ) {
						item.style.display = 'block';
					} else {
						item.style.display = 'none';
					}
				} );
				
			}, false );
		} )();
		</script>
		<?php
	}
	protected function sanitize_checkbox_field( $value = '', $field_args = [] )
    {
		return ( 'on' === $value ) ? 1 : 0;
	}
	protected function sanitize_select_field( $value = '', $field_args = [] )
    {
		$choices = $field_args['choices'] ?? [];
		if ( array_key_exists( $value, $choices ) ) {
			return $value;
		}
	}
	public function render_options_page()
    {
		if ( ! current_user_can( $this->user_capability ) ) {
			return;
		}
		if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
            $this->option_page .'_mesages',
            $this->option_page . '_message',
            esc_html__( 'Settings Saved', 'text_domain' ),
            'updated'
            );
		}
        $args = array(
            'tab' => $this->current_tab,
        );
        $url = add_query_arg($args, 'options.php');
        ?>
		<div class="wrap">
			<form action="<?php echo esc_url($url) ?>" method="post" class="skywin-options-form" autocomplete="off">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
            $this->render_tabs();
            settings_errors( $this->option_page . '_mesages' );
            ?>
				<?php
                do_settings_sections( "{$this->option_page}");
                settings_fields(  "{$this->option_page}_settings" );
                submit_button( __('Save Settings', 'skywin-hub') );
				?>
			</form>
		</div>
		<?php
	}
	protected function render_tabs()
    {
        $tabs = $this->tabs();
		if ( empty( $tabs ) ) {
			return;
		} ?>
		<h2 class="nav-tab-wrapper">
        <?php
        foreach ( $tabs as $id => $label ) {
            $active = $this->current_tab === $id ? ' nav-tab-active' : '';
            $args = array(
                'page' => $this->menu_slug,
                'tab' => $id
            );
            $url = add_query_arg($args);
            ?>
            <a href="<?php echo esc_url( $url ); ?>" data-tab="<?php echo esc_attr( $id ); ?>" class="nav-tab <?php echo $active ?>"><?php echo ucfirst( $label ); ?></a>
            <?php
        } ?>
        </h2>
		<?php
	}
    public function render_title_field($args)
    {
        //echo $args['name'];
    }
    public function render_info_field()
    {
        echo 'dsdfsd';
    }
	public function render_status_field( $args )
    {
        global $swdb;		
        $status = $swdb->status();
		?>
			<input
				type="text"
                id="<?php echo esc_attr( $args['label_for'] ); ?>"
				name="<?php echo esc_attr($args['label_for']) ?>"
				value="<?php echo esc_attr( $status ); ?>"
                class="<?php echo esc_attr($args['class']) ?>"
                <?php echo esc_attr($args['custom_attributes']) ?>
                readonly="true"
                >
		<?php
	}
	public function render_text_field( $args )
    {
        ?>
        <input 
            type="text" 
            id="<?php echo $args['label_for'] ?>"
            name="<?php echo $args['label_for'] ?>"
            value="<?php echo $args['value'] ?>"
            class="<?php echo $args['class']?>"
            <?php echo $args['attr'] ?>
        >
        <?php if ( $args['desc'] ) { ?>
            <p class="description">
                <?php echo esc_html( $args['desc'] ); ?>
            </p>
        <?php }
	}
	public function render_password_field( $args )
    {
		?>
        <input
            type="password"
            id="<?php echo $args['label_for'] ?>"
            name="<?php echo $args['label_for'] ?>"
            value="<?php echo $args['value'] ?>"
            <?php echo $args['attr'] ?>
        >
        <?php if ( $args['desc'] ): ?>
            <p class="description"><?php echo $args['desc'] ?></p>
        <?php endif; ?>
		<?php
	}
	public function render_textarea_field( $args )
    {
        ?>
        <textarea
            type="text"
            id="<?php echo $args['label_for'] ?>"
            name="<?php echo $args['label_for'] ?>"
            class="<?php echo $args['class'] ?>"
            <?php echo $args['attr'] ?>
            >
            <?php esc_html( $args['value'] ) ?>
        </textarea>
        <?php if ( $args['desc'] ): ?>
        <p class="description"><?php echo $args['desc'] ?></p>
		<?php endif;
	}
    public function render_checkbox_field( $args )
    {
		?>
        <input
            type="checkbox"
            id="<?php echo $args['label_for'] ?>"
            name="<?php echo $args['label_for'] ?>"
            class="<?php echo $args['class'] ?>"
            <?php echo $args['attr'] ?>
            <?php checked( $args['value'], 1, true ); ?>
        >
        <?php if ( $args['desc'] ) { ?>
        <p class="description"><?php echo $args['desc'] ?></p>
        <?php } ?>
		<?php
	}
}

function skywin_admin_settings_page(){
	return Skywin_Admin_Settings_Page::instance();
}
skywin_admin_settings_page();
endif;