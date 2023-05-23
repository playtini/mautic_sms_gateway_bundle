<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration\SmsGateway;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSmsGatewayBundle\Integration\Exceptions\SmsGatewayException;
use MauticPlugin\MauticSmsGatewayBundle\Integration\MauticSmsGatewayIntegration;

class Configuration
{
    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * @var string
     */
    private $gatewayUrl;

    public function __construct(IntegrationHelper $integrationHelper, EncryptionHelper $encryptionHelper)
    {
        $this->integrationHelper = $integrationHelper;
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * @return string
     *
     * @throws SmsGatewayException
     */
    public function getGatewayUrl(): string
    {
        $this->setConfiguration();

        return $this->gatewayUrl;
    }

    /**
     * @throws SmsGatewayException
     */
    private function setConfiguration(): void
    {
        if ($this->gatewayUrl) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject(MauticSmsGatewayIntegration::NAME);

        if (!$integration || !$integration->getIntegrationSettings()->getIsPublished()) {
            throw new SmsGatewayException();
        }

        $keys = $this->decryptApiKeys($integration->getIntegrationSettings()->getApiKeys());
        if (empty($keys['gatewayUrl'])) {
            throw new SmsGatewayException();
        }

        $this->gatewayUrl = $keys['gatewayUrl'];
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    private function decryptApiKeys(array $keys): array
    {
        $decrypted = [];

        foreach ($keys as $name => $key) {
            $key = $this->encryptionHelper->decrypt($key);
            if (false === $key) {
                return [];
            }
            $decrypted[$name] = $key;
        }

        return $decrypted;
    }
}
