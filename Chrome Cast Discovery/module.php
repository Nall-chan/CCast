<?php

declare(strict_types=1);

eval('declare(strict_types=1);namespace ChromeCastDiscovery {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/DebugHelper.php') . '}');
require_once dirname(__DIR__) . '/libs/Cast.php';
/**
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class ChromeCastDiscovery extends IPSModuleStrict
{
    use \ChromeCastDiscovery\DebugHelper;

    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }
        $Form['actions'][0]['values'] = $this->GetDevices();
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return json_encode($Form);
    }

    private function GetDevices(): array
    {
        $Devices = $this->GetCastDevices();
        $this->SendDebug('Cast Devices', $Devices, 0);
        $IPSDevices = $this->GetIPSInstances();
        $this->SendDebug('IPSDevices', $IPSDevices, 0);
        $Values = [];
        foreach ($Devices as $Device) {
            $InstanceID = false;
            $Host = false;
            foreach ($Device['host'] as $DeviceHost) {
                $InstanceID = array_search(strtolower($DeviceHost), $IPSDevices);
                if ($InstanceID) {
                    $Host = $DeviceHost;
                    break;
                }
            }
            if (!$Host) {
                $Host = array_shift($Device['host']);
            }
            $Values[] = [
                'host'                => $Host,
                'model'               => $Device['model'],
                'name'                => ($InstanceID ? IPS_GetName($InstanceID) : $Device['name']),
                'instanceID'          => ($InstanceID ? $InstanceID : 0),
                'create'              => [
                    [
                        'moduleID'         => \Cast\Device\GUID,
                        'configuration'    => [
                            \Cast\Device\Property::Open        => true,
                            \Cast\Device\Property::Port        => $Device['port']
                        ]
                    ],
                    [
                        'moduleID'         => \Cast\IO\GUID,
                        'configuration'    => [
                            \Cast\IO\Property::Host        => $Host,
                            \Cast\IO\Property::Port        => $Device['port'],
                            \Cast\IO\Property::UseSSL      => true,
                            \Cast\IO\Property::VerifyHost  => false,
                            \Cast\IO\Property::VerifyPeer  => false,

                        ]
                    ]
                ]
            ];
            if ($InstanceID !== false) {
                unset($IPSDevices[$InstanceID]);
            }
        }
        foreach ($IPSDevices as $InstanceID => $Host) {
            $Values[] = [
                'host'               => $Host,
                'name'               => IPS_GetName($InstanceID),
                'instanceID'         => $InstanceID,
            ];
        }
        return $Values;
    }

    private function GetCastDevices(): array
    {
        $mDNSInstanceIDs = IPS_GetInstanceListByModuleID(\Cast\mDNS\GUID);
        $resultServiceTypes = ZC_QueryServiceType($mDNSInstanceIDs[0], '_googlecast._tcp', 'local.');
        if (!$resultServiceTypes) {
            die;
        }
        $this->SendDebug('mDNS resultServiceTypes', $resultServiceTypes, 0);
        $Devices = [];
        foreach ($resultServiceTypes as $device) {
            $CastDevice = [];

            $this->SendDebug('mDNS QueryService', $device['Name'] . ' ' . $device['Type'] . ' ' . $device['Domain'] . '.', 0);
            $deviceInfo = ZC_QueryService($mDNSInstanceIDs[0], $device['Name'], '_googlecast._tcp', 'local.');
            $this->SendDebug('mDNS QueryService Result', $deviceInfo, 0);
            if (empty($deviceInfo)) {
                continue;
            }
            $CastDevice['Port'] = $deviceInfo[0]['Port'];

            foreach ($deviceInfo[0]['TXTRecords'] as $Line) {
                $Data = explode('=', $Line);
                $Typ = strtoupper(array_shift($Data));
                if (self::filterTXT($Typ)) {
                    $CastDevice[$Typ] = implode('=', $Data);
                }
            }

            if (empty($deviceInfo[0]['IPv4'])) { //IPv4 und IPv6 sind vertauscht
                $CastDevice['IPv4'] = $deviceInfo[0]['IPv6'];
            } else {
                $CastDevice['IPv4'] = $deviceInfo[0]['IPv4'];
                if (isset($deviceInfo[0]['IPv6'])) {
                    foreach ($deviceInfo[0]['IPv6'] as $Index => $ipv6) {
                        $CastDevice['IPv6'][] = '[' . $ipv6 . ']';
                        $Hostname = gethostbyaddr($ipv6);
                        if ($Hostname != $ipv6) {
                            $CastDevice['Hostname'][$Index] = $Hostname;
                        }
                        $CastDevice['Hostname'][20 + $Index] = '[' . $ipv6 . ']';
                    }
                }
            }
            foreach ($CastDevice['IPv4'] as $Index => $ipv4) {
                $Hostname = gethostbyaddr($ipv4);
                if ($Hostname != $ipv4) {
                    $CastDevice['Hostname'][10 + $Index] = $Hostname;
                }
                $CastDevice['Hostname'][((strpos($ipv4, '169.254') === 0) ? 10 : 0) + 30 + $Index] = $ipv4;
            }
            ksort($CastDevice['Hostname']);
            $this->SendDebug('Device', $CastDevice, 0);
            array_push($Devices, ['name' => (isset($CastDevice['Name']) ? $CastDevice['Name'] : 'Cast Device(' . $CastDevice['Hostname'][0] . ')'), 'model' => (isset($CastDevice['ModelName']) ? $CastDevice['ModelName'] : 'unknown'), 'port'=>$CastDevice['Port'], 'host'=>$CastDevice['Hostname']]);
        }
        return $Devices;
    }

    private static function FilterTXT(string &$Typ): bool
    {
        switch ($Typ) {
            case 'ID':
                $Typ = 'DeviceId';
                return true;
            case 'MD':
                $Typ = 'ModelName';
                return true;
            case 'FN':
                $Typ = 'Name';
                return true;
        }
        return false;
    }

    private function GetIPSInstances(): array
    {
        $InstanceIDList = IPS_GetInstanceListByModuleID(\Cast\Device\GUID);
        $Devices = [];
        foreach ($InstanceIDList as $InstanceID) {
            $IO = IPS_GetInstance($InstanceID)['ConnectionID'];
            if ($IO > 0) {
                $parentGUID = IPS_GetInstance($IO)['ModuleInfo']['ModuleID'];
                if ($parentGUID == \Cast\IO\GUID) {
                    $Devices[$InstanceID] = strtolower(IPS_GetProperty($IO, \Cast\IO\Property::Host));
                }
            }
        }
        return $Devices;
    }
}
