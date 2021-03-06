<?
// Klassendefinition
class PresenceSimulation extends IPSModule
{

// Der Konstruktor des Moduls
    // Overwrites the standard constructor of IPS
    public function __construct($InstanceID)
    {
        // Don´t delete this line
        parent::__construct($InstanceID);
    }

    // Overwrites the internal IPS_Create($id) function
    public function Create()
    {
        // Don´t delete this line
        parent::Create();

        // Set the Default-Values in the configuration
        $this->RegisterPropertyString("PSS_start_time", "20:00");
        $this->RegisterPropertyString("PSS_end_time", "22:00");
        $this->RegisterPropertyInteger("PSS_tolerance_on", 30);
        $this->RegisterPropertyInteger("PSS_tolerance_off", 30);

        // Create needed Events and Variables
        $this->CreateVariableByIdent($this->InstanceID, "PSS_active", "Anwesenheit simulieren?", 0, "~Switch", "Eyes");
        $this->EnableAction("PSS_active");
        SetValue($this->GetIDForIdent("PSS_active"), false);
        $this->CreateUpdateTimer("SimulationRefresh", 'PSS_SimulationUpdateTimers($_IPS[\'TARGET\']);');
        $this->CreateUpdateTimer("SimulationTimerOn", 'PSS_SimulationTimerOn($_IPS[\'TARGET\']);');
        $this->CreateUpdateTimer("SimulationTimerOff", 'PSS_SimulationTimerOff($_IPS[\'TARGET\']);');

        // Create Category
        $this->CreateCategoryByIdent($this->InstanceID, "PSS_targets", "Targets (Simulation)");
    }

    // Overwrites the internal IPS_ApplyChanges($id) function
    public function ApplyChanges()
    {
        // Don´t delete this line
        parent::ApplyChanges();

    }

