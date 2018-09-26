<?php
class ReportModel extends CI_Model {

	function __construct() {
        // Call the Model constructor
        parent::__construct();
        
        //$this->load->model('PersonFamilyModel');
    }
	
	function buildReport($reportId, $inputParams, $origin = 'checkin', $page = null) {
		global $REPORT_CONFIG;
		
		global $PER_PAGE;
		
		$count_query = $query = $sum_query = '';
		
		$columns = $REPORT_CONFIG[$reportId]['output'];
		
		$q = $this->input->post('q');
		
		$p = 1;
		if ($page) {
			$p = $page; // Page	
		} else {
			$p = $this->input->post('page'); 
			if (!$p) {
				$p = $this->input->get('page');
			}
		}
		$start = 0;
		$sort = $order = '';
		
		if ($q == '0') {
			$q = '';
		}
		$arr_query = explode(' ', $q);
		
		//print_r_pre($inputParams);
		//print_r_pre($REPORT_CONFIG);
		
		$family_status = $this->input->post('family_status');
		if (is_array($family_status)) {
			if ($family_status) {
				$family_status = '"' . implode('", "', $family_status) . '"';
			}
		} else {
			if ($family_status) {
				$family_status = '"' . $family_status . '"';
			}
		}
		
		
		$idFamily = '';
		if (!empty($inputParams['family_id']) && is_numeric($inputParams['family_id']) ) {
			$idFamily = $inputParams['family_id'];
		}
		/*
		$trace = debug_backtrace();
	    if (isset($trace[1])) {
			echo "\n buildReport() Called by {$trace[1]['class']} :: {$trace[1]['function']} \n";
		}
		*/
		
		$CI =& get_instance();
		
		$idAgency = $this->session->userdata('idAgency');
		
		//echo $origin . "<br />";die;
		
		/*
		if ( $origin == 'family_audit_trail' ) {
			
			$count_query = 'SELECT count(*) as num_records ';
			$query = 'SELECT family.* ';
			$query .= ', family_audit_trail.* ';
			
			$query .= ', ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' as familyTypeAuditTrail ';
			
			$from = 
				' FROM family, family_audit_trail ';
			
			$where = 
				' WHERE family.id_agency ='. $idAgency; 
				
				
		
			///
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
					}
				} else {
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
				}
			}
			////
			
			if ($family_status) {
				//$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
				//$where .= ' AND ( familyTypeAuditTrail IN (' . $family_status . ') ) ' ;
				$where .= ' AND family.id_family = family_audit_trail.id_family ' ;
				//$where .= ' AND ( ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' IN (' . $family_status . ') ) ' ;
				
				$where .= 
 						' AND family_audit_trail.id_family_audit_trail = (' .
						' 	SELECT id_family_audit_trail' .
						' 	FROM family_audit_trail' .
						' 	WHERE family_audit_trail.id_family = family.id_family';
				
				$date_condition = $this->buildStartEndDateConditionForAuditTrail($inputParams, 'family_audit_trail');
				$where .= ' AND ' . $date_condition;
				
				$where .= 
						' 	AND family_audit_trail.familyType IN ("indicated")' .
						' 	ORDER BY id_family_audit_trail DESC LIMIT 0, 1' .
						' )';
			}
			
			if ($idFamily) {
				$where .= ' AND ( family.id_family = ' . $idFamily . ' ) ' ;
			}
			
			//$where .=  ' AND ' . $this->buildWhereFamilyTypeAuditTrailQuery($inputParams);
			
			$where .= $this->buildConditionsForFamilyMembers($origin, $reportId, $inputParams);
			
			
		} else */
		if ( $origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
			$join = array();
			$join = $this->buildLeftJoinsForPersonauditTrail();
			
			
			$count_query = 'SELECT count(*) as num_records ';
			$query = 'SELECT person.* ';
			$query .= ', person_audit_trail.id_person_audit_trail'
					. ', person_audit_trail.pregnant 						AS pregnant_audit_trail'
					. ', person_audit_trail.id_maritalStatus 				AS id_maritalStatus_audit_trail'
					. ', person_audit_trail.id_education 					AS id_education_audit_trail'
					. ', person_audit_trail.id_employmentStatus 			AS id_employmentStatus_audit_trail'
					. ', person_audit_trail.id_familyStructure 				AS id_familyStructure_audit_trail'
					. ', person_audit_trail.homeless 						AS homeless_audit_trail'
					. ', person_audit_trail.IEP 							AS IEP_audit_trail'
					. ', person_audit_trail.migrant 						AS migrant_audit_trail'
					. ', person_audit_trail.FRL 							AS FRL_audit_trail'
					. ', person_audit_trail.livingInFosterHome 				AS livingInFosterHome_audit_trail'
					. ', person_audit_trail.familyReceivesChildSupport 		AS familyReceivesChildSupport_audit_trail'
					. ', person_audit_trail.familyReceivesTANF 				AS familyReceivesTANF_audit_trail'
					. ', person_audit_trail.familyReceivesFoodStamps 		AS familyReceivesFoodStamps_audit_trail'
					. ', person_audit_trail.familyReceivesWIC 				AS familyReceivesWIC_audit_trail'
					. ', person_audit_trail.familyReceivesHousingSubsidy 	AS familyReceivesHousingSubsidy_audit_trail'
					;
			
			
			if (count($join) > 0) {
				$query .= $join['select'];
			}
			
			$query .= ', family.id_family, family.family, family.familyType ';
			$query .= ', ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' as familyTypeAuditTrail ';
			
			$from = ' FROM person';
			
			if (count($join) > 0) {
				$from .= $join['join'];
			}
			
			if (isset($inputParams['idIncome'])) {
				if (!empty($inputParams['idIncome'])) {
					$from .= ' , family_address_audit_trail ';
				}
			}
			
			$from .= ' , person_audit_trail ';
			
			
			if (count($join) > 0) {
				$from .= $join['join_audit_trail'];
			}
				
			
			$from .= ' , family_person, family ';
			
			if (isset($inputParams['language'])) {
				if (!empty($inputParams['language'])) {
					$from .= ', person_language ';
				}
			}
			if (isset($inputParams['educationProvider'])) {
				if (!empty($inputParams['educationProvider'])) {
					$from .= ', person_education_provider ';
				}
			}
			
			$where = 
					' WHERE family_person.id_person = person.id_person' .
					' AND family_person.id_family = family.id_family' .
					' AND family.id_agency ='. $idAgency;
					
			$where .= $this->conditionOnPersonRoleForAuditTrail($inputParams, $origin);
			
			//echo $where;die;
			
			$where .= 
					' AND person.id_person =  person_audit_trail.id_person';
					
			if (isset($inputParams['idIncome'])) {
				if (!empty($inputParams['idIncome'])) {
					$where .= ' AND family.id_family =  family_address_audit_trail.id_family ' .
							' AND family_address_audit_trail.id_income = ' . $inputParams['idIncome'];
				}
			}
			
			
			$start_date = $end_date = null;
			
			if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				
				$start_date = $inputParams['start_date'];
				$end_date = $inputParams['end_date'];
			} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
				$start_date = $inputParams['start_date'];
				$end_date = $inputParams['start_date']; // Assigning start_date into end_date as there is no value for end_date passed from input screen
				
			} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$start_date = $inputParams['end_date']; // Assigning end_date into start_date as there is no value for start_date passed from input screen
				$end_date = $inputParams['end_date']; 
			}
			
			if ( $start_date && $end_date ) {
				$start_date = date("Y-m-d", strtotime($start_date));
				$end_date = date("Y-m-d", strtotime($end_date));
			
				$where .= 
						' AND ( person_audit_trail.start_date <= "' . $end_date . ' 23:59:59" )' . 
						' AND ( person_audit_trail.end_date >= "' . $start_date . ' 00:00:00" OR person_audit_trail.end_date IS NULL ) ';
						
						
				if (isset($inputParams['idIncome'])) {
					if (!empty($inputParams['idIncome'])) {
						$where .= 
								' AND ( family_address_audit_trail.start_date <= "' . $end_date . ' 23:59:59" )' . 
								' AND ( family_address_audit_trail.end_date >= "' . $start_date . ' 00:00:00" OR family_address_audit_trail.end_date IS NULL ) ';
					}
				}
				
			}
			
			$where .= $this->buildWhereForDemographicsFromAuditTrail($inputParams, $origin);
			
			/*
			$where .= 
					'	AND person_audit_trail.id_person_audit_trail = ' .
					'	(' .
					'		SELECT id_person_audit_trail ' .
					'		FROM person_audit_trail ' .
					'		WHERE person_audit_trail.id_person = person.id_person ' .
					'		AND ' . $this->buildStartEndDateConditionForAuditTrail($inputParams, 'person_audit_trail');
					
					//'		AND ( ' . 
					//'			person_audit_trail.start_date <= "2013-07-17" ' .
					//'			AND (person_audit_trail.end_date >= "2013-07-31" OR person_audit_trail.end_date IS NULL) ' .
					//'		) ' .
					
					// Demographics comes here...
			$where .= $this->buildWhereForDemographicsFromAuditTrail($inputParams, $origin);
					
			$where .= 		
					' 		ORDER BY id_person_audit_trail ' .
					'		DESC LIMIT 0, 1 ' .
					'	) ';
			*/
			//echo $where;die;
			
			if ($family_status) {
				//$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
				$where .= ' AND ( ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' IN (' . $family_status . ') ) ' ;
			}
				
			
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'person', $origin);
					}
				} else {
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'person', $origin);
				}
			}
			
		} else if ($origin == 'child' || $origin == 'parent' || $origin == 'mailing_list' || $origin == 'warmline' ) {
			
			$join = array();
			if ($origin != 'warmline') {
				$join = $this->buildLeftJoinsForPerson();
			}
			
			$count_query = 'SELECT count(*) as num_records ';
			$query = 'SELECT person.*, family.id_family, family.family, family.familyType ';
			
			$query .= ', ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' as familyTypeAuditTrail ';
			
			if (count($join) > 0) {
				$query .= $join['select'];
			}
			
			//if ($origin == 'mailing_list' || $origin == 'child') {
			if ( $this->isAddressFieldSelected($inputParams)  || $origin == 'mailing_list' ) {
				$query .= ', family_address.*, state.state, county.county, city.city ';
			}
			/*
			$query .= ', person_audit_trail.pregnant 						AS pregnant_audit_trail'
					. ', person_audit_trail.id_maritalStatus 				AS id_maritalStatus_audit_trail'
					. ', person_audit_trail.id_education 					AS id_education_audit_trail'
					. ', person_audit_trail.id_employmentStatus 			AS id_employmentStatus_audit_trail'
					. ', person_audit_trail.id_familyStructure 				AS id_familyStructure_audit_trail'
					. ', person_audit_trail.homeless 						AS homeless_audit_trail'
					. ', person_audit_trail.IEP 							AS IEP_audit_trail'
					. ', person_audit_trail.migrant 						AS migrant_audit_trail'
					. ', person_audit_trail.FRL 							AS FRL_audit_trail'
					. ', person_audit_trail.livingInFosterHome 				AS livingInFosterHome_audit_trail'
					. ', person_audit_trail.familyReceivesChildSupport 		AS familyReceivesChildSupport_audit_trail'
					. ', person_audit_trail.familyReceivesTANF 				AS familyReceivesTANF_audit_trail'
					. ', person_audit_trail.familyReceivesFoodStamps 		AS familyReceivesFoodStamps_audit_trail'
					. ', person_audit_trail.familyReceivesWIC 				AS familyReceivesWIC_audit_trail'
					. ', person_audit_trail.familyReceivesHousingSubsidy 	AS familyReceivesHousingSubsidy_audit_trail'
					;
			*/
			$from = 
				' FROM person ';
			
			if (count($join) > 0) {
				$from .= $join['join'];
			}
			
			$from .= 
				', family_person, family ';
				//', family_person, family, person_audit_trail ';
			
			if (isset($inputParams['language'])) {
				if (!empty($inputParams['language'])) {
					$from .= ', person_language ';
				}
			}
			if (isset($inputParams['educationProvider'])) {
				if (!empty($inputParams['educationProvider'])) {
					$from .= ', person_education_provider ';
				}
			}
			

			//if ($origin == 'mailing_list' || $origin == 'child') {
			if ( $this->isAddressFieldSelected($inputParams) || $origin == 'mailing_list' ) {
				$from .= ', family_address' .
						' LEFT JOIN city ' .
						'	ON family_address.id_city = city.id_city' .
						' LEFT JOIN  state ' .
						'	ON family_address.id_state = state.id_state ' .
						' LEFT JOIN county ' .
						'	ON family_address.id_county = county.id_county' .
						' , family_address_person ';
			}
			
			$where = 
				' WHERE family_person.id_person = person.id_person' .
				' AND family_person.id_family = family.id_family' .
				' AND family.id_agency ='. $idAgency;
			/*
			$where .= 
				'	AND person_audit_trail.id_person_audit_trail = ' .
				'	(' .
				'		SELECT id_person_audit_trail ' .
				'		FROM person_audit_trail ' .
				'		WHERE person_audit_trail.id_person = person.id_person ' .
				'		AND ( ' .
				'			person_audit_trail.start_date <= "2013-07-17" ' .
				'			AND (person_audit_trail.end_date >= "2013-07-31" OR person_audit_trail.end_date IS NULL) ' .
				'		) ORDER BY id_person_audit_trail ' .
				'		DESC LIMIT 0, 1 ' .
				'	) ';
			*/
			$childIds = '';
			
			$CI->load->model('PersonRoleModel', '', TRUE);
			$childIds = $CI->PersonRoleModel->getChildRoleIds();
			
			if (!empty($childIds)) {
				if ($origin == 'child' || $origin == 'warmline') {
					$where .= 
						' AND family_person.id_personRole IN (' . $childIds . ')';
				} else if ($origin == 'parent') {
					if (isset($inputParams['personRole'])) {
						if (!empty($inputParams['personRole'])) {
							$where .= 
								' AND family_person.id_personRole = ' . $inputParams['personRole'];
						} else{
							$where .= 
								' AND family_person.id_personRole NOT IN (' . $childIds . ')';
						}
					} else {
						$where .= 
							' AND family_person.id_personRole NOT IN (' . $childIds . ')';
					}
				} else if ($origin == 'mailing_list') {
					if (isset($inputParams['personRole'])) {
						if (!empty($inputParams['personRole'])) {
							$where .= 
								' AND family_person.id_personRole = ' . $inputParams['personRole'];
						}
					}
				}
			}
			
			//if ($origin == 'mailing_list' || $origin == 'child') {
			if ( $this->isAddressFieldSelected($inputParams)  || $origin == 'mailing_list' ) {
				$where .= 
					' AND family_address_person.id_person = person.id_person ' .
					' AND family_address_person.id_familyAddress = family_address.id_familyAddress ' ;
					//' AND family_address.id_state = state.id_state ' .
					//' AND family_address.id_county = county.id_county ';
				if ($origin == 'child') {
					$where .= ' AND family_address_person.id_person = person.id_person ';
				}
			}
			
			if ($origin == 'warmline') {
				
				if (!empty($inputParams['start_date'])) {
					$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
				}
				
				if (!empty($inputParams['end_date'])) {
					$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
				}
				
				if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
					$where .= ' AND ( person.dateBirth >= "' . $inputParams['start_date'] . '" AND person.dateBirth <= "' . $inputParams['end_date'] . '") ' ;
				} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
					$where .= ' AND ( person.dateBirth >= "' . $inputParams['start_date'] . '") ';
				} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
					$where .= ' AND ( person.dateBirth <= "' . $inputParams['end_date'] . '") ' ;
				}
				
			} else {
				
				foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
					if (is_numeric($key)) {
						// We consider this be the case where we have grouped fields under sub-headers.
						
						foreach($groups['fields'] as $field => $options ) {
							$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
						}
					} else {
						$field = $key;
						$options = $groups;
						$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
					}
				}
				
				
				
				/*
				if ($family_status) {
					//$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
					$where .= ' AND ( ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' IN (' . $family_status . ') ) ' ;
				}
				*/
				
				if ($family_status) {
					$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
				}
				
				
				if ($idFamily) {
					$where .= ' AND ( family.id_family = ' . $idFamily . ' ) ' ;
				}
				
				
				
				foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
					if (is_numeric($key)) {
						// We consider this be the case where we have grouped fields under sub-headers.
						
						foreach($groups['fields'] as $field => $options ) {
							$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'person', $origin);
						}
					} else {
						$field = $key;
						$options = $groups;
						$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'person', $origin);
					}
				}
			}
			//$where .= $this->buildConditionsForFamilyMembers($origin, $reportId, $inputParams);
			
			/**
			 * -------------------------------------------------------------------
			 * Family Address
			 * -------------------------------------------------------------------
			 */
			//if ( $origin == 'mailing_list' || $origin == 'child' ) {
			if ( $this->isAddressFieldSelected($inputParams) ) {
				$where .= $this->addressFieldsCondition($inputParams);
			} /*else  {
				$where .= $this->buildWhereBasedOnAddress($inputParams, 'family.id_family');
			}
			*/
			
			
			
			/*
			if (isset($REPORT_CONFIG[$reportId]['group_by'])) {
				if ($origin == 'child' || $origin == 'parent') {
					//$REPORT_CONFIG[$reportId]['group_by'] = 'person.' . $REPORT_CONFIG[$reportId]['group_by'];
					$REPORT_CONFIG[$reportId]['group_by'] = $REPORT_CONFIG[$reportId]['group_by'];
				}
			}
			*/
		} else if ($origin == 'family') {
			
			$count_query = 'SELECT count(*) as num_records ';
			$query = 'SELECT family.* ';
			
			$query .= ', ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' as familyTypeAuditTrail ';
			
			$from = 
				' FROM family ';
			
			$where = 
				' WHERE family.id_agency ='. $idAgency;
				
				/*
				' AND checkin.id_agencyProgram = agency_program.id_agencyProgram ' .
				' AND checkin.id_agencyLocation = agency_location.id_agencyLocation ';
				*/
		
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
					}
				} else {
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
				}
			}
			
			if ($family_status) {
				$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
				//$where .= ' AND ( familyTypeAuditTrail IN (' . $family_status . ') ) ' ;
				//$where .= ' AND ( ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' IN (' . $family_status . ') ) ' ;
			}
			
			if ($idFamily) {
				$where .= ' AND ( family.id_family = ' . $idFamily . ' ) ' ;
			}
			
			$where .=  ' AND ' . $this->buildWhereFamilyTypeAuditTrailQuery($inputParams);
			
			$where .= $this->buildConditionsForFamilyMembers($origin, $reportId, $inputParams);
			
		} else if ($origin == 'goal') {
			
			$count_query = 'SELECT count(*) as num_records ';
			$query = 'SELECT family_goal.*, goal_type.goalType, family.family ';
			
			$from = 
				' FROM family_goal, family, goal_type';
			
			
			$where = 
				' WHERE family_goal.id_family = family.id_family ' .
				' AND family.id_agency ='. $idAgency .
				' AND family_goal.id_goalType = goal_type.id_goalType';
				/*
				' AND checkin.id_agencyProgram = agency_program.id_agencyProgram ' .
				' AND checkin.id_agencyLocation = agency_location.id_agencyLocation ';
				*/
			/*
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams);
					}
				} else {
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams);
				}
			}
			*/
			/**
			 * It seems, we do not need to check family type for goals
			 */
			/*
			if ($family_status) {
				$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
			}
			*/
			
			if ($idFamily) {
				$where .= ' AND ( family.id_family = ' . $idFamily . ' ) ' ;
			}
			
			//$where .= $this->buildWhereBasedOnGoals($inputParams, 'family.id_family');
			
			if (!empty($inputParams['start_date'])) {
				$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
			}
			
			if (!empty($inputParams['end_date'])) {
				$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
			}
			
			if (isset($inputParams['accomplished'])) {
				if ($inputParams['accomplished'] == 'on') {
					if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.accomplished_date >= "' . $inputParams['start_date'] . '" AND family_goal.accomplished_date <= "' . $inputParams['end_date'] . '") ' ;
					} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.accomplished_date >= "' . $inputParams['start_date'] . '") ';
					} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.accomplished_date <= "' . $inputParams['end_date'] . '") ' ;
					}
				}
			}
			
			if (isset($inputParams['target'])) {
				if ($inputParams['target'] == 'on') {
					if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.target_date >= "' . $inputParams['start_date'] . '" AND family_goal.target_date <= "' . $inputParams['end_date'] . '") ' ;
					} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.target_date <= "' . $inputParams['start_date'] . '") ';
					} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.target_date >= "' . $inputParams['end_date'] . '") ' ;
					}
				}
			}
			
			if (isset($inputParams['goalTypeId'])) {
				if (!empty($inputParams['goalTypeId'])) {
					if (!is_array($inputParams['goalTypeId'])) {
						$inputParams['goalTypeId'] = explode(",", $inputParams['goalTypeId']);
					}
					$goalTypeId = implode(",", $inputParams['goalTypeId']);
					
					if (!empty($goalTypeId)) {
						$where .= ' AND ( family_goal.id_goalType  IN (' . $goalTypeId . ') ) ';
					}
				}
			}
			
			/**
			 * -------------------------------------------------------------------
			 * Any Condition based on Family members should be placed BELOW this. 
			 * -------------------------------------------------------------------
			 */
			/*
			$where .= 
					" AND (SELECT family_goal_members.id " .
					" 		FROM family_goal_members, person " .
					" 		WHERE family_goal_members.id_familyGoal = family_goal.id_familyGoal " .
					"		AND family_goal_members.id_person = person.id_person";
			
			
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'family_goal_members');
					}
				} else {
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'family_goal_members');
				}
			}
			
			$where .=
					"		LIMIT 0, 1 " .
					"	)";
			*/
			/**
			 * -------------------------------------------------------------------
			 * Any Condition based on Family members should be placed ABOVE this. 
			 * -------------------------------------------------------------------
			 */
			
		} else if ($origin == 'screening') {
			$count_query = 'SELECT count(*) as num_records ';
			
			$query = 'SELECT family_screening.*, professional.professionalName, child_screening.notes, child_screening.id_screeningTools, person.firstName, person.lastName '
			 		//. ' , marital_status.maritalStatus, employment_status.employmentStatus '
			 		;
			
			$from = 
				' FROM family_screening' .
				' LEFT JOIN professional ' .
				'	ON professional.id_professional = family_screening.id_professional,' .
				' family, child_screening, person' /*.
				' LEFT JOIN marital_status ' .
				'	ON person.id_maritalStatus = marital_status.id_maritalStatus ' .
				' LEFT JOIN employment_status ' .
				'	ON person.id_employmentStatus = employment_status.id_employmentStatus '
				*/
				;
			
			$where = 
				' WHERE ' .
				' family_screening.id_family = family.id_family ' .
				' AND family.id_agency ='. $idAgency .
				' AND family_screening.type = "child" ' .
				' AND family_screening.id_familyScreening = child_screening.id_familyScreening ' .
				' AND child_screening.id_person = person.id_person ';
			
			if (isset($inputParams['screeningType'])) {
				if (!empty($inputParams['screeningType'])) {
					$where .= ' AND child_screening.screening_type = "'.$inputParams['screeningType'].'"';
				}
			}
			
			if (isset($inputParams['id_screeningTools'])) {
				if (!empty($inputParams['id_screeningTools'])) {
					$screening_tools = implode(',', $inputParams['id_screeningTools']);
					$where .= ' AND child_screening.id_screeningTools IN ('.$screening_tools.')';
				}
			}
			
			if (isset($inputParams['professionalId'])) {
				if (!empty($inputParams['professionalId'])) {
					if (!is_array($inputParams['professionalId'])) {
						$inputParams['professionalId'] = explode(",", $inputParams['professionalId']);
					}
					$professionalId = implode(",", $inputParams['professionalId']);
					
					if (!empty($professionalId)) {
						$where .= ' AND family_screening.id_professional IN (' . $professionalId . ') ';
					}
				}
			}
			
		} else if ($origin == 'unique_checkin') {
			
			$count_query = 'SELECT count(*) as num_records ';
			$query = 'SELECT  checkin.*, agency_program.name as program_name, agency_location.location, agency_topic.agencyTopic as topic ';		
			$sum_query = 'SELECT SUM(checkin_duration) AS toal_checkin_duration ';
			
			//$from = 
			//		' FROM checkin, agency_program, agency_location, agency_program_topic, agency_topic';
			
			$from = 
					' FROM checkin';
			$from .=
					' LEFT JOIN agency_program_topic' .
					' ON checkin.id_agencyTopic = agency_program_topic.id_agencyProgramTopic ' . 
					' LEFT JOIN agency_topic' .
					' ON agency_program_topic.id_agencyTopic = agency_topic.id_agencyTopic ';
			$from .= 
					', agency_program, agency_location';
			
			if (isset($inputParams['professionalId'])) {
				if (!empty($inputParams['professionalId'])) {
					$from .= ', checkin_staff'; 
				}
			}
			
			$where = 
					' WHERE checkin.id_agency = ' . $idAgency . 
					' AND checkin.id_agencyProgram = agency_program.id_agencyProgram ' .
					' AND checkin.id_agencyLocation = agency_location.id_agencyLocation ';
					//' AND checkin.id_agencyTopic = agency_program_topic.id_agencyProgramTopic ' .
					//' AND agency_program_topic.id_agencyTopic = agency_topic.id_agencyTopic ';
					
			
			if (!empty($inputParams['start_date'])) {
				$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
			}
			
			if (!empty($inputParams['end_date'])) {
				$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
			}
			
			
			if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$where .= ' AND ( checkin.checkin_date >= "' . $inputParams['start_date'] . '" AND checkin.checkin_date <= "' . $inputParams['end_date'] . '") ' ;
			} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
				$where .= ' AND ( checkin.checkin_date >= "' . $inputParams['start_date'] . '") ';
			} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$where .= ' AND ( checkin.checkin_date <= "' . $inputParams['end_date'] . '") ' ;
			}
			
			if (isset($inputParams['professionalId'])) {
				if (!empty($inputParams['professionalId'])) {
					$where .= ' AND checkin_staff.id_checkin = checkin.id_checkin' .
							' AND checkin_staff.id_staff = ' . $inputParams['professionalId']; 
				}
			}
			
			$where .= $this->buildWhereForAgencyTopic($inputParams);
			
			
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						
						if( $field == 'topic' ){
							
						}else{
						
							$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
						}
					}
					
					
				} else {
					
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
				}
			}
		
		} else if ($origin == 'checkin' || $origin == 'checkin_child' || $origin == 'checkin_parent' || $origin == 'checkin_mailing_list') {
			
			if ($reportId != '1') {
				if ($origin == 'checkin') {
					$origin = 'checkin_family_members';
				}
			}
			
			
			if ($origin == 'checkin') {
				
				$count_query = 'SELECT count(*) as num_records ';
				$query = 'SELECT checkin_family.*, checkin.*, family.family, agency_program.name as program_name, agency_location.location, agency_topic.agencyTopic as topic ';
				
				$query .= ', ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' as familyTypeAuditTrail ';
					
				$sum_query = 'SELECT SUM(checkin_duration) AS toal_checkin_duration ';
				
				$from = 
				//		' FROM checkin_family, family, checkin, agency_program, agency_location, agency_program_topic, agency_topic';
						' FROM checkin_family, family, checkin ';
				
				$from .=
					' LEFT JOIN agency_program_topic' .
					' ON checkin.id_agencyTopic = agency_program_topic.id_agencyProgramTopic ' . 
					' LEFT JOIN agency_topic' .
					' ON agency_program_topic.id_agencyTopic = agency_topic.id_agencyTopic ';
			
				$from .=
						' , agency_program, agency_location ';
				
			
				
				if (isset($inputParams['professionalId'])) {
					if (!empty($inputParams['professionalId'])) {
						$from .= ', checkin_staff'; 
					}
				}
				
				$where = 
						' WHERE checkin.id_agency ='. $idAgency . 
						' AND checkin_family.id_checkin = checkin.id_checkin ' .
						' AND checkin_family.id_family = family.id_family ' .
						' AND checkin.id_agencyProgram = agency_program.id_agencyProgram ' .
						' AND checkin.id_agencyLocation = agency_location.id_agencyLocation ';
						//' AND checkin.id_agencyTopic = agency_program_topic.id_agencyProgramTopic ' .
						//' AND agency_program_topic.id_agencyTopic = agency_topic.id_agencyTopic ';
				
				if ($family_status) {
					$where .= ' AND ( family.familyType IN (' . $family_status . ') ) ' ;
					//$where .= ' AND ( ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' IN (' . $family_status . ') ) ' ;
				}
				
				//---------- Not sure why I had this commented
				/*
				if (isset($inputParams['attended'])) {
					if ($inputParams['attended'] == 'yes') {
						$where .= ' AND checkin_family.attended = \'1\'';
					} elseif ($inputParams['attended'] == 'no'){
						$where .= ' AND checkin_family.attended = \'0\'';
					} 
				}
				*/
				
				if (isset($inputParams['attended'])) {
					
					$where .= 
							' AND (' .
							'		SELECT id ' .
							'		FROM checkin_family_members ' .
							'		WHERE checkin_family_members.id_checkin = checkin.id_checkin ';
					
					if ($inputParams['attended'] == 'yes') {
						$where .= ' AND checkin_family_members.attended = \'1\'';
					} elseif ($inputParams['attended'] == 'no'){
						$where .= ' AND checkin_family_members.attended = \'0\'';
					} elseif ($inputParams['attended'] == 'unreported'){
						$where .= ' AND checkin_family_members.attended IS NULL';
					}
					
					$where .=
							'		LIMIT 0, 1 ' .
							' )';
				}
				
				//-------------
				
				if (isset($inputParams['professionalId'])) {
					if (!empty($inputParams['professionalId'])) {
						$where .= ' AND checkin_staff.id_checkin = checkin.id_checkin' .
								' AND checkin_staff.id_staff = ' . $inputParams['professionalId']; 
					}
				}
				
				//$whereClouseForProgAndTopic = $this->buildWhereForProgramAndTopic($inputParams);
				
			} else if ($origin == 'checkin_child' || $origin == 'checkin_parent' || $origin == 'checkin_family_members'  || $origin == 'checkin_mailing_list') {
				
				$join = $this->buildLeftJoinsForPerson();
				
				$count_query = 'SELECT count(*) as num_records ';
				$query = 'SELECT checkin_family_members.*, checkin.*, person.*, agency_program.name as program_name, agency_location.location, agency_topic.agencyTopic as topic ';
				$sum_query = 'SELECT SUM(checkin_duration) AS toal_checkin_duration ';
				
				//$query .= ', ' . $this->buildFamilyTypeAuditTrailQuery($inputParams) . ' as familyTypeAuditTrail ';
				
				if (count($join) > 0) {
					$query .= $join['select'];
					$sum_query .= $join['select'];
				}
				
				//if ($origin == 'checkin_mailing_list') {
				if ( $this->isAddressFieldSelected($inputParams) || $origin == 'checkin_mailing_list') {
					$query .= ', family_address.*, state.state, county.county, city.city ';
				}
				
				$from = 
						' FROM person';
				
				if (count($join) > 0) {
					$from .= $join['join'];
				}
				
				$from .= 
				//	', checkin, agency_program, agency_location, agency_program_topic, agency_topic, checkin_family_members, family_person';
					', checkin ';
				
				$from .=
					' LEFT JOIN agency_program_topic' .
					' ON checkin.id_agencyTopic = agency_program_topic.id_agencyProgramTopic ' . 
					' LEFT JOIN agency_topic' .
					' ON agency_program_topic.id_agencyTopic = agency_topic.id_agencyTopic ';
			
				$from .= 
					', agency_program, agency_location, checkin_family_members, family_person';
				
				
				
				if (isset($inputParams['language'])) {
					if (!empty($inputParams['language'])) {
						$from .= ', person_language ';
					}
				}
				if (isset($inputParams['educationProvider'])) {
					if (!empty($inputParams['educationProvider'])) {
						$from .= ', person_education_provider ';
					}
				}
				
				if (isset($inputParams['professionalId'])) {
					if (!empty($inputParams['professionalId'])) {
						$from .= ', checkin_staff'; 
					}
				}
				
				//if ($origin == 'checkin_mailing_list') {
				if ( $this->isAddressFieldSelected($inputParams) || $origin == 'checkin_mailing_list') {
					$from .= ', family_address' .
							' LEFT JOIN city' .
							'	ON family_address.id_city = city.id_city' .
							', family_address_person, state, county  ';
				}
				
				
				if ( !empty($family_status) && ( $origin == 'checkin_child' || $origin == 'checkin_parent' ) ) {
					$from .= ', checkin_family ';
				} else {
					
					if (isset($inputParams['attended'])) {
						//$from .= ', checkin_family ';
					}
					
				}
				
				$where = 
						' WHERE checkin_family_members.id_checkin = checkin.id_checkin' .
						' AND checkin.id_agency ='. $idAgency . 
						' AND checkin_family_members.id_person = person.id_person' .
						' AND person.id_person = family_person.id_person ' .
						' AND checkin_family_members.id_family = family_person.id_family ' .
						' AND checkin.id_agencyProgram = agency_program.id_agencyProgram ' .
						' AND checkin.id_agencyLocation = agency_location.id_agencyLocation ';
						//' AND checkin.id_agencyTopic = agency_program_topic.id_agencyProgramTopic' .
						//' AND agency_program_topic.id_agencyTopic = agency_topic.id_agencyTopic ';
			
				if ( !empty($family_status) && ( $origin == 'checkin_child' || $origin == 'checkin_parent' || $origin == 'checkin_family_members' ) ) {
					$where .= ' AND checkin_family.id_checkin = checkin.id_checkin  ';
				} else {
					/*
					if (isset($inputParams['attended'])) {
						//$where .= ' AND checkin_family.id_checkin = checkin.id_checkin  ';
						
						if ($inputParams['attended'] == 'yes') {
							$where .= ' AND checkin_family_members.attended = \'1\'';
						} elseif ($inputParams['attended'] == 'no'){
							$where .= ' AND checkin_family_members.attended = \'0\'';
						} elseif ($inputParams['attended'] == 'unreported'){
							$where .= ' AND checkin_family_members.attended IS NULL';
						}
					}
					*/
				}
				
				
				if (isset($inputParams['attended'])) {
					/*
					if ($inputParams['attended'] == 'yes') {
						$where .= ' AND checkin_family.attended = \'1\'';
					} elseif ($inputParams['attended'] == 'no'){
						$where .= ' AND checkin_family.attended = \'0\'';
					}
					*/
					if ($inputParams['attended'] == 'yes') {
						$where .= ' AND checkin_family_members.attended = \'1\'';
					} elseif ($inputParams['attended'] == 'no'){
						$where .= ' AND checkin_family_members.attended = \'0\'';
					} elseif ($inputParams['attended'] == 'unreported'){
						$where .= ' AND checkin_family_members.attended IS NULL';
					}
				}
				
				
				
				if (isset($inputParams['professionalId'])) {
					if (!empty($inputParams['professionalId'])) {
						$where .= ' AND checkin_staff.id_checkin = checkin.id_checkin' .
								' AND checkin_staff.id_staff = ' . $inputParams['professionalId']; 
					}
				}
				
				$childIds = '';
				
				$CI->load->model('PersonRoleModel', '', TRUE);
				$childIds = $CI->PersonRoleModel->getChildRoleIds();
				
				if (!empty($childIds)) {
					if ($origin == 'checkin_child') {
						$where .= 
							' AND family_person.id_personRole IN (' . $childIds . ')';
					} else if ($origin == 'checkin_parent') {
						$all_parents = true;
						
						if (isset($inputParams['personRole'])) {
							if (!empty($inputParams['personRole'])) {
								$all_parents = false;
							} else {
								$all_parents = true;
							}
						} else {
							$all_parents = true;
						}
						if ($all_parents == true) {
							$where .= 
								' AND family_person.id_personRole NOT IN (' . $childIds . ')';
						} else {
							$where .= 
								' AND family_person.id_personRole = ' . $inputParams['personRole'] . '';
						}
					} else if ($origin == 'checkin_mailing_list') {
						if (isset($inputParams['personRole'])) {
							if (!empty($inputParams['personRole'])) {
								$where .= 
									' AND family_person.id_personRole = ' . $inputParams['personRole'];
							}
						}
					}
				}
				
				//if ($origin == 'checkin_mailing_list') {
				if ( $this->isAddressFieldSelected($inputParams)  || $origin == 'checkin_mailing_list' ) {
					$where .= 
						' AND family_address_person.id_person = person.id_person ' .
						' AND family_address_person.id_familyAddress = family_address.id_familyAddress ' .
						' AND family_address.id_state = state.id_state ' .
						' AND family_address.id_county = county.id_county ';
				}
				
				
				/**
				 * -------------------------------------------------------------------
				 * Family Address
				 * -------------------------------------------------------------------
				 */
				//if ($origin == 'checkin_mailing_list') {
				if ( $this->isAddressFieldSelected($inputParams) ) {
					$where .= $this->addressFieldsCondition($inputParams);
				} /*else  {
					$where .= $this->buildWhereBasedOnAddress($inputParams, 'family.id_family');
				}
				*/
				
			}
			
			foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
				if (is_numeric($key)) {
					// We consider this be the case where we have grouped fields under sub-headers.
					
					foreach($groups['fields'] as $field => $options ) {
						
						/*if( $origin == 'checkin' && !empty($whereClouseForProgAndTopic) &&  ( $field == 'program' ||  $field == 'topic' ) ){
							
						}else{*/
						
							$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
						//}
					}
					
					
				} else {
					
					$field = $key;
					$options = $groups;
					$where .= $this->buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin);
				}
			}
			/*if($origin == 'checkin' && !empty($whereClouseForProgAndTopic) ){
				$where .= $whereClouseForProgAndTopic;
			}*/
			
			if ($family_status) {
				$where .= ' AND ( checkin_family.familyType IN (' . $family_status . ') ) ' ;
				
				//$where .= ' AND ( familyTypeAuditTrail IN (' . $family_status . ') ) ' ;
				
				$where .= ' AND ( ' . $this->buildFamilyTypeAuditTrailQuery($inputParams, 'checkin_family') . ' IN (' . $family_status . ') ) ' ;
			}
			
			if ($origin == 'checkin') {
				$where .= $this->buildConditionsForFamilyMembers($origin, $reportId, $inputParams);
			} else if ($origin == 'checkin_child' || $origin == 'checkin_parent') {
				foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
					if (is_numeric($key)) {
						// We consider this be the case where we have grouped fields under sub-headers.
						
						foreach($groups['fields'] as $field => $options ) {
							$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'person', $origin);
						}
					} else {
						$field = $key;
						$options = $groups;
						$where .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, 'person', $origin);
					}
				}
			}
			
			if (isset($REPORT_CONFIG[$reportId]['group_by'])) {
				if ($origin == 'checkin_child' || $origin == 'checkin_parent') {
					//$REPORT_CONFIG[$reportId]['group_by'] = 'checkin_family_members.' . $REPORT_CONFIG[$reportId]['group_by'];
					
					$REPORT_CONFIG[$reportId]['group_by'] = 'checkin_family_members.id_person';
				} elseif ($origin == 'checkin_mailing_list') {
					$REPORT_CONFIG[$reportId]['group_by'] = $REPORT_CONFIG[$reportId]['group_by'];
				}
			}
		}
		
		$group_by = '';
		
		
		if (isset($REPORT_CONFIG[$reportId]['group_by'])) {
			if (!empty($REPORT_CONFIG[$reportId]['group_by'])) {
				$group_by .= ' GROUP BY ' . $REPORT_CONFIG[$reportId]['group_by'];
			}
		}
		
		// Override by Deepak
		//$group_by = '';
		
		
		//$count_query = $count_query . $from . $where . $group_by;
		if (!empty($group_by)) {
			$count_query = $count_query . $from . $where . $group_by;
		} else {
			$count_query = $count_query . $from . $where;
		}
		
		/*
		echo "Query : " . $query . "<br />";
		echo "From : " . $from . "<br />";
		echo "Where : " . $where . "<br />";
		echo "Group by : " . $group_by . "<br />";
		die;
		*/
		
		
		$query = $query . $from . $where . $group_by;
		//echo $query;die;
		if (!empty($sum_query)) {
			$sum_query = $sum_query . $from . $where . $group_by;
		} 
		
		$CI =& get_instance();
		$CI->load->model('PersonFamilyModel','',true);
		$CI->load->model('CheckinModel', '', TRUE);
		
		//echo $count_query;die;
		$result = $this->db->query($count_query);
		if (!empty($group_by)) {
			$numResults = $result->num_rows();
		} else {
			$row = $result->row();
			$numResults = $row->num_records;
		}
		
		if ($numResults > 0) {
			if (!empty($sum_query)) {
				//echo $sum_query;die;
				$result = $this->db->query($sum_query);
				$row = $result->row();
				$sum = $row->toal_checkin_duration;
			}
		}
		
		$sum = null;
		$total_parent_count = 0;
		$total_checkin_duration = 0;
		$header = '';
		if ($origin == 'unique_checkin') {
			$result = $this->db->query($query);
			
			foreach ($result->result() as $row) {
				$parentcount = $CI->PersonFamilyModel->getParentsCountForCheckinAttended($row->id_checkin);
				$total_parent_count = $total_parent_count + $parentcount;
				$total_checkin_duration = $total_checkin_duration + $row->checkin_duration;
			}
			
			$header = '#' . $total_parent_count . ' Parents' . ' / ' . $total_checkin_duration . ' hrs.';
		}
		
		
		$limitQuery = buildLimitQuery($p);
		$query = $query . $limitQuery['query'];
		$start = $limitQuery['start'];
		
		//echo $query;die;
		
		log_message('debug', "checkinModel.getCheckins : " . $query);
		$result = $this->db->query($query);
		
		$results = array();
		
		$origin_person = array(
			'checkin_child',
			'checkin_parent',
			'child',
			'parent',
			
			'family_audit_trail',
			'child_audit_trail',
			'parent_audit_trail',
		);
		
		if (in_array($origin, $origin_person)) {
			$CI->load->model('PersonEducationProviderModel','',true);
			$CI->load->model('PersonLanguageModel','',true);
			$CI->load->model('PersonPhoneModel', '', TRUE);
			$CI->load->model('FamilyEmailModel', '', TRUE);
		} else if ($origin == 'warmline') {
			$CI->load->model('PersonPhoneModel', '', TRUE);
		}
		
		foreach ($result->result() as $row) {
			//print_r_pre($row);die;

			/*
			if (isset($sum)) {
				$row->toal_checkin_duration = $sum;
			}
			*/
			//echo $origin;die;
			
			
			
			if ($origin == 'unique_checkin') {
				$parentcount = $CI->PersonFamilyModel->getParentsCountForCheckinAttended($row->id_checkin);
				//echo "IF : " . $parentcount;die;
				$total_parent_count = $total_parent_count + $parentcount;
			} else {
				
				$members = array();
				
				// Override by Deepak
				if ($origin == 'warmline') {
					$members = $CI->PersonFamilyModel->getPersonsOfFamilyWithRole($row->id_family, 'mother');
				} else {
					$members = $CI->PersonFamilyModel->getPersonsOfFamilyWithRole($row->id_family);
				}
				
				
				//print_r_pre($members);
				//die;
				if ($origin == 'goal') {
					$members = $CI->PersonFamilyModel->getMembersOfGoal($row->id_familyGoal);
					$row->members	= $members;
					
				} /*else if ($origin == 'checkin') {
					$members = $CI->CheckinModel->getCheckinMembersWithRoleForFamily($row->id_checkin, $row->id_family);
					$row->members = $members;
				}
				*/
				
				if (in_array($origin, $origin_person)) {
					
					$emails = $CI->FamilyEmailModel->getEmailsForPerson($row->id_person);
					$row->emails = $emails;
				
					$arr = $CI->PersonEducationProviderModel->getEducationProvidersForPerson($row->id_person);
					$row->education_providers = $arr;
					
					$arr = $CI->PersonLanguageModel->getLanguagesForPerson($row->id_person);
					$row->languages = $arr;
					
					$arr = $CI->PersonPhoneModel->getPhonesForPerson($row->id_person);
					$row->phones = $arr;
				} else if ($origin == 'warmline') {
					$arr = array();
					if (count($members) > 0) {
						$arr = $CI->PersonPhoneModel->getPhonesForPerson($members[0]->id_person);
					}
					$row->phones = $arr;
				}
				
			}
			
			
			$row->output = '';
			foreach($columns as $key => $column) {
				
				switch ($key) {
					case 'parent_count':
						$row->output->$key = $this->load->view('report/output/parent_count', array( 'parent' => $parentcount, 'inputParams' => $inputParams ), true);
			            break;
			            
					case 'parent_name':
						$row->output->$key = $this->load->view('report/output/parent_name', array( 'members' => $members, 'inputParams' => $inputParams ), true);
			            break;
			            
					case 'children_name':
						$row->output->$key = $this->load->view('report/output/children_name', array( 'members' => $members, 'inputParams' => $inputParams  ), true);
		            	break;
		            	
		            case 'children_age':
						$row->output->$key = $this->load->view('report/output/children_age', array( 'members' => $members, 'inputParams' => $inputParams  ), true);
		            	break;
		            
		            case 'mother_age':
						$row->output->$key = $this->load->view('report/output/mother_age', array( 'members' => $members, 'inputParams' => $inputParams  ), true);
		            	break;
		            	
		            case 'program':
		            	$row->output->$key = $this->load->view('report/output/program', array( 'program' => $row->program_name, 'inputParams' => $inputParams  ), true);
		            	break;
		            	
		            case 'location':
		            	$row->output->$key = $this->load->view('report/output/location', array( 'location' => $row->location, 'inputParams' => $inputParams  ), true);
		            	break;
		            	
		            case 'topic':
						$row->output->$key = $this->load->view('report/output/topic', array( 'topic' => $row->topic, 'inputParams' => $inputParams ), true);
			            break;
			        
		            case 'goal':
		            	$row->output->$key = $this->load->view('report/output/goal', array( 'goal' => $row->goal, 'inputParams' => $inputParams  ), true);
		            	break;
		            
		            case 'goal_type':
		            	$row->output->$key = $this->load->view('report/output/goal_type', array( 'goal_type' => $row->goalType, 'inputParams' => $inputParams  ), true);
		            	break;
		            
		            case 'action_steps':
		            	$row->output->$key = $this->load->view('report/output/action_steps', array( 'action_steps' => $row->action_steps, 'inputParams' => $inputParams  ), true);
		            	break;
		            
		            case 'family_name':
		            	$row->output->$key = $this->load->view('report/output/family_name', array( 'family_name' => $row->family, 'inputParams' => $inputParams  ), true);
		            	break;
		            
		            case 'family_members':
						$row->output->$key = $this->load->view('report/output/family_members', array( 'members' => $members, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'family_status':
						$row->output->$key = $this->load->view('report/output/family_status', array( 'family_status' => $row->familyType, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'family_status_audit_trail':
						$row->output->$key = $this->load->view('report/output/family_status_audit_trail', array( 'family_status_audit_trail' => $row->familyTypeAuditTrail, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'checkin_date':
						$row->output->$key = $this->load->view('report/output/target_date', array( 'target_date' => $row->checkin_date, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'target_date':
						$row->output->$key = $this->load->view('report/output/target_date', array( 'target_date' => $row->target_date, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'accomplished_date':
						$row->output->$key = $this->load->view('report/output/accomplished_date', array( 'accomplished_date' => $row->accomplished_date, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'person_name':
						$row->output->$key = $this->load->view('report/output/person_name', array( 'person_name' => $row->firstName . ' ' . $row->lastName, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'person_age':
						$row->output->$key = $this->load->view('report/output/person_age', array( 'person' => $row, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'date_birth':
						$row->output->$key = $this->load->view('report/output/date_birth', array( 'date_birth' => $row->dateBirth, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'topic_hours':
			        	$row->toal_checkin_duration = $sum;
						$row->output->$key = $this->load->view('report/output/topic_hours', array( 'topic_hours' => $row->checkin_duration, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'gender':
						$row->output->$key = $this->load->view('report/output/gender', array( 'gender' => $row->gender, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'weight':
						$row->output->$key = $this->load->view('report/output/weight', array( 'weight' => $row->weight, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'homeless':
						$row->output->$key = $this->load->view('report/output/homeless', array( 'homeless' => $row->homeless, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'iep':
						$row->output->$key = $this->load->view('report/output/iep', array( 'IEP' => $row->IEP, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'migrant':
						$row->output->$key = $this->load->view('report/output/migrant', array( 'migrant' => $row->migrant, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'frl':
						$row->output->$key = $this->load->view('report/output/frl', array( 'FRL' => $row->FRL, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'child_support':
						$row->output->$key = $this->load->view('report/output/child_support', array( 'familyReceivesChildSupport' => $row->familyReceivesChildSupport, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'tanf':
						$row->output->$key = $this->load->view('report/output/tanf', array( 'familyReceivesTANF' => $row->familyReceivesTANF, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'food_stamp':
						$row->output->$key = $this->load->view('report/output/food_stamp', array( 'familyReceivesFoodStamps' => $row->familyReceivesFoodStamps, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'wic':
						$row->output->$key = $this->load->view('report/output/wic', array( 'familyReceivesWIC' => $row->familyReceivesWIC, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'house_subsidy':
						$row->output->$key = $this->load->view('report/output/house_subsidy', array( 'familyReceivesHousingSubsidy' => $row->familyReceivesHousingSubsidy, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'phone':
						$row->output->$key = $this->load->view('report/output/phone', array( 'phones' => $row->phones, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'email':
						$row->output->$key = $this->load->view('report/output/email', array( 'emails' => $row->emails, 'inputParams' => $inputParams ), true);
			            break;
			        
		            case 'education_provider':
						$row->output->$key = $this->load->view('report/output/education_provider', array( 'education_providers' => $row->education_providers, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'language':
						$row->output->$key = $this->load->view('report/output/language', array( 'languages' => $row->languages, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'education':
						$row->output->$key = $this->load->view('report/output/education', array( 'education' => $row->education, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'employment':
						$row->output->$key = $this->load->view('report/output/employment', array( 'employment' => $row->employmentStatus, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'marital_status':
						$row->output->$key = $this->load->view('report/output/marital_status', array( 'marital_status' => $row->maritalStatus, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'ethnicity':
						$row->output->$key = $this->load->view('report/output/ethnicity', array( 'ethnicity' => $row->ethnicity, 'inputParams' => $inputParams ), true);
			            break;

					case 'family_structure':						
						$row->output->$key = $this->load->view('report/output/family_structure', array( 'family_structure' => $row->familyStructure, 'inputParams' => $inputParams ), true);
			            break;
					
					case 'first_name':
						//$row->output->$key = $this->load->view('report/output/first_name', array( 'first_name' => $row->firstName . " : " . $row->id_person . " : " . (isset($row->id_person_audit_trail) ? $row->id_person_audit_trail : ''), 'inputParams' => $inputParams ), true);
			            $row->output->$key = $this->load->view('report/output/first_name', array( 'first_name' => $row->firstName, 'inputParams' => $inputParams ), true);
			            break;
			        			        
			        case 'last_name':
						$row->output->$key = $this->load->view('report/output/last_name', array( 'last_name' => $row->lastName, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'state':
						$row->output->$key = $this->load->view('report/output/state', array( 'state' => $row->state, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'county':
						$row->output->$key = $this->load->view('report/output/county', array( 'county' => $row->county, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'city':
						$row->output->$key = $this->load->view('report/output/city', array( 'city' => $row->city, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'zip':
						$row->output->$key = $this->load->view('report/output/zip', array( 'zip' => $row->zip, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'address':
						$row->output->$key = $this->load->view('report/output/address', array( 'address' => $row->address, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'address2':
						$row->output->$key = $this->load->view('report/output/address2', array( 'address2' => $row->address2, 'inputParams' => $inputParams ), true);
			            break;
			        
		            case 'salutation':
						$row->output->$key = $this->load->view('report/output/salutation', array( 'salutation' => $row->salutation, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'in_care_of':
						$row->output->$key = $this->load->view('report/output/in_care_of', array( 'in_care_of' => $row->in_care_of, 'inputParams' => $inputParams ), true);
			            break;
			        
		            case 'note':
						$row->output->$key = $this->load->view('report/output/note', array( 'note' => $row->notes, 'inputParams' => $inputParams ), true);
			            break;
			        
		            case 'professional_name':
						$row->output->$key = $this->load->view('report/output/professional_name', array( 'professional_name' => $row->professionalName, 'inputParams' => $inputParams ), true);
			            break;
			        
			        case 'attended':
						$row->output->$key = $this->load->view('report/output/attended', array( 'attended' => (isset($row->attended) ? $row->attended : null), 'inputParams' => $inputParams ), true);
			            break;
			      
		            default:
		            	$row->output->$key = NULL;
		            	break;
		            	
				}
			}
			$results[] = $row;
		}
		
		$paginationParams = buildParamForPagination($numResults, $p, $PER_PAGE);
		
		$field = 'report';
		$params = requestToParams($numResults, $start, $paginationParams['totalPages'], $paginationParams['firstPage'], $paginationParams['lastPage'], $paginationParams['currentPage'], $sort, $order, $q, $field, $header);

		$arr = array(
			'results'	=> $results,
			'param'		=> $params,
			'query'     => $query,
			'count_query'     => $count_query,
			'request'	=> $_REQUEST,
			'headers'	=> $columns,
	    );
	    //print_r_pre($arr);die;
		return $arr;
	}
	
	function buildConditionsForFamilyMembers($origin, $reportId, $inputParams) {
		
		global $REPORT_CONFIG;
		
		$where = '';
		/**
		 * -------------------------------------------------------------------
		 * Any Condition based on Family members should be placed BELOW this. 
		 * -------------------------------------------------------------------
		 */
		$tables = array();
		
		$base_table = '';
		$familyIdField = '';
		$selectIdField = '';
		if ($origin == 'checkin' || $origin == 'checkin_child' || $origin == 'checkin_parent') {
			if ($origin == 'checkin' ) {
				$familyIdField = 'checkin_family.id_family';
			} else if ($origin == 'checkin_child' || $origin == 'checkin_parent') {
				$familyIdField = 'checkin_family_members.id_family';
			}
			
			$base_table = 'checkin_family_members';
			
			$selectIdField = 'checkin_family_members.id';
			
		} else if ($origin == 'family') {
			$familyIdField = 'family.id_family';
			
			$base_table = 'family_person';
			
			$selectIdField = 'family_person.id_familyPerson';
		}else if ($origin == 'child' || $origin == 'parent') {
			$familyIdField = 'family.id_family';
			
			$base_table = 'family_person';
			
			$selectIdField = 'family_person.id_familyPerson';
		}
		
		if (!empty($base_table)) {
			$tables[] = $base_table;
		}
		if (!in_array('person', $tables)) {
			$tables[] = 'person';
		}
		
		if (isset($inputParams['language'])) {
			if (!empty($inputParams['language'])) {
				if (!in_array('person_language', $tables)) {
					$tables[] = 'person_language';
				}
			}
		}
		
		if (isset($inputParams['educationProvider'])) {
			if (!empty($inputParams['educationProvider'])) {
				if (!in_array('person_education_provider', $tables)) {
					$tables[] = 'person_education_provider';
				}
			}
		}
		
		if (isset($inputParams['personRole'])) {
			if (!empty($inputParams['personRole'])) {
				if (!in_array('family_person', $tables)) {
					$tables[] = 'family_person';
				}
			}
		}
		
		$conditions = '';
		foreach($REPORT_CONFIG[$reportId]['input'] as $key => $groups) {
			if (is_numeric($key)) {
				// We consider this be the case where we have grouped fields under sub-headers.
				
				foreach($groups['fields'] as $field => $options ) {
					$conditions .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, $base_table, $origin);
				}
			} else {
				$field = $key;
				$options = $groups;
				$conditions .= $this->buildWhereBasedOnFamilyFields($field, $inputParams, $base_table, $origin);
			}
		}
		
		if (!empty($conditions)) {
			//print_r_pre($tables);die;
			$where .= 
					" AND (SELECT " . $selectIdField .
					" 		FROM " . implode(", ", $tables);
			
			if ($base_table == 'checkin_family_members') {
				$where .=
					" 		WHERE checkin_family_members.id_checkin = checkin.id_checkin " .
					"		AND checkin_family_members.id_family = " . $familyIdField .
					"		AND checkin_family_members.id_person = person.id_person";
			
			} else if ($base_table == 'family_person') {
				
				$where .=
					" 		WHERE family_person.id_family = family.id_family " .
					"		AND family_person.id_person = person.id_person";
			}
			
			$where .= $conditions;
			
			$where .=
					"		LIMIT 0, 1 " .
					"	)";
		}
		/**
		 * -------------------------------------------------------------------
		 * Any Condition based on Family members should be placed ABOVE this. 
		 * -------------------------------------------------------------------
		 */
		
		/**
		 * -------------------------------------------------------------------
		 * GOAL 
		 * -------------------------------------------------------------------
		 */
		$where .= $this->buildWhereBasedOnGoals($inputParams, $familyIdField);
		
		/**
		 * -------------------------------------------------------------------
		 * Encounter 
		 * -------------------------------------------------------------------
		 */
		$where .= $this->buildWhereBasedOnEncounters($inputParams, $familyIdField);
		
		/**
		 * -------------------------------------------------------------------
		 * Family Address
		 * -------------------------------------------------------------------
		 */
		$where .= $this->buildWhereBasedOnAddress($inputParams, $familyIdField);
	
		return $where;
	}
	
	function buildWhereBasedOnNonFamilyFields($field, $inputParams, $origin = null) {
		$where = '';
		switch($field) {
			case 'program':
				$condition = $this->buildWhereForProgram($inputParams);
				$where .= $condition;
			break;
			
			case 'location':
				$condition = $this->buildWhereForLocation($inputParams);
				$where .= $condition;
			break;
			
			case 'topic':
				
				/*if( !empty($origin) && $origin == 'checkin' ){
					
					$condition = $this->buildWhereForAgencyTopic($inputParams);
				
				}else{*/
					$condition = $this->buildWhereForTopic($inputParams);
				//}
				
				$where .= $condition;
			break;
			
			
			case 'start_end_date':
				
				if ($origin != 'family' && $origin != 'parent' && $origin != 'child') {
					$condition = $this->buildWhereForStartEndDate($inputParams);
					$where .= $condition;
				}
			break;
		}
		
		return $where;
	}
	
	function buildWhereForProgram($inputParams) {
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['program'])) {
			$programs = '';
			if (isset($inputParams['agencyProgramId'])) {
				if (!empty($inputParams['agencyProgramId'])) {
					
					if (is_array($inputParams['agencyProgramId'])) {
						$programs = implode(",", $inputParams['agencyProgramId']);
					} else {
						$programs = $inputParams['agencyProgramId'];
					}
					
					if (!empty($programs)) {
						$where .= ' AND (';
						$where .= ' checkin.id_agencyProgram IN (' . $programs . ')';
						$where .= ')';
					}
				}
			}
		//}
		
		return $where;
	}
	
	function buildWhereForLocation($inputParams) {
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['location'])) {
			if (isset($inputParams['agencyLocationId'])) {
				if (!empty($inputParams['agencyLocationId'])) {
					if (!is_array($inputParams['agencyLocationId'])) {
						$inputParams['agencyLocationId'] = explode(",", $inputParams['agencyLocationId']);
					}
					$location = implode(",", $inputParams['agencyLocationId']);
					
					if (!empty($location)) {
						$where .= ' AND (';
						$where .= ' checkin.id_agencyLocation IN (' . $location . ')';
						$where .= ')';
					}
				}
			}
		//}
		return $where;
	}
	
	function buildWhereForTopic($inputParams) {
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['program'])) {
			$programs = '';
			if (isset($inputParams['agencyTopicId'])) {
				if (!empty($inputParams['agencyTopicId'])) {
					if (!is_array($inputParams['agencyTopicId'])) {
						$inputParams['agencyTopicId'] = explode(",", $inputParams['agencyTopicId']);
					}
					$topics = implode(",", $inputParams['agencyTopicId']);
					
					if (!empty($topics)) {
						$where .= ' AND (';
						$where .= ' checkin.id_agencyTopic IN (' . $topics . ')';
						$where .= ')';
					}
				}
			}
		//}
		
		return $where;
	}

	function buildWhereForAgencyTopic($inputParams) {
		
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['program'])) {
			$programs = '';
			if (isset($inputParams['agencyTopicId'])) {
				if (!empty($inputParams['agencyTopicId'])) {
					
					if (is_array($inputParams['agencyTopicId'])) {
						
						$agencyTopicIdArray = array();
						foreach($inputParams['agencyTopicId'] as $value){
							$agencyTopicIdArray = array_merge($agencyTopicIdArray,explode(',',$value)) ;
						}
						
						$agencyTopicIdArray = array_unique($agencyTopicIdArray);
						$topics = '';
						if( !empty($agencyTopicIdArray) ){
							$topics = implode(",", $agencyTopicIdArray);
						}
						
					} else {
						$topics = $inputParams['agencyTopicId'];
					}
					if (!empty($topics)) {

						$where .= ' AND (';
						$where .= ' checkin.id_agencyTopic IN (' . $topics . ')';
						$where .= ')';
					}
					
				}
			}
		//}
		
		return $where;
	}
	
	function buildWhereForProgramAndTopic($inputParams) {
		
		$where = '';
			$programs = '';
			if (isset($inputParams['agencyProgramId']) && isset($inputParams['agencyTopicId'])) {
				if (!empty($inputParams['agencyProgramId']) && !empty($inputParams['agencyTopicId'])) {
					
					//For Program
					if (is_array($inputParams['agencyProgramId'])) {
						$programs = implode(",", $inputParams['agencyProgramId']);
					} else {
						$programs = $inputParams['agencyProgramId'];
					}
					
					if (!empty($programs)) {
						$where .= ' AND ( (';
						$where .= ' checkin.id_agencyProgram IN (' . $programs . ')';
						$where .= ')';
					}
					
					//For Topic
					
					if (is_array($inputParams['agencyTopicId'])) {
						
						$agencyTopicIdArray = array();
						foreach($inputParams['agencyTopicId'] as $value){
							$agencyTopicIdArray = array_merge($agencyTopicIdArray,explode(',',$value)) ;
						}
						
						$agencyTopicIdArray = array_unique($agencyTopicIdArray);
						$topics = '';
						if( !empty($agencyTopicIdArray) ){
							$topics = implode(",", $agencyTopicIdArray);
						}
						
					} else {
						$topics = $inputParams['agencyTopicId'];
					}
					if (!empty($topics)) {

						$where .= ' OR (';
						$where .= ' checkin.id_agencyTopic IN (' . $topics . ')';
						$where .= ') )';
					}
				}
			}
		return $where;
	}
	
	
	
	function buildWhereForStartEndDate($inputParams) {
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['start_end_date'])) {
			if (!empty($inputParams['start_date'])) {
				$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
			}
			
			if (!empty($inputParams['end_date'])) {
				$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
			}
			
			//$where .= ' AND (';
			if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$where .= ' AND ( checkin.checkin_date >= "' . $inputParams['start_date'] . '" AND checkin.checkin_date <= "' . $inputParams['end_date'] . '") ' ;
			} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
				$where .= ' AND ( checkin.checkin_date >= "' . $inputParams['start_date'] . '") ';
			} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$where .= ' AND ( checkin.checkin_date <= "' . $inputParams['end_date'] . '") ' ;
			}
			//$where .= ')';
		//}
		return $where;
	}
	
	function buildWhereBasedOnFamilyFields($field, $inputParams, $person_relation_table = null, $origin = null) {
		$where = '';
		
		switch($field) {
			case 'children_age':
				$condition = $this->buildWhereForChildrenAge($inputParams);
				$where .= $condition;
			break;
			
			case 'mothers_birthday':
				$condition = $this->buildWhereForMothersBirthday($inputParams);
				$where .= $condition;
			break;
			
			/*
			case 'pregnant':
				$condition = $this->buildWhereForPregnant($inputParams, $origin);
				$where .= $condition;
			break;
			*/
			
			case 'family_name':
				$condition = $this->buildWhereForFamilyName($inputParams, $person_relation_table);
				$where .= $condition;
			break;
			
			case 'demographics':
				$condition = $this->buildWhereForDemographics($inputParams, $origin);
				$where .= $condition;
			break;
		}
		
		return $where;
	}
	
	function buildWhereForDemographics($inputParams, $origin) {
		//print_r_pre($inputParams);die;
		$where = '';
		
		if ($origin == 'checkin') {
			if (isset($inputParams['personRole'])) {
				if (!empty($inputParams['personRole'])) {
					/*
					$where .= 
						' AND family_person.id_person = person.id_person ' .
						' AND family_person.id_personRole = ' . $inputParams['personRole'];
					*/
					$where .= 
						' AND checkin_family_members.id_person = person.id_person ' .
						' AND person.id_person = family_person.id_person ' .
						' AND family_person.id_personRole = ' . $inputParams['personRole'];
					
				} 
			} 
		}
		
		if (isset($inputParams['pregnant'])) {
			if ($inputParams['pregnant'] == 'on') {
				
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					if ($origin == 'checkin') {
						$where .= ' AND ( checkin_family_members.pregnant = "1" ) ';
					} else {
						$where .= ' AND ( person.pregnant = "1" ) ';
					}
				}
				//$where .= ' AND ( person_audit_trail.pregnant = "1" ) ';
				
			}
		}
		
		if (isset($inputParams['parentsMarriedAtChildBirth'])) {
			if ($inputParams['parentsMarriedAtChildBirth'] == 'yes') {
				$where .= ' AND ( person.parentsMarriedAtChildBirth = "1" ) ';
			} else if ($inputParams['parentsMarriedAtChildBirth'] == 'no') {
				$where .= ' AND ( person.parentsMarriedAtChildBirth = "0" ) ';
			}
		}
		
		if (isset($inputParams['placeBorn'])) {
			if (!empty($inputParams['placeBorn'])) {
				if (!is_array($inputParams['placeBorn'])) {
					$inputParams['placeBorn'] = explode(",", $inputParams['placeBorn']);
				}
				$placeBorn = implode(",", $inputParams['placeBorn']);
				
				if (!empty($placeBorn)) {
					$where .= ' AND ( person.id_birthPlace IN (' . $placeBorn . ' ) ) ';
				}
			}
		}
		
		if (isset($inputParams['familyStructure'])) {
			if (!empty($inputParams['familyStructure'])) {
				if (!is_array($inputParams['familyStructure'])) {
					$inputParams['familyStructure'] = explode(",", $inputParams['familyStructure']);
				}
				$familyStructure = implode(",", $inputParams['familyStructure']);
				
				if (!empty($familyStructure)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= '';
					} else {
						$where .= ' AND ( person.id_familyStructure IN (' . $familyStructure . ' ) ) ';
					}
				}
			}
		}
		
		if (isset($inputParams['ethnicity'])) {
			if (!empty($inputParams['ethnicity'])) {
				if (!is_array($inputParams['ethnicity'])) {
					$inputParams['ethnicity'] = explode(",", $inputParams['ethnicity']);
				}
				$ethnicity = implode(",", $inputParams['ethnicity']);
				
				if (!empty($ethnicity)) {
					$where .= ' AND ( person.id_ethnicity IN (' . $ethnicity . ' ) ) ';
				}
			}
		}
		
		if (isset($inputParams['education'])) {
			if (!empty($inputParams['education'])) {
				if (!is_array($inputParams['education'])) {
					$inputParams['education'] = explode(",", $inputParams['education']);
				}
				$education = implode(",", $inputParams['education']);
				
				if (!empty($education)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= '';
					} else {
						$where .= ' AND ( person.id_education IN (' . $education . ' ) ) ';
					}
				}
			}
		}
		
		/**
		 * Langauge
		 */
		if (isset($inputParams['language'])) {
			if (!empty($inputParams['language'])) {
				if (!is_array($inputParams['language'])) {
					$inputParams['language'] = explode(",", $inputParams['language']);
				}
				$language = implode(",", $inputParams['language']);
				
				if (!empty($language)) {
					$where .= ' AND ( person.id_person = person_language.id_person ' .
							' AND person_language.id_language  IN (' . $language . ') ) ';
				}
			}
		}
		
		
		if (isset($inputParams['mothersNativeLanguage'])) {
			if (!empty($inputParams['mothersNativeLanguage'])) {
				if (!is_array($inputParams['mothersNativeLanguage'])) {
					$inputParams['mothersNativeLanguage'] = explode(",", $inputParams['mothersNativeLanguage']);
				}
				$mothersNativeLanguage = implode(",", $inputParams['mothersNativeLanguage']);
				
				if (!empty($mothersNativeLanguage)) {
					$where .= ' AND ( person.mothersNativeLanguage IN (' . $mothersNativeLanguage . ' ) ) ';
				}
			}
		}
		
		if (isset($inputParams['mothersHomeLanguage'])) {
			if (!empty($inputParams['mothersHomeLanguage'])) {
				if (!is_array($inputParams['mothersHomeLanguage'])) {
					$inputParams['mothersHomeLanguage'] = explode(",", $inputParams['mothersHomeLanguage']);
				}
				$mothersHomeLanguage = implode(",", $inputParams['mothersHomeLanguage']);
				
				if (!empty($mothersHomeLanguage)) {
					$where .= ' AND ( person.mothersHomeLanguage IN (' . $mothersHomeLanguage . ' ) ) ';
				}
			}
		}
		
		
		/**
		 * Birth Date
		 */
		if (!empty($inputParams['birth_date_start_date'])) {
			$inputParams['birth_date_start_date'] = date('Y-m-d', strtotime($inputParams['birth_date_start_date']));
		}
		
		if (!empty($inputParams['birth_date_end_date'])) {
			$inputParams['birth_date_end_date'] = date('Y-m-d', strtotime($inputParams['birth_date_end_date']));
		}
		
		if ( !empty($inputParams['birth_date_start_date']) && !empty($inputParams['birth_date_end_date']) ) {
			$where .= ' AND ( person.dateBirth >= "' . $inputParams['birth_date_start_date'] . '" AND person.dateBirth <= "' . $inputParams['birth_date_end_date'] . '") ';
		} else if ( !empty($inputParams['birth_date_start_date']) && empty($inputParams['birth_date_end_date']) ) {
			$where .= ' AND ( person.dateBirth >= "' . $inputParams['birth_date_start_date']  . '" ) ';
		} else if ( empty($inputParams['birth_date_start_date']) && !empty($inputParams['birth_date_end_date']) ) {
			$where .= ' AND ( person.dateBirth <= "' . $inputParams['birth_date_end_date'] . '" ) ';
		} 
		
		/**
		 * Name
		 */
		
		if (isset($inputParams['firstName'])) {
			if (!empty($inputParams['firstName'])) {
				$where .= ' AND ( person.firstName like "%' . $inputParams['firstName'] . '%" ) ';
			}
		}
		
		if (isset($inputParams['lastName'])) {
			if (!empty($inputParams['lastName'])) {
				$where .= ' AND ( person.lastName like "%' . $inputParams['lastName'] . '%" ) ';
			}
		}
		
		if (isset($inputParams['educationProvider'])) {
			if (!empty($inputParams['educationProvider'])) {
				if (!is_array($inputParams['educationProvider'])) {
					$inputParams['educationProvider'] = explode(",", $inputParams['educationProvider']);
				}
				$educationProvider = implode(",", $inputParams['educationProvider']);
				
				if (!empty($educationProvider)) {
					$where .= ' AND ( person.id_person = person_education_provider.id_person ' .
							' AND person_education_provider.id_agencyEducationProvider IN (' . $educationProvider . ') ) ';
				}
			}
		}
		
		
		if (isset($inputParams['employmentStatus'])) {
			if (!empty($inputParams['employmentStatus'])) {
				if (!is_array($inputParams['employmentStatus'])) {
					$inputParams['employmentStatus'] = explode(",", $inputParams['employmentStatus']);
				}
				$employmentStatus = implode(",", $inputParams['employmentStatus']);
				
				if (!empty($employmentStatus)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= '';
					} else {
						$where .= ' AND ( person.id_employmentStatus IN (' . $employmentStatus . ') ) ';
					}
				}
			}
		}
		
		if (isset($inputParams['maritalStatus'])) {
			if (!empty($inputParams['maritalStatus'])) {
				if (!is_array($inputParams['maritalStatus'])) {
					$inputParams['maritalStatus'] = explode(",", $inputParams['maritalStatus']);
				}
				$maritalStatus = implode(",", $inputParams['maritalStatus']);
				
				if (!empty($maritalStatus)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= '';
					} else {
						$where .= ' AND ( person.id_maritalStatus IN (' . $maritalStatus . ') ) ';
					}
				}
			}
		}
		
		
		/**
		 * Weight
		 */
		$weight = '';
		if (isset($inputParams['weight_lbs'])) {
			if (!empty($inputParams['weight_lbs'])) {
				$weight = $inputParams['weight_lbs'];
			}
		}
		
		if (isset($inputParams['weight_oz'])) {
			if (!empty($inputParams['weight_oz'])) {
				$weight = $weight . '.' . $inputParams['weight_oz'];
			}
		}
		
		$operator = '=';
		if (isset($inputParams['weight_operator'])) {
			if (!empty($inputParams['weight_operator'])) {
				if ($inputParams['weight_operator'] == 'more') {
					$operator = '>';
				} else if ($inputParams['weight_operator'] == 'less') {
					$operator = '<';
				}
			}
		}
		
		if (!empty($weight)) {
			$where .= ' AND ( person.weight ' . $operator . ' ' . $weight . ' ) ';
		}
		
		/**
		 * Checkboxes
		 */
		if (isset($inputParams['homeless'])) {
			if ($inputParams['homeless'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.homeless = "1" ) ';
				}				
			}
		}
		
		if (isset($inputParams['IEP'])) {
			if ($inputParams['IEP'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.IEP = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesChildSupport'])) {
			if ($inputParams['familyReceivesChildSupport'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.familyReceivesChildSupport = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesTANF'])) {
			if ($inputParams['familyReceivesTANF'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.familyReceivesTANF = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesWIC'])) {
			if ($inputParams['familyReceivesWIC'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.familyReceivesWIC = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesFoodStamps'])) {
			if ($inputParams['familyReceivesFoodStamps'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.familyReceivesFoodStamps = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesHousingSubsidy'])) {
			if ($inputParams['familyReceivesHousingSubsidy'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.familyReceivesHousingSubsidy = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['FRL'])) {
			if ($inputParams['FRL'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.FRL = "1" ) ';
				}
			}
		}
		
		if (isset($inputParams['migrant'])) {
			if ($inputParams['migrant'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= '';
				} else {
					$where .= ' AND ( person.migrant = "1" ) ';
				}
			}
		}
		
		return $where;
	}
	
	function buildWhereForDemographicsFromAuditTrail($inputParams, $origin) {
		//print_r_pre($inputParams);die;
		$where = '';
		
		if (isset($inputParams['pregnant'])) {
			if ($inputParams['pregnant'] == 'on') {
				$where .= ' AND ( person_audit_trail.pregnant = "1" ) ';
				
			}
		}
		
		if (isset($inputParams['familyStructure'])) {
			if (!empty($inputParams['familyStructure'])) {
				if (!is_array($inputParams['familyStructure'])) {
					$inputParams['familyStructure'] = explode(",", $inputParams['familyStructure']);
				}
				$familyStructure = implode(",", $inputParams['familyStructure']);
				
				if (!empty($familyStructure)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= ' AND ( person_audit_trail.id_familyStructure IN (' . $familyStructure . ' ) ) ';
					} else {
						$where .= '';
					}
				}
			}
		}
		
		if (isset($inputParams['education'])) {
			if (!empty($inputParams['education'])) {
				if (!is_array($inputParams['education'])) {
					$inputParams['education'] = explode(",", $inputParams['education']);
				}
				$education = implode(",", $inputParams['education']);
				
				if (!empty($education)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= ' AND ( person_audit_trail.id_education IN (' . $education . ' ) ) ';
					} else {
						$where .= '';
					}
				}
			}
		}
		
		if (isset($inputParams['employmentStatus'])) {
			if (!empty($inputParams['employmentStatus'])) {
				if (!is_array($inputParams['employmentStatus'])) {
					$inputParams['employmentStatus'] = explode(",", $inputParams['employmentStatus']);
				}
				$employmentStatus = implode(",", $inputParams['employmentStatus']);
				
				if (!empty($employmentStatus)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= ' AND ( person_audit_trail.id_employmentStatus IN (' . $employmentStatus . ') ) ';
					} else {
						$where .= '';
					}
				}
			}
		}
		
		if (isset($inputParams['maritalStatus'])) {
			if (!empty($inputParams['maritalStatus'])) {
				if (!is_array($inputParams['maritalStatus'])) {
					$inputParams['maritalStatus'] = explode(",", $inputParams['maritalStatus']);
				}
				$maritalStatus = implode(",", $inputParams['maritalStatus']);
				
				if (!empty($maritalStatus)) {
					if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
						$where .= ' AND ( person_audit_trail.id_maritalStatus IN (' . $maritalStatus . ') ) ';
					} else {
						$where .= '';
					}
				}
			}
		}
		
		/**
		 * Checkboxes
		 */
		if (isset($inputParams['homeless'])) {
			if ($inputParams['homeless'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.homeless = "1" ) ';
				} else {
					$where .= '';
				}				
			}
		}
		
		if (isset($inputParams['IEP'])) {
			if ($inputParams['IEP'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.IEP = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesChildSupport'])) {
			if ($inputParams['familyReceivesChildSupport'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.familyReceivesChildSupport = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesTANF'])) {
			if ($inputParams['familyReceivesTANF'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.familyReceivesTANF = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesWIC'])) {
			if ($inputParams['familyReceivesWIC'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.familyReceivesWIC = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesFoodStamps'])) {
			if ($inputParams['familyReceivesFoodStamps'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.familyReceivesFoodStamps = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['familyReceivesHousingSubsidy'])) {
			if ($inputParams['familyReceivesHousingSubsidy'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.familyReceivesHousingSubsidy = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['FRL'])) {
			if ($inputParams['FRL'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.FRL = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		if (isset($inputParams['migrant'])) {
			if ($inputParams['migrant'] == 'on') {
				if ($origin == 'family_audit_trail' || $origin == 'parent_audit_trail' || $origin == 'child_audit_trail' ) {
					$where .= ' AND ( person_audit_trail.migrant = "1" ) ';
				} else {
					$where .= '';
				}
			}
		}
		
		return $where;
	}
	
	function buildWhereForChildrenAge($inputParams) {
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['children_age'])) {
			
			if (!empty($inputParams['children_age_start_date'])) {
				$inputParams['children_age_start_date'] = date('Y-m-d', strtotime($inputParams['children_age_start_date']));
			}
			
			if (!empty($inputParams['children_age_end_date'])) {
				$inputParams['children_age_end_date'] = date('Y-m-d', strtotime($inputParams['children_age_end_date']));
			}
			
			if ( !empty($inputParams['children_age_start_date']) && !empty($inputParams['children_age_end_date']) ) {
				$where .= ' AND ( person.dateBirth >= "' . $inputParams['children_age_start_date'] . '" AND person.dateBirth <= "' . $inputParams['children_age_end_date'] . '") ';
			} else if ( !empty($inputParams['children_age_start_date']) && empty($inputParams['children_age_end_date']) ) {
				$where .= ' AND ( person.dateBirth >= "' . $inputParams['children_age_start_date']  . '" ) ';
			} else if ( empty($inputParams['children_age_start_date']) && !empty($inputParams['children_age_end_date']) ) {
				$where .= ' AND ( person.dateBirth <= "' . $inputParams['children_age_end_date'] . '" ) ';
			} 
		//}
		return $where;
	}
	
	function buildWhereForMothersBirthday($inputParams) {
		$where = '';
		//if (isset($REPORT_CONFIG[$reportId]['input']['mothers_birthday'])) {
			
			if (!empty($inputParams['mother_birthday_start_date'])) {
				$inputParams['mother_birthday_start_date'] = date('Y-m-d', strtotime($inputParams['mother_birthday_start_date']));
			}
			
			if (!empty($inputParams['mother_birthday_end_date'])) {
				$inputParams['mother_birthday_end_date'] = date('Y-m-d', strtotime($inputParams['mother_birthday_end_date']));
			}
			
			if ( !empty($inputParams['mother_birthday_start_date']) && !empty($inputParams['mother_birthday_end_date']) ) {
				$where .= ' AND ( person.dateBirth >= "' . $inputParams['mother_birthday_start_date'] . '" AND person.dateBirth <= "' . $inputParams['mother_birthday_end_date'] . '") ';
			} else if ( !empty($inputParams['mother_birthday_start_date']) && empty($inputParams['mother_birthday_end_date']) ) {
				$where .= ' AND ( person.dateBirth >= "' . $inputParams['mother_birthday_start_date']  . '" ) ';
			} else if ( empty($inputParams['mother_birthday_start_date']) && !empty($inputParams['mother_birthday_end_date']) ) {
				$where .= ' AND ( person.dateBirth <= "' . $inputParams['mother_birthday_end_date'] . '" ) ';
			} 
		//}
		return $where;
	}
	
	function buildWhereForPregnant($inputParams, $origin) {
		$where = '';
		if (!empty($inputParams['pregnant']) && $inputParams['pregnant'] == 'yes') {
			if ($origin == 'checkin') {
				$where .= ' AND ( checkin_family_members.pregnant = "1" ) ';
			} else {
				$where .= ' AND ( person.pregnant = "1" ) ';
			}
		}
		
		return $where;
	}
	
	function buildWhereForFamilyName($inputParams, $person_relation_table) {
		$where = '';
		$idFamily = '';
		if (!empty($inputParams['family_id']) && is_numeric($inputParams['family_id']) ) {
			$idFamily = $inputParams['family_id'];
		}
		
		if (! $idFamily) {
			$userInput = array();
			
			if (isset($inputParams['family_name']) && !empty($inputParams['family_name'])) {
				$userInput = processUserInput($inputParams['family_name']);
			}
			
			if (count($userInput) > 0) {
				if ($userInput['type'] == 'name') {
					$where .= 
						  '	AND (person.firstName like "%' . $userInput['q']['first_name'] . '%"'
						. '	AND person.lastName like "%' . $userInput['q']['last_name'] . '%" ) ';
				} elseif ($userInput['type'] == 'string') {
					$where .= 
						  '	AND ( person.firstName like "%' . $userInput['q'] . '%"'
						. '	OR person.lastName like "%' . $userInput['q'] . '%"'
						. ' OR person.dateBirth like "%' . $userInput['q'] . '%"'
						. ' OR ' . $person_relation_table . '.id_person = "' . $userInput['q'] . '" ) ';
				} elseif ($userInput['type'] == 'date') {
					$where .= 
						  ' AND ( person.dateBirth like "%' . $userInput['q'] . '%" ) ';
				}
			}
		}
		
		return $where;
	}
	
	function buildWhereBasedOnGoals($inputParams, $idFamily) {
		$where = '';
		if (isset($inputParams['goalTypeId'])) {
			if (!empty($inputParams['goalTypeId'])) {
				$where .= 
					" AND ( SELECT id_familyGoal " .
					" 		FROM family_goal " .
					" 		WHERE family_goal.id_family = " . $idFamily;
					
					if (!empty($inputParams['start_date'])) {
						$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
					}
					
					if (!empty($inputParams['end_date'])) {
						$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
					}
					
					if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.target_date >= "' . $inputParams['start_date'] . '" AND family_goal.target_date <= "' . $inputParams['end_date'] . '") ' ;
					} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.target_date <= "' . $inputParams['start_date'] . '") ';
					} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
						$where .= ' AND ( family_goal.target_date >= "' . $inputParams['end_date'] . '") ' ;
					}
				$where .=
					" AND family_goal.id_goalType = " . $inputParams['goalTypeId'];	 
				$where .=
					"		LIMIT 0, 1 " .
					"	)";
			}
		}
		return $where;
	}
	
	function buildWhereBasedOnEncounters($inputParams, $idFamily) {
		$where = '';
		if ( (isset($inputParams['advocacySupport']) && !empty($inputParams['advocacySupport']) ) || isset($inputParams['referrals']) ) {
			
			$where .= 
					" AND ( SELECT id_familyEncounter " .
					" 		FROM family_encounter " .
					" 		WHERE family_encounter.id_family = " . $idFamily;
					//" " .
			if (!empty($inputParams['start_date'])) {
				$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
			}
			
			if (!empty($inputParams['end_date'])) {
				$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
			}
			
			if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$where .= ' AND ( family_encounter.encounter_date >= "' . $inputParams['start_date'] . '" AND family_encounter.encounter_date <= "' . $inputParams['end_date'] . '") ' ;
			} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
				$where .= ' AND ( family_encounter.encounter_date <= "' . $inputParams['start_date'] . '") ';
			} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
				$where .= ' AND ( family_encounter.encounter_date >= "' . $inputParams['end_date'] . '") ' ;
			}
			
			if (isset($inputParams['referrals'])) {
				if ($inputParams['referrals'] == 'on') {
					$where .= " AND family_encounter.referrals_made = '1'";
				}
			}
			
			if ($inputParams['advocacySupport'] != '') {
				$where .= " AND family_encounter.advocacy_support = '" . $inputParams['advocacySupport'] . "'";
			}
					 
			$where .=
				"		LIMIT 0, 1 " .
				"	)";
		}
		return $where;
	}
	
	function buildWhereBasedOnAddress($inputParams, $idFamily) {
		$where = '';
		
		if ( $this->isAddressFieldSelected($inputParams) ) {
			$where .= 
						" AND ( SELECT id_familyAddress " .
						" 		FROM family_address " .
						"		WHERE family_address.id_family = " . $idFamily;
			
			$where .= $this->addressFieldsCondition($inputParams);
			
			$where .=
					"		LIMIT 0, 1 " .
					"	)";
		}
		return $where;
	}
	
	function addressFieldsCondition($inputParams) {
		$where = '';
		if (isset($inputParams['state'])) {
			if (!empty($inputParams['state'])) {
				$where .= ' AND family_address.id_state = ' . $inputParams['state'];
			}
		}
		
		if (isset($inputParams['county'])) {
			if (!empty($inputParams['county'])) {
				$where .= ' AND family_address.id_county = ' . $inputParams['county'];
			}
		}
		
		if (isset($inputParams['cityId'])) {
			if (!empty($inputParams['cityId'])) {
				$where .= ' AND family_address.id_city = ' . $inputParams['cityId'];
			}
		}
		
		if (isset($inputParams['zipcode'])) {
			if (!empty($inputParams['zipcode'])) {
				$where .= ' AND family_address.zip = ' . $inputParams['zipcode'];
			}
		}
		
		if (isset($inputParams['idIncome'])) {
			if (!empty($inputParams['idIncome'])) {
				$where .= ' AND family_address.id_income = ' . $inputParams['idIncome'];
			}
		}
		
		return $where;
	}
	
	function isAddressFieldSelected($inputParams) {
		$return = false;
		
		if (isset($inputParams['state'])) {
			if (!empty($inputParams['state'])) {
				$return = true;
			}
		}
		
		if (isset($inputParams['county'])) {
			if (!empty($inputParams['county'])) {
				$return = true;
			}
		}
		
		if (isset($inputParams['cityId'])) {
			if (!empty($inputParams['cityId'])) {
				$return = true;
			}
		}
		
		if (isset($inputParams['zipcode'])) {
			if (!empty($inputParams['zipcode'])) {
				$return = true;
			}
		}
		
		if (isset($inputParams['idIncome'])) {
			if (!empty($inputParams['idIncome'])) {
				$return = true;
			}
		}
		
		return $return;
	}
	
	function buildLeftJoinsForPerson() {
		
		$array = array();
		
		$select = ', marital_status.maritalStatus, family_structure.familyStructure, employment_status.employmentStatus, ethnicity.ethnicity, education.educationLevels as education';
		
		$join = 
		' LEFT JOIN marital_status ' .
		'	ON person.id_maritalStatus = marital_status.id_maritalStatus ' .
		' LEFT JOIN employment_status ' .
		'	ON person.id_employmentStatus = employment_status.id_employmentStatus ' .
		' LEFT JOIN ethnicity ' .
		'	ON person.id_ethnicity = ethnicity.id_ethnicity ' .
		' LEFT JOIN education ' .
		'	ON person.id_education = education.id_education ' .
		' LEFT JOIN family_structure ' .
		'	ON person.id_familyStructure = family_structure.id_familyStructure  ';
		
		$array['select'] = $select;
		$array['join'] = $join;
		
		return $array;
	}
	
	function checkOriginBasedOnInput($origin) {
		/**
		 * As this block of code is triggerred only in three cases, Query Family, Query Child and Query Parent. 
		 * In all cases, we are using audit trail tables now so selection of date should not be considderred as criteria for deciding origin.  
		 */
		
		/*
		if ($origin != 'family') {
			if ( isset($_REQUEST['start_date']) ) {
				if (!empty($_REQUEST['start_date'])) {
					$origin = 'checkin';
				}
			} 
			
			if ( isset($_REQUEST['end_date']) ) {
				if (!empty($_REQUEST['end_date'])) {
					$origin = 'checkin';
				}
			}
		}
		*/
		if ( isset($_REQUEST['agencyProgramId']) ) {
			if (!empty($_REQUEST['agencyProgramId'])) {
				$origin = 'checkin';
			}
		}
		
		if ( isset($_REQUEST['agencyLocationId']) ) {
			if (!empty($_REQUEST['agencyLocationId'])) {
				$origin = 'checkin';
			}
		}
		
		if ( isset($_REQUEST['agencyTopicId']) ) {
			if (!empty($_REQUEST['agencyTopicId'])) {
				if( $origin == 'family' || $origin == 'child' || $origin == 'parent' ){
					$origin = 'checkin';
				}
			}
		}
		
		if ( isset($_REQUEST['professionalId']) ) {
			if (!empty($_REQUEST['professionalId'])) {
				$origin = 'checkin';
			}
		}
		
		if ( isset($_REQUEST['attended']) ) {
			if (!empty($_REQUEST['attended'])) {
				$origin = 'checkin';
			}
		}
		
		if ( isset($_REQUEST['start_date']) ) {
			if (!empty($_REQUEST['start_date'])) {
				if ($origin == 'family' ) {
					$origin = 'family_audit_trail';
				} else if ($origin == 'child' ) {
					$origin = 'child_audit_trail';
				} else if ($origin == 'parent' ) {
					$origin = 'parent_audit_trail';
				}  
			}
		} 
		
		if ( isset($_REQUEST['end_date']) ) {
			if (!empty($_REQUEST['end_date'])) {
				if ($origin == 'family' ) {
					$origin = 'family_audit_trail';
				} else if ($origin == 'child' ) {
					$origin = 'child_audit_trail';
				} else if ($origin == 'parent' ) {
					$origin = 'parent_audit_trail';
				}
			}
		}
		
		return $origin;
	}
	
	function buildFamilyTypeAuditTrailQuery($inputParams, $family_table = null) {
		$query = null;
		
		if (!$family_table) {
			$family_table = 'family';
		}
		
		if (!empty($inputParams['start_date'])) {
			$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
		}
		
		if (!empty($inputParams['end_date'])) {
			$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
		}
		
		
		$query .= ' ' .
					' ( ' .
					'	SELECT familyType ' .
					' 	FROM family_audit_trail ' .
					'	WHERE family_audit_trail.id_family = ' . $family_table . '.id_family ';
		
		
		
		/*
		if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['start_date'] . ' 00:00:00" AND family_audit_trail.start_date <= "' . $inputParams['end_date'] . ' 23:59:59") ' ;
		} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
			$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['start_date'] . '  00:00:00") ';
		} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['end_date'] . ' 23:59:59") ' ;
		}
		*/
		
		/*
		$start_date = $end_date = null;
		
		
		if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$start_date = $inputParams['start_date'];
			$end_date = $inputParams['end_date'];
			
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . ' 23:59:59" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ) ' ;
		} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
			$start_date = $inputParams['start_date'];
			$end_date = $inputParams['start_date']; // Assigning start_date into end_date as there is no value for end_date passed from input screen
			
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . '  23:59:59" OR family_audit_trail.end_date IS NULL ) ';
		} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$start_date = $inputParams['end_date']; // Assigning end_date into start_date as there is no value for start_date passed from input screen
			$end_date = $inputParams['end_date']; 
			
			//$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ' ;
		}
		
		if ($start_date && $end_date) {
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . ' 23:59:59" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ) ' ;
			$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . '" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . '" OR family_audit_trail.end_date IS NULL) ) ' ;
		}
		*/
		
		$date_condition = $this->buildStartEndDateConditionForAuditTrail($inputParams, 'family_audit_trail');
		
		if($date_condition) {
			$query .= ' AND ' . $date_condition;
		}
		
		$query .=
					'	ORDER BY id_family_audit_trail DESC LIMIT 0, 1 ' .
					' ) ' .
					' ';
		//echo $query;die;
		return $query;
		/*
		$array = array();
		
		$array['select'] = ', family_audit_trail.familyType AS familyTypeAuditTrail';
		$array['from'] = ', family_audit_trail ';
		$array['where'] = ' AND family_audit_trail.id_family = family.id_family';
		
		return $array;
		*/
		
	}
	
	function buildWhereFamilyTypeAuditTrailQuery($inputParams, $family_table = null) {
		$query = null;
		
		if (!$family_table) {
			$family_table = 'family';
		}
		
		if (!empty($inputParams['start_date'])) {
			$inputParams['start_date'] = date('Y-m-d', strtotime($inputParams['start_date']));
		}
		
		if (!empty($inputParams['end_date'])) {
			$inputParams['end_date'] = date('Y-m-d', strtotime($inputParams['end_date']));
		}
		
		
		$query .= ' ' .
					' ( ' .
					'	SELECT id_family_audit_trail ' .
					' 	FROM family_audit_trail ' .
					'	WHERE family_audit_trail.id_family = ' . $family_table . '.id_family ';
		
		
		/*
		if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['start_date'] . ' 00:00:00" AND family_audit_trail.start_date <= "' . $inputParams['end_date'] . ' 23:59:59") ' ;
		} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
			$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['start_date'] . '  00:00:00") ';
		} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['end_date'] . ' 23:59:59") ' ;
		}
		*/
		
		
		$start_date = $end_date = null;
		
		if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			
			$start_date = $inputParams['start_date'];
			$end_date = $inputParams['end_date'];
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . ' 23:59:59" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ) ' ;
		} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
			$start_date = $inputParams['start_date'];
			$end_date = $inputParams['start_date']; // Assigning start_date into end_date as there is no value for end_date passed from input screen
			 
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . '  23:59:59" OR family_audit_trail.end_date IS NULL ) ';
		} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$start_date = $inputParams['end_date']; // Assigning end_date into start_date as there is no value for start_date passed from input screen
			$end_date = $inputParams['end_date']; 
			//$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ' ;
		}
		
		if ($start_date && $end_date) {
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . ' 23:59:59" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ) ' ;
			$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . '" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . '" OR family_audit_trail.end_date IS NULL) ) ' ;
		}
		
		$query .=
					'	ORDER BY id_family_audit_trail DESC LIMIT 0, 1 ' .
					' ) ' .
					' ';
		return $query;
		/*
		$array = array();
		
		$array['select'] = ', family_audit_trail.familyType AS familyTypeAuditTrail';
		$array['from'] = ', family_audit_trail ';
		$array['where'] = ' AND family_audit_trail.id_family = family.id_family';
		
		return $array;
		*/
		
	}
	
	function buildStartEndDateConditionForAuditTrail($inputParams, $table) {
		$start_date = $end_date = null;
		
		$query = '';
		
		if ( !empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			
			$start_date = $inputParams['start_date'];
			$end_date = $inputParams['end_date'];
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . ' 23:59:59" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ) ' ;
		} else if ( !empty($inputParams['start_date']) && empty($inputParams['end_date']) ) {
			$start_date = $inputParams['start_date'];
			$end_date = $inputParams['start_date']; // Assigning start_date into end_date as there is no value for end_date passed from input screen
			 
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . '  23:59:59" OR family_audit_trail.end_date IS NULL ) ';
		} else if ( empty($inputParams['start_date']) && !empty($inputParams['end_date']) ) {
			$start_date = $inputParams['end_date']; // Assigning end_date into start_date as there is no value for start_date passed from input screen
			$end_date = $inputParams['end_date']; 
			//$query .= ' AND ( family_audit_trail.start_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ' ;
		}
		
		if ( $start_date && $end_date ) {
			
			//$query .= ' AND ( family_audit_trail.start_date <= "' . $inputParams['start_date'] . ' 23:59:59" AND (family_audit_trail.end_date >= "' . $inputParams['end_date'] . ' 00:00:00" OR family_audit_trail.end_date IS NULL) ) ' ;
			
			$start_date = date("Y-m-d", strtotime($start_date));
			$end_date = date("Y-m-d", strtotime($end_date));
			//$query .= ' ( ' . $table . '.start_date <= "' . $start_date . '" AND (' . $table . '.end_date >= "' . $end_date . '" OR ' . $table . '.end_date IS NULL) ) ' ;
			
			// Trying new logic
			$query .= ' ( ' . $table . '.start_date <= "' . $end_date . '" ) ' ;
		}
		
		return $query;
	}
	
	function buildLeftJoinsForPersonauditTrail() {
		
		$array = array();
		
		$select = ', marital_status.maritalStatus, family_structure.familyStructure, employment_status.employmentStatus, ethnicity.ethnicity, education.educationLevels as education';
		
		$join = 
		' LEFT JOIN ethnicity ' .
		'	ON person.id_ethnicity = ethnicity.id_ethnicity '
		;
		
		$join_audit_trail = 
		' LEFT JOIN marital_status ' .
		'	ON person_audit_trail.id_maritalStatus = marital_status.id_maritalStatus ' .
		' LEFT JOIN employment_status ' .
		'	ON person_audit_trail.id_employmentStatus = employment_status.id_employmentStatus ' .
		' LEFT JOIN education ' .
		'	ON person_audit_trail.id_education = education.id_education ' .
		' LEFT JOIN family_structure ' .
		'	ON person_audit_trail.id_familyStructure = family_structure.id_familyStructure  '
		;
		
		$array['select'] = $select;
		$array['join'] = $join;
		$array['join_audit_trail'] = $join_audit_trail;
		
		return $array;
	}
	
	function conditionOnPersonRoleForAuditTrail($inputParams, $origin) {
		
		$where = null;
		
		$childIds = '';
		
		$CI =& get_instance();
		$CI->load->model('PersonRoleModel', '', TRUE);
		$childIds = $CI->PersonRoleModel->getChildRoleIds();
		
		if (!empty($childIds)) {
			if ($origin == 'child_audit_trail') {
				$where .= 
					' AND family_person.id_personRole IN (' . $childIds . ')';
			} else if ($origin == 'parent_audit_trail') {
				if (isset($inputParams['personRole'])) {
					if (!empty($inputParams['personRole'])) {
						$where .= 
							' AND family_person.id_personRole = ' . $inputParams['personRole'];
					} else{
						$where .= 
							' AND family_person.id_personRole NOT IN (' . $childIds . ')';
					}
				} else {
					$where .= 
						' AND family_person.id_personRole NOT IN (' . $childIds . ')';
				}
			}
		}
		
		return $where;
	}
}
?>