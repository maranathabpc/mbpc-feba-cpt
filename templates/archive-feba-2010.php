<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */

get_header(); ?>

		<div id="container">
			<div id="content" role="main">

<?php
	/* Queue the first post, that way we know
	 * what date we're dealing with (if that is the case).
	 *
	 * We reset this later so we can run the loop
	 * properly with a call to rewind_posts().
	 */
	if ( have_posts() )
		the_post();
?>

			<h1 class="page-title">
				FEBA 
<?php if ( is_day() ) : ?>
				<?php printf( __( 'Daily Archives: <span>%s</span>', 'twentyten' ), get_the_date() ); ?>
<?php elseif ( is_month() ) : ?>
				<?php printf( __( 'Monthly Archives: <span>%s</span>', 'twentyten' ), get_the_date( 'F Y' ) ); ?>
<?php elseif ( is_year() ) : ?>
				<?php printf( __( 'Yearly Archives: <span>%s</span>', 'twentyten' ), get_the_date( 'Y' ) ); ?>
<?php else : ?>
				<?php _e( 'Blog Archives', 'twentyten' ); ?>
<?php endif; ?>
			</h1>

<?php
	/* Since we called the_post() above, we need to
	 * rewind the loop back to the beginning that way
	 * we can run the loop properly, in full.
	 */
	rewind_posts();

	/* Run the loop for the archives page to output the posts.
	 * If you want to overload this in a child theme then include a file
	 * called loop-archive.php and that will be used instead.
	 * There is a dependency on the MBPC child theme. Output is wrong if default
	 * loop in twentyten is used as it'll show the_excerpt() for archives, which 
	 * does not process the shortcode for the audio player.
	 *
	 */
	 get_template_part( 'loop', 'sermons' );
?>

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
