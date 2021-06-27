<?php

namespace App\Api\ChatworkExtend;

use wataridori\ChatworkSDK\ChatworkRequest as ChatworkRequestBase;
use wataridori\ChatworkSDK\ChatworkSDK;
use wataridori\ChatworkSDK\Exception\RequestFailException;

class ChatworkRequest extends ChatworkRequestBase
{
    /**
     * Send Request to Chatwork.
     *
     * @throws RequestFailException
     *
     * @return array
     */
    public function send()
    {
        $curl = curl_init();
        $url = $this->buildUrl();
        curl_setopt($curl, CURLOPT_HTTPHEADER, [$this->getHeader()]);

        switch ($this->method) {
            case self::REQUEST_METHOD_GET:
                curl_setopt($curl, CURLOPT_HTTPGET, 1);
                if ($this->params) {
                    $url .= '?' . http_build_query($this->params, '', '&');
                }
                break;
            case self::REQUEST_METHOD_POST:
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($this->params) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->params, '', '&'));
                }
                break;
            case self::REQUEST_METHOD_PUT:
                curl_setopt($curl, CURLOPT_PUT, 1);
                if ($this->params) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->params, '', '&'));
                }
                break;
            case self::REQUEST_METHOD_DELETE:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, self::REQUEST_METHOD_DELETE);
                if ($this->params) {
                    $url .= '?' . http_build_query($this->params, '', '&');
                }
                break;
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!ChatworkSDK::getSslVerificationMode()) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        $response = json_decode(curl_exec($curl), 1);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if ($info['http_code'] >= 400) {
            $error = $response['errors'];
            throw new RequestFailException(json_encode($error));
        }

        return [
            'http_code' => $info['http_code'],
            'response' => $response,
        ];
    }
}
