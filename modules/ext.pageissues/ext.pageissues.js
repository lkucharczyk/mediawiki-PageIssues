( function() {
	var PageIssues = {
		api: null,
		reportButton: null,
		upvoteButton: null,
		windowManager: null,
		$sidebuttons: null,
		ReportDialog: null,
		init: function( require ) {
			if (
				!mw.config.get( 'wgIsArticle' ) ||
				mw.config.get( 'wgIsMainPage' ) ||
				mw.config.get( 'wgContentNamespaces' ).indexOf( mw.config.get( 'wgNamespaceNumber' ) ) === -1 ||
				mw.config.get( 'wgAction' ) !== 'view'
			) {
				return;
			}

			this.api = new mw.Api();
			this.ReportDialog = require( 'ext.pageissues.reportdialog' );
			this.$sidebuttons = $( '<div id="pageissues-sidebuttons">' ).appendTo( document.body );

			var self = this;

			if ( mw.config.get( 'ext.pageissues.canUpvote' ) ) {
				this.upvoteButton = new OO.ui.ButtonWidget( {
					icon: 'upvote',
					framed: false,
					flags: mw.config.get( 'ext.pageissues.upvoted' ) ? [ 'progressive' ] : []
				} ).on( 'click', this.upvote.bind( this ) );
				this.$sidebuttons.append( this.upvoteButton.$element );
			}

			if ( mw.config.get( 'ext.pageissues.canReport' ) ) {
				this.reportButton = new OO.ui.ButtonWidget( {
					icon: 'alert',
					framed: false
				} ).on( 'click', this.showReportDialog.bind( this ) );
				this.$sidebuttons.append( this.reportButton.$element );


				$( '#ca-report' ).on( 'click', function( e ) {
					self.showReportDialog();
					e.preventDefault();
				} );
			}
		},
		upvote: function() {
			if ( mw.config.get( 'ext.pageissues.upvoted' ) ) {
				return this.removeUpvote();
			}

			var self = this;
			return this.api.postWithEditToken( {
				action: 'pageissues',
				pageid: mw.config.get( 'wgArticleId' ),
				piaction: 'upvote'
			} ).done( function() {
				mw.notification.notify( mw.message( 'pageissues-upvote-success' ).plain() );
				self.upvoteButton.setFlags( { progressive: true } );
				mw.config.set( 'ext.pageissues.upvoted', true );
			} ).fail( function() {
				mw.notification.notify( mw.message( 'pageissues-unknownerror' ).plain(), { type: 'error' } );
			} );
		},
		removeUpvote: function() {
			var self = this;
			return this.api.postWithEditToken( {
				action: 'pageissues',
				pageid: mw.config.get( 'wgArticleId' ),
				piaction: 'removeupvote'
			} ).done( function() {
				mw.notification.notify( mw.message( 'pageissues-upvote-removed' ).plain() );
				self.upvoteButton.setFlags( { progressive: false } );
				mw.config.set( 'ext.pageissues.upvoted', false );
			} ).fail( function() {
				mw.notification.notify( mw.message( 'pageissues-unknownerror' ).plain(), { type: 'error' } );
			} );
		},
		showReportDialog: function() {
			if ( !this.windowManager ) {
				this.windowManager = new OO.ui.WindowManager();
				$( document.body ).append( this.windowManager.$element );
			}

			var reportDialog = new this.ReportDialog();

			this.windowManager.addWindows( [ reportDialog ] );
			this.windowManager.openWindow( reportDialog );
		}
	}

	$.when(
		mw.loader.using( 'ext.pageissues.issueselector' ),
		mw.loader.using( 'ext.pageissues.reportdialog' ),
		$.ready()
	).then( PageIssues.init.bind( PageIssues ) );
}() );
