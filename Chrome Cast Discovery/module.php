<?php

declare(strict_types=1);

eval('declare(strict_types=1);namespace ChromeCastDiscovery {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/DebugHelper.php') . '}');
require_once dirname(__DIR__) . '/libs/Cast.php';
/**
 * @method bool SendDebug(string $Message, mixed $data, int $Format)
 */
class ChromeCastDiscovery extends IPSModuleStrict
{
    use \ChromeCastDiscovery\DebugHelper;

    public function GetConfigurationForm(): string
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($form);
        }
        $form['actions'][0]['values'] = $this->GetDevices();
        $this->SendDebug('FORM', json_encode($form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return json_encode($form);
    }

    private function GetDevices(): array
    {
        $devices = $this->GetCastDevices();
        $this->SendDebug('Cast Devices', $devices, 0);
        $ipsDevices = $this->GetIPSInstances();
        $this->SendDebug('IPSDevices', $ipsDevices, 0);
        $values = [];
        foreach ($devices as $device) {
            $instanceID = false;
            $host = false;
            foreach ($device['host'] as $deviceHost) {
                $instanceID = array_search(strtolower($deviceHost), $ipsDevices);
                $this->SendDebug('IPSDevice', $instanceID, 0);
                if ($instanceID) {
                    $host = $deviceHost;
                    break;
                }
            }
            if (!$host) {
                $host = array_shift($device['host']);
                $this->SendDebug('Host', $host, 0);
            }
            $values[] = [
                'host'                => $host,
                'model'               => $device['model'],
                'name'                => ($instanceID ? IPS_GetName($instanceID) : $device['name']),
                'instanceID'          => ($instanceID ? $instanceID : 0),
                'create'              => [
                    [
                        'moduleID'         => \Cast\Device\GUID,
                        'configuration'    => [
                            \Cast\Device\Property::OPEN             => true
                        ]
                    ],
                    [
                        'moduleID'         => \Cast\IO\GUID,
                        'configuration'    => [
                            \Cast\IO\Property::HOST         => $host,
                            \Cast\IO\Property::PORT         => $device['port'],
                            \Cast\IO\Property::USE_SSL      => true,
                            \Cast\IO\Property::VERIFY_HOST  => false,
                            \Cast\IO\Property::VERIFY_PEER  => false,

                        ]
                    ]
                ]
            ];
            if ($instanceID !== false) {
                unset($ipsDevices[$instanceID]);
            }
        }
        $this->SendDebug('oldIPSDevices', $ipsDevices, 0);
        foreach ($ipsDevices as $instanceID => $host) {
            $values[] = [
                'host'                => $host,
                'model'               => 'unknown',
                'name'                => IPS_GetName($instanceID),
                'instanceID'          => $instanceID,
            ];
        }
        $this->SendDebug('Values', $values, 0);
        return $values;
    }

    private function GetCastDevices(): array
    {
        $mDNSInstanceIDs = IPS_GetInstanceListByModuleID(\Cast\mDNS\GUID);
        if (count($mDNSInstanceIDs) == 0) {
            $this->SendDebug('mDNS', 'no DNS-SD instance found', 0);
            return [];
        }
        $resultServiceTypes = ZC_QueryServiceType($mDNSInstanceIDs[0], '_googlecast._tcp', 'local.');
        if (!$resultServiceTypes) {
            return [];
        }
        $this->SendDebug('mDNS resultServiceTypes', $resultServiceTypes, 0);
        $devices = [];
        foreach ($resultServiceTypes as $device) {
            $castDevice = [];

            $this->SendDebug('mDNS QueryService', $device['Name'] . ' ' . $device['Type'] . ' ' . $device['Domain'] . '.', 0);
            $deviceInfo = ZC_QueryService($mDNSInstanceIDs[0], $device['Name'], '_googlecast._tcp', 'local.');
            $this->SendDebug('mDNS QueryService Result', $deviceInfo, 0);
            if (empty($deviceInfo)) {
                continue;
            }
            $castDevice['Port'] = $deviceInfo[0]['Port'];

            foreach ($deviceInfo[0]['TXTRecords'] as $line) {
                $data = explode('=', $line);
                $typ = strtoupper(array_shift($data));
                if (self::FilterTXT($typ)) {
                    $castDevice[$typ] = implode('=', $data);
                }
            }

            if (empty($deviceInfo[0]['IPv4'])) { //IPv4 und IPv6 sind vertauscht
                $castDevice['IPv4'] = $deviceInfo[0]['IPv6'] ?? [];
            } else {
                $castDevice['IPv4'] = $deviceInfo[0]['IPv4'];
                if (isset($deviceInfo[0]['IPv6'])) {
                    foreach ($deviceInfo[0]['IPv6'] as $index => $ipv6) {
                        $castDevice['IPv6'][] = '[' . $ipv6 . ']';
                        $hostname = gethostbyaddr($ipv6);
                        if ($hostname != $ipv6) {
                            $castDevice['Hostname'][$index] = $hostname;
                        }
                        $castDevice['Hostname'][20 + $index] = '[' . $ipv6 . ']';
                    }
                }
            }
            foreach ($castDevice['IPv4'] ?? [] as $index => $ipv4) {
                $hostname = gethostbyaddr($ipv4);
                if ($hostname != $ipv4) {
                    $castDevice['Hostname'][10 + $index] = $hostname;
                }
                $castDevice['Hostname'][((strpos($ipv4, '169.254') === 0) ? 10 : 0) + 30 + $index] = $ipv4;
            }
            // Ohne bekannte Adresse kann keine Instanz angelegt werden
            if (empty($castDevice['Hostname'])) {
                continue;
            }
            ksort($castDevice['Hostname']);
            $this->SendDebug('Device', $castDevice, 0);
            array_push($devices, [
                'name'  => $castDevice['Name'] ?? 'Cast Device (' . reset($castDevice['Hostname']) . ')',
                'model' => $castDevice['ModelName'] ?? 'unknown',
                'port'  => $castDevice['Port'],
                'host'  => $castDevice['Hostname']
            ]);
        }
        return $devices;
    }

    private static function FilterTXT(string &$typ): bool
    {
        switch ($typ) {
            case 'ID':
                $typ = 'DeviceId';
                return true;
            case 'MD':
                $typ = 'ModelName';
                return true;
            case 'FN':
                $typ = 'Name';
                return true;
        }
        return false;
    }

    private function GetIPSInstances(): array
    {
        $instanceIDList = IPS_GetInstanceListByModuleID(\Cast\Device\GUID);
        $devices = [];
        foreach ($instanceIDList as $instanceID) {
            $io = IPS_GetInstance($instanceID)['ConnectionID'];
            if ($io > 0) {
                $parentGUID = IPS_GetInstance($io)['ModuleInfo']['ModuleID'];
                if ($parentGUID == \Cast\IO\GUID) {
                    $devices[$instanceID] = strtolower(IPS_GetProperty($io, \Cast\IO\Property::HOST));
                }
            }
        }
        return $devices;
    }
}
