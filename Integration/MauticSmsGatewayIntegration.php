<?php

namespace MauticPlugin\MauticSmsGatewayBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class MauticSmsGatewayIntegration extends BasicIntegration implements BasicInterface
{
  use ConfigurationTrait;

  const DISPLAY_NAME = 'SmsGateway';
  const NAME = 'MauticSmsGateway';

  public function getDisplayName(): string
  {
    return self::DISPLAY_NAME;
  }

  public function getName(): string
  {
    return self::NAME;
  }

  public function getIcon(): string
  {
    return 'plugins/MauticSmsGatewayBundle/Assets/img/icon.png';
  }
}
