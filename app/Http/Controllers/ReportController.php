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
    public function create(Request $request)
    {
        try {
            $validation_result = $this->validate($request, $this->createReportValidationRules(), $this->createReportValidationMessages());

            $user_id = $this->getUserId($validation_result['creator_id']);
            $reward = $this->getReward($validation_result['policies']);
            
            echo '<pre>';
            print_r($validation_result);
            echo '</pre>';

            if ($reward > 0){
                $report = Report::create([
                    'name' => $validation_result['name'],
                    'creator_id' => $user_id,
                    'create_date' => Carbon::now(),
                    'reward' => $reward
                ]);

                $report->policies()->sync($validation_result['policies']);
                return $this->success();
            } else {
                throw new Exception('Отсутствует доступное вознаграждение');
            }
        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), 500);
        }

    }

    public function index(Request $request)
    {

    }


    //Вспомогательные методы

    /**
     * @return array
     */
    private function createReportValidationRules()
    {
        return [
            'name' => 'required',
            'policies' => 'required',
            'creator_id' => 'required',
        ];
    }

    /**
     * @return array
     */
    private function createReportValidationMessages()
    {
        return [
            'name.required' => 'Поле Название не заполнено',
            'policies.required' => 'Поле Полисы не заполнено',
            'creator_id.required' => 'Поле Создатель не заполнено',
        ];
    }

    /**
     * @param array $policies_ids
     * @return int
     * @throws Exception
     */
    private function getReward(array $policies_ids)
    {
        $reward = 0;
        $policy_collection = Policy::whereIn('id', $policies_ids)->get();

        if (count($policy_collection) != count($policies_ids)){ //Найдены не все полисы
            throw new Exception('Отсутсвтуют некоторые полисы');
        }

        if (env("APP_DEBUG")) {
            return 1;
        }


        foreach ($policy_collection as $policy){
            $reward += 1;
        }

        return $reward;
    }

    /**
     * @param $token
     * @return int
     * @throws EmptyTokenException
     * @throws Exception
     */
    private function getUserId($token){
        $tokenEncoded = new TokenEncoded($token);
        $payload = $tokenEncoded->decode()->getPayload();
        $user_id = intval($payload['user_id']);

        if (empty($user_id) || $user_id <= 0){
            throw new Exception('Пользователь не авторизован');
        }

        return $user_id;
    }
}
