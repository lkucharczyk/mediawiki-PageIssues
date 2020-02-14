( function() {
	var PageIssuesList = {
		$table: null,
		issueSelector: null,
		init: function( require ) {
			this.$table = $( '.pageissues-list' );
			this.IssueSelector = require( 'ext.pageissues.issueselector' )

			this.sortTable();
			this.filterIssues();
		},
		filterIssues: function() {
			var self = this;
			var issues = mw.config.get( 'ext.pageissues.issues' );
			var $issueHeader = this.$table.find( '.pageissues-issueheader' );

			var issueSelector = new this.IssueSelector( {
				options: issues,
				selected: Object.values( issues ).flat()
			} ).on( 'change', function() {
				var selected = issueSelector.getValue();
				self.$table.find( 'tbody tr' ).each( function() {
					$this = $( this );
					if ( selected.indexOf( $this.data( 'issue' ) ) === -1 ) {
						$this.addClass( 'oo-ui-element-hidden' );
					} else {
						$this.removeClass( 'oo-ui-element-hidden' );
					}
				} );
			} );

			issueSelector.$element.css( 'width', $issueHeader.width() + 'px' );

			var filterButton = new OO.ui.PopupButtonWidget( {
				icon: 'funnel',
				indicator: 'down',
				title: 'Filter issues',
				popup: {
					content: [ issueSelector ],
					anchor: false,
					padded: false,
					align: 'backwards'
				}
			} );
			filterButton.popup.on( 'toggle', function( visible ) {
				filterButton.setIndicator( visible ? 'up' : 'down' );
			} );
			filterButton.popup.$clippableContainer.toggle();
			filterButton.$element.css( 'float', 'right' );

			var $panel = new OO.ui.PanelLayout( {
				content: [
					new OO.ui.LabelWidget( {
						label: $issueHeader.text()
					} ),
					filterButton
				],
				expanded: false
			} ).$element;

			$issueHeader
				.empty()
				.append( $panel )
				.css( {
					'line-height': filterButton.$element.height() + 'px',
					'width': $panel.width()
				} );
		},
		sortTable: function() {
			var self = this;
			this.$table
				.tablesorter( {
					cssChildRow: 'pageissues-issuedetails'
				} )
				.one( 'sortEnd.tablesorter', function() {
					self.$table.find( '.pageissues-issue > td:first-child' ).attr( 'rowspan', 2 );
					self.$table.find( '.pageissues-issuedetails > td:first-child' ).remove();
				} );
		}
	};

	$.when(
		mw.loader.using( 'ext.pageissues.issueselector' ),
		$.ready()
	).then( PageIssuesList.init.bind( PageIssuesList ) );
}() );
