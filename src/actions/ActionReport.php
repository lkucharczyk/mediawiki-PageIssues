<?php

namespace MediaWiki\Extension\PageIssues\Actions;

use FormAction;
use HTMLForm;
use MediaWiki\Extension\PageIssues\Models\DatabaseModel;
use MediaWiki\Extension\PageIssues\Models\Report;
use MediaWiki\Extension\PageIssues\Models\Issues;

class ActionReport extends FormAction {
	public function getName() {
		return 'report';
	}

	public function getRestriction() {
		return 'pageissues-report';
	}

	public function show() {
		if ( DatabaseModel::isValidTitle( $this->getTitle() ) ) {
			parent::show();
			$this->getOutput()->addModules( 'ext.pageissues.actionreport' );
		} else {
			$this->setHeaders();
			$out = $this->getOutput();
			$out->addWikiMsg( 'pageissues-invalidtitle' );
			$out->addReturnTo( $this->getTitle() );
		}
	}

	protected function getForm() : ?HTMLForm {
		$form = parent::getForm();

		if ( $form instanceof HTMLForm ) {
			$form->setId( 'pageissues-report' );
		}

		return $form;
	}

	protected function getFormFields() : array {
		$options = [];
		foreach ( Issues::ISSUES as $group => $issues ) {
			$groupissues = [];

			foreach ( $issues as $issue ) {
				$groupissues["pageissues-issue-$issue"] = $issue;
			}

			$options["pageissues-issuegroup-$group"] = $groupissues;
		}

		return [
			'issues' => [
				'type' => 'multiselect',
				'options-messages' => $options,
				'required' => true
			],
			'note' => [
				'label' => 'Note:',
				'type' => 'text'
			]
		];
	}

	protected function usesOOUI() : bool {
		return true;
	}

	public function onSubmit( $data ) : bool {
		$out = Report::createNew( [
			'actor' => $this->getUser(),
			'page' => $this->getTitle(),
			'issues' => $data['issues'],
			'note' => $data['note']
		] );

		return (bool)$out;
	}

	public function onSuccess() {
		$out = $this->getOutput();
		$out->addWikiMsg( 'pageissues-actionreport-success' );
		$out->addReturnTo( $this->getTitle() );
	}
}
