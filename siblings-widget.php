<?php

/*
Plugin Name: Page Siblings Widget
Plugin URI: https://github.com/mcguffin/siblings-widget/
Description: Widget showing a menu with all the siblings of the current selected page.
Author: Jörn Lund
Version: 0.0.2
Author URI: https://github.com/mcguffin/

Text Domain: siblings
Domain Path: /lang/
*/



class Silings_Widget extends WP_Widget {
	private $defaults = array(
		'show_title'	=> true,
		'show_branch'	=> 'top', // 'top' | 'current'
		'depth'			=> 0, // flat. 
	);
	function Silings_Widget() {
		$widget_ops = array('classname' => 'siblings_widget', 'description' => __('Shows siblings of the current selected page.','siblings'));
		parent::__construct('siblings_widget',__('Page Sibling','siblings') , $widget_ops);
	}
	function widget( $args , $instance) {
		extract( wp_parse_args( $args + $instance, $this->defaults ) );
		global $post;
		if ( $post && ($post->post_parent xor $this->page_has_children($post->ID) ) ) {
			$parent_post = $post;
			
			// show branch
			if ( $show_branch == 'top' )
				while ( ($parent_post = $this->get_parent_post($parent_post)) && $parent_post->post_parent );
			else if ( $show_branch == 'current' )
				$parent_post = get_post( $post->post_parent );
			
			$func = create_function('$args','$args[\'child_of\'] = '.$parent_post->ID.';$args[\'show_home\']=false;return $args;' );
			
			// exclude non-hierarchical pages as well
			add_filter( 'wp_page_menu_args', $func );
		
			$args = array(
				'depth'       => $depth,
				'sort_column' => 'menu_order',
				'menu_class'  => 'menu',
				'include'     => '',
				'exclude'     => '',
				'echo'        => true,
				'show_home'   => false,
				'link_before' => '',
				'link_after'  => '',
			);
			// widget title
			echo $before_widget;
			if ( $show_title ) {
				?><h3 class="widget-title"><?php echo $parent_post->post_title ?></h3><?php
			}
			echo wp_page_menu( $args );
			echo $after_widget;
			remove_filter( 'wp_page_menu_args', $func );
		}
		
	}
	function update( $new_instance , $old_instance ) {
		return $new_instance;
	}
	function form( $instance ) {
		extract( wp_parse_args($instance, $this->defaults) );
		
	}
	private function page_has_children( $post_ID ) {
		global $wpdb;
		return (bool) $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = $post_ID AND post_type='page' AND post_status='publish' LIMIT 1");
	}
	private function get_parent_post( $post ) {
		return get_post($post->post_parent);
	}
}

add_action( 'widgets_init', function(){
     register_widget( 'Silings_Widget' );
});

function siblings_widget_init(){
	load_plugin_textdomain( 'siblings' , false, dirname( plugin_basename( __FILE__ )) . '/lang');
}
add_action('init','siblings_widget_init');



function pagesiblings_children( $args ) {
	// which page are we on.
	global $post;
	$old_post = $post;
	
	$children = get_posts( "post_type={$post->post_type}&post_parent={$post->ID}" );
	$custom_query = new WP_Query( array(
		'post_type' => 'page',
		'post_parent' => $post->ID,
		'orderby' => 'menu_order',
		'order' => 'ASC',
	) );
	ob_start();
	if ( $custom_query->have_posts() ):
	    while ( $custom_query->have_posts() ) :
	    	$custom_query->the_post();
	    	$post = $custom_query->post;
//	    	var_dump($custom_query->post);
	    	get_template_part( 'content', 'page' );
		endwhile;
	endif;
	$post = $old_post;
	wp_reset_query();
	return ob_get_clean();
}

add_shortcode('children','pagesiblings_children');
