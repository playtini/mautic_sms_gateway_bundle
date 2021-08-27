<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSmsGatewayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigAuthType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gatewayUrl', TextType::class, [
            'label' => 'sms-gateway.keys.gateway-url',
            'label_attr' => ['class' => 'control-label'],
            'required' => true,
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'integration' => null,
        ]);
    }
}
