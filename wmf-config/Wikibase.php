<?php

require_once( "$IP/extensions/Wikidata/Wikidata.php" );

// The version number now comes from the Wikidata build,
// included above, so that cache invalidations can be in sync
// extension changes when there is a new extension branch or
// otherwise needed to change the cache key.
$wgWBSharedCacheKey = '-' . $wmgWikibaseCachePrefix;

if ( defined( 'HHVM_VERSION' ) ) {
	// Split the cache up for hhvm. T73461
	$wgWBSharedCacheKey .= '-hhvm';
}

$wgWBSharedSettings = array();

$wgWBSharedSettings['maxSerializedEntitySize'] = 2500;

$wgWBSharedSettings['siteLinkGroups'] = array(
	'wikipedia',
	'wikibooks',
	'wikinews',
	'wikiquote',
	'wikisource',
	'wikivoyage',
	'special'
);

$wgWBSharedSettings['specialSiteLinkGroups'] = array(
	'commons',
	'mediawiki',
	'meta',
	'species'
);

if ( in_array( $wgDBname, array( 'test2wiki', 'testwiki', 'testwikidatawiki' ) ) ) {
	$wgWBSharedSettings['specialSiteLinkGroups'][] = 'testwikidata';
} else {
	$wgWBSharedSettings['specialSiteLinkGroups'][] = 'wikidata';
}

if ( $wmgUseWikibaseRepo ) {
	$baseNs = 120;

	// Define the namespace indexes
	define( 'WB_NS_PROPERTY', $baseNs );
	define( 'WB_NS_PROPERTY_TALK', $baseNs + 1 );
	define( 'WB_NS_QUERY', $baseNs + 2 );
	define( 'WB_NS_QUERY_TALK', $baseNs + 3 );

	$wgNamespaceAliases['Item'] = NS_MAIN;
	$wgNamespaceAliases['Item_talk'] = NS_TALK;

	// Define the namespaces
	$wgExtraNamespaces[WB_NS_PROPERTY] = 'Property';
	$wgExtraNamespaces[WB_NS_PROPERTY_TALK] = 'Property_talk';
	$wgExtraNamespaces[WB_NS_QUERY] = 'Query';
	$wgExtraNamespaces[WB_NS_QUERY_TALK] = 'Query_talk';

	$wgWBRepoSettings = $wgWBSharedSettings + $wgWBRepoSettings;

	// Assigning the correct content models to the namespaces
	$wgWBRepoSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_ITEM] = NS_MAIN;
	$wgWBRepoSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_PROPERTY] = WB_NS_PROPERTY;

	$wgWBRepoSettings['statementSections'] = array(
		'item' => array(
			'statements' => null,
			'identifiers' => array(
				'type' => 'dataType',
				'dataTypes' => array( 'external-id' ),
			),
		),
	);

	$wgWBRepoSettings['normalizeItemByTitlePageNames'] = true;

	$wgWBRepoSettings['dataRightsText'] = 'Creative Commons CC0 License';
	$wgWBRepoSettings['dataRightsUrl'] = 'https://creativecommons.org/publicdomain/zero/1.0/';

	if ( $wgDBname === 'testwikidatawiki' ) {
		// there is no cronjob dispatcher yet, this will do nothing
		$wgWBRepoSettings['clientDbList'] = array( 'testwiki', 'test2wiki', 'testwikidatawiki' );
		$wgPropertySuggesterClassifyingPropertyIds = array( 7 );
	} else {
		$wgWBRepoSettings['clientDbList'] = array_diff(
			MWWikiversions::readDbListFile( 'wikidataclient' ),
			array( 'testwikidatawiki', 'testwiki', 'test2wiki' )
		);
		// Exclude closed wikis
		$wgWBRepoSettings['clientDbList'] = array_diff(
			$wgWBRepoSettings['clientDbList'],
			MWWikiversions::readDbListFile( $wmfRealm === 'labs' ? 'closed-labs' : 'closed' )
		);
	}

	$wgWBRepoSettings['localClientDatabases'] = array_combine(
		$wgWBRepoSettings['clientDbList'],
		$wgWBRepoSettings['clientDbList']
	);

	// T53637 and T48953
	$wgGroupPermissions['*']['property-create'] = ( $wgDBname === 'testwikidatawiki' );

	$wgCacheEpoch = '20160222134413';

	$wgWBRepoSettings['dataSquidMaxage'] = 1 * 60 * 60;
	$wgWBRepoSettings['sharedCacheDuration'] = 60 * 60 * 24;
	$wgWBRepoSettings['sharedCacheKeyPrefix'] .= $wgWBSharedCacheKey;

	$wgPropertySuggesterMinProbability = 0.069;

	// T72346
	// see https://www.wikidata.org/wiki/Special:WhatLinksHere/Q18644427
	$wgPropertySuggesterDeprecatedIds = array(
		143, // imported from
		357, // (OBSOLETE) title (use P1476)
		387, // (OBSOLETE) quote (use P1683)
		438, // (OBSOLETE) inscription (use P1684)
		513, // (OBSOLETE) birth name (use P1477)
		738, // (OBSOLETE) influence of
		1805, // (OBSOLETE) World Health Organisation International Nonproprietary Name (use P2275)
	);

	// Don't try to let users answer captchas if they try to add links
	// on either Item or Property pages. T86453
	$wgCaptchaTriggersOnNamespace[NS_MAIN]['addurl'] = false;
	$wgCaptchaTriggersOnNamespace[WB_NS_PROPERTY]['addurl'] = false;
}

