# Doorman Analytics

A privacy-first, lightweight analytics package for Laravel applications with built-in Filament dashboard widgets.

[![Pest](https://github.com/webbesoft/doorman/actions/workflows/pest.yml/badge.svg)](https://github.com/webbesoft/doorman/actions/workflows/pest.yml)

## Features

- 🔒 **Privacy-first**: Only stores obfuscated data, no personal information
- 📊 **Single visitor tracking**: One entry per unique visitor per day
- 🎯 **Smart identification**: Prioritizes user ID > session > obfuscated IP
- 📈 **Filament widgets**: Beautiful dashboard widgets included
- 🤖 **Bot filtering**: Automatically excludes crawlers and bots
- 🧹 **Auto-cleanup**: Configurable data retention
- ⚡ **Lightweight**: Minimal database footprint
- 🚀 **Easy setup**: Simple installation and configuration

## Installation

Install from the Git repo:

1. Add to `composer.json`

```json
"require": {
    "webbesoft/doorman": "dev-main"
},
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/webbesoft/doorman"
    }
],
```

Composer coming soon.

Publish and run migrations:

```bash
php artisan vendor:publish --tag=doorman-migrations
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=doorman-config
```

## Configuration

### Environment Variables

Add to your `.env` file:

```env
DOORMAN_ENABLED=true
DOORMAN_RETENTION_DAYS=365
```

### Middleware Setup

Add the middleware to your `bootstrap/app.php`:

```php
// the alias is up to you
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'doorman' => Webbesoft\Doorman\Http\Middleware\TrackAnalyticsMiddleware::class,
    ]);
});
```

Then in your routes (maybe `web.php`):

```php
Route::middleware('doorman')->group(function () {
    // your routes go here
});

// or for an individual route:
Route::get('/', YourController::class)
    ->middleware('doorman');
```

## How It Works

### Smart Visitor Identification

The package uses a priority-based system to identify unique visitors:

1. **Authenticated Users**: Uses `user_id` (most accurate)
2. **Guest Sessions**: Uses `session_id` (good for return visitors)
3. **Anonymous Visitors**: Uses daily-rotated hashed IP (privacy-safe)

**One visitor = one database entry per day**, regardless of how many pages they visit.

### Privacy Protection

- IP addresses are **hashed with daily salt rotation**
- Original IPs **cannot be recovered**
- No personal data stored
- GDPR/CCPA compliant

## Usage

### Basic Analytics Service

```php
use Webbesoft\Doorman\Services\AnalyticsService;

$analytics = app(AnalyticsService::class);

// Today's stats
$today = $analytics->getTodayStats();
// Returns: ['unique_visitors' => 42, 'authenticated_users' => 15, ...]

// Period stats
$stats = $analytics->getStats(now()->subWeek(), now());

// Weekly growth
$growth = $analytics->getWeeklyGrowth();
```

### Manual Tracking

```php
// Track current request
$analytics->track($request);
```

## Filament Integration

### Widget Setup

Add to your Filament Panel Provider (`AdminPanelProvider.php`):

```php
use Webbesoft\Doorman\Filament\Widgets\AnalyticsStatsWidget;
use Webbesoft\Doorman\Filament\Widgets\AnalyticsChartWidget;

public function panel(Panel $panel): Panel
{
    return $panel
        ->widgets([
            AnalyticsStatsWidget::class,
            AnalyticsChartWidget::class,
        ]);
}
```

### Available Widgets

- **AnalyticsStatsWidget**: Shows today's visitors with growth indicators
- **AnalyticsChartWidget**: 30-day trend chart
- **MostVisitedPagesChartWidget**: Chart showing most visited pages
- **CountriesChartWidget**: Pie-chart of countries most visitors come from

## Configuration Options

### Config File (`config/doorman.php`)

```php
return [
    // Enable/disable tracking
    'enabled' => env('DOORMAN_ENABLED', true),

    // Routes to exclude from tracking
    'exclude_routes' => [
        'api/*',
        'admin/*',
        '_debugbar/*',
    ],

    // Bot detection patterns
    'bot_patterns' => [
        'bot', 'crawler', 'spider', 'googlebot', // ...
    ],

    // Data retention (days)
    'retention_days' => env('DOORMAN_RETENTION_DAYS', 365),
];
```

## Console Commands

### Data Cleanup

```bash
# Clean up old data based on retention policy
php artisan analytics:cleanup

# Preview what would be deleted
php artisan analytics:cleanup --dry-run

# Override retention period
php artisan analytics:cleanup --days=90
```

### Scheduled Cleanup

Add to your `routes/console.php`:

```php
Artisan::command('analytics:cleanup', function () {
    Artisan::call('analytics:cleanup');
})->describe('Cleanup old analytics')->dailyAt('00:00');
```

## Privacy & Compliance

### GDPR Compliance

- ✅ **Data minimization**: Only essential data collected
- ✅ **Privacy by design**: IP addresses are hashed
- ✅ **Right to be forgotten**: Automatic data cleanup
- ✅ **Transparency**: Clear about what's collected

### What We Store vs Don't Store

**✅ We Store (Hashed/Anonymous):**

- Daily-rotated IP hashes (cannot be reversed)
- User IDs (for your own authenticated users)
- Session IDs (temporary, automatically expire)
- Visit dates

**❌ We Don't Store:**

- Actual IP addresses
- Personal information
- Tracking cookies

## Performance

- **Minimal overhead**: Single database query per unique visitor per day
- **Efficient storage**: One row per visitor per day (not per page view)
- **Indexed queries**: Optimized database indexes for fast retrieval
- **Graceful failures**: Tracking errors don't affect user experience

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security issues, please email projects@webbe.dev instead of using the issue tracker.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Tawanda Munongo](https://github.com/tmunongo)
- [All Contributors](../../contributors)

## Support

- 📧 Email: projects@webbe.dev
- 🐛 Issues: [GitHub Issues](https://github.com/webbesoft/doorman/issues)
- 📖 Documentation: Coming soon
