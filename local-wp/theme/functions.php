<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function trailforge_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'trailforge_setup' );

function trailforge_enqueue_assets() {
    wp_enqueue_style(
        'trailforge-style',
        get_stylesheet_uri(),
        array(),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'trailforge_enqueue_assets' );

function trailforge_split_chips( $raw ) {
    $parts = preg_split( '/[;,]/', (string) $raw );
    $chips = array();

    foreach ( $parts as $part ) {
        $part = trim( wp_strip_all_tags( $part ) );
        if ( '' !== $part ) {
            $chips[] = $part;
        }
    }

    return array_slice( array_unique( $chips ), 0, 5 );
}
