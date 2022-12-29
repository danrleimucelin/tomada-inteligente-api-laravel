<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Plug;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(UserStoreRequest $request)
    {
        $input = [
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
        ];

        $user = User::create($input);
        if (is_null($user)) {
            return response(["message" => "Erro ao registrar os dados de usuário"], Response::HTTP_BAD_GATEWAY);
        }

        return response([], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt(["email" => $request->email, "password" => $request->password])) {
            return response(['message' => "E-mail ou senha incorretos"], Response::HTTP_UNAUTHORIZED);
        }

        /* @var User $user */
        $user = Auth::user();
        $token = $user->createToken("TomadaInteligenteAPI");

        if (isset($request->push_token) && !empty(trim($request->push_token))) {
            $user->push_token = $request->push_token;
            $user->save();
        }

        return response(["user" => $user, "token" => $token->plainTextToken], Response::HTTP_OK);
    }

    public function show(Request $request)
    {
        $user = Auth::user();
        if ($user)
            return response($user, Response::HTTP_OK);

        return response(['message' => "Não foi possível identificar o usuário logado"], Response::HTTP_UNAUTHORIZED);
    }

    public function update(UserUpdateRequest $request)
    {
        /* @var User $user */
        $user = Auth::user();
        if (is_null($user) || !isset($user->id) || intval($user->id) <= 0) {
            return response(['message' => "Não foi possível identificar o usuário logado"], Response::HTTP_UNAUTHORIZED);
        }

        $user->name = $request->name;
        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }

        if ($user->save()) {
            return response($user, Response::HTTP_OK);
        }

        return response(['message' => "Erro ao salvar os dados enviados"], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function attachPlugToLoggedUser(Request $request, string $serial_number)
    {
        /* @var User $user */
        $user = Auth::user();

        $plug = Plug::query()->where('serial_number', '=', $serial_number)->first();
        if (is_null($plug)) {
            return response(['message' => "Tomada não encontrada. Verifique o número serial."], Response::HTTP_NOT_FOUND);
        } elseif ($plug->pin != $request->pin) {
            return response(['message' => "Tomada não encontrada no sistema."], Response::HTTP_NOT_FOUND);
        } elseif ($user->plugs->contains($plug->id)) {
            $plugUser = $user->plugs->find($plug->id);
            return response(['message' => "Você já possui vínculo com esta tomada, nomeada como \"{$plugUser->pivot->name}\""], Response::HTTP_CREATED);
        }

        $name = $request->name ?? null;

        $user->plugs()->attach([$plug->id => ['name' => $name]]);
        return response([], Response::HTTP_CREATED);
    }

    public function detachPlugFromLoggedUser(Plug $plug)
    {
        /* @var User $user */
        $user = Auth::user();
        if (!$user->plugs->contains($plug->id)) {
            return response([], Response::HTTP_OK);
        }

        DB::table('plug_user')
            ->where('plug_id', $plug->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => DB::raw("NOW()")
            ]);

        return response([], Response::HTTP_OK);
    }

    public function listPlugs()
    {
        /* @var User $user */
        $user = Auth::user();
        return response($user->plugs, Response::HTTP_OK);
    }

    public function listSchedules()
    {
        /* @var User $user */
        $user = Auth::user();

        $schedules = Schedule::query()
            ->join("plug_user", "schedules.plug_user_id", "=", "plug_user.id")
            ->join("plugs", "plugs.id", "=", "plug_user.plug_id")
            ->join("users", "users.id", "=", "plug_user.user_id")
            ->where("plug_user.user_id", $user->id)
            ->where("schedules.end_date", ">", now())
            ->whereNull("plug_user.deleted_at")
            ->whereNull("schedules.deleted_at")
            ->select(
                DB::raw(
                    "schedules.id, schedules.time, schedules.emit_sound, schedules.start_date, schedules.end_date, " .
                    "schedules.voltage, schedules.started, " .
                    "plug_user.name AS plugName, " .
                    "users.id AS userId, users.name AS userName"
                )
            )
            ->orderBy("schedules.start_date", "ASC")
            ->get();
        if (is_null($schedules) || count($schedules) === 0) {
            return response([], Response::HTTP_NO_CONTENT);
        }

        return response($schedules, Response::HTTP_OK);
    }

    function listSchedulesOfDay() {
        /* @var User $user */
        $user = Auth::user();

        $schedules = Schedule::query()
            ->join("plug_user", "schedules.plug_user_id", "=", "plug_user.id")
            ->join("plugs", "plugs.id", "=", "plug_user.plug_id")
            ->join("users", "users.id", "=", "plug_user.user_id")
            ->where("plug_user.user_id", $user->id)
            ->where("schedules.end_date", ">", now())
            ->whereDate("schedules.start_date", "=", date("Y-m-d"))
            ->whereNull("plug_user.deleted_at")
            ->whereNull("schedules.deleted_at")
            ->select(
                DB::raw(
                    "schedules.id, schedules.time, schedules.emit_sound, schedules.start_date, schedules.end_date, " .
                    "schedules.voltage, schedules.started, " .
                    "plug_user.name AS plugName, " .
                    "users.id AS userId, users.name AS userName"
                )
            )
            ->orderBy("schedules.start_date", "ASC")
            ->get();
        if (is_null($schedules) || count($schedules) === 0) {
            return response([], Response::HTTP_NO_CONTENT);
        }

        return response($schedules, Response::HTTP_OK);
    }

    function removeSchedule(Schedule $schedule)
    {
        /* @var User $user */
        $user = Auth::user();
        $userSchedule = $schedule->user();

        if ($user->id != $userSchedule->id) {
            return response(['message' => "Você não possui permissão para remover este agendamento"], Response::HTTP_UNAUTHORIZED);
        }

        if ($schedule->delete())
            return response([], Response::HTTP_OK);

        return response(['message' => "Erro ao remover o agendamento selecionado"], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
