<?php

namespace Tickets\Reports\Infrastructure;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GoogleSheetsClient
{
    public function __construct(
        private string $credentials,
    ) {
        if (empty($credentials)) {
            throw new \RuntimeException('Google Sheets credentials not configured');
        }
    }

    public function appendRows(string $spreadsheetId, string $range, array $rows): void
    {
        $client = new Google_Client();
        $client->setAuthConfig(json_decode($this->credentials, true));
        $client->useApplicationDefaultCredentials();

        $service = new Google_Service_Sheets($client);

        $body = new Google_Service_Sheets_ValueRange([
            'values' => $rows
        ]);

        $params = [
            'valueInputOption' => 'RAW',
        ];

        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            $params
        );
    }
}
