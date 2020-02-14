( function () {
	window.ReportDialog = function ( config ) {
		config = config || {};
		config.size = 'large';
		ReportDialog.parent.call( this, config );
	}
	OO.inheritClass( ReportDialog, OO.ui.ProcessDialog );

	ReportDialog.static.name = 'reportDialog';
	ReportDialog.static.title = mw.message( 'pageissues-reportdialog-title' ).plain();
	ReportDialog.static.actions = [
		{
			action: 'advanced',
			modes: 'basic',
			label: mw.message( 'pageissues-reportdialog-action-more' ).plain()
		},
		{
			action: 'basic',
			modes: 'advanced',
			label: mw.message( 'pageissues-reportdialog-action-less' ).plain()
		},
		{
			action: 'report',
			modes: [ 'basic', 'advanced' ],
			label:  mw.message( 'pageissues-reportdialog-action-report' ).plain(),
			flags: [ 'primary', 'progressive' ],
			icon: 'alert',
			disabled: true
		},
		{
			modes: [ 'basic', 'advanced' ],
			icon: 'close',
			framed: false,
			flags: 'safe'
		}
	];
	ReportDialog.static.basicIssues = [
		'needsinformation',
		'istooshort',
		'hasoutdatedinformation',
		'needsfactchecking',
		'isnotnotable',
		'containsvandalism'
	];

	ReportDialog.prototype.initialize = function() {
		ReportDialog.parent.prototype.initialize.apply( this, arguments );
		var self = this;

		this.basicIssues = new OO.ui.CheckboxMultiselectInputWidget( {
			options: ReportDialog.static.basicIssues.map( function( v ) {
				return {
					data: v,
					label: mw.message( 'pageissues-reportdialog-basic-' + v ).plain()
				}
			} )
		} ).on( 'change', function( value ) {
			console.log( self.reportButton, value.length === 0 );
			self.reportButton.setDisabled( value.length === 0 );
		} );

		this.panelBasic = new OO.ui.PanelLayout( { padded: true, expanded: false } );
		this.panelBasic.$element.append( this.basicIssues.$element );

		this.issueSelector = new ( require( 'ext.pageissues.issueselector' ) )( {
			inputPosition: 'outline',
			options: mw.config.get( 'ext.pageissues.issues' ),
			$overlay: this.$overlay
		} ).on( 'change', function( value ) {
			console.log( self.reportButton, value.length === 0 );
			self.reportButton.setDisabled( value.length === 0 );
			self.updateSize();
		} );

		this.noteField = new OO.ui.TextInputWidget();

		this.panelAdvanced = new OO.ui.PanelLayout( { padded: true, expanded: false } );
		this.panelAdvanced.$element.append(
			new OO.ui.FieldLayout( this.issueSelector, {
				align: 'top',
				label: new OO.ui.HtmlSnippet(
					'<b>' + mw.message( 'pageissues-reportdialog-label-issues' ).text() + '</b> ' + mw.message( 'pageissues-reportdialog-label-issues-details' ).text()
				)
			} ).$element,
			new OO.ui.FieldLayout( this.noteField, {
				align: 'top',
				label: new OO.ui.HtmlSnippet(
					'<b>' + mw.message( 'pageissues-reportdialog-label-note' ).text() + '</b> ' + mw.message( 'pageissues-reportdialog-label-note-details' ).text()
				)
			} ).$element
		);

		this.stackLayout = new OO.ui.StackLayout( {
			items: [ this.panelBasic, this.panelAdvanced ]
		} );

		this.$body.append( this.stackLayout.$element );
	};

	ReportDialog.prototype.getSetupProcess = function ( data ) {
		return ReportDialog.parent.prototype.getSetupProcess.call( this, data )
			.next( function () {
				this.mode = 'basic';
				this.actions.setMode( 'basic' );
				this.reportButton = this.getActions().get( { actions: 'report' } )[0];
			}, this );
	};

	ReportDialog.prototype.getActionProcess = function ( action ) {
		var self = this;

		if ( action === 'advanced' ) {
			this.mode = 'advanced';
			this.issueSelector.setValue( this.basicIssues.getValue() );
			this.stackLayout.setItem( this.panelAdvanced );
			this.actions.setMode( 'advanced' );
			this.title.setLabel( mw.message( 'report' ).plain() );
		} else if ( action === 'basic' ) {
			this.mode = 'basic';
			this.basicIssues.setValue( this.issueSelector.getValue() );
			this.stackLayout.setItem( this.panelBasic );
			this.actions.setMode( 'basic' );
			this.title.setLabel( mw.message( 'pageissues-reportdialog-title' ).plain() );
		} else if ( action === 'report' ) {
			return new OO.ui.Process( function () {
				var issues = [];
				if ( self.mode === 'basic' ) {
					issues = self.basicIssues.getValue();
				} else {
					issues = self.issueSelector.getValue();
				}

				if ( issues.length === 0 ) {
					return;
				}

				new mw.Api().postWithEditToken( {
					action: 'pageissues',
					pageid: mw.config.get( 'wgArticleId' ),
					piaction: 'report',
					issues: issues.join( '|' ),
					note: self.noteField.getValue()
				} ).done( function() {
					self.close();
					mw.notification.notify( mw.message( 'pageissues-actionreport-success' ).plain() );
				} ).fail( function() {
					self.close();
					mw.notification.notify( mw.message( 'pageissues-unknownerror' ).plain(), { type: 'error' } );
				} );
			} );
		} else if ( action === 'close' ) {
			return new OO.ui.Process( function () {
				self.close();
			} );
		}

		return ReportDialog.parent.prototype.getActionProcess.call( this, action );
	};

	ReportDialog.prototype.getBodyHeight = function () {
		return this.stackLayout.getCurrentItem().$element.outerHeight( true );
	};

	module.exports = ReportDialog;
}() );
