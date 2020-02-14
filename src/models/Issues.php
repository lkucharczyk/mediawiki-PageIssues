<?php

namespace MediaWiki\Extension\PageIssues\Models;

class Issues {
	public const ISSUES = [
		'investigationandreferences' => [
			'hasconflictinginformation',
			'hasincompletecitations',
			'hasoutdatedinformation',
			'needcitations',
			'needsfactchecking',
			'needssources',
			'needssourcedreferences',
		],
		'qualityandcleanup' => [
			'hasbadlinks',
			'hasbadtemplates',
			'hasbadimages',
			'hasoutdatedtemplatelabels',
			'needscleanup',
			'shouldberewritten'
		],
		'addmorecontent' => [
			'hasincompletedata',
			'hasincompletelists',
			'needsintroduction',
			'needsinfobox',
			'needsmedia',
			'needsinformation'
		],
		'organizationandguidelines' => [
			'containsplagiarism',
			'containsvandalism',
			'containesunreleased',
			'isnotnotable',
			'isnotwithinguidelines',
			'istoolong',
			'istooshort',
			'needsrewritingfromsource',
			'needstranslation',
			'shouldbedeleted',
			'shouldbemerged',
			'shouldberetitled',
			'shouldbesplit'
		]
	];

	/**
	 * @return string[]
	 */
	public static function getAll() : array {
		$out = [];

		foreach ( static::ISSUES as $group => $issues ) {
			foreach ( $issues as $issue ) {
				$out[] = $issue;
			}
		}

		return $out;
	}

	public static function groupIssues( array $issues ) : array {
		$out = [];

		foreach ( static::ISSUES as $group => $gissues ) {
			foreach ( $gissues as $issue ) {
				if ( in_array( $issue, $issues ) ) {
					if ( !array_key_exists( $group, $out ) ) {
						$out[$group] = [];
					}

					$out[$group][] = $issue;
				}
			}
		}

		return $out;
	}
}