if ( $wmgUseWikibaseClient ) {
	$wgWBClientSettings = $wgWBSharedSettings + $wgWBClientSettings;

	// to be safe, keeping this here although $wgDBname is default setting
	$wgWBClientSettings['siteGlobalID'] = $wgDBname;

	// Note: Wikibase-production.php overrides this for the test wikis
	$wgWBClientSettings['changesDatabase'] = 'wikidatawiki';
	$wgWBClientSettings['repoDatabase'] = 'wikidatawiki';
	$wgWBClientSettings['repoUrl'] = "//{$wmfHostnames['wikidata']}";

	$wgWBClientSettings['repoNamespaces'] = array(
		'wikibase-item' => '',
		'wikibase-property' => 'Property'
	);

	$wgWBClientSettings['languageLinkSiteGroup'] = $wmgWikibaseSiteGroup;

	if ( in_array( $wgDBname, array( 'commonswiki', 'mediawikiwiki', 'metawiki', 'specieswiki' ) ) ) {
		$wgWBClientSettings['languageLinkSiteGroup'] = 'wikipedia';
	}

	$wgWBClientSettings['siteGroup'] = $wmgWikibaseSiteGroup;
	$wgWBClientSettings['otherProjectsLinksByDefault'] = true;

	$wgWBClientSettings['excludeNamespaces'] = function() {
		// @fixme 102 is LiquidThread comments on wikinews and elsewhere?
		// but is the Extension: namespace on mediawiki.org, so we need
		// to allow wiki-specific settings here.
		return array_merge(
			MWNamespace::getTalkNamespaces(),
			// 90 => LiquidThread threads
			// 92 => LiquidThread summary
			// 118 => Draft
			// 1198 => NS_TRANSLATE
			// 2600 => Flow topic
			array( NS_USER, NS_FILE, NS_MEDIAWIKI, 90, 92, 118, 1198, 2600 )
		);
	};

	if ( $wgDBname === 'wikidatawiki' || $wgDBname === 'testwikidatawiki' ) {
		$wgWBClientSettings['namespaces'] = array(
			NS_CATEGORY,
			NS_PROJECT,
			NS_TEMPLATE,
			NS_HELP,
			828 // NS_MODULE
		);

		$wgWBClientSettings['languageLinkSiteGroup'] = 'wikipedia';
		$wgWBClientSettings['injectRecentChanges'] = false;
		$wgWBClientSettings['showExternalRecentChanges'] = false;
	}

	foreach( $wmgWikibaseClientSettings as $setting => $value ) {
		$wgWBClientSettings[$setting] = $value;
	}

	$wgWBClientSettings['allowDataTransclusion'] = $wmgWikibaseEnableData;
	$wgWBClientSettings['allowArbitraryDataAccess'] = $wmgWikibaseEnableArbitraryAccess;
	$wgWBClientSettings['entityAccessLimit'] = $wmgWikibaseEntityAccessLimit;

	$wgWBClientSettings['sharedCacheKeyPrefix'] .= $wgWBSharedCacheKey;
	$wgWBClientSettings['sharedCacheDuration'] = 60 * 60 * 24;
}

require_once "{$wmfConfigDir}/Wikibase-{$wmfRealm}.php";
