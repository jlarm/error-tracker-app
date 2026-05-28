<?php

namespace Database\Factories;

use App\Models\ErrorLog;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ErrorLog>
 */
class ErrorLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exceptionClass = fake()->randomElement([
            'RuntimeException',
            'InvalidArgumentException',
            'Illuminate\\Database\\QueryException',
            'TypeError',
            'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException',
        ]);
        $file = '/var/www/app/'.fake()->randomElement([
            'Http/Controllers/UserController',
            'Http/Controllers/OrderController',
            'Models/Invoice',
            'Services/PaymentGateway',
            'Jobs/SendReminder',
        ]).'.php';
        $line = fake()->numberBetween(20, 400);
        $method = fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']);
        $url = fake()->url();
        $environment = fake()->randomElement(['production', 'staging', 'local']);
        $level = fake()->randomElement(['error', 'error', 'error', 'warning', 'info']);

        return [
            'project_id' => Project::factory(),
            'exception_class' => $exceptionClass,
            'message' => fake()->sentence(),
            'file' => $file,
            'line' => $line,
            'url' => $url,
            'environment' => $environment,
            'level' => $level,
            'release' => 'v'.fake()->numerify('#.#.#'),
            'stack_trace' => [
                ['file' => $file, 'line' => $line, 'class' => 'App\\Http\\Controllers\\UserController', 'type' => '::', 'function' => 'show'],
                ['file' => '/var/www/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php', 'line' => 46, 'function' => 'callAction'],
                ['file' => '/var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php', 'line' => 260, 'function' => 'runController'],
                ['file' => '/var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php', 'line' => 824, 'function' => 'run'],
            ],
            'request_payload' => [
                'method' => $method,
                'input' => ['user_id' => fake()->numberBetween(1, 1000)],
                'headers' => [
                    'accept' => 'application/json',
                    'user-agent' => fake()->userAgent(),
                    'x-forwarded-for' => fake()->ipv4(),
                ],
            ],
            'breadcrumbs' => array_map(
                fn (int $i) => [
                    'category' => fake()->randomElement(['db.sql.query', 'http.request', 'cache', 'log.info', 'log.debug']),
                    'level' => fake()->randomElement(['info', 'info', 'info', 'warning']),
                    'message' => fake()->randomElement([
                        'select * from `users` where `id` = ? limit 1',
                        'GET https://api.example.com/v1/users',
                        'cache read users.42',
                        'Job dispatched: ProcessPayment',
                        'select * from `orders` where `user_id` = ?',
                    ]),
                    'timestamp' => now()->subSeconds(15 - $i)->toIso8601String(),
                    'data' => [
                        'duration_ms' => fake()->randomFloat(2, 0.1, 50),
                    ],
                ],
                range(0, 5),
            ),
            'tags' => [
                'environment' => $environment,
                'level' => $level,
                'transaction' => parse_url($url, PHP_URL_PATH) ?: '/',
                'server_name' => fake()->randomElement(['web-01', 'web-02', 'queue-01']),
                'runtime' => 'php',
                'php_version' => '8.5.0',
            ],
            'context' => [
                'runtime' => [
                    'name' => 'php',
                    'version' => '8.5.0',
                    'sapi' => fake()->randomElement(['fpm-fcgi', 'cli']),
                ],
                'os' => [
                    'name' => 'Linux',
                    'version' => '6.5.0-generic',
                    'kernel' => 'Linux 6.5.0-generic x86_64',
                ],
                'browser' => [
                    'name' => fake()->randomElement(['Chrome', 'Safari', 'Firefox', 'Edge']),
                    'version' => fake()->numerify('1##.0'),
                ],
                'user' => [
                    'id' => fake()->numberBetween(1, 10000),
                    'email' => fake()->safeEmail(),
                    'ip_address' => fake()->ipv4(),
                    'geography' => fake()->city().', '.fake()->countryCode(),
                ],
                'server' => [
                    'name' => fake()->randomElement(['web-01', 'web-02']),
                    'environment' => $environment,
                ],
                'response' => [
                    'status_code' => fake()->randomElement([500, 422, 404, 403]),
                ],
                'trace' => [
                    'trace_id' => fake()->uuid(),
                    'span_id' => bin2hex(random_bytes(8)),
                ],
            ],
            'fingerprint' => md5($exceptionClass.$file.$line),
            'occurrences' => fake()->numberBetween(1, 250),
            'last_seen_at' => now(),
        ];
    }
}
