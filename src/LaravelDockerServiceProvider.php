<?php

namespace TimoDeWinter\LaravelDocker;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Symfony\Component\Process\Process;
use TimoDeWinter\LaravelDocker\Services\ComposeBuilder;

class LaravelDockerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-docker')
            ->hasInstallCommand(function (InstallCommand $installCommand) {
                $installCommand
                    ->endWith(function (InstallCommand $installCommand) {
                        $name = $installCommand->ask('What name would you like to give your container?');
                        $database = $installCommand->choice(
                            'Which database service would you like to use?',
                            ['mariadb', 'mysql', 'postgres'],
                            'mariadb'
                        );

                        $enableAiTools = $installCommand->confirm(
                            'Do you want to enable AI tools (Claude Code + Laravel Boost MCP server)?',
                            false
                        );

                        $sluggedName = str($name)->slug()->value();
                        $envName = str($name)->slug('_')->upper()->value();

                        $installCommand->callSilently('vendor:publish', [
                            '--provider' => 'TimoDeWinter\LaravelDocker\LaravelDockerServiceProvider',
                        ]);

                        // Generate the dynamic compose file
                        $composeBuilder = new ComposeBuilder($sluggedName);
                        $composeBuilder->addDatabaseService($database);

                        file_put_contents(
                            base_path('compose.dev.yml'),
                            $composeBuilder->toYaml()
                        );

                        // First we rename the reference in the Dockerfile and conditionally add AI tools
                        $this->updateDockerfile(base_path('.docker/dev/Dockerfile'), $sluggedName, $enableAiTools);

                        // Now we rename all references in the binary file
                        $this->replaceInFile(file: base_path('binary'), replacements: [
                            'timodewinter-laravel-docker' => $sluggedName,
                            'TDW_LARAVEL_DOCKER' => $envName,
                        ]);

                        // Update binary file for the selected database
                        $this->updateBinaryForDatabase(base_path('binary'), $database);

                        // Install AI tools if requested
                        if ($enableAiTools) {
                            $this->installAiTools($installCommand);
                            $this->addClaudeCommandToBinary(base_path('binary'));
                        }

                        // Install Laravel Horizon for queue processing
                        $this->installHorizon($installCommand);

                        // Last we rename the binary file to the sluggedname
                        rename(base_path('binary'), $sluggedName);
                    });
            });
    }

    public function registeringPackage(): void
    {
        $this->publishes([
            __DIR__.'/../resources/stubs/.docker' => base_path('.docker'),
            __DIR__.'/../resources/stubs/root' => base_path(),
        ], 'docker');
    }

    private function replaceInFile(string $file, array $replacements): void
    {
        $contents = file_get_contents($file);

        file_put_contents(
            $file,
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                $contents
            )
        );
    }

    private function updateDockerfile(string $dockerfilePath, string $sluggedName, bool $enableAiTools): void
    {
        $contents = file_get_contents($dockerfilePath);

        // Replace the project name
        $contents = str_replace('timodewinter-laravel-docker', $sluggedName, $contents);

        // Add AI tools installation if requested
        if ($enableAiTools) {
            // Add Claude Code installation after npm install
            $npmInstallLine = 'npm install -g npm';
            $aiToolsInstallation = $npmInstallLine . ' && \\' . "\n" . '    npm install -g @anthropic/claude-code';
            
            $contents = str_replace($npmInstallLine, $aiToolsInstallation, $contents);
            
            // Enable Laravel Boost MCP server autostart
            $contents = str_replace(
                'ENV SUPERVISOR_BOOST_MCP_AUTOSTART="false"',
                'ENV SUPERVISOR_BOOST_MCP_AUTOSTART="true"',
                $contents
            );
        }

        file_put_contents($dockerfilePath, $contents);
    }

    private function addClaudeCommandToBinary(string $binaryPath): void
    {
        $contents = file_get_contents($binaryPath);

        // Claude Code command to insert before the final Docker Compose execution
        $claudeCommand = '
# Start Claude Code session within the application container...
elif [ "$1" == "claude" ]; then
    shift 1

    if [ "$EXEC" == "yes" ]; then
        ARGS+=(exec -u "$WWWUSER")
        [ ! -t 0 ] && ARGS+=(-T)
        ARGS+=("$APP_SERVICE" claude)
    else
        timodewinter-laravel-docker_is_not_running
    fi';

        // Insert before the final fi statement
        $insertPoint = 'fi

# Run Docker Compose with the defined arguments...';
        
        $replacement = $claudeCommand . '
fi

# Run Docker Compose with the defined arguments...';

        $contents = str_replace($insertPoint, $replacement, $contents);

        file_put_contents($binaryPath, $contents);
    }

    private function installHorizon(InstallCommand $installCommand): void
    {
        $installCommand->info('Installing Laravel Horizon...');
        
        // Install Horizon via Composer
        $process = new Process(['composer', 'require', 'laravel/horizon']);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            $installCommand->error('Failed to install Laravel Horizon: ' . $process->getErrorOutput());
            return;
        }

        // Publish Horizon assets
        $installCommand->callSilently('vendor:publish', [
            '--provider' => 'Laravel\Horizon\HorizonServiceProvider',
        ]);

        $installCommand->info('Laravel Horizon installed successfully!');
    }

    private function installAiTools(InstallCommand $installCommand): void
    {
        $installCommand->info('Installing AI tools (Laravel Boost)...');
        
        // Install Laravel Boost via Composer
        $process = new Process(['composer', 'require', 'laravel/boost', '--dev']);
        $process->setWorkingDirectory(base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            $installCommand->error('Failed to install Laravel Boost: ' . $process->getErrorOutput());
            return;
        }

        $installCommand->info('AI tools installed successfully!');
        $installCommand->info('After starting your containers, run: ./your-project-name artisan boost:install');
    }

    private function updateBinaryForDatabase(string $binaryPath, string $database): void
    {
        $contents = file_get_contents($binaryPath);

        // Update the database service name references
        $replacements = [
            'mariadb' => $database,
        ];

        // Update database-specific CLI commands
        if ($database === 'postgres') {
            // Replace the MariaDB/MySQL CLI section with PostgreSQL equivalent
            $mariadbSection = '# Initiate a MySQL CLI terminal session within the "mariadb" container...
elif [ "$1" == "mariadb" ]; then
    shift 1

    if [ "$EXEC" == "yes" ]; then
        ARGS+=(exec -u "$WWWUSER")
        [ ! -t 0 ] && ARGS+=(-T)
        ARGS+=(mariadb bash -c)
        ARGS+=("MYSQL_PWD=\${MYSQL_PASSWORD} mariadb -u \${MYSQL_USER} \${MYSQL_DATABASE}")
    else
        timodewinter-laravel-docker_is_not_running
    fi';

            $postgresSection = '# Initiate a PostgreSQL CLI terminal session within the "postgres" container...
elif [ "$1" == "postgres" ] || [ "$1" == "psql" ]; then
    shift 1

    if [ "$EXEC" == "yes" ]; then
        ARGS+=(exec -u "$WWWUSER")
        [ ! -t 0 ] && ARGS+=(-T)
        ARGS+=(postgres bash -c)
        ARGS+=("PGPASSWORD=\${POSTGRES_PASSWORD} psql -U \${POSTGRES_USER} -d \${POSTGRES_DB}")
    else
        timodewinter-laravel-docker_is_not_running
    fi';

            $replacements[$mariadbSection] = $postgresSection;
        } elseif ($database === 'mysql') {
            // Replace mariadb command with mysql
            $mariadbSection = '# Initiate a MySQL CLI terminal session within the "mariadb" container...
elif [ "$1" == "mariadb" ]; then
    shift 1

    if [ "$EXEC" == "yes" ]; then
        ARGS+=(exec -u "$WWWUSER")
        [ ! -t 0 ] && ARGS+=(-T)
        ARGS+=(mariadb bash -c)
        ARGS+=("MYSQL_PWD=\${MYSQL_PASSWORD} mariadb -u \${MYSQL_USER} \${MYSQL_DATABASE}")
    else
        timodewinter-laravel-docker_is_not_running
    fi';

            $mysqlSection = '# Initiate a MySQL CLI terminal session within the "mysql" container...
elif [ "$1" == "mysql" ]; then
    shift 1

    if [ "$EXEC" == "yes" ]; then
        ARGS+=(exec -u "$WWWUSER")
        [ ! -t 0 ] && ARGS+=(-T)
        ARGS+=(mysql bash -c)
        ARGS+=("MYSQL_PWD=\${MYSQL_PASSWORD} mysql -u \${MYSQL_USER} \${MYSQL_DATABASE}")
    else
        timodewinter-laravel-docker_is_not_running
    fi';

            $replacements[$mariadbSection] = $mysqlSection;
        }

        file_put_contents(
            $binaryPath,
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                $contents
            )
        );
    }
}
