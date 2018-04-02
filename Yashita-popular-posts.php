<?php
/**
 * Plugin Name: Yashita's Popular Posts Plugin
 * Description: A simple plugin that counts post popularity based on view numbers
 * Version: 0.1
 * License: A "Slug" license name e.g. GPL2
 */

/**
 * Post popularity feature
 */

function my_popular_post_views($postID) {
    // Set a key name for the custom field
    $total_key = 'views';
    $total = get_post_meta($postID, $total_key, true);
    if($total==''){
        $total = 0;
        delete_post_meta($postID, $total_key);
        add_post_meta($postID, $total_key, '0');
    }else{
        $total++;
        update_post_meta($postID, $total_key, $total);
    }
}

// Dynamically inject counter into single posts
function my_count_popular_posts($post_id) {
    if ( !is_single() ) return;
    if ( !is_user_logged_in() ) {
        if ( empty ( $post_id) ) {
            global $post;
            $post_id = $post->ID;    
        }
        my_popular_post_views($post_id);
    }
}
add_action( 'wp_head', 'my_count_popular_posts');

/*
 * Add popular post view data to All posts table
 */
function my_add_views_column($defaults) {
    $defaults['post_views'] = 'View Count';
    return $defaults;
}

add_filter('manage_posts_columns', 'my_add_views_column');

// Now we need to add content to the new Views column
function my_display_views($column_name) {
    if ($column_name === 'post_views') {
        echo (int) get_post_meta(get_the_ID(), 'views', true);
    }
}
add_action('manage_posts_custom_column', 'my_display_views');


/**
 * Adds Popular posts widget.
 */
class Popular_Posts extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'popular_posts', // Base ID
			esc_html__( 'Popular Posts', 'text_domain' ), // Name
			array( 'description' => esc_html__( 'Displays 5 most popular posts', 'text_domain' ), ) // Args
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
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		// The Query
                
                $query_args = array (
                    'post_type' => 'post',
                    'post_per_page' => 5,
                    'meta_key' => 'views',
                    'order_by' => 'meta_value_num',
                    'order' => 'DESC',
                    'ignore_sticky_post' => true
                );
                $the_query = new WP_Query( $query_args );

                // The Loop
                if ( $the_query->have_posts() ) {
                        echo '<ul>';
                        while ( $the_query->have_posts() ) {
                                $the_query->the_post();
                                echo '<li>';
                                echo '<a href="' . get_the_permalink() . '" rel="bookmark">';
                                echo get_the_title();
                                echo '(' . get_post_meta(get_the_ID(), 'views', true) . ')';
                                echo '</a>';
                                echo '</li>';
                        }
                        echo '</ul>';
                        /* Restore original Post Data */
                        wp_reset_postdata();
                } else {
                        // no posts found
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
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'New title', 'text_domain' );
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
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
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class Popular_Posts

// register Popular_Posts widget
function register_popular_posts_widget() {
    register_widget( 'popular_posts' );
}
add_action( 'widgets_init', 'register_popular_posts_widget' );
