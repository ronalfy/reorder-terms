jQuery(document).ready(function($) {
	$.fn.reverse = function() {
	    return this.pushStack(this.get().reverse(), arguments);
	};
	
	// create two new functions: prevALL and nextALL. they're very similar, hence this style.
	$.each( ['prev', 'next'], function(unusedIndex, name) {
	    $.fn[ name + 'ALL' ] = function(matchExpr) {
	        // get all the elements in the body, including the body.
	        var $all = $('body').find('#post-list li').andSelf();
	
	        // slice the $all object according to which way we're looking
	        $all = (name == 'prev')
	             ? $all.slice(0, $all.index(this)).reverse()
	             : $all.slice($all.index(this) + 1)
	        ;
	        // filter the matches if specified
	        if (matchExpr) $all = $all.filter(matchExpr);
	        return $all;
	    };
	});
	
	
	//Variable sortnonce is declared globally	
	var postList = $('#post-list');
	var max_levels = 6;
	if ( reorder_posts.hierarchical == 'false' ) {
		max_levels = 1;
	}
	var callback = false;
	var sort_start = {};
	var sort_end = {};
	
	var index = 0;
	function reorder_term_get_index( item ) {
		var item_index = parseInt( item.index() ) + 1;
		index += item_index;
		if ( item.parent( 'li' ).length > 0 ) {
			reorder_term_get_index( item.parent() ); 
		}	
		return index;
	};
	
	postList.nestedSortable( {
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li',
		maxLevels: max_levels,
		opacity: .6,
		placeholder: 'placeholder',
		revert: 250,
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div',
		listType: 'ul',
		update: function( event, ui ) {
			$loading_animation = jQuery( '#loading-animation' );
			var reorder_ajax_callback = function( response ) {
				response = jQuery.parseJSON( response );
				if ( true == response.more_posts ) {
					$.post( ajaxurl, response, reorder_ajax_callback );
				} else {    					
					$('#loading-animation').css("display", "none");
				}
			};			
			ui.item.find( 'div' ).append( $loading_animation );
			
			$loading_animation.css("display", "block");
			
			//Get the end items where the post was placed
			sort_end.item = ui.item;
			sort_end.prev = ui.item.prev( ':not(".placeholder")' );
			sort_end.next = ui.item.next( ':not(".placeholder")' );
			
			//Get starting post parent
			var start_term_parent = parseInt( sort_start.item.attr( 'data-parent' ) );
			
			//Get ending post parent
			var end_term_parent = 0;
			if( sort_end.prev.length > 0 || sort_end.next.length > 0 ) {
				if ( sort_end.prev.length > 0 ) {
					end_term_parent = parseInt( sort_end.prev.attr( 'data-parent' ) );
				} else if ( sort_end.next.length > 0 ) {
					end_term_parent = parseInt( sort_end.next.attr( 'data-parent' ) );
				} 	
			} else if ( sort_end.prev.length == 0 && sort_end.next.length == 0 ) {
				//We're the only child :(
				end_term_parent = ui.item.parents( 'li:first' ).attr( 'data-id' );	
			}
			
			//Update post parent in DOM
			sort_end.item.attr( 'data-parent', end_term_parent );
			
			
			
			//Find the menu order and update dom accordingly
			var offset = parseInt( $( '#reorder-offset' ).val() );
			var list_offset = sort_end.item.prevALL( 'li' ).length + offset;
			sort_end.item.attr( 'data-menu-order', list_offset );
			
			//Get attributes
			var attributes = {};
			$.each(  sort_end.item[0].attributes, function() {
				attributes [ this.name ] = this.value;
			} );
			
			//Perform Ajax Call
			var parent_ajax_args = {
				action: reorder_posts.action,
				term_parent: end_term_parent,
				start: 0,
				nonce: reorder_posts.sortnonce,
				term_id: sort_end.item.attr( 'data-id' ),
				menu_order: sort_end.item.attr( 'data-menu-order' ),
				excluded: {},
				post_type: sort_start.item.attr( 'data-post-type' ),
				attributes: attributes,
				parent: 0
			};
			$.post( ajaxurl, parent_ajax_args, reorder_ajax_callback );			
		},
		start: function( event, ui ) {
			sort_start.item = ui.item;
			sort_start.prev = ui.item.prev( ':not(".placeholder")' );
			sort_start.next = ui.item.next( ':not(".placeholder")' );
		}
	});
	$( "#post-list a" ).toggle( function() {
		$( this ).html( reorder_posts.collapse );
		$( this ).parent().next( '.children' ).slideDown( "slow" );
		return false;
	}, function() {
		$( this ).html( reorder_posts.expand );
		$( this ).parent().next( '.children' ).slideUp( "slow" );
		return false;
	} );
});