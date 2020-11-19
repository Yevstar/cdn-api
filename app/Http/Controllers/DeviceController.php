<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Device;
use App\Company;
use App\Machine;
use App\Imports\DevicesImport;
use Maatwebsite\Excel\Facades\Excel;
use GuzzleHttp\Client;
use Validator;

class DeviceController extends Controller
{
    /*
    SIM status:
        1: Not initialized
        2: Active
        1: Suspended
        4: Scrapped
    */
    private $suspendURL = "https://prismproapi.sandbox.koretelematics.com/4/TransactionalAPI.svc/json/suspendDevice";
    private $activateURL = "https://prismproapi.sandbox.koretelematics.com/4/TransactionalAPI.svc/json/activateDevice";
    private $queryURL = "https://prismproapi.sandbox.koretelematics.com/4/TransactionalAPI.svc/json/queryDevice";

	public function getDevices($pageNumber = 1) {
        $devices = Device::select('id', 'iccid', 'serial_number', 'registered', 'company_id', 'machine_id', 'sim_status', 'public_ip_sim')->paginate(7, ['*'], 'page', $pageNumber);
        $companies = Company::select('id', 'name')->get();
        $machines = Machine::select('id', 'name')->get();

        foreach ($devices as $key => $device) {
            if($device->sim_status === 1) {
                $device->sim_status = $this->querySIM($device->iccid)->sim_status;
            }
        }

        return response()->json([
            'devices' => $devices->items(),
            'companies' => $companies,
            'machines' => $machines,
            'last_page' => $devices->lastPage()
        ]);
	}

    public function uploadDevices(Request $request) {
    	$validator = Validator::make($request->all(), [ 
	        'devicesFile' => 'required|file',
	    ]);

	    if ($validator->fails())
	    {
            return response()->json(['error'=>$validator->errors()], 422);            
        }

    	$existing_devices = Device::all();
    	$numAdded = 0;
    	$numDuplicates = 0;
    	$devices = Excel::toArray(new DevicesImport, $request->devicesFile);
        foreach ($devices[0] as $key => $device) {
        	if($key == 0)
        		continue;
        	if ($existing_devices->where('serial_number', $device[0])->count() > 0) {
        		$numDuplicates++;
        		continue;
        	}
        	Device::create([
	           'serial_number' => $device[0],
	           'imei' => $device[1], 
	           'lan_mac_address' => $device[2],
	           'iccid' => substr($device[3], 0, -1),
               'public_ip_sim' => $device[4],
               'machine_id' => null,
               'company_id' => null,
               'registered' => false,
               'sim_status' => 1
        	]);
        	$numAdded++;
        }

        return response()->json([
    		'numAdded' => $numAdded,
    		'numDuplicates' => $numDuplicates
        ]);
    }

    public function deviceAssigned(Request $request) {
        $device = Device::findOrFail($request->device_id);

        $device->company_id = $request->company_id;
        $device->machine_id = $request->machine_id;

        $device->save();

        return response()->json('Successfully assigned.');
    }

    public function updateRegistered(Request $request) {
        $device = Device::findOrFail($request->device_id);

        $device->registered = $request->register;

        $device->save();

        return response()->json('Successfully updated.');
    }

    public function suspendSIM($iccid) {
        $device = Device::where('iccid', $iccid)->first();

        if(!$device) {
            return response()->json('Device Not Found', 404);
        }

        $client = new Client();
        try {
            $response = $client->post(
                $this->suspendURL,
                [
                    'headers' => ['Content-type' => 'application/json'],
                    'auth' => [
                        'ACSGroup_API', 
                        'HBSMYJM2'
                    ],
                    'json' => [
                        "deviceNumber" => $device->iccid,
                    ], 

                ]
            );
            
            return $response->getBody();
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json(json_decode($e->getResponse()->getBody()->getContents(), true), $e->getCode());
        }
    }

    public function querySIM($iccid) {
        $device = Device::where('iccid', $iccid)->first();

        $client = new Client();
        try {
            $response = $client->post(
                $this->queryURL,
                [
                    'headers' => ['Content-type' => 'application/json'],
                    'auth' => [
                        'ACSGroup_API', 
                        'HBSMYJM2'
                    ],
                    'json' => [
                        "deviceNumber" => $iccid,
                    ], 

                ]
            );

            $device->setSimStatus(json_decode($response->getBody())->d->status);

            return $device;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json(json_decode($e->getResponse()->getBody()->getContents(), true), $e->getCode());
        }
    }
}