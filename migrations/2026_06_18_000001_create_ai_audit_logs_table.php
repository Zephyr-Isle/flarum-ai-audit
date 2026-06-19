<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('zia_ai_audit_logs', function (Blueprint $table) {
    $table->increments('id');
    $table->string('subject_type', 32)->index();
    $table->unsignedBigInteger('subject_id')->nullable()->index();
    $table->unsignedInteger('actor_id')->nullable()->index();
    $table->unsignedInteger('owner_id')->nullable()->index();

    $table->string('status', 16)->default('pending')->index();
    $table->decimal('risk', 5, 4)->nullable();
    $table->unsignedTinyInteger('severity')->default(0);
    $table->text('actions')->nullable();
    $table->text('conclusion')->nullable();

    $table->text('snapshot')->nullable();
    $table->text('analysis')->nullable();
    $table->text('error')->nullable();

    $table->unsignedInteger('retry_count')->default(0);

    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();

    $table->index(['subject_type', 'status']);
    $table->index(['owner_id', 'created_at']);
});

