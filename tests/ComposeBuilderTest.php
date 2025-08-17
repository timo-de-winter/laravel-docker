<?php

use TimoDeWinter\LaravelDocker\Services\ComposeBuilder;

test('compose builder creates basic structure', function () {
    $builder = new ComposeBuilder('test-project');
    $yaml = $builder->toYaml();
    
    expect($yaml)->toContain('services:');
    expect($yaml)->toContain('application:');
    expect($yaml)->toContain('networks:');
    expect($yaml)->toContain('volumes:');
});

test('compose builder adds mariadb service', function () {
    $builder = new ComposeBuilder('test-project');
    $builder->addDatabaseService('mariadb');
    $yaml = $builder->toYaml();
    
    expect($yaml)->toContain('mariadb:');
    expect($yaml)->toContain('image: \'mariadb:11\'');
});

test('compose builder adds mysql service', function () {
    $builder = new ComposeBuilder('test-project');
    $builder->addDatabaseService('mysql');
    $yaml = $builder->toYaml();
    
    expect($yaml)->toContain('mysql:');
    expect($yaml)->toContain('image: \'mysql:8.0\'');
});

test('compose builder adds postgres service', function () {
    $builder = new ComposeBuilder('test-project');
    $builder->addDatabaseService('postgres');
    $yaml = $builder->toYaml();
    
    expect($yaml)->toContain('postgres:');
    expect($yaml)->toContain('image: \'postgres:15\'');
});