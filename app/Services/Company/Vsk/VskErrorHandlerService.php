<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskErrorHandlerServiceContract;

class VskErrorHandlerService extends VskService implements VskErrorHandlerServiceContract
{
    /** @var array $errorsMessages Массив ошибок для обработки ошибки PROCESS_ERROR  */
    protected $errorsMessages = [
        0 => [
            'code' => 'validator',
            'eng' => 'Validator internal error',
            'rus' => 'Ошибка валидации, попробуйте расчет позже',
        ],

        1 => [
            'code' => 'guide_not_found',
            'eng' => 'Cannot find RDM data by Mark, Model, Modification',
            'rus' => 'Не найдены данные в справочнике',
        ],

        2 => [
            'code' => 'segment_error',
            'eng' => 'Something goes wrong',
            'rus' => 'Страховая компания не произвела расчет',
        ],
    ];

    /** @var array $rsaErrorsMessages Массив ошибок для обработки ошибки RSA_CHECK_ERROR  */
    protected $rsaErrorsMessages = [
        '0' => '',
        '3' => '',
        '34030023' => 'Невозможно выполнить проверку документа в ФОИВ',
        '34050999' => 'Произошла системная ошибка, попробуйте позже',
    ];

    /** @var array $errorCodes Массив типов ошибок и их обработчиков */
    protected $errorCodes = [
        'PROCESS_ERROR' => 'parseProcessError',
        'RSA_CHECK_ERROR' => 'parseRsaError',
        'INCORRECT_CODE' => 'parseIncorrectCodeError',
        'VALIDATIONERROR' => 'parseValidationError',
        'PAYMENT_GATE_ERROR' => 'parsePaymentGateError',
    ];

    /** @var array $foundError Массив ошибки найденной при проверке ответа */
    protected $foundError = [
        'code' => '',
        'error' => '',
    ];

    /** @var array $parsedErrors Массив ошибок для вывода на фронтенд */
    protected $parsedErrors = [];

    /** @var string $token Токен формы (чтобы не передавать в каждый метод сделал свойством) */
    protected $token;

    /** @var array $parsed_response Массив ответа от ВСК (чтобы не передавать в каждый метод сделал свойством) */
    protected $parsed_response;

    /** @var boolean $isError Глобальный статус ошибки (иногда ошибки нужно игнорировать) */
    protected $isError;

    private function parseError()
    {
        if (!empty($this->errorCodes[strtoupper($this->foundError['code'])])) {
            $method = $this->errorCodes[$this->foundError['code']];
            $this->$method($this->foundError['error']);
        } else {
            $this->parsedErrors[] = 'Произошла ошибка, попробуйте позже';
            $this->writeErrorsToToken();
        }
    }

    public function checkError(string $token, array $parsed_response)
    {
        $this->isError = false;
        $this->token = $token;
        $this->parsed_response = $parsed_response;

        foreach ($parsed_response as $tag) {
            if ($tag['tag'] == 'COM:ERRORCODE') {
                $this->isError = true;
                $this->foundError['code'] .= $tag['value'];
            }

            if ($tag['tag'] == 'COM:ERRORMESSAGE') {
                $this->isError = true;
                $this->foundError['error'] .= $tag['value'];
            }
        }

        if ($this->isError) {
            $this->parseError();
        }

        return $this->isError;
    }

    public function writeErrorsToToken($token = null, $errors = null)
    {
        if ($errors == null) {
            $errors = $this->parsedErrors;
        }

        if ($token == null) {
            $token = $this->token;
        }

        $tokenData = $this->getTokenData($token, true);
        $tokenData[self::companyCode]['status'] = 'error';
        $tokenData[self::companyCode]['errorMessages'] = $errors;

        $this->intermediateDataService->update($token, [
            'data' => json_encode($tokenData),
        ]);
    }

