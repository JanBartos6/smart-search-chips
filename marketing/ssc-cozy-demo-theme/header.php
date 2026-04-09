<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="ssc-site-shell">
    <header class="ssc-site-header">
        <div class="ssc-shell ssc-site-header__inner">
            <a class="ssc-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <span class="ssc-brand__mark">S</span>
                <span class="ssc-brand__text">
                    <strong>Smart Search Chips</strong>
                    <small>Cozy Demo Site</small>
                </span>
            </a>
            <nav class="ssc-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'ssc-cozy-demo' ); ?>">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Live Demo', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'Catalog', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'Search Results', 'ssc-cozy-demo' ); ?></a>
            </nav>
        </div>
    </header>

