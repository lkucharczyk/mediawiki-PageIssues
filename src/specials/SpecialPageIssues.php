<?php

namespace MediaWiki\Extension\PageIssues\Specials;

use MediaWiki\Extension\PageIssues\Models\Issues;
use MediaWiki\Extension\PageIssues\Models\PageIssue;
use MediaWiki\Extension\PageIssues\Models\PageIssuesSet;
use MediaWiki\Extension\PageIssues\Models\Report;
use Message;
use SpecialPage;
use TemplateParser;

class SpecialPageIssues extends SpecialPage {
	function __construct() {
		parent::__construct( 'PageIssues' );
	}

	function execute( $par ) {
		global $wgExtensionDirectory;

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'pageissues' ) );
		$out->addModules( 'ext.pageissues.specialpageissues' );
		$out->addJsConfigVars( 'ext.pageissues.issues', Issues::ISSUES );

		$req = $this->getRequest();
		$oldreports = $req->getBool( 'oldreports' );

		$pageissuesset = new PageIssuesSet();
		$pageissuesset->getAll( $oldreports ? [] : [ Report::COLPREFIX . 'timestamp > ' . wfTimestamp( TS_MW, wfTimestamp() - 30 * 86400 ) ] );

		$items = [];
		foreach ( $pageissuesset->pageissues as $pageissues ) {
			foreach ( $pageissues->issues as $pageissue ) {
				$items[$pageissue->getSortKey()] = $this->processPageIssue( $pageissue );
			}
		}

		ksort( $items );

		$out->addHTML(
			( new TemplateParser( "$wgExtensionDirectory/PageIssues/templates" ) )->processTemplate( 'specialpageissues', [
				'messages' => [
					'quantity' => $this->msg( 'pageissues-specialpageissues-quantity' )->plain(),
					'pagetitle' => $this->msg( 'pageissues-specialpageissues-pagetitle' )->plain(),
					'issues' => $this->msg( 'pageissues-specialpageissues-issues' )->plain()
				],
				'toggleold' => $this->msg( 'pageissues-specialpageissues-toggleold' )->rawParams(
					$this->getLinkRenderer()->makeLink(
						$this->getPageTitle(),
						$this->msg( $oldreports ? 'pageissues-specialpageissues-hideold' : 'pageissues-specialpageissues-showold' ),
						[],
						$oldreports ? [] : [ 'oldreports' => 1 ]
					)
				),
				'items' => $items
			] )
		);
	}

	protected function processPageIssue( PageIssue $pageissue ) : array {
		$lang = $this->getContext()->getLanguage();
		$linkrenderer = $this->getLinkRenderer();

		$notes = [];
		foreach ( $pageissue->getNotes() as $note ) {
			$notes[] = $this->msg( 'pageissues-specialpageissues-issuenote' )->params(
				htmlspecialchars( $note['note'] ),
				Message::rawParam(
					$linkrenderer->makeLink(
						$note['user']->getUserPage(),
						$note['user']->getName()
					)
				),
				Message::rawParam(
					$linkrenderer->makeLink(
						$note['user']->getTalkPage(),
						$this->msg( 'talkpagelinktext' )->plain()
					)
				),
				$lang->date( $note['timestamp'] ),
				$note['revisionage']
			)->parse();
		}

		$minorreports = $pageissue->getMinorReportsData();
		$mrout = [];

		if ( $minorreports['current']['count'] > 0 ) {
			$mrout['current'] = $this->msg( 'pageissues-specialpageissues-issueminor' )->params(
				Message::rawParam(
					$linkrenderer->makeLink(
						$minorreports['current']['lastuser']->getUserPage(),
						$minorreports['current']['lastuser']->getName()
					)
				),
				$minorreports['current']['count'] > 1 ? $minorreports['current']['count'] - 1 : '',
				$lang->date( $minorreports['current']['timestamp_earliest'] ),
				$lang->date( $minorreports['current']['timestamp_latest'] ),
				$notes === [] ? '' : 'only'
			);
		}

		if ( $minorreports['old']['count'] > 0 ) {
			$mrout['old'] = $this->msg( 'pageissues-specialpageissues-issueminor' )->params(
				Message::rawParam(
					$linkrenderer->makeLink(
						$minorreports['old']['lastuser']->getUserPage(),
						$minorreports['old']['lastuser']->getName()
					)
				),
				$minorreports['old']['count'] > 1 ? $minorreports['old']['count'] - 1 : '',
				$lang->date( $minorreports['old']['timestamp_earliest'] ),
				$lang->date( $minorreports['old']['timestamp_latest'] ),
				'old',
				$minorreports['old']['age']
			);
		}

		return [
			'q' => $pageissue->geReportCount(),
			'page_url' => $linkrenderer->makeLink( $pageissue->page ),
			'issue' => $pageissue->issue,
			'issue_msg' => $pageissue->getIssueMessage()->plain(),
			'notes' => $notes,
			'minorreports' => $mrout
		];
	}

	function getGroupName() {
		return 'maintenance';
	}
}
