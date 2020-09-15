<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskCallbackServiceContract;
use App\Contracts\Company\Vsk\VskMethodServiceInterface;
use App\Exceptions\CompanyException;
use App\Exceptions\MethodNotFoundException;
use App\Traits\CompanyServicesTrait;

class VskCallbackService extends VskService implements VskCallbackServiceContract
{
    use CompanyServicesTrait;

    /**
     * @param array $callback_response
     * @throws CompanyException
     * @throws MethodNotFoundException
     */
    public function runNextStep(array $callback_response)
    {
        $callback_info = $this->parseContent($callback_response['content']);
        $token_data = $this->getTokenFromCallback($callback_info);
        $company = $this->getCompany(self::companyCode);
        $contract = 'App\\Contracts\\Company\\Vsk\\Vsk' . $token_data['method'] . 'ServiceContract';

        /** @var VskMethodServiceInterface $processService */
        $processService = app($contract);
        $process_data = $processService->processCallback($company, $token_data, $callback_info);

        if (!empty($process_data['nextMethod'])){
            $this->runService($company, $token_data, $process_data['nextMethod']);
        }
    }

    private function parseContent($content)
    {
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $content, $vals);
        xml_parser_free($parser);
        unset($parser);
        return $vals;
    }

    /**
     * Метод ищет токен в ответе и возвращает массив из метода и токена
     * @param array $parsed_response - массив ответа от колбека
     * @return array
     */
    private function getTokenFromCallback(array $parsed_response)
    {
        $token = [];
        foreach ($parsed_response as $tag) {
            if (in_array($tag['tag'], ['SCH1:MESSAGEID', 'COM:MESSAGEID'])) {
                $tmp = explode('.', $tag['value']);
                $token = [
                    'method' => $tmp[0],
                    'token' => $tmp[1],
                ];
            }
        }
        return $token;
    }
}
