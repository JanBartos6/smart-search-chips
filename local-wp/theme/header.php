<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="tf-header">
    <div class="tf-shell tf-header__inner">
        <a class="tf-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <span class="tf-brand__mark">TF</span>
            <span>TrailForge Supply</span>
        </a>
        <nav class="tf-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'trailforge' ); ?>">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'trailforge' ); ?></a>
            <a href="<?php echo esc_url( home_url( '/search-gear/' ) ); ?>"><?php esc_html_e( 'Search Gear', 'trailforge' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-search-chips' ) ); ?>"><?php esc_html_e( 'Plugin Settings', 'trailforge' ); ?></a>
        </nav>
    </div>
</header>
