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
        var $ss_max_result_per_page = 5;

        function __construct() {
            /**
             * Execute this when plugin has been loaded
             * 1. Register AJAX search shortcode
             */
            add_action( 'plugins_loaded', array( $this, 'ssSearchPluginsLoadedHandlers' ) );
        }

        //-- function for displaying ajax search form
        function ssSearchDisplayForm() {
    ?>

        <!-- form -->
        <form class="ajax-search-form ui form" method="POST">
            <h3>Search Similar Posts</h3>

            <div class="field">
                <!-- input post title -->
                <div class="field">
                    <label>Post Title</label>
                    <input type="text" class="ajax-input-post-title" name="ajax-input-post-title" />
                </div>
                
                <!-- submit button -->
                <div class="field">
                    <button type="button" class="ajax-search-submit-button ui button">Search</button>
                </div>
            </div>
        </form>
        <!-- end form -->

        <!-- ajax result container -->
        <div class="ajax-search-result-container" style="display: none;">
            <h3>Post Suggestions :</h3>

            <!-- result -->
            <div class="post-suggestion">
            </div>
            <!-- end result -->

            <!-- pagination -->
            <div class="pagination-container">
                
            </div>
            <!-- end pagination -->
        </div>
        <!-- end ajax result container -->

    <?php
        }

        //-- function for creating shortcode
        function ssSearchShortcodeCreate() {
            ob_start();

            //-- display ajax search form
            $this->ssSearchDisplayForm();

            return ob_get_clean();
        }

        //-- function for enqueueing css and js
        function ssSearchEnqueueScript() {
            //-- main js file
            wp_enqueue_script( 'ss_search_main_js', plugin_dir_url( __FILE__ ) . 'js/ss_ajax_search.js', array( 'jquery' ) );

            //-- main css file
            wp_enqueue_style( 'ss_search_main_css', plugin_dir_url( __FILE__ ) . 'css/ss_ajax_search.css' );

            //-- localize ajax
	        wp_localize_script( 'ss_search_main_js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'input_value' => 1234 ) );

            //-- jquery ui css and js
            wp_enqueue_script( 'ss_search_jquery_ui_js', plugin_dir_url( __FILE__ ) . 'lib/jquery-ui/jquery-ui.min.js' );
            wp_enqueue_style( 'ss_search_jquery_ui_css', plugin_dir_url( __FILE__ ) . 'lib/jquery-ui/jquery-ui.min.css' );
        }

        //-- function for executing some task when this plugin loaded
        function ssSearchPluginsLoadedHandlers() {
            //-- register ajax search shortcode
            add_shortcode( 'wp6_training', array( $this, 'ssSearchShortcodeCreate' ) );

            //-- enqueue css and js
            add_action( 'wp_enqueue_scripts', array( $this, 'ssSearchEnqueueScript' ) );

            //-- action to call ajax
            add_action( 'wp_ajax_ssSearchHandleAjaxRequest', array( $this, 'ssSearchHandleAjaxRequest' ) );
            
            //-- call ajax on front end
            add_action( 'wp_ajax_ssSearchHandleAjaxRequest', array( $this, 'ssSearchHandleAjaxRequest' ) );
            add_action( 'wp_ajax_nopriv_ssSearchHandleAjaxRequest', array( $this, 'ssSearchHandleAjaxRequest' ) );
        }

        //-- function to handle ajax request
        function ssSearchHandleAjaxRequest() {
            global $wpdb;
            $ss_input_post_title    = "";
            $ss_sql_title           = "";
            $ss_sql_pagination      = "";
            $ss_results             = "";
            $ss_results_with_tag    = array();
            $ss_select_terms        = " $wpdb->posts.post_title ";
            $ss_count_max_posts     = 0;
            $ss_current_page        = 1;
            $ss_max_page            = 1;

            //-- get keyword
            if( isset( $_POST[ 'ajax-input-post-title' ] ) && !isset( $_POST[ 'ajax-all-post-title' ] ) ) {
                //-- split keywords into individual words
                if( !empty( $_POST[ 'ajax-input-post-title' ] ) ) {
                    $ss_input_post_title    = explode( ' ', preg_replace( '/[,.]+/', '', trim( $_POST[ 'ajax-input-post-title' ] ) ) );
                    $ss_select_terms        = " $wpdb->posts.ID, $wpdb->posts.post_title ";
                    $ss_sql_title           = " $wpdb->posts.post_title != '" . $_POST[ 'ajax-input-post-title' ] . "' AND $wpdb->posts.post_title REGEXP '.[[:<:]]" . implode( '[[:>:]]|.[[:<:]]', $ss_input_post_title ) . "[[:>:]]' AND ";                    
                }
                
                //-- count max post ( for ajax pagination )
                $ss_count_max_posts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( $wpdb->posts.ID )
                                                                        FROM $wpdb->posts
                                                                        WHERE ".$ss_sql_title." 
                                                                        $wpdb->posts.post_status = 'publish' 
                                                                        AND $wpdb->posts.post_type = 'post'"
                                                                    , array() ) );

                //-- get max page
                $ss_max_page = ceil( $ss_count_max_posts / $this->ss_max_result_per_page );
                
                //-- get current page
                if( isset( $_POST[ 'ajax-go-to-page' ] ) && $_POST[ 'ajax-go-to-page' ] > 0 ) {
                    $ss_current_page = $_POST[ 'ajax-go-to-page' ];
                }

                //-- set sql pagination
                $ss_sql_pagination = " LIMIT " . ( ($ss_current_page-1) * $this->ss_max_result_per_page ) . ", " . $this->ss_max_result_per_page;
            }

            //-- get post that has publish status ( autocomplete using this sql too )
            $ss_sql = "SELECT DISTINCT". $ss_select_terms ."
                FROM $wpdb->posts
                WHERE ".$ss_sql_title." 
                $wpdb->posts.post_status = 'publish' 
                AND $wpdb->posts.post_type = 'post'
                ORDER BY $wpdb->posts.post_title ASC ".$ss_sql_pagination;

            $ss_results = $wpdb->get_results( $ss_sql );

            //-- return the result ( only string for autocomplete )
            if( isset( $_POST[ 'ajax-input-post-title' ] ) && !isset( $_POST[ 'ajax-all-post-title' ] ) ) {
                foreach( $ss_results as $ss_result ) {
                    $ss_result_tag = array(
                        'ID'            => $ss_result->ID,
                        'post_url'      => esc_url( get_permalink( $ss_result->ID ) ),
                        'post_title'    => $ss_result->post_title,
                        'max_page'      => $ss_max_page,
                        'current_page'  => $ss_current_page
                    );
                    array_push( $ss_results_with_tag, $ss_result_tag);
                }
                
                echo json_encode( $ss_results_with_tag );
            } else {
                echo json_encode( $ss_results );
            }

            //-- to terminate immediately and get the proper response
            wp_die();
        }
    }


    //-- execute main class
    $ss_ajax_search_main_class = new SS_Ajax_Search_Main();
?>
