<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Schedule extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "plug_user_id",
        "time",
        "emit_sound",
        "start_date",
        "end_date",
        "voltage"
    ];

    public function plug(): Plug
    {
        $plugRow = Schedule::query()
            ->join("plug_user", "schedules.plug_user_id", "=", "plug_user.id")
            ->where("schedules.id", $this->id)
            ->select("plug_user.plug_id")
            ->get();

        $plugId = $plugRow[0]->plug_id;
        return Plug::find($plugId);
    }

    public function user(): User
    {
        $userRow = Schedule::query()
            ->join("plug_user", "schedules.plug_user_id", "=", "plug_user.id")
            ->where("schedules.id", $this->id)
            ->select("plug_user.user_id")
            ->get();

        $userId = $userRow[0]->user_id;
        return User::find($userId);
    }

    public function start()
    {
        if ($this->isStarted()) {
            return;
        }

        $this->started = true;
        $this->save();
    }

    public function cancel()
    {
        if ($this->isStarted()) {
            $this->deleted_at = Carbon::now();
            $this->save();
        }
    }

    public function isStarted()
    {
        return $this->started;
    }
}
