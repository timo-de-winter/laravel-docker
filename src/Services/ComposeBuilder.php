<?php

namespace TimoDeWinter\LaravelDocker\Services;

class ComposeBuilder
{
    private array $services = [];

    private array $volumes = [];

    private array $networks = [];

    private string $projectName;

    public function __construct(string $projectName)
    {
        $this->projectName = $projectName;
        $this->initializeDefaults();
    }

    private function initializeDefaults(): void
    {
        $this->networks = [
            $this->projectName => [
                'driver' => 'bridge',
            ],
        ];

        $this->addApplicationService();
        $this->addRedisService();
        $this->addMailpitService();
        $this->addMinioService();
        $this->addMeilisearchService();
    }

    public function addDatabaseService(string $type): self
    {
        switch ($type) {
            case 'mysql':
                $this->addMysqlService();
                break;
            case 'postgres':
                $this->addPostgresService();
                break;
            case 'mariadb':
                $this->addMariadbService();
                break;
        }

        return $this;
    }

    private function addApplicationService(): void
    {
        $this->services['application'] = [
            'build' => [
                'context' => './.docker/dev',
                'dockerfile' => 'Dockerfile',
            ],
            'extra_hosts' => [
                'host.docker.internal:host-gateway',
            ],
            'ports' => [
                '${APP_PORT:-80}:80',
                '${VITE_PORT:-5174}:${VITE_PORT:-5174}',
            ],
            'volumes' => [
                '.:/var/www/html:delegated',
                '../packages:/var/www/packages:delegated',
            ],
            'networks' => [
                $this->projectName,
            ],
            'environment' => [
                'NODE_OPTIONS' => '--max-old-space-size=4096',
                'XDEBUG_MODE' => '${XDEBUG_MODE:-off}',
            ],
            'depends_on' => [],
        ];
    }

    private function addMysqlService(): void
    {
        $this->services['mysql'] = [
            'image' => 'mysql:8.0',
            'ports' => [
                '${FORWARD_DB_PORT:-3306}:3306',
            ],
            'environment' => [
                'MYSQL_ROOT_PASSWORD' => '${DB_PASSWORD}',
                'MYSQL_ROOT_HOST' => '%',
                'MYSQL_DATABASE' => '${DB_DATABASE}',
                'MYSQL_USER' => '${DB_USERNAME}',
                'MYSQL_PASSWORD' => '${DB_PASSWORD}',
                'MYSQL_ALLOW_EMPTY_PASSWORD' => 'yes',
            ],
            'volumes' => [
                'mysql:/var/lib/mysql',
            ],
            'networks' => [
                $this->projectName,
            ],
            'healthcheck' => [
                'test' => [
                    'CMD',
                    'mysqladmin',
                    'ping',
                    '-p${DB_PASSWORD}',
                ],
                'retries' => 3,
                'timeout' => '5s',
            ],
        ];

        $this->volumes['mysql'] = ['driver' => 'local'];
        $this->services['application']['depends_on'][] = 'mysql';
    }

    private function addPostgresService(): void
    {
        $this->services['postgres'] = [
            'image' => 'postgres:15',
            'ports' => [
                '${FORWARD_DB_PORT:-5432}:5432',
            ],
            'environment' => [
                'PGPASSWORD' => '${DB_PASSWORD}',
                'POSTGRES_DB' => '${DB_DATABASE}',
                'POSTGRES_USER' => '${DB_USERNAME}',
                'POSTGRES_PASSWORD' => '${DB_PASSWORD}',
            ],
            'volumes' => [
                'postgres:/var/lib/postgresql/data',
            ],
            'networks' => [
                $this->projectName,
            ],
            'healthcheck' => [
                'test' => [
                    'CMD',
                    'pg_isready',
                    '-q',
                    '-d',
                    '${DB_DATABASE}',
                    '-U',
                    '${DB_USERNAME}',
                ],
                'retries' => 3,
                'timeout' => '5s',
            ],
        ];

        $this->volumes['postgres'] = ['driver' => 'local'];
        $this->services['application']['depends_on'][] = 'postgres';
    }

