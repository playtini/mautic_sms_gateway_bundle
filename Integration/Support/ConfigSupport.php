<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration\Support;

use MauticPlugin\MauticSmsGatewayBundle\Integration\MauticSmsGatewayIntegration;
use MauticPlugin\MauticSmsGatewayBundle\Form\Type\ConfigAuthType;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;

class ConfigSupport extends MauticSmsGatewayIntegration  implements ConfigFormInterface, ConfigFormAuthInterface
{
  use DefaultConfigFormTrait;

  public function getAuthConfigFormName(): string
  {
    return ConfigAuthType::class;
  }
}
