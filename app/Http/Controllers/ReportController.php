<?php


namespace App\Http\Controllers;


use App\Models\Policy;
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

            if ($this->getUserId($validation_result['creator_id'])){
                echo 'Yes';
            }

            if ($this->checkPolicies($validation_result['policies'])){
                echo 'Yes';
            }

            return true;
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

    private function checkPolicies(array $policies_ids)
    {
        $policy_collection = Policy::whereIn('id', $policies_ids)->get();

//        if (count($policy_collection) != count($policies_ids)){ //Найдены не все полисы
//
//        }

        return true;
    }

    private function getUserId($token){
        try {
            $tokenEncoded = new TokenEncoded($token);
        } catch (EmptyTokenException $exception) {
            return $this->error($exception->getMessage(), 500);
        }
        $payload = $tokenEncoded->decode()->getPayload();

        return true;
    }
}
