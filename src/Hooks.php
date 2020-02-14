<?php

namespace MediaWiki\Extension\PageIssues;

use DatabaseUpdater;
use MediaWiki\Extension\PageIssues\Models\DatabaseModel;
use MediaWiki\Extension\PageIssues\Models\Report;
use MediaWiki\Extension\PageIssues\Models\Upvote;
use MediaWiki\Extension\PageIssues\Models\Issues;
use OutputPage;
use Skin;
use SkinTemplate;
use User;
use WikiPage;

class Hooks {
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$title = $out->getTitle();
		$user = $out->getUser();

		if ( $user->isAllowedAny( 'pageissues-upvote', 'pageissues-report' ) && DatabaseModel::isValidTitle( $title ) ) {
			$out->addModules( 'ext.pageissues' );

			$canUpvote = $user->isAllowed( 'pageissues-upvote' );
			$out->addJsConfigVars( [
				'ext.pageissues.issues' => Issues::ISSUES,
				'ext.pageissues.upvoted' => $canUpvote && Upvote::getByUserAndPage( $out->getUser(), $out->getTitle() ) !== null,
				'ext.pageissues.canUpvote' => $canUpvote,
				'ext.pageissues.canReport' => $user->isAllowed( 'pageissues-report' )
			] );
		}
	}

	public static function onArticleDelete( WikiPage $page, User $user, $reason, &$error ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic( __METHOD__ );
		$dbw->delete( Report::TABLE, [ Report::COLPREFIX . 'page' => $page->getId() ] );
		$dbw->delete( Upvote::TABLE, [ Upvote::COLPREFIX . 'page' => $page->getId() ] );
		$dbw->endAtomic( __METHOD__ );
	}

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'pageissues_report', __DIR__ . '/../sql/pageissues_report.sql' );
		$updater->addExtensionTable( 'pageissues_upvote', __DIR__ . '/../sql/pageissues_upvote.sql' );
	}

	public static function onSkinTemplateNavigationUniversal( SkinTemplate $template, array &$links ) {
		$title = $template->getTitle();
		if ( DatabaseModel::isValidTitle( $title ) ) {
			$links['actions']['report'] = [
				'id' => 'ca-report',
				'text' => 'Report issues',
				'href' => $title->getLocalUrl( [ 'action' => 'report' ] )
			];
			$links['actions']['issues'] = [
				'id' => 'ca-issues',
				'text' => 'Page issues',
				'href' => $title->getLocalUrl( [ 'action' => 'issues' ] )
			];
		}
	}
}
