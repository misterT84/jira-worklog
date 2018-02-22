<?php

namespace Jira;

use GuzzleHttp\Client;

class JiraClient
{
    /** @var string */
    protected $jiraUrl;
    /** @var int */
    protected $filterId;
    /** @var array */
    protected $authSettings;
    /** @var Client */
    protected $httpClient;
    /** @var string */
    private $jiraApiUrlPattern = '%s/rest/api/2/search?jql=filter%%3D%d&fields=worklog&maxResults=100';

    /**
     * JiraClient constructor.
     * @param string $jiraUrl
     * @param int $filterId
     * @param array $authSettings
     */
    public function __construct(string $jiraUrl, int $filterId, array $authSettings)
    {
        $this->jiraUrl = sprintf($this->jiraApiUrlPattern, rtrim($jiraUrl, '/'), $filterId);
        $this->filterId = $filterId;
        $this->authSettings = $authSettings;
        $this->httpClient = new Client();
    }

    /**
     * @return array
     */
    public function getWorklog(): array
    {

        $basicAuth = $this->authSettings['basicAuthString'] ?: $this->getBasicAuthCredentials();

        $response = $this->httpClient->get($this->jiraUrl, [
            'headers' => [
                'Authorization' => 'Basic ' . $basicAuth,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @return string
     */
    private function getBasicAuthCredentials(): string
    {
        return base64_encode($this->authSettings['username'] . ':' . $this->authSettings['password']);
    }


}
