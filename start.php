<?php

	function customsearch_init(){
		// Register search hook
		register_plugin_hook('search', 'all', 'customsearch_search_hook');
	} 

	function customsearch_search_hook(){
		if (!@include_once(dirname(__FILE__) . "/index.php")){
			return false;
		}
		return true;
	}

	function get_all_subtypes(){
		global $CONFIG;
		
		$rows = get_data("SELECT subtype as regsubtypes FROM {$CONFIG->dbprefix}entity_subtypes");
		
		return $rows;
	}

	register_elgg_event_handler('init', 'system', 'customsearch_init');

?>