    private function parseProcessError()
    {
        $re = "/Errors:(.*)Warnings:/";
        $result = preg_match_all($re, str_replace(PHP_EOL, '', $this->foundError['error']), $matches, PREG_SET_ORDER,
            0);

        if (!empty($result)) {
            $this->parsedErrors[] = 'API страховой компании вернуло ошибку: ';
            $errorStr = $matches[0][1];

            foreach ($this->errorsMessages as $errorsMessage) {
                if (strpos($errorStr, $errorsMessage['eng']) !== false) {
                    $this->parsedErrors[] = $errorsMessage['rus'];
                }
            }

            $this->parsedErrors = array_unique($this->parsedErrors);
        } else {
            $this->parsedErrors[] = 'Произошла ошибка, попробуйте позже';
        }

        $this->writeErrorsToToken();
    }

    private function parseRsaError()
    {
        $this->isError = false;
        $re = '/Code=(\d*), Message=([\w ]*)/u';
        $errorCodes = [];

        //Если не пришел номер полиса, то это ошибка
        foreach ($this->parsed_response as $tag) {
            if ($tag['tag'] == 'POL:POLICYNUMBER' && empty($tag['value'])) {
                $this->isError = true;
            }
        }

        if ($this->isError === true) {
            $result = preg_match_all($re, str_replace(PHP_EOL, '', $this->foundError['error']), $matches,
                PREG_SET_ORDER, 0);

            /** @var  $matches [$i][0] полное вхождение шаблона */
            /** @var  $matches [$i][1] коды ошибок */
            /** @var  $matches [$i][2] сообщения ошибок */

            if (!empty($result)) {
                $this->parsedErrors[] = 'РСА вернуло ошибку: ';
                foreach ($matches as $match) {
                    $code = $match[1];

                    if (!in_array($code, $errorCodes)){
                        if (in_array($code, array_keys($this->rsaErrorsMessages))) {
                            $this->parsedErrors[] = $this->rsaErrorsMessages[$code];
                        } else {
                            $this->parsedErrors[] = $match[2]; //Тут могут быть не красивые ошибки
                        }

                        $errorCodes[] = $code;
                    }
                }
            } else {
                $this->parsedErrors[] = 'Произошла ошибка, попробуйте позже';
            }

            $this->writeErrorsToToken();
        }
    }

    private function parseIncorrectCodeError()
    {
        $re = '/\d+/';
        $result = preg_match_all($re, str_replace(PHP_EOL, '', $this->foundError['error']), $matches,
            PREG_SET_ORDER, 0);
        $has_attempts = true;

        if (!empty($result)) {
            $attempts_left = $matches[0][0];
            $status = 'signError';
            if ((int)$attempts_left > 0) {
                $has_attempts = true;
                $this->parsedErrors[] = sprintf('Количество оставшихся попыток ввода кода %s', $attempts_left);
            } else {
                $has_attempts = false;
                $this->parsedErrors[] = 'Количество попыток ввода кода закончилось.
                 Нажмите кнопку Обновить код для повторной отправки запроса на оформление полиса.';
            }

        } else {
            $status = 'error';
            $this->parsedErrors[] = 'Произошла ошибка, попробуйте позже';
        }

        $tokenData = $this->getTokenData($this->token, true);
        $tokenData[self::companyCode]['signSuccess'] = false;
        $tokenData[self::companyCode]['has_attempts'] = $has_attempts;
        $tokenData[self::companyCode]['status'] = $status;
        $tokenData[self::companyCode]['errorMessages'] = $this->parsedErrors;

        $this->intermediateDataService->update($this->token, [
            'data' => json_encode($tokenData),
        ]);
    }

    private function parseValidationError()
    {
        $this->parsedErrors[] = 'Страховая компания не произвела расчет';
        $this->writeErrorsToToken();
    }

    private function parsePaymentGateError()
    {
        $this->parsedErrors[] = 'Произошла системная ошибка. Попробуйте повторить операцию позднее.';
        $this->writeErrorsToToken();
    }
}
