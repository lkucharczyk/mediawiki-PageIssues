<?php

namespace MediaWiki\Extension\PageIssues\Models;

class PageIssuesSet {
	/** @var PageIssues[] */
	public $pageissues = [];

	public function addReport( Report $report ) : bool {
		$out = false;

		$pageid = $report->page->getArticleId();
		if ( !array_key_exists( $pageid, $this->pageissues ) ) {
			$this->pageissues[$pageid] = new PageIssues( $report->page );
		}

		$out |= $this->pageissues[$pageid]->addReport( $report );

		return $out;
	}

	public function getAll( array $conditions = [] ) {
		$reports = Report::getAll( $conditions );

		foreach ( $reports as $report ) {
			$this->addReport( $report );
		}
	}
}
