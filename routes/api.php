<?php

use App\Http\Controllers\PlugController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//- Rotas sem necessidade de autenticação
{
    Route::controller(PlugController::class)->group(function () {
        Route::post("/plug/serial_number", "findBySerialNumber");
        Route::post("/plug/{serial_number}/power", "getPowerBySerialNumber");
        Route::put("/plug/{serial_number}/consumption", "setConsumptionBySerialNumber");
        Route::get("/plug/{serial_number}/consumption", "getConsumptionBySerialNumber");
    });

    Route::get('/currentServerTime', function () {
        $mytime = Carbon\Carbon::now();
        return $mytime->toDateTimeString();
    });
}

{
    Route::controller(UserController::class)->group(function () {
        Route::post("/user", "store");
        Route::post("/login", "login");
    });

    Route::controller(PlugController::class)
        ->prefix("/plug")
        ->group(function () {
            Route::post("/", "store");
            Route::put("/", "update");

            Route::prefix("/{plug}/{token}")
                ->middleware("auth.plug")
                ->group(function () {
                    Route::get("/next-schedule", "getNextSchedule");
                    Route::post("/start-schedule", "startSchedule");
                    Route::post("/check-canceled-schedule", "checkCanceledSchedule");
            });
    });
}

//- Rotas que necessitam de autenticação
{
    Route::middleware('auth:sanctum')->group(function() {
        Route::controller(UserController::class)->group(function () {
            Route::get("/user", "show");
            Route::get("/user/plugs", "listPlugs");
            Route::get("/user/schedules", "listSchedules");
            Route::get("/user/schedules/today", "listSchedulesOfDay");
            Route::post("/user/attach-plug/{serial_number}", "attachPlugToLoggedUser");
            Route::delete("/user/detach-plug/{plug}", "detachPlugFromLoggedUser");
            Route::delete("/schedule/remove/{schedule}", "removeSchedule");
            Route::patch("user", "update");
        });

        Route::controller(PlugController::class)->group(function () {
            Route::post("/{plug}/schedule", "newSchedule");
            Route::get("/{plug}/schedules", "listSchedules");
        });
    });
}
