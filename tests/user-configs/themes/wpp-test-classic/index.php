<?php
/**
 * Fixture classic theme: one template for everything.
 *
 * @package WpPrivate
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main>
<?php
while ( have_posts() ) {
	the_post();
	the_title( '<h2>', '</h2>' );
	if ( ! is_singular() ) {
		the_excerpt();
	}
	the_content();
}
?>
</main>
<?php wp_footer(); ?>
</body>
</html>
