<?php

namespace Tickets\Reports\Infrastructure;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ClearValuesRequest;
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

    private function createClient(): Google_Service_Sheets
    {
        $client = new Google_Client;
        $client->setAuthConfig(json_decode($this->credentials, true));
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);

        return new Google_Service_Sheets($client);
    }

    public function clearRange(string $spreadsheetId, string $range): void
    {
        $service = $this->createClient();
        $request = new Google_Service_Sheets_ClearValuesRequest;
        $service->spreadsheets_values->clear($spreadsheetId, $range, $request);
    }

    public function appendRows(string $spreadsheetId, string $range, array $rows): void
    {
        $service = $this->createClient();

        $body = new Google_Service_Sheets_ValueRange([
            'values' => $rows,
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
