<?php

/**
 *
 * @package   History Search by Webloft
 * @author    Håkon Sundaune <haakon@bibliotekarensbestevenn.no>
 * @license   GPL-3.0+
 * @link      http://www.bibvenn.no/finnlokalhist
 * @copyright 2014 Sundaune
 *
 * @wordpress-plugin
 * Plugin Name:       History Search by Webloft
 * Plugin URI:        http://www.bibvenn.no/finnlokalhist
 * Description:       S&oslash;ker etter lokalhistorisk materiale / search for historical materials (books, images...)
 * Version:           1.0.2
 * Author:            H&aring;kon Sundaune
 * Author URI:        http://www.sundaune.no
 * Text Domain:       finnlokalhistorie-locale
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

// INCLUDE NECESSARY

    add_action( 'wp_enqueue_scripts', 'finnlokalhistorie_safely_add_stylesheet' );

    /**
     * Add stylesheet to the page
     */
    function finnlokalhistorie_safely_add_stylesheet() {
        wp_register_style( 'finnlokalhistorie-shortcode-style', plugins_url('/css/public.css', __FILE__) );
        wp_register_script( 'finnlokalhistorie-script', plugins_url( 'js/public.js', __FILE__ ), array('jquery') );
        wp_register_script( 'webloft-tab-script', plugins_url( 'js/tabcontent.js', __FILE__ ), array('jquery') );
    }

// FIRST COMES THE SHORTCODE... EH, CODE!

function finnlokalhistorie_func ($atts){

extract(shortcode_atts(array(
  'width' => "250px",
  'makstreff' => "25",
  'show_heading' => false,
  'show_share_links' => false
   ), $atts));

if ( $show_heading === 'false' ) {
  $show_heading = false;
}
$show_heading = (boolean) $show_heading;

if ( $show_share_links === 'false' ) {
  $show_share_links = false;
}
$show_share_links = (boolean) $show_share_links;

// DEFINE HTML TO OUTPUT WHEN SHORTCODE IS FOUND

$width = strip_tags(stripslashes($width));

$htmlout = '<script type="text/javascript">';
$htmlout .= "var pluginsUrl = '" . plugins_url('/search.php' , __FILE__) . "'";
$htmlout .= "/***********************************************";
$htmlout .= "* Tab Content script v2.2- © Dynamic Drive DHTML code library (www.dynamicdrive.com)";
$htmlout .= "* This notice MUST stay intact for legal use";
$htmlout .= "* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code";
$htmlout .= "***********************************************/";
$htmlout .= '</script>';
$htmlout .= '<div class="lokalhistorie_skjema" style="width: ' . $width . '">';

if ( $show_heading ) {
  $htmlout .= '<h2>S&oslash;k i lokalhistorie</h2>';
}

$htmlout .= '<form id="lokalhistform" target="_blank" method="GET" action="' . plugins_url('lokalhist_fullpagesearch.php' , __FILE__) . '">';

$htmlout .= '<table style="width: 85%; border: 0; margin: 0; padding: 0;"><tr><td style="border: 0; padding: 0; margin: 0; vertical-align: middle; width: 80%;">';
$htmlout .= '<input name="query" type="text" autocomplete="off" id="lokalhistorie_search" placeholder="S&oslash;k etter..." />';
$htmlout .= '</td></tr></table>';
$htmlout .= '<input type="hidden" id="finnlokalhist_makstreff" value="' . $makstreff . '" />';
$htmlout .= '<input type="hidden" name="show_share_links" id="finnlokalhist_show_share_links" value="' . $show_share_links . '" />';
$htmlout .= '<br style="clear: both;">';
$htmlout .= '</div>';
$htmlout .= '<div id="fhls_loader" class="small"><div></div></div>';
$htmlout .= '<div id="lokalhistorieresults-text" style="display: none;">';
$htmlout .= 'Viser maks. ' . $makstreff . ' treff for: <span id="finnlokalhistorie_search-string"></span><br /><span>S&oslash;ket oppdateres mens du skriver, og kan ta noen sekunder... v&aelig;r t&aring;lmodig! Vil du &aring;pne s&oslash;ket i et eget vindu og eventuelt vise flere treff, klikk <input style="vertical-align: top;" type="submit" value="her!"></form></span></div>';
$htmlout .= '<div id="finnlokalhistorie_results" style="' . $width . '"></div>';

return $htmlout;

}; // end function

add_shortcode("finnlokalhistorie_skjema", "finnlokalhistorie_func");

function fhls_enqueue_style() {
  global $post;
  if ( is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'finnlokalhistorie_skjema' ) ) {
    wp_enqueue_style('finnlokalhistorie-shortcode-style');
    wp_enqueue_script( 'finnlokalhistorie-script' );
    wp_enqueue_script( 'webloft-tab-script' ); // in order to prevent enqueueing a script more than once if localhistory search is active
  }
}
add_action( 'wp_enqueue_scripts', 'fhls_enqueue_style');