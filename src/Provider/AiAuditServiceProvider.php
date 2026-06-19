<?php

namespace ZephyrIsle\AiAudit\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use ZephyrIsle\AiAudit\Service\AuditClient;
use ZephyrIsle\AiAudit\Service\DecisionApplier;
use ZephyrIsle\AiAudit\Service\Flagger;
use ZephyrIsle\AiAudit\Service\SnapshotBuilder;

class AiAuditServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(AuditClient::class, function ($container) {
            return new AuditClient($container->make('flarum.settings'), $container->make('log'));
        });

        $this->container->singleton(SnapshotBuilder::class, function ($container) {
            return new SnapshotBuilder($container->make('flarum.settings'), $container->make('log'));
        });

        $this->container->singleton(Flagger::class, function ($container) {
            return new Flagger($container->make('translator'), $container->make('log'));
        });

        $this->container->singleton(DecisionApplier::class, function ($container) {
            return new DecisionApplier(
                $container->make('flarum.settings'),
                $container->make('log'),
                $container->make(Flagger::class)
            );
        });
    }
}

