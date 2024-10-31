<?php

/*
Plugin Name: Parsel Store
Plugin URI: http://www.parsel.me
Description: Plugin for embedding a Parsel store in your wordpress website
Author: Parsel
Version: 0.19
Author URI: http://www.parsel.me
*/

if( !defined("__DIR__") )
    define("__DIR__", dirname(__FILE__));

include_once __DIR__ . "/includes/ParselZip.php";

function parsel_is_session_started(){

    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

function parsel_store_url($store_id){
    global $parsel_options;

    if(empty($parsel_options))
        $parsel_options = parsel_get_options();

    $sessionId = session_id();

    parsel_debug("session_id: " . $sessionId);

    $protocol = "https";

    if($parsel_options['parsel_support']['use_http'])
        $protocol = "http";

    $url = sprintf("%s://www.openapps.com/embed/home/%s?oa-query=%s&oa-session=%s&oa-host=%s",
        $protocol, $store_id, urlencode($_SERVER['QUERY_STRING']."&oa-sort=price-asc"), $sessionId, urlencode((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off'?"https://":"http://").$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));

    parsel_debug("url: " . $url);

    return $url;

}

function parsel_plugin_url($url){
    $base = "https://www.openapps.com";

    //dev server base
    if( preg_match("`mamba.openapps.com`", $_SERVER['HTTP_HOST'] ) ){
        preg_match("`~([^/]+)`", $_SERVER['REQUEST_URI'], $matches);
        //$base = "http://".$_SERVER['HTTP_HOST']."/~{$matches[1]}/openapps.com";
        $base = "http://".$_SERVER['HTTP_HOST']."/~habbas/openapps.com";
    }

    $url = "{$base}/utilities/wp_plugin_api/{$url}";

    return $url;
}

/*******************************************************
* add/get and update parsel options in database
*/
function parsel_get_options(){
    global $parsel_options;

    $parsel_options = get_option("parsel_options");

    if( !empty($parsel_options) )
        return json_decode($parsel_options, true);
    else
        return array();
}

function parsel_add_options($options){
    add_option("parsel_options", json_encode($options));
}

function parsel_update_options($options){
    update_option('parsel_options', json_encode($options));
}

function parsel_remove_options(){
    delete_option('parsel_options');
}


/*******************************************************
* render parsel store via various techniques
*/
function parsel_render_curl($store_id){
    $ch = curl_init();
    curl_setopt($ch  , CURLOPT_URL            , parsel_store_url($store_id));
    curl_setopt ($ch , CURLOPT_SSL_VERIFYHOST , 0);
    curl_setopt ($ch , CURLOPT_SSL_VERIFYPEER , 0);
    curl_setopt($ch  , CURLOPT_RETURNTRANSFER , 1);
    curl_setopt($ch  , CURLOPT_FOLLOWLOCATION , 1);
    echo curl_exec ($ch);
    curl_close ($ch);
}

function parsel_render_fopen($store_id){
    $file = fopen(parsel_store_url($store_id), "r");
    if (!$file) echo "Unable to open Parsel store.";
    $content = ""; while (!feof ($file)) $content .= fgets ($file, 1024); fclose($file);
    echo $content;
}

function parsel_render_file_get_contents($store_id){
    echo file_get_contents( parsel_store_url($store_id) );
}

function parsel_render_file($store_id){
    echo implode('', file(parsel_store_url($store_id)));
}


/**
 * Adds admin menu option
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_admin_menu(){
    add_menu_page('Parsel Store', 'Parsel Store', 1, 'parsel-store', 'parsel_admin_page');
}

function parsel_curl_request($url, $file=null, $download=true){
    $ch = curl_init();
    curl_setopt($ch , CURLOPT_URL            , $url);
    curl_setopt($ch , CURLOPT_SSL_VERIFYHOST , 0);
    curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , 0);
    curl_setopt($ch , CURLOPT_RETURNTRANSFER , 1);
    curl_setopt($ch , CURLOPT_FOLLOWLOCATION , 1);
    curl_setopt($ch , CURLOPT_TIMEOUT        , 5040);

    if($file){
        $file = fopen($file,'w+');
        curl_setopt($ch, CURLOPT_FILE, $file); //auto write to file
    }

    $content = curl_exec ($ch);
    curl_close ($ch);

    if($file){
        fclose($file);
    }

    return $content;
}

function parsel_plugin_check_update($version){
    $url = parsel_plugin_url("?action=check-update&version={$version}");

    parsel_debug("plugin update url: " . $url);

    $content = parsel_curl_request( $url );
    $content = json_decode($content, true);

    parsel_debug($content);

    return $content;
}

function parsel_plugin_download_update($version){
    $zipfile =  __DIR__.DIRECTORY_SEPARATOR."parsel_store_{$version}.zip";

    //download the zip update
    $url = parsel_plugin_url("?action=download-update");

    parsel_debug("download url: " . $url);

    parsel_curl_request( $url, $zipfile );

    parsel_debug("downloaded zip file: " . $zipfile);

    //unzip it
    if( file_exists($zipfile) && filesize($zipfile) > 0 ){

        parsel_debug("unzipping");

        $zip   = new ParselZip();
        $files = $zip->extract($zipfile);

        parsel_debug("files in zip: " );
        parsel_debug($files);

        //things went smoothly lets delete the zipfile
        if( !empty($files) ){
            parsel_debug("removing file");
            unlink($zipfile);
        }

    } else if( filesize($zipfile) == 0 ){
        unlink($zipfile);
    }

}

function parsel_update_plugin(){
    global $parsel_options;

    if(empty($parsel_options))
        $parsel_options = parsel_get_options();

    //dispable updates in case of any error


    //update will only work if curl is supported
    if( isset($parsel_options['parsel_support']['curl']) && $parsel_options['parsel_support']['curl'] == true ){

        //if there is an update, download plugin and unzip it
        $pluginInfo = get_plugin_data(__FILE__);

        parsel_debug($pluginInfo);

        $content    = parsel_plugin_check_update($pluginInfo['Version']);

        if( $content['update']  ){
            parsel_plugin_download_update($content['version']);
        }

    }


}


/**
 * Renders admin page and processes the form values. Saves options
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_admin_page(){

    parsel_update_plugin();

    //do some checks to see if the server supports remote urls
    $data['parsel_support'] = array();
    $data['parsel_support'][ "allow_url_fopen" ]   = ini_get("allow_url_fopen");
    $data['parsel_support'][ "curl" ]              = function_exists("curl_version");
    $data['parsel_support'][ "file_get_contents" ] = function_exists("file_get_contents");
    $data['parsel_support'][ "fopen" ]             = function_exists("fopen");
    $data['parsel_support'][ "file" ]              = function_exists("file");

    if( !empty($_POST) ){

        $error   = false;
        $message = array();

        $data['parsel_options'] = array(
            'parsel_store_id'  => $_POST['parsel_store_id'],
            'parsel_add_store' => $_POST['parsel_add_store'],
            'parsel_store_url' => $_POST['parsel_store_url'],
            'parsel_button_fg' => $_POST['parsel_button_fg'],
            'parsel_button_bg' => $_POST['parsel_button_bg'],
            'parsel_analytics' => $_POST['parsel_analytics'],
        );

        $data['parsel_options']['parsel_support']                  = $data['parsel_support'];
        $data['parsel_options']['parsel_support']['render_engine'] = $_POST['parsel_render_engine'];
        $data['parsel_options']['parsel_support']['debug']         = $_POST['parsel_debug'];
        $data['parsel_options']['parsel_support']['use_http']      = $_POST['parsel_use_http'];

        //
        //validate
        //
        if(empty($_POST['parsel_store_id'])){
            $error   = true;
            $message[] = 'parsel store id cannot be empty';
        }

        if(!empty($_POST['parsel_add_store'])){

            if($_POST['parsel_add_store'] == 'yes' ){

                if(empty($_POST['parsel_button_fg'])){
                    $error = true;
                    $message[] = "Text color cannot be empty";
                }

                if(empty($_POST['parsel_button_bg'])){
                    $error = true;
                    $message[] = "Background color cannot be empty";
                }

                if(empty($_POST['parsel_store_url'])){
                    $error = true;
                    $message[] = "Store url cannot be empty";
                }

            }

        }

        //
        //if no error write to database
        //
        if(!$error){

            //write to database

            $exists = parsel_get_options();

            //if options exist update
            if( !empty($exists) ){
                parsel_update_options($data['parsel_options']);
                $data['message'] = "Settings saved.";

            //else add
            } else {
                parsel_add_options($data['parsel_options']);
                $data['message'] = "Settings saved.";

            }

        } else {

            $data['error'] = true;
            $data['message'] = implode("<br/>", $message);
        }

    } else {
        //read opitons from database
        $data['parsel_options'] = parsel_get_options();

    }

    include __DIR__."/templates/parsel_admin_page.php";
}

function parsel_debug($content=array()){
    global $parsel_options;

    if( isset($parsel_options['parsel_support']['debug']) && $parsel_options['parsel_support']['debug'] == "yes" ){
        print "<!-- PARSEL DEBUG START\n";

        if(is_array($content) || is_object($content)){
            print print_r($content, true);
        } else {
            print $content;
        }

        print "\nPARSEL DEBUG END -->\n";
    }
}


/**
 * When shortcodes are placed in post, this function renders the results
 *
 * @param mixed $atts
 * @param string $content
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_shortcode($atts, $content=''){
    global $parsel_options;

    if(empty($parsel_options))
        $parsel_options = parsel_get_options();

    parsel_debug($parsel_options);

    extract( shortcode_atts(array(
        'id' => $parsel_options['parsel_store_id'],
    ), $atts ) );

    if( isset($parsel_options['parsel_support']['render_engine']) && !empty($parsel_options['parsel_support']['render_engine']) ){

        parsel_debug('force render engine');

        switch($parsel_options['parsel_support']['render_engine']){
            case 'curl':
                parsel_debug('render engine: curl');
                parsel_render_curl($id);
                break;

            case 'fopen':
                parsel_debug('render engine: fopen');
                parsel_render_fopen($id);
                break;

            case 'file_get_contents':
                parsel_debug('render engine: file_get_contents');
                parsel_render_file_get_contents($id);
                break;

            case 'file':
                parsel_debug('render engine: file');
                parsel_render_file($id);
                break;

            default;
                parsel_debug('render engine: curl [default]');
                parsel_render_curl($id);
                break;
        }


    } else {

        parsel_debug('auto detect render engine');

        //auto detect
        if( $parsel_options['parsel_support']['allow_url_fopen'] ){
            parsel_debug('render engine: file_get_contents');
            parsel_render_file_get_contents($id);

        } else if( $parsel_options['parsel_support']['curl'] ){
            parsel_debug('render engine: curl');
            parsel_render_curl($id);

        } else {

            parsel_debug('render engine: cannot determine');
            print "Cannot open Parsel store. [NO_SUPPORTED_RENDER_ENGINE_FOUND]";
        }

    }

}

/**
 * Only runs on admin pages init
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_admin_init(){
    wp_register_style( 'parsel_admin_style', plugins_url('css/parsel_admin_style.css', __FILE__) );
    wp_enqueue_style( 'parsel_admin_style' );

    wp_register_script( 'parsel_admin_script', plugins_url('js/parsel_admin_script.js', __FILE__) );
    wp_enqueue_script( 'parsel_admin_script' );
}

/**
 * Runs everytime wordpress footer runs. Add store button
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_wp_footer(){
    global $parsel_options;

    if(empty($parsel_options))
        $parsel_options = parsel_get_options();

    parsel_debug($parsel_options);

    if( isset($parsel_options['parsel_add_store']) && $parsel_options['parsel_add_store'] == "yes" ){

        //add javascript to create the side store button
        wp_register_script( 'parsel_client_script', plugins_url('js/parsel_client_script.js', __FILE__), null, 1, true );
        wp_enqueue_script( 'parsel_client_script' );

        $store_button      = "parsel-store-".$parsel_options['parsel_button_fg'].".png";
        $data['image']     = plugins_url("img/{$store_button}", __FILE__);
        $data['url']       = $parsel_options['parsel_store_url'];
        $data['button_bg'] = $parsel_options['parsel_button_bg'];

        parsel_debug($data);

        include __DIR__."/templates/parsel_client_footer.php";

    }

    if( isset($parsel_options['parsel_analytics']) && !empty($parsel_options['parsel_analytics']) ){

        $data['ga_code'] = $parsel_options['parsel_analytics'];

        parsel_debug($data);

        include __DIR__ . "/templates/parsel_client_analytics.php";

    }

}

/**
 * Runs only when plugin is activated
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_activate(){
}

/**
 * Only runs once when deactiavted
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_deactivate(){
}

function parsel_uninstall(){
    parsel_remove_options();
}

/**
 * Runs everytime wordpress runs
 *
 * @access public
 * @return void
 * @author Hamid
 */
function parsel_init(){
    if( !session_id() )
        session_start();
}

add_action('init'       , 'parsel_init');
add_action('admin_menu' , 'parsel_admin_menu');
add_action('admin_init' , 'parsel_admin_init');
add_action('wp_footer'  , 'parsel_wp_footer');

add_shortcode('parsel'  , 'parsel_shortcode');

//first time getting activated
register_activation_hook( __FILE__   , 'parsel_activate' );
register_deactivation_hook( __FILE__ , 'parsel_deactivate' );
register_uninstall_hook(__FILE__     , 'parsel_uninstall');
