<?php

namespace MauticPlugin\MauticSmsGatewayBundle;

return [
    'version' => '1.0.0',
    'services' => [
        'integrations' => [
            'mautic.integration.MauticSmsGateway' => [
                'class' => Integration\MauticSmsGatewayIntegration::class,
                'tags' => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'MauticSmsGateway.integration.configuration' => [
                'class' => Integration\Support\ConfigSupport::class,
                'tags' => [
                    'mautic.config_integration',
                ],
            ],
        ],
        'others' => [
            'mautic.sms_gateway.transport' => [
                'class' => Integration\SmsGateway\SmsGatewayTransport::class,
                'arguments' => [
                    'mautic.http.connector',
                    'mautic.sms_gateway.configuration',
                    'monolog.logger.mautic',
                ],
                'tag' => 'mautic.sms_transport',
                'tagArguments' => [
                    'integrationAlias' => 'MauticSmsGateway',
                ],
                'alias' => 'mautic.sms.config.transport.sms_gateway',
            ],
            'mautic.sms_gateway.configuration' => [
                'class' => Integration\SmsGateway\Configuration::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.helper.encryption',
                ]
            ],
        ],
    ],
];
