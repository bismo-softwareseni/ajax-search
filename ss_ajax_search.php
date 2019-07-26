<?php
    /*
        Plugin Name: SoftwareSeni AJAX Search
        Description: Understand about AJAX on Wordpress (How to build it in Right Way)
        Version: 1.0
        Author: Bismoko Widyatno
    */

    /**
     * --------------------------------------------------------------------------
     * Main class for this plugin. This class will handle most of the 
     * AJAX Search plugin logic
     * --------------------------------------------------------------------------
     **/

    class SS_Ajax_Search_Main {
        function __construct() {
            /**
             * Execute this when plugin has been loaded
             * 1. Register AJAX search shortcode
             */
            add_action( 'plugins_loaded', array( $this, 'ssSearchPluginsLoadedHandlers' ) );
        }

        //-- function for creating shortcode
        function ssSearchShortcodeCreate() {
            ob_start();

            //-- 

            return ob_get_clean();
        }

        //-- function for executing some task when this plugin loaded
        function ssSearchPluginsLoadedHandlers() {
            //-- register ajax search shortcode
            add_shortcode( 'wp6_training', array( $this, 'ssSearchShortcodeCreate' ) );
        }
    }
?>
