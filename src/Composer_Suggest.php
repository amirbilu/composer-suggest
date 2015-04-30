<?php
/**
 * Composer 'plugin'. Can we find a simple way of installing Composer suggestions?
 *
 * USAGE:  composer run-script install-suggest "Ju?X(ta)?L"
 *
 * @author Nick Freear, 29 April 2015.
 */

namespace Nfreear\Composer;

use Composer\Composer;


class Composer_Suggest {

  const ENV = 'NF_COMPOSER_SUGGEST';

  const COMPOSER = 'php ../composer.phar ';

  const RE_VERSION = '@^(?<version>[^\s]+) @';

  const RE_VEND_PKG = '@(?<vendor>[a-z\d\-]+)\/(?<package>[\w\-]+)@';


  /** Main install method.
  */
  public static function install() {

    self::out( __METHOD__ );

    $regex = self::get_argv_env_pattern();
    $composer = self::get_composer_data();

    self::out( 'Pattern (perl-compatible reg exp):  ' . $regex );

    $suggest_r = self::match_suggestions( $composer->suggest, $regex );

    if ($suggest_r) {
      $command = self::COMPOSER . 'require ' . implode( ' ', $suggest_r );

      self::out( 'Command:' );
      self::out( $command . PHP_EOL );

      system( $command );
    } else {
      self::out( 'No matches, no composer-require triggered.' );
    }
    exit( 0 );
  }


  // ======================================================

  /** Get the `pattern` from command-line or environment.
  * @return string
  */
  protected function get_argv_env_pattern() {
    global $argv, $argc;

    $pattern = ($argc > 1) ? $argv[ $argc - 1 ] : getenv( self::ENV );

    if (! $pattern) {
      var_dump( $argv );
      self::fatal( 'Insufficient arguments/ no environment variable set; '. self::ENV );
    }
    $regex = '/' . $pattern . '/i';

    return $regex;
  }

  /** Get object representation of `composer.json`
  * @return object
  */
  protected function get_composer_data() {
    $json = file_get_contents( './composer.json' );
    return (object) json_decode( $json );
  }

  /** Loop through suggestions, finding matches
  * @return array Array of matches.
  */
  protected function match_suggestions( $suggestions, $regex ) {
    $suggest_r = array();

    foreach ( $suggestions as $package => $info ) {
      if (preg_match( $regex, $info )
          && preg_match( self::RE_VEND_PKG, $package )
          && preg_match( self::RE_VERSION, $info, $matches )) {

        $version = rtrim( $matches[ 'version' ], ';,. ' );
        $suggest_r[] = $package . ':' . $version;
        self::out( "Match:  '$package' => '$info'" );
      }
      else {
        self::out( "No match (or error):  '$package' => '$info'" );
      }
    }
    return count( $suggest_r ) > 0 ? $suggest_r : null;
  }

  /** Utilities.
  */

  protected static function out( $msg ) {
    // Verbose option?
    echo ' > ' . $msg . PHP_EOL;
  }

  protected static function fatal( $msg ) {
    fwrite( STDERR, 'ERROR. ' . $msg . PHP_EOL );
    exit( 1 );
  }
}


if (FALSE !== strpos( __FILE__, $argv[ 0 ])) {
  Composer_Suggest::install();
}

#End.
