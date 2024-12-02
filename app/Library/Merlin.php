<?php

namespace App\Library;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

class Merlin
{
    /**
     * Send request to Merlin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse | mixed
     */
    public static function setOrder($payload){
        try{
            // Send the request using Guzzle
            $client = new Client();
            $response = $client->post(env('MERLIN_BASE_URL'), [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => 'CCBS/SetOrder',
                ],
                'http_errors' => false,
                'body' => $payload,
            ]);
            $responseBody = $response->getBody()->getContents();

            $updatedResponse = preg_replace('/xmlns="CCBS"/', 'xmlns="http://www.example.com/CCBS"', $responseBody);

            $xml = simplexml_load_string($updatedResponse);
            $code = (string) $xml->xpath('//status/code')[0];
            $message = (string) $xml->xpath('//status/message')[0];

            // Return the extracted data as an array
            $result = [
                'code' => $code,
                'message' => $message,
            ];
            return $result;
        } catch (SoapFault $e) {
            echo 'Error: ' . $e->getMessage();
            // Optionally, display the full response
            echo 'Response: ' . $e->getCode();
        }

    }
}
