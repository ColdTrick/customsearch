<?php
	global $CONFIG;
	
	set_context('search');
	
	$searchstring		= get_input('tag');
	$tag				= $searchstring;
	$search_min_size	= 2;
	
	if(!empty($tag) and strlen($tag) >= $search_min_size){
		$allowedTypes	= array();
		$added			= array();
		
		$allowedT = get_all_subtypes();
		foreach ($allowedT as $key => $subT) {
			foreach($subT as $k => $v) {
				$allowedTypes[] = $v;
			}
		}
		
		// Search in Metadata
		$rows = get_data("SELECT a1.*,a2.* 
							FROM {$CONFIG->dbprefix}metastrings as a1, {$CONFIG->dbprefix}metadata as a2  
							WHERE a1.string LIKE '%" . sanitise_string($searchstring) . "%' AND a2.value_id=a1.id
							GROUP BY a2.entity_guid
							ORDER BY a2.time_created DESC");
		
		if(!empty($rows)){
			foreach($rows as $row){
				$entity_id = $row->entity_guid;
				$entities  = get_entity($entity_id);
				
				if((in_array(get_subtype_from_id($entities->subtype), $allowedTypes)) or (empty($entities->subtype))) {
					if($entities->type != 'site') {
						if(!array_key_exists($entities->guid, $added)){
							$added[$entities->guid] = $entities;
						}
					}
				}
			}
		}
		
		// Search in Annotations
		$rowsA = get_data("SELECT a1.*,a2.* 
							FROM {$CONFIG->dbprefix}metastrings as a1, {$CONFIG->dbprefix}annotations as a2  
							WHERE a1.string LIKE '%" . sanitise_string($searchstring) . "%' AND a2.value_id=a1.id 
							GROUP BY a2.entity_guid
							ORDER BY a2.time_created DESC");
		
		if(!empty($rowsA)) {
			foreach($rowsA as $rowA){
				$entity_idA = $rowA->entity_guid;
				$entitiesA  = get_entity($entity_idA);
				
				if((in_array(get_subtype_from_id($entitiesA->subtype), $allowedTypes)) or (empty($entitiesA->subtype))) {
					if($entitiesA->type != 'site') {
						if(!array_key_exists($entities->guid, $added)){
							$added[$entitiesA->guid] = $entitiesA;
						}
					}
				}
			}
		}
		
		// Search in Objects
		$rows2 = get_data("SELECT * 
							FROM {$CONFIG->dbprefix}objects_entity 
							WHERE  title LIKE '%" . sanitise_string($searchstring) . "%' OR description LIKE '%" . sanitise_string($searchstring) . "%'");
		
		if(!empty($rows2)) {
			foreach($rows2 as $row2){
				$entity_id2 = $row2->guid;
				$entities2  = get_entity($entity_id2);
				
				if((in_array(get_subtype_from_id($entities2->subtype), $allowedTypes)) or (empty($entities2->subtype))) {  
					if($entities2->type != 'site') {
						if(!array_key_exists($entities2->guid, $added)) {
							$added[$entities2->guid] = $entities2;
						}
					}   
				}
			}
		}
		
		// Search in Groups
		$rows3 = get_data("SELECT * 
							FROM {$CONFIG->dbprefix}groups_entity 
							WHERE  name LIKE '%" . sanitise_string($searchstring) . "%' OR description LIKE '%" . sanitise_string($searchstring) . "%'");
		
		if(!empty($rows3)) {
			foreach($rows3 as $row3) {
				$entity_id3 = $row3->guid;
				$entities3  = get_entity($entity_id3);
				
				if((in_array(get_subtype_from_id($entities3->subtype), $allowedTypes)) or (empty($entities3->subtype))){ 
					if($entities3->type != 'site'){
						if(!array_key_exists($entities3->guid, $added)){
							$added[$entities3->guid] = $entities3;
						}
					}   
				}
			}
		}
		
		// Search for Users
		$user_count = search_for_user($tag, 0, 0, "", true);
		if(!empty($user_count)){
			$users = search_for_user($tag, $user_count);
			
			foreach($users as $user){
				if(!array_key_exists($user->guid, $added)){
					$added[$user->guid] = $user;
				}
			}
		}
		
		// Filter search results
		$entitiesList = array();
		if(!empty($added)){
			$objectType = get_input("object");
			$subtype = get_input("subtype");
			
			foreach($added as $guid => $entity){
				if(empty($objectType) || (!empty($objectType) && $entity->type == $objectType)){
					if(empty($subtype) || (!empty($subtype) && $entity->getSubtype() == $subtype)){
						$entitiesList[] = $entity;
					}
				}
			}
		}
		
		// Make search result view
		$total = count($entitiesList);
		$offset = get_input("offset", 0);
		$limit = 10;
		$fullview = false;
		$viewToggle = false;
		$multiPage = true;
		
		$limitedEntities = array_slice($entitiesList, $offset, $limit);
		
		$body = elgg_view_entity_list($limitedEntities, $total, $offset, $limit, $fullview, $viewToggle, $multiPage);
		
		// Show search result counter
		$results_found_message = '';
		if ($total) {
			$results_found_message = sprintf(elgg_echo('customsearch:search:found'), $total);
		}
		
		$body .= $results_found_message;
	} else {
		$body = sprintf(elgg_echo(empty($tag) ? 'customsearch:search:no_result' : 'customsearch:search:too_short'), $search_min_size);
	}
	
	$title = elgg_view_title(sprintf(elgg_echo("customsearch:search:title"), $tag));
	
	$page_data = $title . $body;
	
	page_draw(elgg_echo('search'), elgg_view_layout('two_column_left_sidebar', '', $page_data));
?>