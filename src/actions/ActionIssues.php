<?php

namespace MediaWiki\Extension\PageIssues\Actions;

use FormAction;
use HTMLForm;
use MediaWiki\Extension\PageIssues\Models\DatabaseModel;
use MediaWiki\Extension\PageIssues\Models\Issues;
use MediaWiki\Extension\PageIssues\Models\PageIssues;

class ActionIssues extends FormAction {
	/** @var PageIssues */
	protected $pageissues;
	/** @var string[] */
	protected $issues = [];

	public function getName() {
		return 'issues';
	}

	public function show() {
		if ( !DatabaseModel::isValidTitle( $this->getTitle() ) ) {
			$this->setHeaders();
			$this->getOutput()->addWikiMsg( 'pageissues-invalidtitle' );
			return;
		}

		$out = $this->getOutput();

		$this->pageissues = new PageIssues( $this->getTitle() );
		$this->pageissues->getAll();
		if ( count( $this->pageissues->issues ) === 0 ) {
			$this->setHeaders();
			$out->addWikiMsg( 'pageissues-actionissues-noissues' );
			$out->addReturnTo( $this->getTitle() );
			return;
		}

		$out->addWikiMsg( 'pageissues-actionissues-header' );

		foreach ( $this->pageissues->issues as $pageissue ) {
			$this->issues[] = $pageissue->issue;
		}

		$this->issues = Issues::groupIssues( $this->issues );

		if ( $this->getUser()->isAllowed( 'pageissues-resolve' ) ) {
			$out->addWikiMsg( 'pageissues-actionissues-resolve' );
			parent::show();
		} else {
			$this->setHeaders();

			foreach ( $this->issues as $group => $issues ) {
				$out->addHTML( '<h3>' . $this->msg( "pageissues-issuegroup-$group" ) . '</h3>' );

				$msgs = [];
				foreach ( $issues as $issue ) {
					$msgs[] = $this->msg( "pageissues-issue-$issue" );
				}

				$out->addHTML( '<ul><li>' . implode( '</li><li>', $msgs ) . '</li></ul>' );
			}
		}
	}

	protected function getForm() : ?HTMLForm {
		$form = parent::getForm();

		if ( $form instanceof HTMLForm ) {
			$form->setSubmitText( wfMessage( 'pageissues-actionissues-submit' )->plain() );
		}

		return $form;
	}

	protected function getFormFields() : array {
		$options = [];
		foreach ( $this->issues as $group => $issues ) {
			$group = "pageissues-issuegroup-$group";
			$options[$group] = [];

			foreach ( $issues as $issue ) {
				$options[$group]["pageissues-issue-$issue"] = $issue;
			}
		}

		return [
			'issues' => [
				'type' => 'multiselect',
				'options-messages' => $options,
				'required' => true
			]
		];
	}

	protected function usesOOUI() {
		return true;
	}

	public function onSubmit( $data ) {
		if ( count( $data['issues'] ) === 0 ) {
			return false;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic( __METHOD__ );

		foreach ( $this->pageissues->reports as $report ) {
			$newissues = [];
			$modify = false;
			foreach ( $report->issues as $issue ) {
				if ( in_array( $issue, $data['issues'] ) ) {
					$modify = true;
				} else {
					$newissues[] = $issue;
				}
			}

			if ( $modify ) {
				if ( count( $newissues ) === 0 ) {
					$report->delete( $dbw );
				} else {
					$report->issues = $newissues;
					$report->update( $dbw );
				}
			}
		}

		$dbw->endAtomic( __METHOD__ );

		return true;
	}

	public function onSuccess() {
		$out = $this->getOutput();
		$out->clearHTML();
		$out->addWikiMsg( 'pageissues-actionissues-success' );
		$out->addReturnTo( $this->getTitle() );
	}
}
