<?php
/*
Plugin Name: Flickr Pulgin
Description: My flickr plugin
Version:     0.1
Author:      Hao Luo
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

*/
add_action('widgets_init', 'flickr_widget');
add_action( 'wp_enqueue_scripts', 'add_stylesheet' );
add_action( 'wp_enqueue_scripts', 'add_javascript' );

function flickr_widget() {
	register_widget('wp_flickr_widget');
}

function add_javascript(){
	if (!wp_script_is('jquery', 'enqueued')){
		wp_enqueue_script( 'jquery' );
	}
	wp_register_script( 'main_flickr', plugins_url('main_flickr.js', __FILE__) );
	wp_enqueue_script( 'main_flickr' );
	wp_register_script( 'magnific_popup', plugins_url('jquery.magnific-popup.min.js', __FILE__) );
	wp_enqueue_script( 'magnific_popup' );
}

function add_stylesheet(){
	wp_register_style( 'flickr_style', plugins_url('flickr_style.css', __FILE__) );
    wp_enqueue_style( 'flickr_style' );
    wp_register_style( 'magnific-popup', plugins_url('magnific-popup.css', __FILE__) );
    wp_enqueue_style( 'magnific-popup' );
    if (!wp_style_is('font-awesome', 'enqueued')){
		wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
	}    
}

function debug_to_console( $data ) {

    $output = "<script>console.log(".$data.");</script>";

    echo $output;
}

class wp_flickr_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	function wp_flickr_widget() {
		$widget_ops = array('classname' => 'wp_flickr_widget', 'description' => __('Flickr gallery showcase', 'wp_flickr_widget') );
		$control_ops = array('id_base' => 'flickr-widget' );
		$this->WP_Widget('flickr-widget', __('Flickr', 'wp_flickr_widget'), $widget_ops, $control_ops);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		extract($args);

		$title = apply_filters('widget_title', $instance['title'] );
		$displayNum = $instance['displayNum'];
		$api_key = $instance['api_key'];
		$description = $instance['description'];
		$user_id = $instance['user_id'];
		$photoset_id = $instance['photoset_id'];
		$showLikes = $instance['show_likes'];

		echo $before_widget;

		$displayData = $this -> getPhotos($api_key, $user_id, $photoset_id);
		$displayData_farm = $displayData[1];
		$displayData_photoid = $displayData[0];
		$displayData_server = $displayData[2];
		$displayData_secret = $displayData[3];
		$number_of_photos = $displayData[4];

		echo '<div class="flickr-widget-title">';
		echo '<h1>'.$title.'</h></div>';
		echo '<div class="flickr-widget-description">';
		echo '<p>'.$description.'</p></div>';
		
		echo '<div class="wp-flickr-gallery">';
		echo '<ul id="grid" class="grid-wrapper">';
		for ($i = 0; $i<intval($displayNum); $i++){
			echo '<li class="mix">';
            echo '<a href="https://farm'.$displayData_farm[$i].'.staticflickr.com/'.$displayData_server[$i].'/'.$displayData_photoid[$i].'_'.$displayData_secret[$i].'_z.jpg">';
            echo '<div class="overlay"><i class="fa fa-search"></i>';
            echo '</div><img src="https://farm'.$displayData_farm[$i].'.staticflickr.com/'.$displayData_server[$i].'/'.$displayData_photoid[$i].'_'.$displayData_secret[$i].'_q.jpg" alt="">';
            echo '</a></li>';
		}
		echo '</ul></div>';
		
