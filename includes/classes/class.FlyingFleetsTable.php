<?php

/**
 *  2Moons
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package   2Moons
 * @author    Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence   MIT
 * @version   1.8.0
 * @link      https://github.com/jkroepke/2Moons
 */

class FlyingFleetsTable
{
    protected $Mode      = NULL;
    protected $userId    = NULL;
    protected $planetId  = NULL;
    protected $IsPhalanx = FALSE;
    protected $missions  = FALSE;

    public function __construct()
    {

    }

    public function setUser($userId)
    {
        $this->userId = $userId;
    }

    public function setPlanet($planetId)
    {
        $this->planetId = $planetId;
    }

    public function setPhalanxMode()
    {
        $this->IsPhalanx = TRUE;
    }

    public function setMissions($missions)
    {
        $this->missions = implode(',', array_filter(explode(',', $missions), 'is_numeric'));
    }

    public function renderTable()
    {
        $fleetResult = $this->getFleets();
        $ACSDone     = [];
        $FleetData   = [];

        if(!empty($fleetResult)) {
            foreach($fleetResult as $fleetRow) {

                if($fleetRow['fleet_mess'] == 0 && $fleetRow['fleet_start_time'] > TIMESTAMP && ($fleetRow['fleet_group'] == 0 || !isset($ACSDone[$fleetRow['fleet_group']]))) {
                    $ACSDone[$fleetRow['fleet_group']]                                = TRUE;
                    $FleetData[$fleetRow['fleet_start_time'] . $fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 0);
                }

                if($fleetRow['fleet_mission'] == 10 || ($fleetRow['fleet_mission'] == 4 && $fleetRow['fleet_mess'] == 0)) {
                    continue;
                }

                if($fleetRow['fleet_end_stay'] != $fleetRow['fleet_start_time'] && $fleetRow['fleet_end_stay'] > TIMESTAMP && ($this->IsPhalanx && $fleetRow['fleet_end_id'] == $this->planetId)) {
                    $FleetData[$fleetRow['fleet_end_stay'] . $fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 2);
                }

                $MissionsOK = 5;
                if($fleetRow['fleet_end_stay'] > TIMESTAMP && $fleetRow['fleet_mission'] == $MissionsOK) {

                    $FleetData[$fleetRow['fleet_end_stay'] . $fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 2);
                }
                if($fleetRow['fleet_owner'] != $this->userId) {
                    continue;
                }

                //Rückkehr der Flotte
                if($fleetRow['fleet_end_time'] > TIMESTAMP) {
                    $FleetData[$fleetRow['fleet_end_time'] . $fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 1);
                }

            }
            #$FleetData[$fleetRow['fleet_end_time'] . $fleetRow['fleet_id']]['fleetId'] = $fleetRow['fleet_id'];
        }

        ksort($FleetData);

        return $FleetData;
    }

    private function getFleets($acsID = FALSE)
    {
        if($this->IsPhalanx) {
            $where = '(fleet_start_id = :planetId AND fleet_start_type = 1 AND fleet_mission != 4) OR
					  (fleet_end_id = :planetId AND fleet_end_type = 1 AND fleet_mess IN (0, 2))';

            $param = [
                ':planetId' => $this->planetId,
            ];
        } elseif(!empty($acsID)) {
            $where = 'fleet_group = :acsId';
            $param = [
                ':acsId' => $acsID,
            ];
        } elseif($this->missions) {
            $where = '(fleet_owner = :userId OR fleet_target_owner = :userId) AND fleet_mission IN (' . $this->missions . ')';
            $param = [
                ':userId' => $this->userId,
            ];
        } else {
            $where = 'fleet_owner = :userId OR (fleet_target_owner = :userId AND fleet_mission != 8)';
            $param = [
                ':userId' => $this->userId,
            ];
        }

        $sql = 'SELECT DISTINCT fleet.*, ownuser.username as own_username, targetuser.username as target_username,
		ownplanet.name as own_planetname, targetplanet.name as target_planetname
		FROM %%FLEETS%% fleet
		LEFT JOIN %%USERS%% ownuser ON (ownuser.id = fleet.fleet_owner)
		LEFT JOIN %%USERS%% targetuser ON (targetuser.id = fleet.fleet_target_owner)
		LEFT JOIN %%PLANETS%% ownplanet ON (ownplanet.id = fleet.fleet_start_id)
		LEFT JOIN %%PLANETS%% targetplanet ON (targetplanet.id = fleet.fleet_end_id)
		WHERE ' . $where . ';';

        return Database::get()->select($sql, $param);
    }

