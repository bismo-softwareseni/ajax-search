/**
 * This is the main javascript file to handle the ajax search logic
 */
(function($) {
    "use strict";

    /**
     * Main class
     */
    class SS_Ajax_Search_Main {
        //-- main search form element
        ss_text_input_elem  = '.ajax-input-post-title';
        ss_button_elem      = '.ajax-search-submit-button';
        
        constructor() {
            var this_main_obj = this;

            //-- execute when document is loaded
            $( document ).ready( function() {
                //-- set handlers for ajax search form
                this_main_obj.ssAjaxFormHandlers();
            } );
        }

        //-- function to handle ajax search form
        ssAjaxFormHandlers() {
            var this_main_obj           = this;
            var ss_autocomplete_result  = new Array();

            //-- get autocomplete data
            var ss_autocomplete_ajax_data = {
                'action' : 'ssSearchHandleAjaxRequest',
                'ajax-all-post-title' : true
            };

            jQuery.post( ajax_object.ajax_url, ss_autocomplete_ajax_data, function( response ) {
                if( response !== "" ) {
                    ss_autocomplete_result = JSON.parse( response ).map( function( item ) { return item.post_title; } );
                
                    //-- enabling jQuery autocomplete
                    $( this_main_obj.ss_text_input_elem ).autocomplete( {
                        source: ss_autocomplete_result
                    } );
                }
            } );
            //-- end get autocomplete data

            //-- submit button clicked
            $( this_main_obj.ss_button_elem ).on( 'click', function() {
                //-- default parameter = ss_to_page = 1
                if( $( this_main_obj.ss_text_input_elem ).val() != '' ) {
                    this_main_obj.ssAjaxFormSubmitHandlers( 1 );
                }
            } );
        }
        //-- end function to handle ajax search form

        //-- function to handle submit ajax search button
        ssAjaxFormSubmitHandlers( ss_to_page ) {
            var this_main_obj       = this;
            var ss_search_result    = new Array();

            //-- clear result
            $( '.post-suggestion' ).html('');

            //-- get post suggestion
            var ss_input_data = {
                'action' : 'ssSearchHandleAjaxRequest',
                'ajax-input-post-title' : $( this_main_obj.ss_text_input_elem ).val(),
                'ajax-go-to-page' : ss_to_page
            };

            jQuery.post( ajax_object.ajax_url, ss_input_data, function( response ) {
                var ss_search_result = JSON.parse( response );
                
                //-- show result
                $( '.ajax-search-result-container' ).fadeIn( 'fast' );

                //-- if result exists
                if( ss_search_result.length > 0 ) {
                    for( var i=0; i<ss_search_result.length; i++ ) {
                        var ss_html_tags = '<h4><a href="' + ss_search_result[ i ][ 'post_url' ] + '">' + ss_search_result[ i ][ 'post_title' ] + '</a></h4>';
                        $( '.post-suggestion' ).append( ss_html_tags );    
                    }
    
                    //-- get current page & max page
                    var ss_max_page     = parseInt( ss_search_result[ 0 ][ 'max_page' ] );
                    var ss_current_page = parseInt( ss_search_result[ 0 ][ 'current_page' ] );
                    var ss_page_next    = 0;
                    var ss_page_prev    = 0;
                    
                    //-- define next and prev page
                    if( (ss_current_page-1) >= 1 ) {
                        ss_page_prev = ss_current_page-1;
                    } else {
                        ss_page_prev = 1;
                    }
    
                    if( (ss_current_page+1) <= ss_max_page ) {
                        ss_page_next = (ss_current_page+1);
                    } else {
                        ss_page_next = ss_max_page;
                    }
    
                    //-- show pagination
                    var ss_pagination_html_tags = '<div class="page-number">'+ ss_current_page +' of '+ ss_max_page +'</div>';
                        ss_pagination_html_tags += '<div class="ui large buttons">';
                        ss_pagination_html_tags +=      '<button class="ui button left labeled icon button-ajax-pagination" data-page="' + ss_page_prev + '"><i class="left arrow icon"></i> Previous Page</button>';
                        ss_pagination_html_tags +=      '<button class="ui button right labeled icon button-ajax-pagination" data-page="' + ss_page_next + '"><i class="right arrow icon"></i> Next Page</button>';
                        ss_pagination_html_tags += '</div>';
    
                    $( '.pagination-container' ).html( ss_pagination_html_tags );
    
                    //-- call pagination handlers
                    this_main_obj.ssAjaxFormPaginationHandlers();
                } else {
                    //-- if results not found
                    $( '.post-suggestion' ).append( '<h5>Data Not Found!</h5>' ); 
                }
            } );
        }

        //-- function to handle ajax search forms pagination
        ssAjaxFormPaginationHandlers() {
            var this_main_obj = this;

            $( '.button-ajax-pagination' ).on( 'click', function() {
                this_main_obj.ssAjaxFormSubmitHandlers( $( this ).data( 'page' ) );
            } );
        }

    }

    //-- execute main class
    var ss_ajax_search_main_class = new SS_Ajax_Search_Main();

})( jQuery );