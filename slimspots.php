<?php
/*
 * Plugin Name: Anti Adblock Adspaces
 * Description: Adds a widget option to show the returned value from Slimspots
 * Author: ks.slimspots
 * Version: 1.0.0
 * Author URI: http://slimspots.com/register/1463
 * Text Domain: Slimpots
 */


/**
 * Adds widget.
 */
class Slimpots_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
	parent::__construct(
	    'slimpots_widget', // Base ID
	    __( 'Anti Adblock Adspaces', 'slimpots' ), // Name
	    array( 'description' => __( 'Add Slimpots widget', 'slimpots' ), ) // Args
	);
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
	echo $args['before_widget'];
        
        $sp_key = get_option("spKEY");
        $sp_id = get_option("spID");        

        
	if ( !empty( $instance['sp_code'] )
        && !empty($sp_key)
        && !empty($sp_id)) {
       
            
            require_once(plugin_dir_path( __FILE__ )."libs/class.wmapi.php");
            require_once(plugin_dir_path( __FILE__ )."libs/class.tec.php");
            
            $wmapi = new WMAPI ($sp_id, $sp_key, false);
            $TEC = new TEC();

            // var_dump($instance['sp_code']);
            
            $TEC->source_set( $instance['sp_code'] );
            
            $var_src = $TEC->src_get();

            if (isset($var_src[0]) && !empty($var_src[0])){

                $ad_space = @$wmapi->GetAdspace($var_src[0]);
                if (!empty($ad_space)){
                    echo $ad_space;
                }else{
                    echo "ERROR_2: Empty ad returned";
                }
                
            }else{
                echo "ERROR_1: Empty url to load";
            }
            

            

        }else{
            echo __('Empty KEY and/or ID values', 'slimpots');
        }
        
        
	echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $sp_code = ! empty( $instance['sp_code'] ) ? $instance['sp_code'] : "";
?>
    <p>
	<label for="<?php echo $this->get_field_id( 'sp_id' ); ?>"><?php _e( 'Your Adspace Code:' ); ?></label> 
	<textarea class="widefat" id="<?php echo $this->get_field_id( 'sp_code' ); ?>" name="<?php echo $this->get_field_name( 'sp_code' ); ?>" style="min-height:150px;" placeholder="&lt;script type=&quot;text/javascript&quot; language=&quot;javascript&quot; charset=&quot;utf-8&quot; src=&quot;http://spaces.slimspots.com/adspace/XXX.js?wsid=&quot;&gt;&lt;/script&gt;"><?php echo esc_attr( $sp_code ); ?></textarea>
    </p>        
<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
	    $instance = array();
            $instance['sp_code'] = ( ! empty( $new_instance['sp_code'] ) ) ? $new_instance['sp_code'] : '';
	    return $instance;
	}

} // class





function register_slimpots_menu() {
    add_menu_page( 'Slimpots', 'Anti-AdBlock Ads', 'manage_options', plugin_dir_path(__FILE__)."sp-admin.php", '' );
    add_action( 'admin_init', 'register_slimpots_settings' );
}
function register_slimpots_settings() { 
    register_setting( 'slimpots-group', 'spKEY' );
    register_setting( 'slimpots-group', 'spID' );
}    

// register widget
function register_slimpots_widget() {
    register_widget( 'Slimpots_Widget' );
}

add_action( 'admin_menu', 'register_slimpots_menu' );
add_action( 'widgets_init', 'register_slimpots_widget' );

?>
