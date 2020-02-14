( function() {
	function IssueSelector( config ) {
		config = config || {};
		config.allowArbitrary = false;

		let options = config.options || [];
		let selected = config.selected || [];
		config.options = [];
		config.selected = [];

		IssueSelector.parent.call( this, config );

		this.processOptions( options );
		this.setValue( selected );
	}
	OO.inheritClass( IssueSelector, OO.ui.MenuTagMultiselectWidget );

	IssueSelector.prototype.processOptions = function( options ) {
		for ( const key in options ) {
			let option = options[key];

			if ( option instanceof Array ) {
				this.menu.addItems(
					new OO.ui.MenuSectionOptionWidget( {
						label: mw.message( 'pageissues-issuegroup-' + key ).plain()
					} )
				);
				this.processOptions( option );
			} else {
				this.menu.addItems(
					new OO.ui.MenuOptionWidget( {
						data: option,
						label: mw.message( 'pageissues-issue-' + option ).plain(),
						icon: 'tag'
					} )
				);
			}
		}
	};

	module.exports = IssueSelector;
}() );
