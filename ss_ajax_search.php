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
        var $ss_input_post_title;
        var $ss_input_post_title_origin;

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

            //-- jquery ui js and css
            $ss_wp_scripts = wp_scripts();

            wp_enqueue_script( 'jquery-ui-autocomplete' );
            wp_enqueue_style( 'jquery-ui-css',
                'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $ss_wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css',
                false,
                'v1.0',
                false);
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
            $ss_sql_title           = "";
            $ss_sql_pagination      = "";
            $ss_results             = "";
            $ss_count_max_posts     = 0;
            $ss_current_page        = 1;
            $ss_max_page            = 1;

            //-- get autocomplete data
            if( isset( $_POST[ 'ajax-get-autocomplete-data' ] ) && !empty( $_POST[ 'ajax-get-autocomplete-data' ] ) ) {
                $ss_autocomplete_args = array(
                    'post_type'     => 'post',
                    'post_status'   => 'publish'
                );
    
                $ss_results = new WP_Query( $ss_autocomplete_args );
            }

            //-- search similar title
            if( isset( $_POST[ 'ajax-input-post-title' ] ) && !empty( $_POST[ 'ajax-input-post-title' ] ) ) {
                $this->ss_input_post_title          = explode( ' ', preg_replace( '/[,.]+/', '', trim( $_POST[ 'ajax-input-post-title' ] ) ) );
                $this->ss_input_post_title_origin   = $_POST[ 'ajax-input-post-title' ];

                //-- count max post
                add_filter( 'posts_where', array( $this, 'ssSearchRegexFilter' ) );
                $ss_max_post_args = array(
                    'post_type'     => 'post',
                    'post_status'   => 'publish',
                    'orderby'       => 'title',
                    'order'         => 'asc'
                );
                
                $ss_max_post_results = new WP_Query( $ss_max_post_args );

                remove_filter( 'posts_where', array( $this, 'ssSearchRegexFilter' ) );

                //-- get max page
                $ss_count_max_posts = $ss_max_post_results->post_count;
                $ss_max_page        = ceil( $ss_count_max_posts / $this->ss_max_result_per_page );
                
               

                //-- get current page
                if( isset( $_POST[ 'ajax-go-to-page' ] ) && $_POST[ 'ajax-go-to-page' ] > 0 ) {
                    $ss_current_page = $_POST[ 'ajax-go-to-page' ];
                }

                //-- search similar title
                add_filter( 'posts_where', array( $this, 'ssSearchRegexFilter' ) );
                $ss_search_args = array(
                    'post_type'         => 'post',
                    'post_status'       => 'publish',
                    'orderby'           => 'title',
                    'order'             => 'asc',
                    'posts_per_page'    => $this->ss_max_result_per_page,
                    'paged'             => $ss_current_page,
                    'offset'            => ($ss_current_page-1) * $this->ss_max_result_per_page
                );                
                $ss_results = new WP_Query( $ss_search_args );

                remove_filter( 'posts_where', array( $this, 'ssSearchRegexFilter' ) );
            }

            
            wp_send_json( $ss_results );            

            //-- to terminate immediately and get the proper response
        }

        //-- function to filter the suggestion search
        function ssSearchRegexFilter( $where ) {
            if( !empty( $this->ss_input_post_title ) ) {
                $where .= " AND wp_posts.post_title != '" . $this->ss_input_post_title_origin . "' AND wp_posts.post_title REGEXP '.[[:<:]]" . implode( '[[:>:]]|.[[:<:]]', $this->ss_input_post_title ) . "[[:>:]]' "; 
            }

            return $where;
        }
    }


    //-- execute main class
    $ss_ajax_search_main_class = new SS_Ajax_Search_Main();
?>
