#!/usr/bin/env php
<?php
/**
 * Validate the generated WordPress.org package before copying it to SVN.
 *
 * Usage:
 *   php scripts/validate-wporg-package.php [package-directory]
 */

$repo_root       = dirname( __DIR__ );
$slug            = 'smart-search-chips';
$default_package = $repo_root . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . $slug . '-wporg' . DIRECTORY_SEPARATOR . $slug;
$package_arg     = isset( $argv[1] ) ? $argv[1] : $default_package;
$package_path    = realpath( $package_arg );

echo 'WP.org package path: ' . ( false === $package_path ? $package_arg : $package_path ) . PHP_EOL;
echo "SVN upload source: copy only this generated package directory to SVN trunk; never copy the raw GitHub repository or Pro build.\n";

if ( false === $package_path || ! is_dir( $package_path ) ) {
	fwrite( STDERR, "Generated WordPress.org package was not found. Run npm run build:wporg first.\n" );
	exit( 2 );
}

$failures = array();

/**
 * Normalize paths for readable output.
 *
 * @param string $base Base directory.
 * @param string $path Absolute file path.
 * @return string
 */
function sschp_wporg_relative_path( $base, $path ) {
	$base = rtrim( $base, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

	if ( 0 === stripos( $path, $base ) ) {
		$path = substr( $path, strlen( $base ) );
	}

	return str_replace( DIRECTORY_SEPARATOR, '/', $path );
}

/**
 * Read a text file safely.
 *
 * @param string $path File path.
 * @return string
 */
function sschp_wporg_read_file( $path ) {
	$contents = file_get_contents( $path );

	return false === $contents ? '' : $contents;
}

/**
 * Check whether a file extension is safe to scan as text.
 *
 * @param string $extension File extension.
 * @return bool
 */
function sschp_wporg_is_text_extension( $extension ) {
	static $extensions = array(
		'css'  => true,
		'html' => true,
		'js'   => true,
		'json' => true,
		'md'   => true,
		'php'  => true,
		'po'   => true,
		'pot'  => true,
		'txt'  => true,
		'xml'  => true,
		'yml'  => true,
		'yaml' => true,
	);

	return isset( $extensions[ strtolower( $extension ) ] );
}

$required_files = array(
	'smart-search-chips.php',
	'readme.txt',
	'blocks/search/block.json',
	'blocks/search/editor.asset.php',
	'blocks/search/editor.js',
	'includes/class-edition.php',
	'includes/class-search-explainer.php',
);

foreach ( $required_files as $required_file ) {
	if ( ! is_file( $package_path . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $required_file ) ) ) {
		$failures[] = "Missing required package file: {$required_file}";
	}
}

$directory = new RecursiveDirectoryIterator( $package_path, FilesystemIterator::SKIP_DOTS );
$iterator  = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::SELF_FIRST );
$files     = array();

foreach ( $iterator as $entry ) {
	$relative = sschp_wporg_relative_path( $package_path, $entry->getPathname() );
	$basename = strtolower( $entry->getBasename() );

	if ( $entry->isDir() && 'vendor' === $basename ) {
		$failures[] = "Forbidden vendor directory found: {$relative}";
		continue;
	}

	if ( false !== strpos( strtolower( $entry->getBasename() ), '__premium_only' ) ) {
		$failures[] = "Forbidden premium-only path found: {$relative}";
	}

	if ( $entry->isFile() ) {
		$files[] = $entry;
	}
}

$forbidden_patterns = array(
	'Freemius',
	'fs_dynamic_init',
	'can_use_premium_code',
	'__premium_only',
	'wp_org_gatekeeper',
	'has_paid_plans',
	'is_premium',
	'SSCHP_License',
	'SSCHP_Analytics',
	'SSCHP_AI_Tagger',
	'SSCHP_Chip_Rules',
	'sschp_export_chip_rules',
	'sschp_import_chip_rules',
	'[ssc' . '_search]',
	'trial',
	'quota',
	'free limit',
	'free version limit',
	'Upgrade to Pro to unlock',
	'unlock included feature',
	'unlock included functionality',
	'included functionality requires payment',
	'locked premium',
	'becomes available in Pro',
);

$allowed_pro_link_files = array(
	'readme.txt'                    => true,
	'smart-search-chips.php'        => true,
	'includes/class-edition.php'    => true,
	'includes/class-settings.php'   => true,
);
$pro_link_patterns = array(
	'smartsearchchips.app',
	'View Pro Features',
	'Smart Search Chips Pro',
);

foreach ( $files as $file ) {
	if ( ! sschp_wporg_is_text_extension( $file->getExtension() ) ) {
		continue;
	}

	$contents = sschp_wporg_read_file( $file->getPathname() );
	$relative = sschp_wporg_relative_path( $package_path, $file->getPathname() );

	foreach ( $forbidden_patterns as $pattern ) {
		$position = stripos( $contents, $pattern );

		if ( false === $position ) {
			continue;
		}

		$line = 1 + substr_count( substr( $contents, 0, $position ), "\n" );
		$failures[] = sprintf(
			'Forbidden reference "%s" found in %s:%d',
			$pattern,
			$relative,
			$line
		);
	}

	foreach ( $pro_link_patterns as $pattern ) {
		$position = stripos( $contents, $pattern );

		if ( false === $position || isset( $allowed_pro_link_files[ $relative ] ) ) {
			continue;
		}

		$line = 1 + substr_count( substr( $contents, 0, $position ), "\n" );
		$failures[] = sprintf(
			'Pro/product link or wording appears outside admin/settings/readme context in %s:%d',
			$relative,
			$line
		);
	}
}

$main_file  = $package_path . DIRECTORY_SEPARATOR . 'smart-search-chips.php';
$readme     = $package_path . DIRECTORY_SEPARATOR . 'readme.txt';
$version    = null;
$stable_tag = null;

if ( is_file( $main_file ) ) {
	$main_contents = sschp_wporg_read_file( $main_file );

	if ( preg_match( '/^\s*\*?\s*Version:\s*([^\r\n]+)/mi', $main_contents, $matches ) ) {
		$version = trim( $matches[1] );
	} else {
		$failures[] = 'Could not read plugin header Version from smart-search-chips.php';
	}
}

if ( is_file( $readme ) ) {
	$readme_contents = sschp_wporg_read_file( $readme );

	if ( preg_match( '/^Stable tag:\s*([^\r\n]+)/mi', $readme_contents, $matches ) ) {
		$stable_tag = trim( $matches[1] );
	} else {
		$failures[] = 'Could not read Stable tag from readme.txt';
	}
}

if ( null !== $version && null !== $stable_tag && $version !== $stable_tag ) {
	$failures[] = "Stable tag ({$stable_tag}) does not match plugin header Version ({$version})";
}

$lint_script = __DIR__ . DIRECTORY_SEPARATOR . 'lint-php.php';

if ( ! is_file( $lint_script ) ) {
	$failures[] = 'Missing scripts/lint-php.php';
} else {
	$lint_command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $lint_script ) . ' ' . escapeshellarg( $package_path );
	$lint_status  = 0;

	passthru( $lint_command, $lint_status );

	if ( 0 !== $lint_status ) {
		$failures[] = 'PHP lint failed for the generated WordPress.org package.';
	}
}

if ( ! empty( $failures ) ) {
	echo "WP.org package validation failed:\n";

	foreach ( array_unique( $failures ) as $failure ) {
		echo ' - ' . $failure . PHP_EOL;
	}

	exit( 1 );
}

echo "WP.org package validation passed.\n";
exit( 0 );
