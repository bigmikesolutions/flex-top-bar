<?php
/**
 * PHPUnit bootstrap for Top Bar plugin tests.
 *
 * @package TopBar
 */

$autoload = dirname( __DIR__, 3 ) . '/vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	throw new RuntimeException( 'Run composer install from the project root.' );
}
require_once $autoload;
