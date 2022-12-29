<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewScheduleRequest;
use App\Http\Requests\PlugStoreRequest;
use App\Http\Requests\PlugUpdateRequest;
use App\Models\Plug;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlugController extends Controller
{
    public function store(PlugStoreRequest $request)
    {
        $token = Str::random(100);
        $data = array_merge($request->all(), ['token' => $token]);

        /* @var Plug $plug */
        $plug = Plug::create($data);
        if (is_null($plug)) {
            return response(['message' => "Erro ao registrar os dados da Tomada"], Response::HTTP_BAD_GATEWAY);
        }

        return response(["plug" => $plug], Response::HTTP_CREATED);
    }


    public function update(Request $request)
    {
        $plugQuery = Plug::query();
        if (!empty($request->serial_number)) {
            $plugQuery->where("serial_number", "=", $request->serial_number);
        } elseif (!empty($request->id)) {
            $plugQuery->where("id", "=", $request->id);
        }
        $plug = $plugQuery->first();

        if (is_null($plug)) {
            return response(['message' => "Erro ao buscar os dados da sua tomada"], Response::HTTP_BAD_GATEWAY);
        }

        $plug->power = $request->power;
        $plug->save();

        return response(["plug" => $plug], Response::HTTP_CREATED);
    }

    public function findBySerialNumber(Request $request)
    {
        /* @var Plug $plug */
        $plug = Plug::query()
            ->where('serial_number', $request->serial_number)
            ->first();

        if (is_null($plug)) {
            return response(['message' => "Tomada não encontrada. Verifique o número serial"], Response::HTTP_NOT_FOUND);
        }

        return response([$plug], Response::HTTP_OK);
    }

    public function getPowerBySerialNumber($serial_number)
    {
        /* @var Plug $plug */
        $plug = Plug::query()
            ->where('serial_number', $serial_number)
            ->first();
        if (is_null($plug)) {
            return response(['message' => "Tomada não encontrada. Verifique o número serial"], Response::HTTP_NOT_FOUND);
        }

        return response($plug->power);
    }

    public function setConsumptionBySerialNumber(PlugUpdateRequest $request)
    {
        /* @var Plug $plug */
        $plug = Plug::query()
            ->where('serial_number', $request->serial_number)
            ->first();
        if (is_null($plug))
            return response(["message" => "Tomada não encontrada. Verifique o número serial"], Response::HTTP_NOT_FOUND);

        $plug->consumption = $request->consumption;
        $plug->save();

        return response([$plug], Response::HTTP_OK);
    }

    public function getConsumptionBySerialNumber(Request $request)
    {
        /* @var Plug $plug */
        $plug = Plug::query()
            ->where('serial_number', $request->serial_number)
            ->first();
        if (is_null($plug)) {
            return response(['message' => "Tomada não encontrada. Verifique o número serial"], Response::HTTP_NOT_FOUND);
        }

        return response($plug->consumption);
    }

    public function newSchedule(Plug $plug, NewScheduleRequest $request)
    {
        /* @var User $user */
        $user = Auth::user();

        $plugUser = DB::table('plug_user')
            ->select("id")
            ->where('plug_id', $plug->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        $time = $request->input('time');
        $startDate = $request->input('start_date');
        $timeStartDate = strtotime($startDate);
        $timeEndDate = $timeStartDate + $time;
        $endDate = date("Y-m-d H:i:s", $timeEndDate);

        $input = array_merge(["plug_user_id" => $plugUser->id, "end_date" => $endDate], $request->all());

        /* @var Schedule $schedule */
        $schedule = Schedule::create($input);
        if (is_null($schedule))
            return response(['message' => "Erro ao registrar os dados da Tomada"], Response::HTTP_BAD_GATEWAY);

        return response(["schedule" => $schedule], Response::HTTP_CREATED);
    }

    public function listSchedules(Plug $plug)
    {
        $schedules = Plug::query()
            ->join("plug_user", "plugs.id", "=", "plug_user.plug_id")
            ->join("schedules", "schedules.plug_user_id", "=", "plug_user.id")
            ->where("plugs.id", $plug->id)
            ->whereNull("plug_user.deleted_at")
            ->whereNull("schedules.deleted_at")
            ->select(["schedules.id", "schedules.time", "schedules.emit_sound", "schedules.start_date", "schedules.voltage"])
            ->get();
        if (is_null($schedules) || count($schedules) === 0) {
            return response([], Response::HTTP_NO_CONTENT);
        }

        return response($schedules, Response::HTTP_OK);
    }

    public function getNextSchedule(Plug $plug)
    {
        $schedule = Schedule::query()
            ->join("plug_user", "plug_user.id", "=", "schedules.plug_user_id")
            ->where("plug_user.plug_id", $plug->id)
            ->where("schedules.started", false)
            ->where("schedules.start_date", ">", DB::raw("DATE_SUB(NOW(), INTERVAL " . env("SCHEDULE_SEGUNDOS_TOLERANCIA") . " SECOND)"))
            ->orderBy("start_date")
            ->select("schedules.*")
            ->first();
        if (!$schedule)
            return response([], Response::HTTP_NO_CONTENT);

        return response($schedule, Response::HTTP_OK);
    }

    public function checkCanceledSchedule(Plug $plug, Request $request)
    {
        $scheduleId = $request->input('schedule');

        if (intval($scheduleId) <= 0) {
            return response(['message' => "Erro ao identificar o agendamento a ser cancelado"], Response::HTTP_BAD_GATEWAY);
        }

        /* @var Schedule $schedule */
        $schedule = Schedule::query()->where('id', $scheduleId)->withTrashed()->first();
        if (!$schedule) {
            return response(['message' => "Agendamento não encontrado"], Response::HTTP_BAD_GATEWAY);
        }

        if ($schedule->deleted_at != null) {
            return response(true, Response::HTTP_OK);
        }

        return response([], Response::HTTP_OK);
    }

    public function cancelCurrentSchedule(Plug $plug, Request $request)
    {
        $scheduleId = $request->input('schedule');
        if (intval($scheduleId) <= 0) {
            return response(['message' => "Erro ao identificar o agendamento a ser cancelado"], Response::HTTP_BAD_GATEWAY);
        }

        /* @var Schedule $schedule */
        $schedule = Schedule::find(intval($scheduleId));
        if (!$schedule) {
            return response(['message' => "Agendamento não encontrado"], Response::HTTP_BAD_GATEWAY);
        }

        $schedulePlug = $schedule->plug();
        if ($schedulePlug->id !== $plug->id) {
            return response(['message' => "Agendamento e tomada não estão vinculados"], Response::HTTP_BAD_GATEWAY);
        }

        $schedule->cancel();
        return response([], Response::HTTP_OK);
    }

    public function startSchedule(Plug $plug, Request $request)
    {
        $scheduleId = $request->input('schedule');
        if (intval($scheduleId) <= 0) {
            return response(['message' => "Erro ao identificar o agendamento que deve ser iniciado"], Response::HTTP_BAD_GATEWAY);
        }

        /* @var Schedule $schedule */
        $schedule = Schedule::find(intval($scheduleId));
        if (!$schedule) {
            return response(['message' => "Agendamento não encontrado"], Response::HTTP_BAD_GATEWAY);
        }

        $schedulePlug = $schedule->plug();
        if ($schedulePlug->id !== $plug->id) {
            return response(['message' => "Agendamento e tomada não estão vinculados"], Response::HTTP_BAD_GATEWAY);
        }

        $schedule->start();
        return response([], Response::HTTP_OK);
    }
}
