<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/favicon.svg' ) ); ?>" type="image/svg+xml">
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
                </span>
            </a>
            <nav class="ssc-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'ssc-cozy-demo' ); ?>">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Live Demo', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( sschp_demo_get_free_plugin_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Free Plugin', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/#pro' ) ); ?>"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></a>
            </nav>
        </div>
    </header>
