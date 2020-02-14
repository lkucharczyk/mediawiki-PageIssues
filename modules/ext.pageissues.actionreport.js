( function() {
	if ( mw.config.get( 'wgAction' ) !== 'report' ) {
		return;
	}

	$( '#pageissues-report fieldset' ).each( function() {
		var toggleIcon = new OO.ui.IconWidget( { icon: 'expand' } );
		$( this ).children( 'div' ).makeCollapsible( {
			$customTogglers: $( this ).children( 'legend' ).append( toggleIcon.$element.css( 'margin-left', '1em' ) ),
			collapsed: true
		} ).on( 'beforeExpand.mw-collapsible', function() {
			toggleIcon.setIcon( 'collapse' );
		} ).on( 'beforeCollapse.mw-collapsible', function() {
			toggleIcon.setIcon( 'expand' );
		} );
	} );
}() );
