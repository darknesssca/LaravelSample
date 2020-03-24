<?php


namespace App\Http\Controllers;


use App\Models\Policy;
use App\Models\Report;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nowakowskir\JWT\Exceptions\EmptyTokenException;
use Nowakowskir\JWT\TokenEncoded;

class ReportController extends Controller
{
    private $httpErrorCode = 400;

    //Методы обработки маршрутов

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        //TODO Реализовать метод проверки физ лица после реализации получения полисов
        try {
            $validation_result = $this->validate($request, $this->createReportValidationRules(),
                $this->createReportValidationMessages());

            $user_id = $this->getUserId($validation_result['creator_id']);
            $reward = $this->getReward($validation_result['policies'], $user_id);

            if ($reward > 0) {
                $report = Report::create([
                    'name' => $validation_result['name'],
                    'creator_id' => $user_id,
                    'create_date' => Carbon::now(),
                    'reward' => $reward
                ]);

                $report->policies()->sync($validation_result['policies']);
                $this->sendLog('Создан отчет', 'create_report', $user_id);
                return $this->success();
            } else {
                throw new Exception('Отсутствует доступное вознаграждение');
            }
        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), $this->httpErrorCode);
        }

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validation_result = $this->validate(
                $request,
                ['agent_id' => 'required'],
                ['agent.required' => 'Пользователь не авторизован']
            );
            $reports = [];
            $agent_id = $this->getUserId($validation_result['agent_id']);
            $report_collection = Report::where('creator_id', $agent_id)->get();

            if (count($report_collection) > 0) {
                $creator = $this->getCreator($agent_id);
                foreach ($report_collection as $report) {
                    $reports[] = $this->getReportInfo($report, $creator);
                }
                return response()->json($reports, 200);
            } else {
                $this->httpErrorCode = 404;
                throw new Exception('Отчеты не найдены');
            }


        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), $this->httpErrorCode);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $id = intval($id);

            if ($id <= 0){
                $this->httpErrorCode = 404;
                throw new Exception('Передан некорректный id');
            }

            $report = Report::find($id);

            if (empty($report)){
                throw new Exception(sprintf('Не найден отчет с id %s', $id));
            }

            $creator = $this->getCreator($report->creator_id);
            $report_info = $this->getReportInfo($report, $creator);

            return response()->json($report_info, 200);
        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), $this->httpErrorCode);
        }
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
    private function getReward(array $policies_ids, $user_id)
    {
        $reward_sum = 0;
        $policy_collection = Policy::whereIn('id', $policies_ids)->get();

        if (count($policy_collection) != count($policies_ids)) { //Найдены не все полисы
            throw new Exception('Отсутсвтуют некоторые полисы');
        }

        if (env("APP_DEBUG")) {
            return 1;
        }

        $url = 'api/v1/commission-calculation/rewards';
        $params = ['user_id' => $user_id];
        $response = $this->sendRequest('GET', $url, $params);

        if (empty($response['content'])){
            throw new Exception('Ошибка получения данных');
        }

        $content = json_decode($response['content'], true, 512,  JSON_OBJECT_AS_ARRAY);

        $rewards = $this->indexRewards($content);

        foreach ($policy_collection as $policy) {
            if ($policy->paid == false){
                throw new Exception(sprintf('Полис %s не оплачен', $policy->number));
            }

            $reward_sum += floatval($rewards[$policy->id]['value']);
        }

        return $reward_sum;
    }

    /**
     * @param $token
     * @return int
     * @throws EmptyTokenException
     * @throws Exception
     */
    private function getUserId($token)
    {
        $tokenEncoded = new TokenEncoded($token);
        $payload = $tokenEncoded->decode()->getPayload();
        $user_id = intval($payload['user_id']);

        if (empty($user_id) || $user_id <= 0) {
            $this->httpErrorCode = 401;
            throw new Exception('Пользователь не авторизован');
        }

        return $user_id;
    }

    /**
     * @param $user_id
     * @return array
     * @throws Exception
     */
    private function getCreator($user_id)
    {
        if (env("APP_DEBUG")) {
            $user = [
                'id' => $user_id,
                'full_name' => 'Иванов Иван Иванович'
            ];
            return $user;
        }

        $url = 'api/v1/auth/users/' . $user_id;
        $response = $this->sendRequest('GET', $url);

        if (empty($response['content'])){
            throw new Exception('Ошибка получения данных');
        }

        $user = json_decode($response['content'], true, 512,  JSON_OBJECT_AS_ARRAY);

        return [
            'id' => $user['id'],
            'full_name' => $user['full_name']
        ];
    }

    /**
     * @param $report
     * @return array
     * @throws Exception
     */
    private function getPolicies($report)
    {
        $policies = [];
        $policies_collection = $report->policies;

        if (count($policies_collection) <= 0) {
            throw new Exception(sprintf('У отчета %s отсутствуют полисы', $report->name));
        }

        foreach ($policies_collection as $policy) {
            $policies[] = [
                'id' => $policy->id,
                'number' => $policy->number,
            ];
        }

        return $policies;
    }

    /**
     * @param $report
     * @param $creator
     * @return array
     * @throws Exception
     */
    private function getReportInfo($report, $creator)
    {
        return [
            'id' => $report->id,
            'name' => $report->name,
            'creator' => $creator,
            'is_payed' => $report->is_payed,
            'policies' => $this->getPolicies($report)
        ];
    }

    /**
     * @param array $rewards
     * @return mixed
     */
    private function indexRewards(array $rewards)
    {
        foreach ($rewards as $reward){
            $new_rewards[$rewards['policy']['id']] = $reward;
        }

        return $new_rewards;
    }
}