		echo $after_widget;
	}

	function getPhotos($akey, $userID, $photosetID){
		$baseURL = 'https://api.flickr.com/services/rest/?';
		$method = 'flickr.photosets.getPhotos';
		$api_key = $akey;
		$user_id = $userID;
		$photoset_id = $photosetID;
		$completeURL = $baseURL.'&method='.$method.'&api_key='.$api_key.'&user_id='.$user_id.'&photoset_id='.$photoset_id.'&format=json';

		$photoData = wp_remote_get($baseURL.'&method='.$method.'&api_key='.$api_key.'&user_id='.$user_id.'&photoset_id='.$photoset_id.'&format=json');

		$data_tmp = str_replace( 'jsonFlickrApi(', '', $photoData['body']);
		$data_tmp = substr( $data_tmp, 0, strlen( $data_tmp ) - 1 );
		$photoJSON = json_decode($data_tmp, true);

		$photo_id = array();
		$farm_id = array();
		$server_id= array();
		$secret = array();

		if ($photoJSON == NULL){
			$data = NULL;
			echo 'NULL';
		}else{
			for ($i = 0; $i<count($photoJSON['photoset']['photo']); $i++){
				array_push($photo_id, $photoJSON['photoset']['photo'][$i]['id']);
				array_push($farm_id, $photoJSON['photoset']['photo'][$i]['farm']);
				array_push($server_id, $photoJSON['photoset']['photo'][$i]['server']);
				array_push($secret, $photoJSON['photoset']['photo'][$i]['secret']);
			}
			$data = array($photo_id, $farm_id, $server_id, $secret, count($photoJSON['photoset']['photo']));
		}
		
		return $data;
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	function form($instance) {
		?>

		<!-- Widget Title: Text Input -->
		<p>
			<div for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></div>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Description: Text Input -->
		<p>
			<div for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e('Description:', 'example'); ?></div>
			<input id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" value="<?php echo $instance['description']; ?>" style="width:100%;" />
		</p>

		<!-- API Key: Text Input-->
		<p>
			<div for="<?php echo $this->get_field_id( 'api_key' ); ?>"><?php _e('API Key:', 'example'); ?></div>
 			<div for="<?php echo $this->get_field_id( 'api_key' ); ?>"><?php _e('Please use the following link to obtain an API Key if you don&apos;t already have one: ', 'example'); ?><a class="access_token_link" href="https://www.flickr.com/services/api/misc.api_keys.html">Get Access Token</a></div>
			<input id="<?php echo $this->get_field_id( 'api_key' ); ?>" name="<?php echo $this->get_field_name( 'api_key' ); ?>" value="<?php echo $instance['api_key']; ?>" style="width:100%;" />
		</p>

		<p>
			<div for="<?php echo $this->get_field_id( 'user_id' ); ?>"><?php _e('User ID:', 'example'); ?></div>
			<input id="<?php echo $this->get_field_id( 'user_id' ); ?>" name="<?php echo $this->get_field_name( 'user_id' ); ?>" value="<?php echo $instance['user_id']; ?>" style="width:100%;" />
		</p>

		<p>
			<div for="<?php echo $this->get_field_id( 'photoset_id' ); ?>"><?php _e('Photoset ID:', 'example'); ?></div>
			<input id="<?php echo $this->get_field_id( 'photoset_id' ); ?>" name="<?php echo $this->get_field_name( 'photoset_id' ); ?>" value="<?php echo $instance['photoset_id']; ?>" style="width:100%;" />
		</p>

		<p>
			<div for="<?php echo $this->get_field_id( 'configure_display' ); ?>"><?php _e('Configure your display:', 'example'); ?></div> 
		</p>

		<!-- Number of Photos: Text Input -->
		<p>
			<div for="<?php echo $this->get_field_id( 'displayNum' ); ?>"><?php _e('How many photos do you want to display (maximum 40, default is 20):', 'example'); ?></div>
			<input id="<?php echo $this->get_field_id( 'displayNum' ); ?>" name="<?php echo $this->get_field_name( 'displayNum' ); ?>" value="<?php echo $instance['displayNum']; ?>" style="width:100%;" />
		</p>

		<p>
			<div style="font-weight:900;" for="<?php echo $this->get_field_id( 'default_display' ); ?>"><?php _e('This widget by default displays the most recent photos from across your company.', 'example'); ?></div> 
		</p>

	<?php

	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['description'] = trim(strip_tags($new_instance['description']));
		$instance['displayNum'] = trim(strip_tags($new_instance['displayNum']));
		$instance['api_key'] = trim(strip_tags($new_instance['api_key']));
		$instance['user_id'] = trim(strip_tags($new_instance['user_id']));
		$instance['photoset_id'] = trim(strip_tags($new_instance['photoset_id']));
		$instance['show_likes'] = $new_instance['show_likes'];
		return $instance;
	}
}
?>