    private function BuildFleetEventTable($fleetRow, $FleetState)
    {
        $Time = 0;
        $Rest = 0;

        // FleetState 0 = Start
        // FleetState 1 = Rückkehr

        if($FleetState == 0 && !$this->IsPhalanx && $fleetRow['fleet_group'] != 0) {
            $acsResult   = $this->getFleets($fleetRow['fleet_group']);
            $EventString = '';
            foreach($acsResult as $acsRow) {
                $Return = $this->getEventData($acsRow, $FleetState);

                $Rest        = $Return[0];
                $EventString .= $Return[1] . '<br><br>';
                $Time        = $Return[2];
            }

            $EventString = substr($EventString, 0, -8);
        } else {

            [$Rest, $EventString, $Time] = $this->getEventData($fleetRow, $FleetState);
        }
        return [
            'text'       => $EventString,
            'returntime' => $Time,
            'resttime'   => $Rest,
            'fleetState' => $FleetState,
            'fleetId' => $fleetRow['fleet_id'],
        ];
    }

    public function getEventData($fleetRow, $Status)
    {
        global $LNG;
        $Owner      = $fleetRow['fleet_owner'] == $this->userId;
        $FleetStyle = [
            1  => 'attack',
            2  => 'federation',
            3  => 'transport',
            4  => 'deploy',
            5  => 'hold',
            6  => 'espionage',
            7  => 'colony',
            8  => 'harvest',
            9  => 'destroy',
            10 => 'missile',
            11 => 'transport',
            15 => 'transport',
        ];

        $GoodMissions = [3, 5];
        $MissionType  = $fleetRow['fleet_mission'];

        $FleetPrefix   = ($Owner == TRUE) ? 'own' : '';
        $FleetType     = $FleetPrefix . $FleetStyle[$MissionType];
        $FleetName     = (!$Owner && ($MissionType == 1 || $MissionType == 2) && $Status == FLEET_OUTWARD && $fleetRow['fleet_target_owner'] != $this->userId) ? $LNG['cff_acs_fleet'] : $LNG['ov_fleet'];
        $FleetContent  = $this->CreateFleetPopupedFleetLink($fleetRow, $FleetName, $FleetPrefix . $FleetStyle[$MissionType]);
        $FleetCapacity = $this->CreateFleetPopupedMissionLink($fleetRow, $LNG['type_mission_' . $MissionType], $FleetPrefix . $FleetStyle[$MissionType]);
        $FleetStatus   = [0 => 'flight', 1 => 'return', 2 => 'holding'];
        $StartType     = $LNG['type_planet_' . $fleetRow['fleet_start_type']];
        $TargetType    = $LNG['type_planet_' . $fleetRow['fleet_end_type']];

        if($MissionType == 8) {
            if($Status == FLEET_OUTWARD)
                $EventString = sprintf($LNG['cff_mission_own_recy_0'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
            else
                $EventString = sprintf($LNG['cff_mission_own_recy_1'], $FleetContent, GetTargetAddressLink($fleetRow, $FleetType), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $FleetCapacity);
        } elseif($MissionType == 10) {
            if($Owner)
                $EventString = sprintf($LNG['cff_mission_own_mip'], $fleetRow['fleet_amount'], $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType));
            else
                $EventString = sprintf($LNG['cff_mission_target_mip'], $fleetRow['fleet_amount'], $this->BuildHostileFleetPlayerLink($fleetRow), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType));
        } elseif($MissionType == 11 || $MissionType == 15) {
            if($Status == FLEET_OUTWARD)
                $EventString = sprintf($LNG['cff_mission_own_expo_0'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
            elseif($Status == FLEET_HOLD)
                $EventString = sprintf($LNG['cff_mission_own_expo_2'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
            else
                $EventString = sprintf($LNG['cff_mission_own_expo_1'], $FleetContent, GetTargetAddressLink($fleetRow, $FleetType), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $FleetCapacity);
        } else {
            if($Owner == TRUE) {
                if($Status == FLEET_OUTWARD) {
                    if(!$Owner && ($MissionType == 1 || $MissionType == 2))
                        $Message = $LNG['cff_mission_acs'];
                    else
                        $Message = $LNG['cff_mission_own_0'];

                    $EventString = sprintf($Message, $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
                } elseif($Status == FLEET_RETURN)
                    $EventString = sprintf($LNG['cff_mission_own_1'], $FleetContent, $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $FleetCapacity);
                else
                    $EventString = sprintf($LNG['cff_mission_own_2'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
            } else {
                if($Status == FLEET_HOLD)
                    $Message = $LNG['cff_mission_target_stay'];
                elseif(in_array($MissionType, $GoodMissions))
                    $Message = $LNG['cff_mission_target_good'];
                else
                    $Message = $LNG['cff_mission_target_bad'];

                $EventString = sprintf($Message, $FleetContent, $this->BuildHostileFleetPlayerLink($fleetRow), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
            }
        }
        $EventString = '<div class="' . $FleetStatus[$Status] . ' ' . $FleetType . '">' . $EventString . '</div>';

        if($Status == FLEET_OUTWARD)
            $Time = $fleetRow['fleet_start_time'];
        elseif($Status == FLEET_RETURN)
            $Time = $fleetRow['fleet_end_time'];
        elseif($Status == FLEET_HOLD)
            $Time = $fleetRow['fleet_end_stay'];
        else
            $Time = TIMESTAMP;



        $Rest = $Time - TIMESTAMP;
        return [$Rest, $EventString, $Time];
    }

    private function CreateFleetPopupedFleetLink($fleetRow, $Text, $FleetType)
    {
        global $LNG, $USER, $resource;
        $SpyTech      = $USER[$resource[106]];
        $Owner        = $fleetRow['fleet_owner'] == $this->userId;
        $FleetRec     = explode(';', $fleetRow['fleet_array']);
        $FleetPopup   = '<a href="#" data-tooltip-content="<div class=\'col-lg-12 ' . $FleetType . '\'>';
        $textForBlind = '';

        $m  = ($fleetRow['fleet_resource_metal'] <= 0) ? "" : "<div class='row'><div class='col-lg-6'>" . $LNG["rs_metal"] . ":</div><div class='col-lg-6'>" . number_format($fleetRow['fleet_resource_metal'], 0, ',', '.') . "</div></div>";
        $c  = ($fleetRow['fleet_resource_crystal'] <= 0) ? "" : "<div class='row'><div class='col-lg-6'>" . $LNG["rs_crystal"] . ":</div><div class='col-lg-6'>" . number_format($fleetRow['fleet_resource_crystal'], 0, ',', '.') . "</div></div>";
        $d  = ($fleetRow['fleet_resource_deuterium'] <= 0) ? "" : "<div class='row'><div class='col-lg-6'>" . $LNG["rs_deuterium"] . ":</div><div class='col-lg-6'>" . number_format($fleetRow['fleet_resource_deuterium'], 0, ',', '.') . "</div></div>";
        $dm = ($fleetRow['fleet_resource_darkmatter'] <= 0) ? "" : "<div class='row'><div class='col-lg-6'>" . $LNG["rs_darkmatter"] . ":</div><div class='col-lg-6'>" . number_format($fleetRow['fleet_resource_darkmatter'], 0, ',', '.') . "</div></div>";

        $resDiv = "<div class='col-lg-12' style='margin-top: 15px;'>" . $m . "" . $c . "" . $d . "" . $dm;

        if($this->IsPhalanx || $SpyTech >= 4 || $Owner) {

            if($SpyTech < 8 && !$Owner) {
                $FleetPopup   .= '<div class=\'row\'><div class=\'col-lg-6\'>' . $LNG['cff_aproaching'] . $fleetRow['fleet_amount'] . $LNG['cff_ships'] . ':</div></div>';
                $textForBlind = $LNG['cff_aproaching'] . $fleetRow['fleet_amount'] . $LNG['cff_ships'] . ': ';
            }
            $shipsData = [];

            foreach($FleetRec as $Item => $Group) {

                if(empty($Group))
                    continue;

                $Ship = explode(',', $Group);
                if($Owner) {
                    $FleetPopup  .= '<div class=\'row\'><div class=\'col-lg-6\'>' . $LNG['tech'][$Ship[0]] . ':</div><div class=\'col-lg-2\'>' . pretty_number($Ship[1]) . '</div></div>';
                    $shipsData[] = floatToString($Ship[1]) . ' ' . $LNG['tech'][$Ship[0]];
                } else {
                    if($SpyTech >= 8) {
                        $FleetPopup  .= '<div class=\'row\'><div class=\'col-lg-6\'>' . $LNG['tech'][$Ship[0]] . ':</div><div class=\'col-lg-2\'>' . pretty_number($Ship[1]) . '</div></div>';
                        $shipsData[] = floatToString($Ship[1]) . ' ' . $LNG['tech'][$Ship[0]];
                    } else {
                        $FleetPopup  .= '<div class=\'row\'><div class=\'col-lg-6\'>' . $LNG['tech'][$Ship[0]] . '</div></div>';
                        $shipsData[] = $LNG['tech'][$Ship[0]];
                    }
                }
            }
            $textForBlind .= implode('; ', $shipsData);
        } else {
            $FleetPopup   .= '<div class=\'row\'><div class=\'col-lg-6\'>' . $LNG['cff_no_fleet_data'] . '</div></div>';
            $textForBlind = $LNG['cff_no_fleet_data'];
        }

        $FleetPopup .= '</div>' . $resDiv . ' " id="' . hash('SHA1', rand(9999, 9999999999)) . '" class="ttip ' . $FleetType . '">' . $Text . '</a><div class="textForBlind"> (' . $textForBlind . ')</div>';

        return $FleetPopup;
    }

    private function CreateFleetPopupedMissionLink($fleetRow, $Texte, $FleetType)
    {
        global $LNG;
        $FleetTotalC = $fleetRow['fleet_resource_metal'] + $fleetRow['fleet_resource_crystal'] + $fleetRow['fleet_resource_deuterium'] + $fleetRow['fleet_resource_darkmatter'];
        if($FleetTotalC != 0) {
            $textForBlind = $LNG['tech'][900] . ': ';
            $textForBlind .= floatToString($fleetRow['fleet_resource_metal']) . ' ' . $LNG['tech'][901];
            $textForBlind .= '; ' . floatToString($fleetRow['fleet_resource_crystal']) . ' ' . $LNG['tech'][902];
            $textForBlind .= '; ' . floatToString($fleetRow['fleet_resource_deuterium']) . ' ' . $LNG['tech'][903];
            if($fleetRow['fleet_resource_darkmatter'] > 0)
                $textForBlind .= '; ' . floatToString($fleetRow['fleet_resource_darkmatter']) . ' ' . $LNG['tech'][921];

            $FRessource = '<table >';
            $FRessource .= '<tr><td>' . $LNG['tech'][901] . '</td><td>' . pretty_number($fleetRow['fleet_resource_metal']) . '</td></tr>';
            $FRessource .= '<tr><td>' . $LNG['tech'][902] . '</td><td>' . pretty_number($fleetRow['fleet_resource_crystal']) . '</td></tr>';
            $FRessource .= '<tr><td>' . $LNG['tech'][903] . '</td><td>' . pretty_number($fleetRow['fleet_resource_deuterium']) . '</td></tr>';
            if($fleetRow['fleet_resource_darkmatter'] > 0)
                $FRessource .= '<tr><td>' . $LNG['tech'][921] . '</td><td style=\'width:50%;color:white\'>' . pretty_number($fleetRow['fleet_resource_darkmatter']) . '</td></tr>';
            $FRessource .= '</table>';

            $MissionPopup = '<span data-tooltip-content="' . $FRessource . '" class="' . $FleetType . ' ' . lcfirst($Texte) . '">' . $Texte . '</span><span class="textForBlind"> (' . $textForBlind . ')</span>';
        } else {
            $MissionPopup = $Texte;
        }

        return $MissionPopup;
    }

    private function BuildHostileFleetPlayerLink($fleetRow)
    {
        global $LNG;
        return $fleetRow['own_username'] . ' <a href="#" onclick="return Dialog.PM(' . $fleetRow['fleet_owner'] . ')">' . $LNG['PM'] . '</a>';
    }
}


/*class FlyingFleetsTable
{
	protected $Mode = null;
	protected $userId	= null;
	protected $planetId = null;
	protected $IsPhalanx = false;
	protected $missions = false;

	public function __construct() {
		
	}

	public function setUser($userId) {
		$this->userId = $userId;
	}

	public function setPlanet($planetId) {
		$this->planetId = $planetId;
	}

	public function setPhalanxMode() {
		$this->IsPhalanx = true;
	}

	public function setMissions($missions) {
		$this->missions = implode(',', array_filter(explode(',', $missions), 'is_numeric'));
	}
	
	private function getFleets($acsID = false) {
		if($this->IsPhalanx) {
			$where = '(fleet_start_id = :planetId AND fleet_start_type = 1 AND fleet_mission != 4) OR
					  (fleet_end_id = :planetId AND fleet_end_type = 1 AND fleet_mess IN (0, 2))';

			$param = array(
				':planetId'	  => $this->planetId
			);
		} elseif(!empty($acsID)) {
			$where	= 'fleet_group = :acsId';
			$param = array(
				':acsId'	=> $acsID
			);
		} elseif($this->missions) {
			$where = '(fleet_owner = :userId OR fleet_target_owner = :userId) AND fleet_mission IN ('.$this->missions.')';
			$param = array(
				':userId'	=> $this->userId
			);
		} else {
			$where  = 'fleet_owner = :userId OR (fleet_target_owner = :userId AND fleet_mission != 8)';
			$param = array(
				':userId'	=> $this->userId,
			);
		}

		$sql = 'SELECT DISTINCT fleet.*, ownuser.username as own_username, targetuser.username as target_username,
		ownplanet.name as own_planetname, targetplanet.name as target_planetname
		FROM %%FLEETS%% fleet
		LEFT JOIN %%USERS%% ownuser ON (ownuser.id = fleet.fleet_owner)
		LEFT JOIN %%USERS%% targetuser ON (targetuser.id = fleet.fleet_target_owner)
		LEFT JOIN %%PLANETS%% ownplanet ON (ownplanet.id = fleet.fleet_start_id)
		LEFT JOIN %%PLANETS%% targetplanet ON (targetplanet.id = fleet.fleet_end_id)
		WHERE '.$where.';';

		return Database::get()->select($sql, $param);
	}

	public function renderTable()
	{
		$fleetResult	= $this->getFleets();
		$ACSDone		= array();
		$FleetData		= array();
		
		foreach($fleetResult as $fleetRow)
		{
			if ($fleetRow['fleet_mess'] == 0 && $fleetRow['fleet_start_time'] > TIMESTAMP && ($fleetRow['fleet_group'] == 0 || !isset($ACSDone[$fleetRow['fleet_group']])))
			{
				$ACSDone[$fleetRow['fleet_group']]		= true;
				$FleetData[$fleetRow['fleet_start_time'].$fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 0);
			}
				
			if ($fleetRow['fleet_mission'] == 10 || ($fleetRow['fleet_mission'] == 4 && $fleetRow['fleet_mess'] == 0))
				continue;
				
			if ($fleetRow['fleet_end_stay'] != $fleetRow['fleet_start_time'] && $fleetRow['fleet_end_stay'] > TIMESTAMP && ($this->IsPhalanx && $fleetRow['fleet_end_id'] == $this->planetId))
				$FleetData[$fleetRow['fleet_end_stay'].$fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 2);
			
			$MissionsOK = 5;
			if ($fleetRow['fleet_end_stay'] > TIMESTAMP && $fleetRow['fleet_mission'] == $MissionsOK )
			$FleetData[$fleetRow['fleet_end_stay'].$fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 2);
				
			if ($fleetRow['fleet_owner'] != $this->userId)
				continue;
		
			if ($fleetRow['fleet_end_time'] > TIMESTAMP)
				$FleetData[$fleetRow['fleet_end_time'].$fleetRow['fleet_id']] = $this->BuildFleetEventTable($fleetRow, 1);
		}
		
		ksort($FleetData);
		return $FleetData;
	}

	private function BuildFleetEventTable($fleetRow, $FleetState)
	{
		$Time	= 0;
		$Rest	= 0;

		if ($FleetState == 0 && !$this->IsPhalanx && $fleetRow['fleet_group'] != 0)
		{
			$acsResult		= $this->getFleets($fleetRow['fleet_group']);
			$EventString	= '';

			foreach($acsResult as $acsRow)
			{
				$Return			= $this->getEventData($acsRow, $FleetState);
					
				$Rest			= $Return[0];
				$EventString    .= $Return[1].'<br><br>';
				$Time			= $Return[2];
			}

			$EventString	= substr($EventString, 0, -8);
		}
		else
		{
			list($Rest, $EventString, $Time) = $this->getEventData($fleetRow, $FleetState);
		}
		
		return array(
			'text'			=> $EventString,
			'returntime'	=> $Time,
			'resttime'		=> $Rest
		);
	}
	
	public function getEventData($fleetRow, $Status)
	{
		global $LNG;
		$Owner			= $fleetRow['fleet_owner'] == $this->userId;
		$FleetStyle  = array (
			1 => 'attack',
			2 => 'federation',
			3 => 'transport',
			4 => 'deploy',
			5 => 'hold',
			6 => 'espionage',
			7 => 'colony',
			8 => 'harvest',
			9 => 'destroy',
			10 => 'missile',
			11 => 'transport',
			15 => 'transport',
		);
		
	    $GoodMissions	= array(3, 5);
		$MissionType    = $fleetRow['fleet_mission'];

		$FleetPrefix    = ($Owner == true) ? 'own' : '';
		$FleetType		= $FleetPrefix.$FleetStyle[$MissionType];
		$FleetName		= (!$Owner && ($MissionType == 1 || $MissionType == 2) && $Status == FLEET_OUTWARD && $fleetRow['fleet_target_owner'] != $this->userId) ? $LNG['cff_acs_fleet'] : $LNG['ov_fleet'];
		$FleetContent   = $this->CreateFleetPopupedFleetLink($fleetRow, $FleetName, $FleetPrefix.$FleetStyle[$MissionType]);
		$FleetCapacity  = $this->CreateFleetPopupedMissionLink($fleetRow, $LNG['type_mission_'.$MissionType], $FleetPrefix.$FleetStyle[$MissionType]);
		$FleetStatus    = array(0 => 'flight', 1 => 'return' , 2 => 'holding');
		$StartType		= $LNG['type_planet_'.$fleetRow['fleet_start_type']];
		$TargetType		= $LNG['type_planet_'.$fleetRow['fleet_end_type']];
	
		if ($MissionType == 8) {
			if ($Status == FLEET_OUTWARD)
				$EventString = sprintf($LNG['cff_mission_own_recy_0'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
			else
				$EventString = sprintf($LNG['cff_mission_own_recy_1'], $FleetContent, GetTargetAddressLink($fleetRow, $FleetType), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $FleetCapacity);
		} elseif ($MissionType == 10) {
			if ($Owner)
				$EventString = sprintf($LNG['cff_mission_own_mip'], $fleetRow['fleet_amount'], $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType));
			else
				$EventString = sprintf($LNG['cff_mission_target_mip'], $fleetRow['fleet_amount'], $this->BuildHostileFleetPlayerLink($fleetRow), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType));
		} elseif ($MissionType == 11 || $MissionType == 15) {		
			if ($Status == FLEET_OUTWARD)
				$EventString = sprintf($LNG['cff_mission_own_expo_0'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
			elseif ($Status == FLEET_HOLD)
				$EventString = sprintf($LNG['cff_mission_own_expo_2'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
			else
				$EventString = sprintf($LNG['cff_mission_own_expo_1'], $FleetContent, GetTargetAddressLink($fleetRow, $FleetType), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $FleetCapacity);
		} else {
			if ($Owner == true) {
				if ($Status == FLEET_OUTWARD) {
					if (!$Owner && ($MissionType == 1 || $MissionType == 2))
						$Message  = $LNG['cff_mission_acs']	;
					else
						$Message  = $LNG['cff_mission_own_0'];
						
					$EventString  = sprintf($Message, $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
				} elseif($Status == FLEET_RETURN)
					$EventString  = sprintf($LNG['cff_mission_own_1'], $FleetContent, $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $FleetCapacity);
				else
					$EventString  = sprintf($LNG['cff_mission_own_2'], $FleetContent, $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
			} else {
				if ($Status == FLEET_HOLD)
					$Message	= $LNG['cff_mission_target_stay'];
				elseif(in_array($MissionType, $GoodMissions))
					$Message	= $LNG['cff_mission_target_good'];
				else
					$Message	= $LNG['cff_mission_target_bad'];

				$EventString	= sprintf($Message, $FleetContent, $this->BuildHostileFleetPlayerLink($fleetRow), $StartType, $fleetRow['own_planetname'], GetStartAddressLink($fleetRow, $FleetType), $TargetType, $fleetRow['target_planetname'], GetTargetAddressLink($fleetRow, $FleetType), $FleetCapacity);
			}		       
		}
		$EventString = '<div class="'.$FleetStatus[$Status].' '.$FleetType.'">'.$EventString.'</div>';

		if ($Status == FLEET_OUTWARD)
			$Time = $fleetRow['fleet_start_time'];
		elseif ($Status == FLEET_RETURN)
			$Time = $fleetRow['fleet_end_time'];
		elseif ($Status == FLEET_HOLD)
			$Time = $fleetRow['fleet_end_stay'];
		else
			$Time = TIMESTAMP;

		$Rest	= $Time - TIMESTAMP;
		return array($Rest, $EventString, $Time);
	}

	private function BuildHostileFleetPlayerLink($fleetRow)
    {
		global $LNG;
		return $fleetRow['own_username'].' <a href="#" onclick="return Dialog.PM('.$fleetRow['fleet_owner'].')">'.$LNG['PM'].'</a>';
	}

	private function CreateFleetPopupedMissionLink($fleetRow, $Texte, $FleetType)
	{
		global $LNG;
		$FleetTotalC  = $fleetRow['fleet_resource_metal'] + $fleetRow['fleet_resource_crystal'] + $fleetRow['fleet_resource_deuterium'] + $fleetRow['fleet_resource_darkmatter'];
		if ($FleetTotalC != 0)
		{
			$textForBlind = $LNG['tech'][900].': ';
			$textForBlind .= floatToString($fleetRow['fleet_resource_metal']).' '.$LNG['tech'][901];
			$textForBlind .= '; '.floatToString($fleetRow['fleet_resource_crystal']).' '.$LNG['tech'][902];
			$textForBlind .= '; '.floatToString($fleetRow['fleet_resource_deuterium']).' '.$LNG['tech'][903];
			if($fleetRow['fleet_resource_darkmatter'] > 0)
				$textForBlind .= '; '.floatToString($fleetRow['fleet_resource_darkmatter']).' '.$LNG['tech'][921];
			
			$FRessource   = '<table style=\'width:200px\'>';
			$FRessource  .= '<tr><td style=\'width:50%;color:white\'>'.$LNG['tech'][901].'</td><td style=\'width:50%;color:white\'>'. pretty_number($fleetRow['fleet_resource_metal']).'</td></tr>';
			$FRessource  .= '<tr><td style=\'width:50%;color:white\'>'.$LNG['tech'][902].'</td><td style=\'width:50%;color:white\'>'. pretty_number($fleetRow['fleet_resource_crystal']).'</td></tr>';
			$FRessource  .= '<tr><td style=\'width:50%;color:white\'>'.$LNG['tech'][903].'</td><td style=\'width:50%;color:white\'>'. pretty_number($fleetRow['fleet_resource_deuterium']).'</td></tr>';
			if($fleetRow['fleet_resource_darkmatter'] > 0)
				$FRessource  .= '<tr><td style=\'width:50%;color:white\'>'.$LNG['tech'][921].'</td><td style=\'width:50%;color:white\'>'. pretty_number($fleetRow['fleet_resource_darkmatter']).'</td></tr>';
			$FRessource  .= '</table>';
			
			$MissionPopup  = '<a data-tooltip-content="'.$FRessource.'" class="tooltip '.$FleetType.'">'.$Texte.'</a><span class="textForBlind"> ('.$textForBlind.')</span>';
		}
		else
			$MissionPopup  = $Texte;

		return $MissionPopup;
	}

	private function CreateFleetPopupedFleetLink($fleetRow, $Text, $FleetType)
	{
		global $LNG, $USER, $resource;
		$SpyTech		= $USER[$resource[106]];
		$Owner			= $fleetRow['fleet_owner'] == $this->userId;
		$FleetRec		= explode(';', $fleetRow['fleet_array']);
		$FleetPopup		= '<div class="col-lg-12"><a href="#" data-tooltip-content="<div class=\'col-lg-12\'>';
		$textForBlind	= '';


		if ($this->IsPhalanx || $SpyTech >= 4 || $Owner){
			
			if($SpyTech < 8 && !$Owner)
			{
				$FleetPopup		.= '<div class=\'row\'>'.$LNG['cff_aproaching'].$fleetRow['fleet_amount'].$LNG['cff_ships'].':</div>';
				$textForBlind	= $LNG['cff_aproaching'].$fleetRow['fleet_amount'].$LNG['cff_ships'].': ';
			}
			$shipsData	= array();
			foreach($FleetRec as $Item => $Group)
			{
				if (empty($Group))
					continue;
					
				$Ship    = explode(',', $Group);
				if($Owner)
                {
					$FleetPopup 	.= '<div class=\'row\'>
                                            <div class=\'col-lg-9\'>'.$LNG['tech'][$Ship[0]].':</div>
                                            <div class=\'col-lg-3\'>'.pretty_number($Ship[1]).'</div>
                                        </div>';
                    $shipsData[]	= floatToString($Ship[1]).' '.$LNG['tech'][$Ship[0]];
				}
                else
                {
					if($SpyTech >= 8)
					{
                        $FleetPopup 	.= '<div class=\'row\'>
                                                <div class=\'col-lg-9\'>'.$LNG['tech'][$Ship[0]].':</div>
                                                <div class=\'col-lg-3\'>'.pretty_number($Ship[1]).'</div>
                                            </div>';
						$shipsData[]	= floatToString($Ship[1]).' '.$LNG['tech'][$Ship[0]];
					}
					else
					{
                        $FleetPopup 	.= '<div class=\'row\'>
                                                <div class=\'col-lg-12\'>'.$LNG['tech'][$Ship[0]].'</div>
                                            </div>';
						$shipsData[]	= $LNG['tech'][$Ship[0]];
					}
				}
			}
			$textForBlind	.= implode('; ', $shipsData);
		}else{
			$FleetPopup 	.= '<div>
                                    <div class=\'col-lg-12\'>'.$LNG['cff_no_fleet_data'].'</div>
                                </div>';
			$textForBlind	= $LNG['cff_no_fleet_data'];
		}


		$FleetPopup  .= '</div>" class="tooltip '. $FleetType .'">'. $Text .'</a></div><span class="textForBlind"> ('.$textForBlind.')</span>';

		return $FleetPopup;
	}	
}*/
