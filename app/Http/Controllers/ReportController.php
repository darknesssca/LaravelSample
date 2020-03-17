<?php


namespace App\Http\Controllers;


use App\Models\Policy;
use App\Models\Report;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Nowakowskir\JWT\Exceptions\EmptyTokenException;
use Nowakowskir\JWT\TokenEncoded;

class ReportController extends Controller
{

    //Методы обработки маршрутов
    public function createReport(Request $request)
    {
        try {
            $validation_result = $this->validate($request, $this->createReportValidationRules(), $this->createReportValidationMessages());

            $user_id = $this->getUserId($validation_result['creator_id']);
            $reward = $this->getReward($validation_result['policies']);

            if ($user_id && $reward){
                $report = Report::create([
                    'name' => $validation_result['name'],
                    'creator_id' => $user_id,
                    'create_date' => Carbon::now(),
                    'reward' => $reward
                ]);

                $report->policies()->sync([$validation_result['policies']]);
            }

            return $this->success();
        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), 500);
        }

    }


    //Вспомогательные методы
    private function createReportValidationRules()
    {
        return [
            'name' => 'required',
            'policies' => 'required',
            'creator_id' => 'required',
        ];
    }

    private function createReportValidationMessages()
    {
        return [
            'name.required' => 'Поле Название не заполнено',
            'policies.required' => 'Поле Полисы не заполнено',
            'creator_id.required' => 'Поле Создатель не заполнено',
        ];
    }

    private function getReward(array $policies_ids)
    {
        $reward = 0;
        $policy_collection = Policy::whereIn('id', $policies_ids)->get();

        if (count($policy_collection) != count($policies_ids)){ //Найдены не все полисы
           return $this->error('Найдены не все полисы');
        }

        foreach ($policy_collection as $policy){
            $reward += 1;
        }

        return $reward;
    }

    private function getUserId($token){
        try {
            $tokenEncoded = new TokenEncoded($token);
        } catch (EmptyTokenException $exception) {
            return $this->error($exception->getMessage(), 500);
        }
        $payload = $tokenEncoded->decode()->getPayload();

        if (empty($payload['user_id'])){
            return $this->error('Пользователь не авторизован');
        }

        return $payload['user_id'];
    }
}
