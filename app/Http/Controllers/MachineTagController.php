<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MachineTag;
use App\AlarmType;

use \stdClass;

class MachineTagController extends Controller
{
    public function getMachineTags($machine_id) {
    	$tags = MachineTag::where('configuration_id', $machine_id)->orderBy('name')->get();
    	$alarm_tags = AlarmType::where('machine_id', $machine_id)->orderBy('name')->get();

    	$tags = $tags->merge($alarm_tags);

    	return response()->json(compact('tags'));
    }

	public function getMachinesTags(Request $request) {
		$machine_ids = $request->machineIds;
		$tags = [];
		foreach ($machine_ids as $key => $machine_id) {
			$machine_data = new stdClass();
			$machine_data->machine_id = $machine_id;

			$machine_tags = MachineTag::where('configuration_id', $machine_id)->orderBy('name')->get();
    		$alarm_tags = AlarmType::where('machine_id', $machine_id)->orderBy('name')->get();

    		$machine_tags = $machine_tags->merge($alarm_tags);
			$machine_data->tags = $machine_tags;

			array_push($tags, $machine_data);
		}
		return response()->json(compact('tags'));
	}
}