    private function addMariadbService(): void
    {
        $this->services['mariadb'] = [
            'image' => 'mariadb:11',
            'ports' => [
                '${FORWARD_DB_PORT:-3306}:3306',
            ],
            'environment' => [
                'MYSQL_ROOT_PASSWORD' => '${DB_PASSWORD}',
                'MYSQL_ROOT_HOST' => '%',
                'MYSQL_DATABASE' => '${DB_DATABASE}',
                'MYSQL_USER' => '${DB_USERNAME}',
                'MYSQL_PASSWORD' => '${DB_PASSWORD}',
                'MYSQL_ALLOW_EMPTY_PASSWORD' => 'yes',
            ],
            'volumes' => [
                'mariadb:/var/lib/mysql',
            ],
            'networks' => [
                $this->projectName,
            ],
            'healthcheck' => [
                'test' => [
                    'CMD',
                    'healthcheck.sh',
                    '--connect',
                    '--innodb_initialized',
                ],
                'retries' => 3,
                'timeout' => '5s',
            ],
        ];

        $this->volumes['mariadb'] = ['driver' => 'local'];
        $this->services['application']['depends_on'][] = 'mariadb';
    }

    private function addRedisService(): void
    {
        $this->services['redis'] = [
            'image' => 'redis:alpine',
            'ports' => [
                '${FORWARD_REDIS_PORT:-6379}:6379',
            ],
            'volumes' => [
                'redis:/data',
            ],
            'networks' => [
                $this->projectName,
            ],
            'healthcheck' => [
                'test' => [
                    'CMD',
                    'redis-cli',
                    'ping',
                ],
                'retries' => 3,
                'timeout' => '5s',
            ],
        ];

        $this->volumes['redis'] = ['driver' => 'local'];
        $this->services['application']['depends_on'][] = 'redis';
    }

    private function addMailpitService(): void
    {
        $this->services['mailpit'] = [
            'image' => 'axllent/mailpit:latest',
            'ports' => [
                '${FORWARD_MAILPIT_PORT:-1025}:1025',
                '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}:8025',
            ],
            'networks' => [
                $this->projectName,
            ],
        ];

        $this->services['application']['depends_on'][] = 'mailpit';
    }

    private function addMinioService(): void
    {
        $this->services['minio'] = [
            'image' => 'minio/minio:latest',
            'ports' => [
                '${FORWARD_MINIO_PORT:-9000}:9000',
                '${FORWARD_MINIO_CONSOLE_PORT:-8900}:8900',
            ],
            'environment' => [
                'MINIO_ROOT_USER' => $this->projectName,
                'MINIO_ROOT_PASSWORD' => 'password',
            ],
            'volumes' => [
                'minio:/data',
            ],
            'networks' => [
                $this->projectName,
            ],
            'command' => 'minio server /data --console-address ":8900"',
            'healthcheck' => [
                'test' => [
                    'CMD',
                    'mc',
                    'ready',
                    'local',
                ],
                'retries' => 3,
                'timeout' => '5s',
            ],
        ];

        $this->volumes['minio'] = ['driver' => 'local'];
        $this->services['application']['depends_on'][] = 'minio';
    }

    private function addMeilisearchService(): void
    {
        $this->services['meilisearch'] = [
            'image' => 'getmeili/meilisearch:latest',
            'ports' => [
                '${FORWARD_MEILISEARCH_PORT:-7700}:7700',
            ],
            'environment' => [
                'MEILI_NO_ANALYTICS' => '${MEILISEARCH_NO_ANALYTICS:-false}',
            ],
            'volumes' => [
                'meilisearch:/meili_data',
            ],
            'networks' => [
                $this->projectName,
            ],
            'healthcheck' => [
                'test' => [
                    'CMD',
                    'wget',
                    '--no-verbose',
                    '--spider',
                    'http://127.0.0.1:7700/health',
                ],
                'retries' => 3,
                'timeout' => '5s',
            ],
        ];

        $this->volumes['meilisearch'] = ['driver' => 'local'];
        $this->services['application']['depends_on'][] = 'meilisearch';
    }

    public function toYaml(): string
    {
        $data = [
            'services' => $this->services,
            'networks' => $this->networks,
            'volumes' => $this->volumes,
        ];

        return $this->arrayToYaml($data);
    }

    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isSequentialArray($value)) {
                    $yaml .= $indentStr.$key.":\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= $indentStr."  -\n";
                            $yaml .= $this->arrayToYaml($item, $indent + 2);
                        } else {
                            $yaml .= $indentStr.'  - '.$this->formatValue($item)."\n";
                        }
                    }
                } else {
                    $yaml .= $indentStr.$key.":\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= $indentStr.$key.': '.$this->formatValue($value)."\n";
            }
        }

        return $yaml;
    }

    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function formatValue($value): string
    {
        if (is_string($value) && (
            strpos($value, ':') !== false || 
            strpos($value, '${') !== false ||
            $value === '%' ||
            $value === 'yes' ||
            $value === 'no' ||
            $value === 'true' ||
            $value === 'false'
        )) {
            return "'".$value."'";
        }

        return (string) $value;
    }
}
