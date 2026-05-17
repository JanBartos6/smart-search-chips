#!/usr/bin/env php
<?php
/**
 * Cross-platform PHP syntax linter.
 *
 * Usage:
 *   php scripts/lint-php.php [target-directory]
 */

$target = isset( $argv[1] ) ? $argv[1] : dirname( __DIR__ );
$target_path = realpath( $target );

if ( false === $target_path || ! is_dir( $target_path ) ) {
	fwrite( STDERR, "PHP lint target does not exist or is not a directory: {$target}\n" );
	exit( 2 );
}

$skip_directories = array_fill_keys(
	array(
		'.git',
		'vendor',
		'node_modules',
		'build',
		'marketing',
		'test-results',
		'playwright-report',
	),
	true
);

/**
 * Return a normalized path relative to the lint target.
 *
 * @param string $base Base directory.
 * @param string $path Absolute file path.
 * @return string
 */
function sschp_lint_relative_path( $base, $path ) {
	$base = rtrim( $base, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

	if ( 0 === stripos( $path, $base ) ) {
		$path = substr( $path, strlen( $base ) );
	}

	return str_replace( DIRECTORY_SEPARATOR, '/', $path );
}

/**
 * Extract the first useful lint error line.
 *
 * @param array $output Linter output lines.
 * @return string
 */
function sschp_lint_first_error_line( array $output ) {
	foreach ( $output as $line ) {
		$line = trim( $line );

		if ( '' === $line || false !== stripos( $line, 'No syntax errors detected' ) ) {
			continue;
		}

		return $line;
	}

	return 'php -l exited with a non-zero status.';
}

$directory = new RecursiveDirectoryIterator( $target_path, FilesystemIterator::SKIP_DOTS );
$filter    = new RecursiveCallbackFilterIterator(
	$directory,
	static function ( SplFileInfo $current ) use ( $skip_directories ) {
		if ( ! $current->isDir() ) {
			return true;
		}

		return ! isset( $skip_directories[ strtolower( $current->getBasename() ) ] );
	}
);
$iterator  = new RecursiveIteratorIterator( $filter );
$php_files = array();

foreach ( $iterator as $file ) {
	if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
		$php_files[] = $file->getPathname();
	}
}

sort( $php_files, SORT_STRING );

echo 'PHP lint target: ' . $target_path . PHP_EOL;
echo 'PHP files checked: ' . count( $php_files ) . PHP_EOL;

$failures = array();

foreach ( $php_files as $file ) {
	$output = array();
	$status = 0;
	$cmd    = escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file ) . ' 2>&1';

	exec( $cmd, $output, $status );

	if ( 0 !== $status ) {
		$failures[] = array(
			'file'    => sschp_lint_relative_path( $target_path, $file ),
			'message' => sschp_lint_first_error_line( $output ),
		);
	}
}

if ( ! empty( $failures ) ) {
	echo "PHP lint failed:\n";

	foreach ( $failures as $failure ) {
		echo ' - ' . $failure['file'] . PHP_EOL;
		echo '   ' . $failure['message'] . PHP_EOL;
	}

	exit( 1 );
}

echo "PHP lint passed.\n";
exit( 0 );
