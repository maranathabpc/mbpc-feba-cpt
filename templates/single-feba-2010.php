<?php
/**
 * The Template for displaying all single posts.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */

get_header(); ?>

		<div id="container">
			<div id="content" role="main">

			<?php get_template_part('loop');?>

			</div><!-- #content -->
		</div><!-- #container -->
<?php // display sidebar with only the feba widget area 
	  // don't call the usual get_sidebar() function as that will load all the other widgets
	  // use the #mbpc-feba-widget-area id here because in my custom theme which uses this, .widget-area is float:right

 		if( 'feba' == get_post_type() ) : ?>

		<div id="mbpc-feba-widget-area" class="widget-area" role="complementary">
			<ul class="xoxo">

<?php
	   dynamic_sidebar( 'mbpc-feba-widget-area' )  ?>

			</ul>
		</div><!-- #feba .widget-area -->

<?php endif; ?>

<?php get_footer(); ?>
