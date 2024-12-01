<?php

namespace App\Library;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

class Merlin
{
    public function setOrder($payload){
        try{
            // Send the request using Guzzle
            $client = new Client();
            $response = $client->post(env('MERLIN_BASE_URL'), [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => 'CCBS_SetOrder',
                ],
                'body' => $payload,
            ]);

            // Output the response
            $responseBody = $response->getBody()->getContents();

            // Load the response into SimpleXMLElement for parsing
            $xmlResponse = simplexml_load_string($responseBody);

            // Convert the XML to JSON to inspect the structure easily
            $jsonResponse = json_encode($xmlResponse);

            // Output the JSON response to inspect it
            return $jsonResponse;
        } catch (RequestException $e) {
            return $e->getMessage();
        }

    }
}
