<?php

namespace App\Http\Requests;

use App\Models\Plug;
use DateTime;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class NewScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        /* @var Plug $plug */
        $plug = $this->plug;

        $validarDataInicial = function ($attribute, $value, $fail) use ($plug) {
            try {
                $startDate = new DateTime($value);

                if ($startDate->getTimestamp() <= time()) {
                    return $fail("Verifique a data inicial, não é possível realizar agendamentos para datas passadas.");
                }

                $schedule = DB::select("
                    SELECT s.start_date, s.end_date
                    FROM schedules s
                    INNER JOIN plug_user pu ON (pu.id = s.plug_user_id)
                    WHERE pu.plug_id = ?
                    AND s.start_date <= ? AND s.end_date > ?
                    AND s.deleted_at IS NULL
                    AND pu.deleted_at IS NULL
                    LIMIT 1",
                    [$plug->id, $startDate->format("Y-m-d H:i:s"), $startDate->format("Y-m-d H:i:s")]
                );

                if (is_array($schedule) && isset($schedule[0])) {
                    $dataInicial = new DateTime($schedule[0]->start_date);
                    $dataFinal = new DateTime($schedule[0]->end_date);

                    return $fail("Não será possível realizar seu agendamento pois já existe outro com início previsto para " .
                        "{$dataInicial->format("d/m/Y H:i")} e fim previsto para {$dataFinal->format("d/m/Y H:i")}");
                }
            } catch (Exception $e) {
                return $fail("Ocorreu um erro. Verificque a data inicial.");
            }
        };

        return [
            "time" => "required|integer|min:0",
            "start_date" => ["required", "date", $validarDataInicial],
            "emit_sound" => "boolean",
            "end_date" => "date",
            "voltage" => "integer|min:0|max:100"
        ];
    }
}
