<?php

namespace MauticPlugin\MauticSmsGatewayBundle;

return [
    'version' => '1.0.0',
    'services' => [
        'events' => [
            'mautic_integration.mauticsmsgateway.send_sms.subscriber' => [
                'class'     => Integration\EventListener\SendSmsSubscriber::class,
                'arguments' => [
                    'mautic.http.client',
                    'mautic.sms_gateway.configuration',
                    'doctrine.orm.entity_manager',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.mauticsmsgateway' => [
                'class' => Integration\MauticSmsGatewayIntegration::class,
                'tags' => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'mauticsmsgateway.integration.configuration' => [
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
    'menu' => [
        'main' => [
            'items' => [
                'mautic.sms.smses' => [
                    'route'  => 'mautic_sms_index',
                    'access' => ['sms:smses:viewown', 'sms:smses:viewother'],
                    'parent' => 'mautic.core.channels',
                    'checks' => [
                        'integration' => [
                            'MauticSmsGateway' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
