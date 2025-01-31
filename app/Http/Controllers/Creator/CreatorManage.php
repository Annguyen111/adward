<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Creators;
use App\Models\Events;
use App\Models\Projects;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorManage extends Controller
{
    public function index()
    {
        $creator = User::where('id', Auth::user()->id)->first();
        $creator_detail = Creators::where('main_id', Auth::user()->id)->first();
        $project_assigned = Tasks::where('creator_id', Auth::user()->id)->pluck('project_id');
        $project_name = Projects::whereIn('id', $project_assigned)->get();

        $project_name->each(function ($project) use ($creator) {
            $total_hours_creator = Events::where('project_id', $project->id)
                ->where('creator_id', $creator->id)
                ->sum('hours');
            $project->total_hours_creator = $total_hours_creator;
        });

        return view('creator.index', [
            'creator' => $creator,
            'creatorDetail' => $creator_detail,
            'projectName' => $project_name,
        ]);
    }

    public function editProfile(Request $request)
{
    $user = User::find(Auth::user()->id);
    $current_Creator = Creators::where('email', Auth::user()->email)->first();

    if ($request->hasFile('file')) {
        // Nếu có file ảnh được tải lên, thực hiện cập nhật ảnh
        $file = $request->file;
        $fileName = $file->getClientOriginalName();
        $file->move('public/uploads', $file->getClientOriginalName());
        $thumbnail = $fileName;

        $user->thumbnail = $thumbnail;
    }

    // Cập nhật các trường thông tin khác không liên quan đến ảnh
    $user->name = $request->name;
    $user->save();

    if (Auth::user()->email == $current_Creator->email) {
        // Nếu người dùng là creator, thực hiện cập nhật thông tin creator
        $creator = Creators::where('main_id', Auth::user()->id)->first();
        $creator->name = $request->name;
        $creator->phone = $request->phone;
        $creator->experience = $request->experience;
        $creator->major = $request->major;

        if (isset($thumbnail)) {
            // Chỉ cập nhật ảnh nếu có file ảnh được tải lên
            $creator->thumbnail = $thumbnail;
        }

        $creator->save();
    }

    session()->flash('Success', 'Cập nhật thành công');
    return redirect()->back();
}


    public function getEvent($id, $creator_id)
    {
        $events = array();
        $auth_current = Auth::user()->id;
        $workings = Events::where('project_id', $id)->where('creator_id', $auth_current)->get();
        foreach ($workings as $working) {

            $events[] = [
                'id' => $working->id,
                'title' => $working->title,
                'hours' => $working->hours,
                'start' => $working->start,
                'project_id ' => $id,
                'creator_id ' => $working->creator_id,
                'end' => $working->end,
            ];
        }
        return view('creator.fullcalendar', ['event' => $events, 'projectId' => $id, 'creatorId' => $creator_id, 'currenUserId' => $auth_current]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'hours' => 'required|numeric',
        ]);

        $booking = Events::create([
            'title' => $request->title,
            'hours' => $request->hours,
            'project_id' => $request->project_id,
            'creator_id' => $request->creator_id,
            'start' => $request->start_date,
            'end' => $request->end_date,
        ]);

        // $color = null;

        // if($booking->title == 'Test') {
        //     $color = '#924ACE';
        // }

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = Events::find($id);
        if (!$booking) {
            return response()->json([
                'error' => 'Unable to locate the event',
            ], 404);
        }
        $booking->update([
            'start' => $request->start_date,
            'end' => $request->end_date,
        ]);
        return response()->json('Event updated');
    }
    public function destroy($id)
    {
        $booking = Events::find($id);
        if (!$booking) {
            return response()->json([
                'error' => 'Unable to locate the event',
            ], 404);
        }
        $booking->delete();
        return $id;
    }

    public function search(Request $request, $projectId)
    {
        $date = $request->input('date');

        $event_search = Events::where("project_id", $projectId)->whereDate("start", $date)->first();

        // $events = array();
        $events[] = [
            'id' => $event_search->id,
            'title' => $event_search->title,
            'hours' => $event_search->hours,
            'start' => $event_search->start,
            'creator_id' => $event_search->creator_id,
            'project_id' => $event_search->project_id,
            'end' => $event_search->end,
        ];
        return response()->json(['events' => $events]);
    }
}