    // Get and React on Simulationstatus ON / OFF
    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case "PSS_active":
                $this->SetupSimulation($Value);
                break;
            default:
                throw new Exception("Invalid ident");
        }
    }

    // Setup RefreshTimer and the Values for the SimulationTimerOn and SimulationTimerOff
    public function SetupSimulation(bool $SwitchOn)
    {
        if ($SwitchOn) {

            IPS_SetEventActive($this->GetIDForIdent("SimulationRefresh"), true);
            IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], "Anwesenheits-Simulation ist aktiviert");
            $this->SimulationUpdateTimers();

        } else {

            IPS_SetEventActive($this->GetIDForIdent("SimulationRefresh"), false);
            IPS_SetEventActive($this->GetIDForIdent("SimulationTimerOn"), false);
            IPS_SetEventActive($this->GetIDForIdent("SimulationTimerOff"), false);
            IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], "Anwesenheits-Simulation ist deaktiviert");
        }

        SetValue($this->GetIDForIdent("PSS_active"), $SwitchOn);

    }

    // Daily Refresh and Update of the TimerOn and TimerOff Start-Times
    public function SimulationUpdateTimers()
    {

        $PSS_random_times = $this->CalculateRange();

        IPS_SetEventCyclicTimeFrom($this->GetIDForIdent("SimulationTimerOn"), date("H", $PSS_random_times['PSS_rand_time_on']), date("i", $PSS_random_times['PSS_rand_time_on']), 0);
        IPS_SetEventActive($this->GetIDForIdent("SimulationTimerOn"), true);

        IPS_SetEventCyclicTimeFrom($this->GetIDForIdent("SimulationTimerOff"), date("H", $PSS_random_times['PSS_rand_time_off']), date("i", $PSS_random_times['PSS_rand_time_off']), 0);
        IPS_SetEventActive($this->GetIDForIdent("SimulationTimerOff"), true);

        IPS_SetEventCyclicTimeFrom($this->GetIDForIdent("SimulationRefresh"), date("H", $PSS_random_times['PSS_refresh_time']), date("i", $PSS_random_times['PSS_refresh_time']), 0);

    }

    public function SimulationTimerOn()
    {
        $this->UpdateTargets(true);
        IPS_SetEventActive($this->GetIDForIdent("SimulationTimerOn"), false);

    }

    public function SimulationTimerOff()
    {
        $this->UpdateTargets(false);
        IPS_SetEventActive($this->GetIDForIdent("SimulationTimerOff"), false);

    }

    private function CalculateRange()
    {
        // Calculate the ranges of Time ON
        $PSS_time_on_min = strtotime($this->ReadPropertyString("PSS_start_time")) - $this->ReadPropertyInteger("PSS_tolerance_on") * 60;
        $PSS_time_on_max = strtotime($this->ReadPropertyString("PSS_start_time")) + $this->ReadPropertyInteger("PSS_tolerance_on") * 60;

        // Calculate the ranges of Time OFF
        $PSS_time_off_min = strtotime($this->ReadPropertyString("PSS_end_time")) - $this->ReadPropertyInteger("PSS_tolerance_off") * 60;
        $PSS_time_off_max = strtotime($this->ReadPropertyString("PSS_end_time")) + $this->ReadPropertyInteger("PSS_tolerance_off") * 60;

        // Calculate a random time betwenn the ranges
        $PSS_rand_time_on  = mt_rand($PSS_time_on_min, $PSS_time_on_max);
        $PSS_rand_time_off = mt_rand($PSS_time_off_min, $PSS_time_off_max);

        // Calculate the next RefreshTimer
        $PSS_refresh_time = strtotime($this->ReadPropertyString("PSS_end_time")) + ($this->ReadPropertyInteger("PSS_tolerance_off") * 120);

        //------------------------- Return all calculated Variables -------------------------
        return array(   'PSS_rand_time_on'  => $PSS_rand_time_on,
                        'PSS_rand_time_off' => $PSS_rand_time_off,
                        'PSS_refresh_time'  => $PSS_refresh_time
                    );
    }

    private function UpdateTargets($PSS_status)
    {

        foreach (IPS_GetchildrenIDs(IPS_GetObjectIDByIdent("PSS_targets", $this->InstanceID)) as $childID) {

            if (IPS_LinkExists($childID)) {
                if (IPS_VariableExists(IPS_GetLink($childID)['TargetID'])) {
                    $child    = IPS_GetObject($childID);
                    $objectid = IPS_GetLink($child['ObjectID']);
                }

                if (IPS_InstanceExists(IPS_GetParent($objectid['TargetID']))) {
                    IPS_RequestAction(IPS_GetParent($objectid['TargetID']), IPS_GetObject($objectid['TargetID'])['ObjectIdent'], $PSS_status);
                } elseif (IPS_ScriptExists(IPS_GetParent($objectid['TargetID']))) {
                    IPS_RunScriptWaitEx(IPS_GetParent($objectid['TargetID']), array("VARIABLE" => $objectid['TargetID'], "VALUE" => $PSS_status));
                } elseif (IPS_VariableExists($objectid['TargetID'])) {
                    SetValue($objectid['TargetID'], $PSS_status);
                }
            }
        }
    }

    // INSTALL FUNCTIONS
    private function CreateUpdateTimer($Ident, $Action)
    {
        //search for already available scripts with proper ident
        $event_id = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);

        //properly update eventID
        if ($event_id === false) {
            $event_id = 0;
        } else if (IPS_GetEvent($event_id)['EventType'] != 1) {
            IPS_DeleteEvent($event_id);
            $event_id = 0;
        }
        //we need to create one
        if ($event_id == 0) {
            $event_id = IPS_CreateEvent(1);
            IPS_SetParent($event_id, $this->InstanceID);
            IPS_SetIdent($event_id, $Ident);
            IPS_SetName($event_id, $Ident);
            IPS_SetHidden($event_id, true);
            IPS_SetEventScript($event_id, $Action);
        }

        IPS_SetEventCyclic($event_id, 2, 1, 0, 0, 0, 0);
        IPS_SetEventCyclicTimeFrom($event_id, 0, 0, 1);
        IPS_SetEventActive($event_id, false);
    }

    private function CreateCategoryByIdent($id, $ident, $name)
    {
        $cat_id = @IPS_GetObjectIDByIdent($ident, $id);

        if ($cat_id === false) {
            $cat_id = IPS_CreateCategory();
            IPS_SetParent($cat_id, $id);
            IPS_SetName($cat_id, $name);
            IPS_SetIdent($cat_id, $ident);
        }

        return $cat_id;
    }

    private function CreateVariableByIdent($id, $ident, $name, $type, $profile, $icon)
    {
        $var_id = @IPS_GetObjectIDByIdent($ident, $id);

        if ($var_id === false) {

            $var_id = IPS_CreateVariable($type);
            IPS_SetParent($var_id, $id);
            IPS_SetName($var_id, $name);
            IPS_SetIdent($var_id, $ident);

            if ($profile != "") {
                IPS_SetVariableCustomProfile($var_id, $profile);
            }

            if ($icon != "") {
                IPS_SetIcon($var_id, $icon);
            }

            return $var_id;
        }
    }

    protected function CreateProfile_Boolean($name, $icon, $prefix, $suffix, $minimal_value, $maximum_value, $step_size)
    {
        if (!IPS_VariableProfileExists($name)) {
            IPS_CreateVariableProfile($name, 0);
        } else {
            $profile = IPS_GetVariableProfile($name);
            if ($profile['ProfileType'] != 0) {
                throw new Exception("Profile type does not match for Variable " . $name);
            }
        }

        IPS_SetVariableProfileIcon($name, $icon);
        IPS_SetVariableProfileText($name, $prefix, $suffix);
        IPS_SetVariableProfileValues($name, $minimal_value, $maximum_value, $step_size);

    }
}
