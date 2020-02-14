<?php

namespace MediaWiki\Extension\PageIssues\Specials;

use MediaWiki\Extension\PageIssues\Models\Upvote;
use Message;
use SpecialPage;

class SpecialMostUpvoted extends SpecialPage {
	const ALLOWED_DAYS = [ 1, 10, 30, 90, 365 ];

	function __construct() {
		parent::__construct( 'MostUpvoted' );
	}

	function execute( $par ) {
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'mostupvoted' ) );

		$req = $this->getRequest();
		$days = $req->getIntOrNull( 'days' );

		if ( !in_array( $days, static::ALLOWED_DAYS ) ) {
			$days = 30;
		}

		$linkrenderer = $this->getLinkRenderer();
		$self = $this->getPageTitle();
		$links = [];
		foreach ( static::ALLOWED_DAYS as $day ) {
			$links[] = $day === $days ? "<b>$day</b>" : $linkrenderer->makeLink( $self, $day, [], [ 'days' => $day ] );
		}

		$out->addWikiMsg(
			'pageissues-specialmostupvoted-daysselect',
			Message::rawParam( implode( ', ', $links ) )
		);

		$list = Upvote::getMostUpvoted( $days );
		$out->addHTML( '<ul>' );
		foreach ( $list as $item ) {
			$link = $linkrenderer->makeLink( $item['page'] );
			$msg = wfMessage( 'pageissues-nupvotes', $item['count'] )->parse();
			$out->addHTML( "<li>$link ($msg)</li>" );
		}
		$out->addHTML( '</ul>' );
	}

	function getGroupName() : string {
		return 'maintenance';
	}
}
