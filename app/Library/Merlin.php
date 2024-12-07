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

    /**
     * Send request to Merlin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse | mixed
     */
    public static function getStock($payload){
        try{
            // return $payload;
            // Send the request using Guzzle
            $client = new Client();
            $response = $client->post(env('MERLIN_BASE_URL'), [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => 'CCBS/GetStockList',
                ],
                'http_errors' => false,
                'body' => $payload,
            ]);
            $responseBody = $response->getBody()->getContents();
            $updatedResponse = preg_replace('/xmlns="CCBS"/', 'xmlns="http://www.example.com/CCBS"', $responseBody);

            // Load the updated XML string into SimpleXMLElement
            $xml = simplexml_load_string($updatedResponse);
            // Check if the XML was parsed successfully
            if ($xml === false) {
                // Handle XML parsing error (optional)
                return response()->json(['error' => 'Invalid XML response'], 400);
            }

            // Extract the desired values using xpath or direct access
            $dataCount = (string) $xml->xpath('//data/@count')[0];
            $dataCountAvailable = (string) $xml->xpath('//data/@count_available')[0];

            // Optionally, you can loop through the rows if needed
            $items = [];
            foreach ($xml->xpath('//data/row') as $row) {
                $items[] = [
                    'stockid' => (string) $row->stockid,
                    'company' => (string) $row->company,
                    'depot' => (string) $row->depot,
                    'sku' => (string) $row->part,
                    'qty_free' => (string) $row->qty_free,
                    'list_price' => (string) $row->list_price,
                ];
            }

            // Return the extracted data as an array or JSON response
            $result = [
                'count' => $dataCount,
                'count_available' => $dataCountAvailable,
                'items' => $items,
            ];
            return $result;
        } catch (SoapFault $e) {
            echo 'Error: ' . $e->getMessage();
            // Optionally, display the full response
            echo 'Response: ' . $e->getCode();
        }

    }
}
