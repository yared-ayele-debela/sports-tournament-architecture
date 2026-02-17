# Tournament Service Testing Guide

## Overview

This guide covers how to test the tournament creation flow and tournament service functionality.

## Test Files

### 1. TournamentModelTest.php
**Location**: `tests/Feature/TournamentModelTest.php`

**Purpose**: Tests the Tournament model, relationships, and basic functionality.

**Test Coverage**:
- Tournament creation and validation
- Sport relationships
- Tournament settings management
- Status transitions
- Date validation
- Search functionality

**Run Command**:
```bash
php artisan test tests/Feature/TournamentModelTest.php
```

### 2. TournamentIntegrationTest.php
**Location**: `tests/Feature/TournamentIntegrationTest.php`

**Purpose**: Tests complete tournament creation flow simulation.

**Test Coverage**:
- Complete tournament lifecycle (planned → ongoing → completed)
- Multi-sport tournament management
- Tournament settings configuration
- Data integrity verification

**Run Command**:
```bash
php artisan test tests/Feature/TournamentIntegrationTest.php
```

## Testing the Tournament Creation Flow

The tournament creation flow consists of three main steps:

### 1. Tournament Creation
```php
$tournament = Tournament::create([
    'sport_id' => $sport->id,
    'name' => 'World Cup 2024',
    'location' => 'Qatar',
    'start_date' => now()->addDays(30)->format('Y-m-d'),
    'end_date' => now()->addDays(60)->format('Y-m-d'),
    'status' => 'planned',
    'created_by' => 1
]);
```

### 2. Tournament Settings
```php
$settings = TournamentSettings::create([
    'tournament_id' => $tournament->id,
    'match_duration' => 90,
    'win_rest_time' => 15,
    'daily_start_time' => '09:00',
    'daily_end_time' => '18:00'
]);
```

### 3. Teams and Matches (External Services)
- **Teams**: Created via team-service
- **Matches**: Created via match-service
- **Results**: Managed via results-service

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test File
```bash
php artisan test tests/Feature/TournamentModelTest.php
php artisan test tests/Feature/TournamentIntegrationTest.php
```

### Run Specific Test Method
```bash
php artisan test --filter test_tournament_model_creation
php artisan test --filter test_complete_tournament_flow_simulation
```

### Run Tests with Coverage
```bash
php artisan test --coverage
```

## Test Data Setup

The tests automatically create the necessary test data:

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Create a sport for testing
    $this->sport = Sport::create([
        'name' => 'Football',
        'description' => 'Association Football',
        'is_active' => true
    ]);
}
```

## Key Test Assertions

### Tournament Creation
```php
$this->assertInstanceOf(Tournament::class, $tournament);
$this->assertEquals('World Cup 2024', $tournament->name);
$this->assertEquals('planned', $tournament->status);
```

### Relationships
```php
$this->assertInstanceOf(Sport::class, $tournament->sport);
$this->assertInstanceOf(TournamentSettings::class, $tournament->settings);
```

### Status Transitions
```php
$tournament->update(['status' => 'ongoing']);
$this->assertEquals('ongoing', $tournament->fresh()->status);
```

## Microservice Integration

### External Service Calls
In the actual application flow, the following services are called:

1. **Team Service**: Creates teams for tournaments
2. **Match Service**: Creates matches between teams
3. **Results Service**: Manages match results and standings
4. **Auth Service**: Handles authentication and authorization

### Mocking External Services
For testing, external services are mocked:

```php
Http::fake([
    'team-service/*' => Http::response(['success' => true], 200),
    'match-service/*' => Http::response(['success' => true], 200),
    'auth-service/*' => Http::response(['valid' => true], 200),
]);
```

## Database Testing

### Using RefreshDatabase Trait
```php
use RefreshDatabase;

// This trait resets the database between tests
```

### Database Assertions
```php
$this->assertDatabaseHas('tournaments', [
    'name' => 'World Cup 2024',
    'status' => 'planned'
]);

$this->assertDatabaseHas('tournament_settings', [
    'tournament_id' => $tournament->id,
    'match_duration' => 90
]);
```

## Common Test Scenarios

### 1. Valid Tournament Creation
- All required fields provided
- Valid sport_id
- Proper date range
- Valid status

### 2. Invalid Tournament Creation
- Missing required fields
- Invalid sport_id
- Invalid date ranges
- Invalid status

### 3. Tournament Status Flow
- planned → ongoing → completed
- planned → cancelled
- ongoing → cancelled

### 4. Multi-Sport Tournaments
- Different settings for different sports
- Sport-specific configurations

## Troubleshooting

### Common Issues

1. **Authentication Failures**: Tests don't require authentication headers for model tests
2. **Missing Relationships**: Ensure foreign keys are properly set
3. **Date Format Issues**: Use proper date formatting (`Y-m-d`)
4. **Time Field Issues**: Time fields are cast to Carbon objects

### Debugging Tests

```bash
# Run with verbose output
php artisan test --verbose

# Run specific test with debugging
php artisan test --filter test_method_name --debug
```

## Best Practices

1. **Test One Thing**: Each test should focus on one specific functionality
2. **Use Descriptive Names**: Test method names should clearly describe what they test
3. **Arrange-Act-Assert**: Structure tests with clear setup, execution, and verification
4. **Mock External Dependencies**: Don't rely on external services in unit tests
5. **Clean Test Data**: Use RefreshDatabase trait to ensure clean state

## Future Test Enhancements

1. **API Endpoint Tests**: Add tests for REST API endpoints
2. **Authentication Tests**: Add tests for authorization middleware
3. **Performance Tests**: Add tests for large tournament datasets
4. **Integration Tests**: Add tests that span multiple services
5. **Edge Case Tests**: Add tests for boundary conditions and error scenarios
